<?php

namespace App\Filament\Resources\AppUpdates\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AppUpdateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('clientApp.name')
                    ->label('Client app'),
                TextEntry::make('version'),
                TextEntry::make('store_url')
                    ->placeholder('-'),
                IconEntry::make('is_force_update')
                    ->boolean(),
                TextEntry::make('release_notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
