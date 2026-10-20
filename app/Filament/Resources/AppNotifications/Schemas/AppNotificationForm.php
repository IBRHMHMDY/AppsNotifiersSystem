<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Schemas;

use App\Enums\OsType;
use App\Models\ClientApp;
use App\Models\Device;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class AppNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الإشعار والاستهداف')
                    ->schema([
                        Select::make('client_app_id')
                            ->label('التطبيق المستهدف')
                            ->relationship('clientApp', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder('جميع التطبيقات'),

                        Select::make('target_os')
                            ->label('نظام التشغيل المستهدف')
                            ->options(OsType::class)
                            ->live()
                            ->placeholder('جميع الأنظمة (Android & iOS)'),

                        TextInput::make('topic')
                            ->label('موضوع الاستهداف (Firebase Topic - اختياري)')
                            ->placeholder('مثال: offers أو vip_users')
                            ->maxLength(100)
                            ->regex('/^[a-zA-Z0-9-_.~%]+$/')
                            ->live(onBlur: true)
                            ->helperText('في حال تحديد Topic، سيتم الإرسال عبر قنوات Firebase Topics للمشتركين فيه.')
                            ->columnSpanFull(),

                        Placeholder::make('audience_counter')
                            ->label('حجم الجمهور المستهدف المتوقع')
                            ->columnSpanFull()
                            ->content(function (Get $get): HtmlString {
                                $clientAppId = $get('client_app_id');
                                $targetOs = $get('target_os');
                                $topic = trim((string) $get('topic'));

                                $count = Device::query()
                                    ->whereNotNull('fcm_token')
                                    ->when($clientAppId, fn ($q) => $q->where('client_app_id', (int) $clientAppId))
                                    ->when($targetOs, fn ($q) => $q->where('os_type', $targetOs))
                                    ->when($topic !== '', fn ($q) => $q->whereJsonContains('subscribed_topics', $topic))
                                    ->count();

                                $badgeColor = $count > 0 || $topic !== ''
                                    ? 'background:#dcfce7;color:#166534;border:1px solid #86efac;'
                                    : 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;';

                                $topicLabel = $topic !== '' ? " (مشتركين في الموضوع: <code>{$topic}</code>)" : '';

                                return new HtmlString(
                                    "<div style='padding:10px 14px;border-radius:8px;font-weight:600;{$badgeColor}'>"
                                    .'الجمهور المطابق في قاعدة البيانات: <strong>'.number_format($count)."</strong> جهاز{$topicLabel}."
                                    .'</div>'
                                );
                            }),

                        TextInput::make('title')
                            ->label('عنوان الإشعار')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->placeholder('مثال: تحديث جديد متاح الآن! 🎉')
                            ->columnSpanFull(),

                        Textarea::make('body')
                            ->label('محتوى الإشعار')
                            ->required()
                            ->rows(4)
                            ->live(onBlur: true)
                            ->placeholder('اكتب نص الرسالة التي ستظهر للمستخدم على شاشة القفل...')
                            ->columnSpanFull(),

                        KeyValue::make('payload_data')
                            ->label('بيانات إضافية للتوجيه داخل التطبيق (Custom Payload Data)')
                            ->keyLabel('المفتاح (Key)')
                            ->valueLabel('القيمة (Value)')
                            ->keyPlaceholder('مثال: screen')
                            ->valuePlaceholder('مثال: offers_page')
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('image')
                            ->label('صورة الإشعار (اختياري)')
                            ->collection('notification_image')
                            ->image()
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        DateTimePicker::make('scheduled_at')
                            ->label('جدولة الإرسال لوقت لاحق (اختياري)')
                            ->native(false)
                            ->minDate(now())
                            ->helperText('اتركه فارغاً إذا كنت ترغب في الإرسال الفوري عند التفعيل.'),
                    ])
                    ->columns(2)
                    ->columnSpan(2),

                Section::make('معاينة حية على الهاتف 📱')
                    ->description('شكل تقريبي لكيفية ظهور الإشعار للمستخدم')
                    ->schema([
                        Placeholder::make('mobile_preview')
                            ->hiddenLabel()
                            ->content(function (Get $get): HtmlString {
                                $clientAppId = $get('client_app_id');
                                $appName = $clientAppId
                                    ? (ClientApp::query()->whereKey($clientAppId)->value('name') ?? 'تطبيقك')
                                    : 'جميع التطبيقات';

                                $title = e((string) ($get('title') ?: 'عنوان الإشعار يظهر هنا'));
                                $body = nl2br(e((string) ($get('body') ?: 'محتوى نص الإشعار سيظهر في هذه المساحة تماماً كما يراه المستخدم على شاشة هاتفه...')));
                                $osBadge = $get('target_os') ? strtoupper((string) ($get('target_os') instanceof OsType ? $get('target_os')->value : $get('target_os'))) : 'ALL OS';
                                $topic = trim((string) $get('topic'));
                                $topicBadge = $topic !== '' ? "<span style='background:#0284c7;padding:2px 8px;border-radius:99px;margin-right:4px;'>#{$topic}</span>" : '';

                                return new HtmlString("
                                    <div style='background:#0f172a;padding:18px;border-radius:18px;color:#f8fafc;box-shadow:0 10px 25px -5px rgba(0,0,0,0.25);'>
                                        <div style='display:flex;justify-content:space-between;align-items:center;font-size:11px;color:#94a3b8;margin-bottom:10px;'>
                                            <span><strong>".e((string) $appName)."</strong> • الآن</span>
                                            <div>{$topicBadge}<span style='background:#1e293b;padding:2px 8px;border-radius:99px;'>{$osBadge}</span></div>
                                        </div>
                                        <div style='background:rgba(255,255,255,0.08);backdrop-filter:blur(8px);padding:14px;border-radius:12px;border:1px solid rgba(255,255,255,0.12);'>
                                            <div style='font-weight:700;font-size:14px;color:#ffffff;margin-bottom:6px;'>{$title}</div>
                                            <div style='font-size:13px;color:#cbd5e1;line-height:1.5;'>{$body}</div>
                                        </div>
                                    </div>
                                ");
                            }),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }
}
