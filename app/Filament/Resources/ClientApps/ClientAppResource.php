<?php

namespace App\Filament\Resources\ClientApps;

use App\Filament\Resources\ClientApps\Pages\CreateClientApp;
use App\Filament\Resources\ClientApps\Pages\EditClientApp;
use App\Filament\Resources\ClientApps\Pages\ListClientApps;
use App\Filament\Resources\ClientApps\Pages\ViewClientApp;
use App\Filament\Resources\ClientApps\Schemas\ClientAppForm;
use App\Filament\Resources\ClientApps\Schemas\ClientAppInfolist;
use App\Filament\Resources\ClientApps\Tables\ClientAppsTable;
use App\Models\ClientApp;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientAppResource extends Resource
{
    protected static ?string $model = ClientApp::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'تطبيقاتي';
    protected static ?string $modelLabel = 'تطبيق';
    protected static ?string $pluralModelLabel = 'التطبيقات';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة النظام';

    public static function form(Schema $schema): Schema
    {
        return ClientAppForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClientAppInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientAppsTable::configure($table);
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
            'index' => ListClientApps::route('/'),
            'create' => CreateClientApp::route('/create'),
            'view' => ViewClientApp::route('/{record}'),
            'edit' => EditClientApp::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
