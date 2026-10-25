<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Schemas;

use App\Models\AppNotification;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل الإشعار والاستهداف')
                    ->schema([
                        TextEntry::make('clientApp.name')
                            ->label('التطبيق')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('status')
                            ->label('حالة الإشعار')
                            ->badge(),

                        TextEntry::make('target_os')
                            ->label('نظام التشغيل المستهدف')
                            ->badge()
                            ->placeholder('جميع الأنظمة (All OS)'),

                        TextEntry::make('topic')
                            ->label('الموضوع المستهدف (Topic)')
                            ->badge()
                            ->color('info')
                            ->placeholder('بث عام (Broadcast)'),

                        TextEntry::make('scheduled_at')
                            ->label('موعد الجدولة')
                            ->dateTime('Y-m-d h:i A')
                            ->placeholder('إرسال فوري'),

                        TextEntry::make('sent_at')
                            ->label('تاريخ الإرسال الفعلي')
                            ->dateTime('Y-m-d h:i A')
                            ->placeholder('لم يُرسل بعد'),
                    ])->columns(3),

                Section::make('إحصائيات الأداء والتفاعل (Analytics)')
                    ->description('مؤشرات وصول الإشعار وتفاعل المستخدمين معه في الوقت الفعلي.')
                    ->schema([
                        TextEntry::make('sent_count')
                            ->label('إجمالي المرسل (Sent)')
                            ->numeric()
                            ->badge()
                            ->color('info'),

                        TextEntry::make('delivered_count')
                            ->label('تم الاستلام (Delivered)')
                            ->numeric()
                            ->badge()
                            ->color('success'),

                        TextEntry::make('opened_count')
                            ->label('تم الفتح (Opened)')
                            ->numeric()
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('clicked_count')
                            ->label('تم النقر (Clicked)')
                            ->numeric()
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('failed_count')
                            ->label('فشل الإرسال (Failed)')
                            ->numeric()
                            ->badge()
                            ->color('danger'),

                        TextEntry::make('ctr')
                            ->label('معدل التحويل (CTR)')
                            ->state(function (AppNotification $record): string {
                                if ($record->sent_count === 0) {
                                    return '0%';
                                }

                                return round(($record->opened_count / $record->sent_count) * 100, 2) . '%';
                            })
                            ->badge()
                            ->color('success'),
                    ])->columns(6),

                Section::make('محتوى الرسالة والبيانات المرفقة')
                    ->schema([
                        TextEntry::make('title')
                            ->label('عنوان الإشعار')
                            ->weight('bold')
                            ->columnSpanFull(),

                        TextEntry::make('body')
                            ->label('نص الإشعار')
                            ->columnSpanFull(),

                        SpatieMediaLibraryImageEntry::make('notification_image')
                            ->label('صورة الإشعار')
                            ->collection('notification_image')
                            ->placeholder('لا توجد صورة مرفقة')
                            ->columnSpanFull(),

                        KeyValueEntry::make('payload_data') // تم تصحيح الاسم ليطابق المودل وقاعدة البيانات
                            ->label('البيانات الإضافية (Payload Data)')
                            ->keyLabel('المفتاح (Key)')
                            ->valueLabel('القيمة (Value)')
                            ->placeholder('لا توجد بيانات إضافية مرفقة')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}