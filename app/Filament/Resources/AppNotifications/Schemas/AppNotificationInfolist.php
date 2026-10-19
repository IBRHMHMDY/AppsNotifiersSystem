<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل الإشعار')
                    ->schema([
                        TextEntry::make('clientApp.name')
                            ->label('التطبيق المستهدف')
                            ->placeholder('جميع التطبيقات'),

                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge(),

                        TextEntry::make('target_os')
                            ->label('نظام التشغيل')
                            ->badge()
                            ->placeholder('جميع الأنظمة'),

                        TextEntry::make('topic')
                            ->label('الموضوع (Topic)')
                            ->badge()
                            ->color('info')
                            ->placeholder('بث مباشر للأجهزة'),

                        TextEntry::make('title')
                            ->label('العنوان')
                            ->columnSpanFull(),

                        TextEntry::make('body')
                            ->label('محتوى الإشعار')
                            ->columnSpanFull(),

                        KeyValueEntry::make('payload_data')
                            ->label('البيانات المخصصة (Payload Data)')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Section::make('مؤشرات الأداء والتتبع التلقائي (Delivery & Engagement)')
                    ->schema([
                        TextEntry::make('sent_count')
                            ->label('تم الإرسال (Sent)')
                            ->badge()
                            ->color('success'),

                        TextEntry::make('failed_count')
                            ->label('فشل الإرسال (Failed)')
                            ->badge()
                            ->color('danger'),

                        TextEntry::make('delivered_count')
                            ->label('تم الاستلام (Delivered)')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('opened_count')
                            ->label('تم الفتح (Opened)')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('clicked_count')
                            ->label('تم النقر (Clicked)')
                            ->badge()
                            ->color('warning'),
                    ])
                    ->columns(5),

                Section::make('التوقيت الزمني')
                    ->schema([
                        TextEntry::make('scheduled_at')
                            ->label('موعد الجدولة')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('sent_at')
                            ->label('وقت الإرسال الفعلي')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(3),
            ]);
    }
}