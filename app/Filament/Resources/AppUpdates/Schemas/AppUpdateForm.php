<?php

namespace App\Filament\Resources\AppUpdates\Schemas;

use Filament\Forms\Components\Select;
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
                Section::make('بيانات التحديث')->schema([
                    Select::make('client_app_id')
                        ->label('التطبيق')
                        ->relationship('clientApp', 'name')
                        ->required()
                        ->searchable(),
                        
                    TextInput::make('version')
                        ->label('رقم الإصدار الجديد')
                        ->required()
                        ->placeholder('مثال: 1.2.0')
                        ->maxLength(255),
                        
                    TextInput::make('store_url')
                        ->label('رابط المتجر')
                        ->url()
                        ->maxLength(255)
                        ->columnSpanFull(),
                        
                    Toggle::make('is_force_update')
                        ->label('تحديث إجباري؟')
                        ->default(false),
                        
                    Textarea::make('release_notes')
                        ->label('ملاحظات الإصدار')
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
