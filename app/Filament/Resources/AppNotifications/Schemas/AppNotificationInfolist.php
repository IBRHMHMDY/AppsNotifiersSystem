<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications\Schemas;

use App\Enums\NotificationTargetType;
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
                // القسم الأول: بطاقة الهوية والاستهداف
                Section::make('الهوية، التصنيف، وتفاصيل الاستهداف')
                    ->icon('heroicon-o-funnel')
                    ->schema([
                        TextEntry::make('clientApp.name')
                            ->label('التطبيق المستهدف')
                            ->placeholder('جميع التطبيقات النشطة (Broadcast)')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('type')
                            ->label('نوع الإشعار')
                            ->badge(),

                        TextEntry::make('status')
                            ->label('حالة الإرسال')
                            ->badge(),

                        TextEntry::make('target_type')
                            ->label('طريقة الاستهداف')
                            ->badge(),

                        TextEntry::make('target_summary')
                            ->label('ملخص الجمهور المستهدف')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('target_os')
                            ->label('نظام التشغيل (OS)')
                            ->badge()
                            ->placeholder('جميع الأنظمة (All OS)')
                            ->visible(fn (AppNotification $record): bool => $record->target_type === NotificationTargetType::Segmented),

                        TextEntry::make('target_app_versions')
                            ->label('الإصدارات المستهدفة')
                            ->badge()
                            ->placeholder('جميع الإصدارات')
                            ->visible(fn (AppNotification $record): bool => $record->target_type === NotificationTargetType::Segmented),

                        TextEntry::make('topic')
                            ->label('قناة الموضوع (FCM Topic)')
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(fn (?string $state): string => $state ? "#{$state}" : '-')
                            ->visible(fn (AppNotification $record): bool => $record->target_type === NotificationTargetType::Topic || ! empty($record->topic)),

                        TextEntry::make('scheduled_at')
                            ->label('موعد الجدولة')
                            ->dateTime('Y-m-d h:i A')
                            ->placeholder('إرسال فوري'),

                        TextEntry::make('sent_at')
                            ->label('تاريخ الإرسال الفعلي')
                            ->dateTime('Y-m-d h:i A')
                            ->placeholder('لم يُرسل بعد'),
                    ])->columns(4),

                // القسم الثاني: إحصائيات الأداء والتفاعل
                Section::make('إحصائيات الأداء والتفاعل (Delivery & Engagement Analytics)')
                    ->description('مؤشرات وصول الإشعار وتفاعل المستخدمين معه في الوقت الفعلي.')
                    ->icon('heroicon-o-chart-bar')
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

                                return round(($record->opened_count / $record->sent_count) * 100, 2).'%';
                            })
                            ->badge()
                            ->color('success'),
                    ])->columns(6),

                // القسم الثالث: المحتوى، التوجيه، والبيانات المرفقة
                Section::make('محتوى الرسالة، سلوك النقر، والبيانات البرمجية')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        TextEntry::make('title')
                            ->label('عنوان الإشعار')
                            ->weight('bold')
                            ->placeholder('إشعار صامت (بدون عنوان مرئي)')
                            ->columnSpan(2),

                        TextEntry::make('action_type')
                            ->label('إجراء النقر (Click Action)')
                            ->badge(),

                        TextEntry::make('action_value')
                            ->label('مسار التوجيه / الرابط (Action Target)')
                            ->placeholder('لا يوجد توجيه إضافي')
                            ->copyable()
                            ->color('primary'),

                        TextEntry::make('body')
                            ->label('نص الإشعار')
                            ->placeholder('إشعار صامت (بدون نص مرئي)')
                            ->columnSpanFull(),

                        SpatieMediaLibraryImageEntry::make('notification_image')
                            ->label('صورة الإشعار المرفقة')
                            ->collection('notification_image')
                            ->placeholder('لا توجد صورة مرفقة')
                            ->visible(fn (AppNotification $record): bool => ! $record->type?->isSilent())
                            ->columnSpanFull(),

                        KeyValueEntry::make('payload_data')
                            ->label('البيانات الإضافية المخصصة (Custom Payload Data)')
                            ->keyLabel('المفتاح (Key)')
                            ->valueLabel('القيمة (Value)')
                            ->placeholder('لا توجد بيانات إضافية مرفقة')
                            ->columnSpanFull(),
                    ])->columns(4),
            ]);
    }
}
