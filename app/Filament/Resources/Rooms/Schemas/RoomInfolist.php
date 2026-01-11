<?php

namespace App\Filament\Resources\Rooms\Schemas;

use App\Models\Room;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RoomInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('topic'),
                TextEntry::make('remarks')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('is_ai_debate')
                    ->boolean(),
                TextEntry::make('language'),
                TextEntry::make('format_type'),
                IconEntry::make('evidence_allowed')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Room $record): bool => $record->trashed()),
            ]);
    }
}
