<?php
declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Schemas;

use App\Enums\OsType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('محتوى الإشعار')->schema([
                    Select::make('client_app_id')
                        ->label('التطبيق المستهدف')
                        ->relationship('clientApp', 'name')
                        ->searchable()
                        ->placeholder('إرسال لجميع التطبيقات'),
                        
                    Select::make('target_os')
                        ->label('نظام التشغيل المستهدف')
                        ->options(OsType::class)
                        ->placeholder('إرسال لجميع الأنظمة'),
                        
                    TextInput::make('title')
                        ->label('عنوان الإشعار')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                        
                    Textarea::make('body')
                        ->label('نص الإشعار')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                        
                    SpatieMediaLibraryFileUpload::make('image')
                        ->label('صورة مرفقة')
                        ->collection('notification_image')
                        ->image()
                        ->maxSize(2048) // بحد أقصى 2 ميجا
                        ->columnSpanFull(),
                        
                    DateTimePicker::make('scheduled_at')
                        ->label('جدولة الإرسال (اختياري)'),
                ])->columns(2),
            ]);
    }
}
