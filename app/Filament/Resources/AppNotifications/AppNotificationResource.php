<?php

// app/Filament/Resources/AppNotifications/AppNotificationResource.php

declare(strict_types=1);

namespace App\Filament\Resources\AppNotifications;

use App\Enums\NotificationStatus;
use App\Filament\RelationManagers\ActivitiesRelationManager;
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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AppNotificationResource extends Resource
{
    protected static ?string $model = AppNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'الإشعارات';

    protected static ?string $navigationLabel = 'الإشعارات';

    protected static ?string $modelLabel = 'إشعار';

    protected static ?string $pluralModelLabel = 'الإشعارات';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['clientApp']);
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = AppNotification::query()
            ->whereIn('status', [NotificationStatus::PENDING, NotificationStatus::PROCESSING])
            ->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

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
            ActivitiesRelationManager::class,
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
