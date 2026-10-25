<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Schemas;

use App\Enums\NotificationStatus;
use App\Enums\OsType;
use App\Models\AppNotification;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AppNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('إعدادات الاستهداف الذكي (Targeting)')
                    ->description('حدد التطبيق وشريحة المستخدمين المستهدفة بهذا الإشعار.')
                    ->schema([
                        Select::make('client_app_id')
                            ->label('التطبيق')
                            ->relationship('clientApp', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('target_type')
                            ->label('نوع الاستهداف')
                            ->options([
                                'all' => 'جميع أجهزة التطبيق (Broadcast)',
                                'os' => 'حسب نظام التشغيل (OS)',
                                'topic' => 'حسب الاهتمام / الموضوع (Topic)',
                            ])
                            ->default('all')
                            ->required()
                            ->live()
                            ->dehydrated(false) // حقل واجهة فقط ولا يوجد له عمود في قاعدة البيانات
                            ->formatStateUsing(function (?string $state, ?AppNotification $record): string {
                                if ($record === null) {
                                    return $state ?? 'all';
                                }

                                if ($record->topic !== null && $record->topic !== '') {
                                    return 'topic';
                                }

                                if ($record->target_os !== null) {
                                    return 'os';
                                }

                                return 'all';
                            })
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                // تصفير الحقول غير المختارة لضمان نظافة البيانات في قاعدة البيانات
                                if ($state === 'all') {
                                    $set('target_os', null);
                                    $set('topic', null);
                                } elseif ($state === 'os') {
                                    $set('topic', null);
                                } elseif ($state === 'topic') {
                                    $set('target_os', null);
                                }
                            }),

                        Select::make('target_os')
                            ->label('نظام التشغيل المستهدف')
                            ->options(OsType::class)
                            ->visible(fn (Get $get): bool => $get('target_type') === 'os')
                            ->required(fn (Get $get): bool => $get('target_type') === 'os')
                            ->nullable(),

                        TextInput::make('topic')
                            ->label('اسم الموضوع (Topic)')
                            ->placeholder('مثال: offers, news, vip_users')
                            ->helperText('سيتم الإرسال فقط للأجهزة المشتركة في هذا الموضوع.')
                            ->visible(fn (Get $get): bool => $get('target_type') === 'topic')
                            ->required(fn (Get $get): bool => $get('target_type') === 'topic')
                            ->maxLength(255)
                            ->nullable(),
                    ])->columns(2),

                Section::make('محتوى الإشعار والمرفقات')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان الإشعار')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('body')
                            ->label('نص الإشعار')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('notification_image')
                            ->label('صورة الإشعار (Rich Notification)')
                            ->collection('notification_image') // مطابق تماماً للمودل والـ Job والـ Infolist
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText('اختياري: صورة تظهر بشكل كبير داخل الإشعار (بحد أقصى 2MB).')
                            ->columnSpanFull(),
                    ]),

                Section::make('الجدولة والبيانات الإضافية (Scheduling & Payload)')
                    ->schema([
                        DateTimePicker::make('scheduled_at')
                            ->label('وقت الجدولة (اختياري)')
                            ->placeholder('اختر التاريخ والوقت للإرسال اللاحق...')
                            ->helperText('اتركه فارغاً للإرسال الفوري، أو حدد تاريخاً ووقتاً لاحقاً ليتم إرساله تلقائياً.')
                            ->native(false)
                            ->seconds(false)
                            ->timezone(config('app.timezone'))
                            ->minDate(now()->startOfDay())
                            ->afterOrEqual('now')
                            ->validationMessages([
                                'after_or_equal' => 'يجب أن يكون وقت الجدولة في الوقت الحالي أو في وقت لاحق.',
                            ])
                            ->closeOnDateSelection(false)
                            ->nullable(),

                        Select::make('status')
                            ->label('حالة الإشعار')
                            ->options(NotificationStatus::class)
                            ->default(NotificationStatus::PENDING)
                            ->disabled()
                            ->dehydrated(),

                        KeyValue::make('payload_data') // مطابق تماماً لعمود قاعدة البيانات والمودل
                            ->label('بيانات إضافية مخصصة (Custom Data Payload)')
                            ->keyLabel('المفتاح (Key)')
                            ->valueLabel('القيمة (Value)')
                            ->addActionLabel('إضافة خاصية جديدة')
                            ->helperText('تُرسل هذه البيانات في الخلفية لتوجيه المستخدم داخل تطبيق Flutter (مثل: route => /offers).')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}