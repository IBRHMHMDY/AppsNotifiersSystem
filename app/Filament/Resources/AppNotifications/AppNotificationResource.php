<?php

namespace App\Filament\Resources\AppNotifications;

use App\Filament\Resources\AppNotifications\Pages\CreateAppNotification;
use App\Filament\Resources\AppNotifications\Pages\EditAppNotification;
use App\Filament\Resources\AppNotifications\Pages\ListAppNotifications;
use App\Filament\Resources\AppNotifications\Pages\ViewAppNotification;
use App\Filament\Resources\AppNotifications\Schemas\AppNotificationForm;
use App\Filament\Resources\AppNotifications\Schemas\AppNotificationInfolist;
use App\Filament\Resources\AppNotifications\Tables\AppNotificationsTable;
use App\Models\AppNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AppNotificationResource extends Resource
{
    protected static ?string $model = AppNotification::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'الإشعارات';

    protected static ?string $modelLabel = 'إشعار';

    protected static string|UnitEnum|null $navigationGroup = 'التواصل';

    public static function form(Schema $schema): Schema
    {
        return AppNotificationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppNotificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppNotificationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppNotifications::route('/'),
            'create' => CreateAppNotification::route('/create'),
            'view' => ViewAppNotification::route('/{record}'),
            'edit' => EditAppNotification::route('/{record}/edit'),
        ];
    }
}
