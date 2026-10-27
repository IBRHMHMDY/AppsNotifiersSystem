<?php

// app/Filament/Resources/ClientApps/Schemas/ClientAppForm.php

declare(strict_types=1);

namespace App\Filament\Resources\ClientApps\Schemas;

use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ClientAppForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات التطبيق')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم التطبيق')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('bundle_id')
                            ->label('المعرف الفريد (Bundle ID)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('مثال: com.example.app')
                            ->maxLength(255),

                        TextInput::make('app_key')
                            ->label('مفتاح الربط (App Key)')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('يتم توليده تلقائياً. استخدم هذا المفتاح في تطبيق Flutter.')
                            ->extraInputAttributes(['readonly' => true]),

                        Toggle::make('is_active')
                            ->label('مفعل؟')
                            ->default(true),
                    ])->columns(2),

                Section::make('وضع الصيانة المؤقت (Maintenance Mode)')
                    ->description('عند تفعيل وضع الصيانة، سيستلم تطبيق الموبايل إشارة صيانة مع الرسالة المحددة عند فحص التحديثات.')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->schema([
                        Toggle::make('is_under_maintenance')
                            ->label('تفعيل وضع الصيانة لهذا التطبيق')
                            ->default(false)
                            ->live(),

                        Textarea::make('maintenance_message')
                            ->label('رسالة الصيانة للمستخدمين')
                            ->placeholder('مثال: نقوم حالياً بإجراء تحديثات لتحسين الخدمة، سنعود خلال دقائق...')
                            ->rows(2)
                            ->visible(fn (Get $get): bool => (bool) $get('is_under_maintenance'))
                            ->required(fn (Get $get): bool => (bool) $get('is_under_maintenance'))
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Section::make('إعدادات حساب Firebase المخصص (اختياري - Multi-Project FCM)')
                    ->description('اترك هذا الحقل فارغاً لاستخدام مشروع Firebase الافتراضي للسيرفر، أو الصق محتوى ملف Service Account JSON إذا كان هذا التطبيق على مشروع Firebase مستقل.')
                    ->icon('heroicon-o-fire')
                    ->collapsible()
                    ->schema([
                        TextInput::make('firebase_project_id')
                            ->label('معرّف مشروع Firebase الحالي (Project ID)')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('يُستخدم حالياً: المشروع الافتراضي للنظام (Default Server Config)')
                            ->columnSpanFull(),

                        Textarea::make('firebase_credentials')
                            ->label('محتوى ملف Firebase Service Account JSON')
                            ->rows(7)
                            ->placeholder('{"type": "service_account", "project_id": "my-app-id", "private_key": "-----BEGIN PRIVATE KEY-----...", "client_email": "..."}')
                            ->helperText('يتم تشفير هذه البيانات في قاعدة البيانات تلقائياً بمعيار AES-256. لتحديثها الصق الـ JSON الجديد، ولإلغائها والعودة للإعداد الافتراضي امسح المحتوى.')
                            ->formatStateUsing(fn (mixed $state): ?string => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                : (is_string($state) ? $state : null))
                            ->dehydrateStateUsing(function (?string $state): ?array {
                                if ($state === null || trim($state) === '') {
                                    return null;
                                }

                                $decoded = json_decode($state, true);

                                return is_array($decoded) ? $decoded : null;
                            })
                            ->rules([
                                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                    if ($value === null || trim((string) $value) === '') {
                                        return;
                                    }

                                    $decoded = is_array($value) ? $value : json_decode((string) $value, true);

                                    if (! is_array($decoded)) {
                                        $fail('يجب أن يكون النص المدخل بصيغة JSON صحيحة.');

                                        return;
                                    }

                                    foreach (['project_id', 'private_key', 'client_email'] as $requiredKey) {
                                        if (empty($decoded[$requiredKey])) {
                                            $fail("ملف JSON غير مكتمل؛ المفتاح المطلوب [{$requiredKey}] مفقود.");

                                            return;
                                        }
                                    }
                                },
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
