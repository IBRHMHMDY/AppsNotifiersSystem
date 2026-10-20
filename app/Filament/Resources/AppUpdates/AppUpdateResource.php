<?php

namespace App\Filament\Resources\AppUpdates;

use App\Filament\Resources\AppUpdates\Pages\CreateAppUpdate;
use App\Filament\Resources\AppUpdates\Pages\EditAppUpdate;
use App\Filament\Resources\AppUpdates\Pages\ListAppUpdates;
use App\Filament\Resources\AppUpdates\Pages\ViewAppUpdate;
use App\Filament\Resources\AppUpdates\Schemas\AppUpdateForm;
use App\Filament\Resources\AppUpdates\Schemas\AppUpdateInfolist;
use App\Filament\Resources\AppUpdates\Tables\AppUpdatesTable;
use App\Models\AppUpdate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AppUpdateResource extends Resource
{
    protected static ?string $model = AppUpdate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'تحديثات التطبيقات';

    protected static ?string $modelLabel = 'تحديث';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة النظام';

    public static function form(Schema $schema): Schema
    {
        return AppUpdateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppUpdateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppUpdatesTable::configure($table);
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
            'index' => ListAppUpdates::route('/'),
            'create' => CreateAppUpdate::route('/create'),
            'view' => ViewAppUpdate::route('/{record}'),
            'edit' => EditAppUpdate::route('/{record}/edit'),
        ];
    }
}
