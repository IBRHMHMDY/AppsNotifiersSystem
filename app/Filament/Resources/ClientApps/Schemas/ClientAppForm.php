<?php

namespace App\Filament\Resources\ClientApps\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientAppForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات التطبيق')->schema([
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
                        ->dehydrated(false) // لا تقم بحفظه عند التعديل لأنه يُولد تلقائياً
                        ->helperText('يتم توليده تلقائياً. استخدم هذا المفتاح في تطبيق Flutter.')
                        ->extraInputAttributes(['readonly' => true]),

                    Toggle::make('is_active')
                        ->label('مفعل؟')
                        ->default(true),
                ])->columns(2),
            ]);
    }
}
