<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Schemas;

use App\Enums\NotificationActionType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationTargetType;
use App\Enums\NotificationType;
use App\Enums\OsType;
use App\Models\Device;
use App\Services\TargetAudienceService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class AppNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // القسم الأول: الهوية وتصنيف الإشعار
                Section::make('1. الهوية وتصنيف الإشعار (Identity & Classification)')
                    ->description('حدد التطبيق المستهدف وطبيعة الإشعار لتكييف خيارات المحتوى والإرسال تلقائياً.')
                    ->icon('heroicon-o-Squares-2x2')
                    ->schema([
                        Select::make('client_app_id')
                            ->label('التطبيق المستهدف')
                            ->relationship('clientApp', 'name')
                            ->placeholder('جميع التطبيقات النشطة (بث شامل)')
                            ->helperText('اختر تطبيقاً محدداً أو اتركه فارغاً لإرسال الإشعار لكافة التطبيقات المفعلة.')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                // إعادة ضبط الفلاتر المرتبطة بالتطبيق عند تغييره
                                $set('target_app_versions', []);
                                $set('target_device_ids', []);
                            })
                            ->columnSpanFull(),

                        ToggleButtons::make('type')
                            ->label('نوع الإشعار (Notification Type)')
                            ->options(NotificationType::class)
                            ->default(NotificationType::General)
                            ->inline()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ( mixed $state, Set $set): void {
                                $type = $state instanceof NotificationType
                                    ? $state
                                    : NotificationType::tryFrom((string) $state);

                                if ($type?->isSilent()) {
                                    $set('title', null);
                                    $set('body', null);
                                    $set('action_type', NotificationActionType::OpenApp);
                                    $set('action_value', null);
                                }
                            })
                            ->columnSpanFull(),

                        Placeholder::make('type_description_banner')
                            ->hiddenLabel()
                            ->content(function (Get $get): HtmlString {
                                $rawType = $get('type');
                                $type = $rawType instanceof NotificationType
                                    ? $rawType
                                    : (NotificationType::tryFrom((string) $rawType) ?? NotificationType::General);

                                $badgeColor = match ($type) {
                                    NotificationType::SystemAlert => 'border-danger-500 bg-danger-50 text-danger-700 dark:bg-danger-950/40 dark:text-danger-300',
                                    NotificationType::Promotional => 'border-success-500 bg-success-50 text-success-700 dark:bg-success-950/40 dark:text-success-300',
                                    NotificationType::SilentData => 'border-gray-500 bg-gray-50 text-gray-700 dark:bg-gray-900 dark:text-gray-300',
                                    default => 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300',
                                };

                                return new HtmlString(
                                    "<div class='rounded-lg border-r-4 p-3 text-sm {$badgeColor}'>" .
                                    "<strong>{$type->getLabel()}:</strong> {$type->getDescription()}" .
                                    '</div>'
                                );
                            })
                            ->columnSpanFull(),
                    ]),

                // القسم الثاني: محرك الاستهداف الدقيق
                Section::make('2. الجمهور وطريقة الاستهداف (Audience & Targeting)')
                    ->description('اختر شريحة الأجهزة التي سيصلها الإشعار بدقة، وراقب العدد التقديري للوصول لحظياً.')
                    ->icon('heroicon-o-funnel')
                    ->schema([
                        ToggleButtons::make('target_type')
                            ->label('طريقة الاستهداف')
                            ->options(NotificationTargetType::class)
                            ->default(NotificationTargetType::AllDevices)
                            ->inline()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                $targetType = $state instanceof NotificationTargetType
                                    ? $state
                                    : NotificationTargetType::tryFrom((string) $state);

                                match ($targetType) {
                                    NotificationTargetType::AllDevices => (function () use ($set): void {
                                        $set('topic', null);
                                        $set('target_os', null);
                                        $set('target_app_versions', []);
                                        $set('target_device_ids', []);
                                    })(),
                                    NotificationTargetType::Topic => (function () use ($set): void {
                                        $set('target_os', null);
                                        $set('target_app_versions', []);
                                        $set('target_device_ids', []);
                                    })(),
                                    NotificationTargetType::Segmented => (function () use ($set): void {
                                        $set('topic', null);
                                        $set('target_device_ids', []);
                                    })(),
                                    NotificationTargetType::SpecificDevices => (function () use ($set): void {
                                        $set('topic', null);
                                        $set('target_os', null);
                                        $set('target_app_versions', []);
                                    })(),
                                    default => null,
                                };
                            })
                            ->columnSpanFull(),

                        // خيار 1: الاستهداف عبر موضوع (FCM Topic)
                        TextInput::make('topic')
                            ->label('اسم الموضوع (FCM Topic)')
                            ->placeholder('مثال: offers, breaking_news, vip_users')
                            ->helperText('اختر من المواضيع المسجلة في الأجهزة أو اكتب اسم موضوع جديد (بدون علامة #).')
                            ->datalist(function (Get $get): array {
                                $clientAppId = $get('client_app_id') ? (int) $get('client_app_id') : null;

                                return array_keys(app(TargetAudienceService::class)->getAvailableTopics($clientAppId));
                            })
                            ->visible(fn (Get $get): bool => self::resolveTargetType($get('target_type')) === NotificationTargetType::Topic)
                            ->required(fn (Get $get): bool => self::resolveTargetType($get('target_type')) === NotificationTargetType::Topic)
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->columnSpanFull(),

                        // خيار 2: الاستهداف عبر شريحة مفلترة (Segmented)
                        ToggleButtons::make('target_os')
                            ->label('نظام التشغيل (OS)')
                            ->options(OsType::class)
                            ->placeholder('كل الأنظمة')
                            ->inline()
                            ->live()
                            ->visible(fn (Get $get): bool => self::resolveTargetType($get('target_type')) === NotificationTargetType::Segmented),

                        Select::make('target_app_versions')
                            ->label('إصدارات التطبيق المستهدفة (اختياري)')
                            ->placeholder('جميع الإصدارات')
                            ->helperText('حدد إصداراً أو أكثر، أو اتركه فارغاً ليشمل كل الإصدارات.')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->options(function (Get $get): array {
                                $clientAppId = $get('client_app_id') ? (int) $get('client_app_id') : null;

                                return app(TargetAudienceService::class)->getAvailableAppVersions($clientAppId);
                            })
                            ->visible(fn (Get $get): bool => self::resolveTargetType($get('target_type')) === NotificationTargetType::Segmented),

                        // خيار 3: الاستهداف لأجهزة محددة (Specific Devices)
                        Select::make('target_device_ids')
                            ->label('الأجهزة المحددة')
                            ->placeholder('ابحث بمعرّف الجهاز أو نظام التشغيل أو الإصدار...')
                            ->helperText('مثالي لإرسال إشعارات تجريبية (Testing) أو رسائل دعم فني لأجهزة محددة.')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->options(function (Get $get): array {
                                $clientAppId = $get('client_app_id') ? (int) $get('client_app_id') : null;

                                return Device::query()
                                    ->whereNotNull('fcm_token')
                                    ->when($clientAppId !== null, fn ($q) => $q->where('client_app_id', $clientAppId))
                                    ->latest('last_active_at')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(function (Device $device): array {
                                        $os = $device->os_type?->getLabel() ?? 'Unknown OS';
                                        $version = $device->app_version ? "v{$device->app_version}" : 'v?';
                                        $shortToken = substr((string) $device->fcm_token, 0, 12) . '...';

                                        return [$device->id => "جهاز #{$device->id} ({$os} • {$version}) [{$shortToken}]"];
                                    })
                                    ->all();
                            })
                            ->visible(fn (Get $get): bool => self::resolveTargetType($get('target_type')) === NotificationTargetType::SpecificDevices)
                            ->required(fn (Get $get): bool => self::resolveTargetType($get('target_type')) === NotificationTargetType::SpecificDevices)
                            ->columnSpanFull(),

                        // مؤشر الوصول اللحظي (Live Audience Reach Counter)
                        Placeholder::make('estimated_reach_counter')
                            ->label('ملخص الوصول التقديري (Live Reach)')
                            ->content(function (Get $get): HtmlString {
                                $targetType = self::resolveTargetType($get('target_type'));
                                $clientAppId = $get('client_app_id') ? (int) $get('client_app_id') : null;
                                $osType = $get('target_os');
                                $topic = $get('topic') ? (string) $get('topic') : null;
                                $versions = is_array($get('target_app_versions')) ? $get('target_app_versions') : null;
                                $deviceIds = is_array($get('target_device_ids')) ? $get('target_device_ids') : null;

                                $count = app(TargetAudienceService::class)->estimateReachCount(
                                    targetType: $targetType,
                                    clientAppId: $clientAppId,
                                    osType: $osType,
                                    topic: $topic,
                                    appVersions: $versions,
                                    deviceIds: $deviceIds,
                                );

                                $extraNote = $targetType === NotificationTargetType::Topic
                                    ? ' (بالإضافة إلى أي أجهزة اشتركت مباشرة عبر FCM SDK في نفس الموضوع)'
                                    : '';

                                $colorClass = $count > 0
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
                                    : 'border-amber-500 bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300';

                                return new HtmlString(
                                    "<div class='flex items-center justify-between rounded-lg border p-3 {$colorClass}'>" .
                                    "<div><span class='font-semibold'>الجمهور المطابق حالياً:</span> نحو <strong>{$count}</strong> جهاز مسجل{$extraNote}.</div>" .
                                    "<span class='text-xs font-medium uppercase px-2 py-1 rounded bg-white/60 dark:bg-black/30'>{$targetType->getLabel()}</span>" .
                                    '</div>'
                                );
                            })
                            ->columnSpanFull(),
                    ])->columns(2),

                // القسم الثالث: المحتوى المرئي والمرفقات (يختفي تلقائياً إذا كان الإشعار صامتاً)
                Section::make('3. محتوى الإشعار والمرفقات (Content & Media)')
                    ->description('أدخل عنوان ونص الإشعار والصورة التوضيحية التي ستظهر على شاشة هاتف المستخدم.')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->visible(fn (Get $get): bool => ! self::resolveNotificationType($get('type'))->isSilent())
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان الإشعار')
                            ->placeholder('أدخل عنواناً جذاباً ومختصراً...')
                            ->required(fn (Get $get): bool => ! self::resolveNotificationType($get('type'))->isSilent())
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('body')
                            ->label('نص الإشعار')
                            ->placeholder('اكتب تفاصيل الرسالة هنا...')
                            ->required(fn (Get $get): bool => ! self::resolveNotificationType($get('type'))->isSilent())
                            ->rows(3)
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('notification_image')
                            ->label('صورة الإشعار الكبيرة (Big Picture)')
                            ->collection('notification_image')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText('اختياري: صورة تظهر بحجم كامل عند توسيع الإشعار على الهاتف (بحد أقصى 2MB).')
                            ->columnSpanFull(),
                    ]),

                // القسم الرابع: التوجيه، البيانات البرمجية، والجدولة
                Section::make('4. التوجيه، البيانات البرمجية، والجدولة (Action, Payload & Scheduling)')
                    ->description('حدد ما يحدث عند النقر على الإشعار، والبيانات المرسلة للتطبيق، ووقت الإرسال.')
                    ->icon('heroicon-o-bolt')
                    ->schema([
                        ToggleButtons::make('action_type')
                            ->label('إجراء النقر على الإشعار (Click Action)')
                            ->options(NotificationActionType::class)
                            ->default(NotificationActionType::OpenApp)
                            ->inline()
                            ->required()
                            ->live()
                            ->visible(fn (Get $get): bool => ! self::resolveNotificationType($get('type'))->isSilent())
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                $actionType = self::resolveActionType($state);
                                if (! $actionType->requiresValue()) {
                                    $set('action_value', null);
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('action_value')
                            ->label(fn (Get $get): string => match (self::resolveActionType($get('action_type'))) {
                                NotificationActionType::DeepLink => 'مسار الشاشة الداخلية (Deep Link / Route)',
                                NotificationActionType::ExternalUrl => 'الرابط الخارجي (External URL)',
                                default => 'قيمة الإجراء',
                            })
                            ->placeholder(fn (Get $get): string => match (self::resolveActionType($get('action_type'))) {
                                NotificationActionType::DeepLink => 'مثال: /offers/25 أو product_details?id=10',
                                NotificationActionType::ExternalUrl => 'مثال: https://example.com/promo',
                                default => '',
                            })
                            ->url(fn (Get $get): bool => self::resolveActionType($get('action_type')) === NotificationActionType::ExternalUrl)
                            ->visible(fn (Get $get): bool => ! self::resolveNotificationType($get('type'))->isSilent()
                                && self::resolveActionType($get('action_type'))->requiresValue())
                            ->required(fn (Get $get): bool => ! self::resolveNotificationType($get('type'))->isSilent()
                                && self::resolveActionType($get('action_type'))->requiresValue())
                            ->maxLength(500)
                            ->columnSpanFull(),

                        KeyValue::make('payload_data')
                            ->label('البيانات الإضافية المخصصة (Custom Data Payload)')
                            ->keyLabel('المفتاح (Key)')
                            ->valueLabel('القيمة (Value)')
                            ->addActionLabel('إضافة مفتاح جديد')
                            ->required(fn (Get $get): bool => self::resolveNotificationType($get('type'))->isSilent())
                            ->helperText(fn (Get $get): string => self::resolveNotificationType($get('type'))->isSilent()
                                ? 'إلزامي للإشعارات الصامتة: أدخل المفاتيح والقيم التي سيعالجها التطبيق في الخلفية (مثل: sync_catalog => true).'
                                : 'اختياري: بيانات إضافية تُرسل مع الإشعار إلى تطبيق الموبايل.')
                            ->columnSpanFull(),

                        DateTimePicker::make('scheduled_at')
                            ->label('وقت الجدولة (اختياري)')
                            ->placeholder('اتركه فارغاً للإرسال الفوري الآن...')
                            ->helperText('حدد تاريخاً ووقتاً مستقبلياً إذا كنت ترغب في جدولة الإشعار ليُرسل تلقائياً.')
                            ->native(false)
                            ->seconds(false)
                            ->timezone(config('app.timezone'))
                            ->minDate(now()->startOfDay())
                            ->afterOrEqual('now')
                            ->validationMessages([
                                'after_or_equal' => 'يجب أن يكون وقت الجدولة في الوقت الحالي أو في المستقبل.',
                            ])
                            ->closeOnDateSelection(false)
                            ->nullable(),

                        Select::make('status')
                            ->label('حالة الإشعار')
                            ->options(NotificationStatus::class)
                            ->default(NotificationStatus::PENDING)
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),
            ]);
    }

    private static function resolveNotificationType(mixed $state): NotificationType
    {
        if ($state instanceof NotificationType) {
            return $state;
        }

        return NotificationType::tryFrom((string) $state) ?? NotificationType::General;
    }

    private static function resolveTargetType(mixed $state): NotificationTargetType
    {
        if ($state instanceof NotificationTargetType) {
            return $state;
        }

        return NotificationTargetType::tryFrom((string) $state) ?? NotificationTargetType::AllDevices;
    }

    private static function resolveActionType(mixed $state): NotificationActionType
    {
        if ($state instanceof NotificationActionType) {
            return $state;
        }

        return NotificationActionType::tryFrom((string) $state) ?? NotificationActionType::OpenApp;
    }
}