<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppUpdates\Schemas;

use App\Enums\OsType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppUpdateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('البيانات الأساسية للتحديث')
                    ->schema([
                        Select::make('client_app_id')
                            ->label('التطبيق')
                            ->relationship('clientApp', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('target_os')
                            ->label('نظام التشغيل المستهدف')
                            ->options(OsType::class)
                            ->placeholder('جميع الأنظمة (Android & iOS)'),

                        TextInput::make('version')
                            ->label('رقم الإصدار الجديد (Latest Version)')
                            ->required()
                            ->placeholder('مثال: 2.1.0')
                            ->regex('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/')
                            ->helperText('استخدم صيغة Semantic Versioning مثل 1.2.0')
                            ->maxLength(50),

                        TextInput::make('store_url')
                            ->label('رابط التحميل / المتجر')
                            ->url()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('https://play.google.com/store/apps/details?id=...'),

                        Toggle::make('is_active')
                            ->label('تفعيل هذا التحديث')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('التحكم الذكي في الإطلاق وإجبار التحديث (Smart Rollout & Force Control)')
                    ->description('تحكم في الإطلاق التدريجي (Rolling Update) أو احظر إصدارات محددة بها مشاكل برمجية.')
                    ->schema([
                        TextInput::make('rollout_percentage')
                            ->label('نسبة الإطلاق التدريجي (Rollout %)')
                            ->numeric()
                            ->default(100)
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required()
                            ->helperText('حدد 100% لإظهار التحديث للجميع، أو نسبة أقل (مثلاً 20%) للإطلاق التدريجي لتخفيف الضغط على السيرفر.'),

                        TextInput::make('min_supported_version')
                            ->label('الحد الأدنى للإصدار المدعوم (Min Supported Version)')
                            ->placeholder('مثال: 1.5.0')
                            ->regex('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/')
                            ->maxLength(50)
                            ->helperText('أي مستخدم يمتلك إصداراً أقدم من هذا الرقم سيتم إجباره تلقائياً على التحديث.'),

                        TagsInput::make('blocked_versions')
                            ->label('إصدارات محظورة تماماً (Blocked Versions)')
                            ->placeholder('اكتب رقم الإصدار ثم اضغط Enter (مثال: 1.2.3)')
                            ->helperText('مستخدمو هذه الإصدارات المحددة فقط سيُفرض عليهم التحديث الإجباري فوراً حتى لو كان التحديث اختيارياً لغيرهم.')
                            ->columnSpanFull(),

                        Toggle::make('is_force_update')
                            ->label('تحديث إجباري عام لجميع الإصدارات السابقة (Global Force Update)')
                            ->default(false)
                            ->columnSpanFull(),

                        Textarea::make('release_notes')
                            ->label('ملاحظات الإصدار (Release Notes)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
