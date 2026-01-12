<?php

namespace App\Filament\Resources\Debates\Schemas;

use App\Models\Debate;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DebateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('room_id')
                    ->numeric(),
                TextEntry::make('affirmative_user_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('negative_user_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('current_turn')
                    ->numeric(),
                TextEntry::make('turn_end_time')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Debate $record): bool => $record->trashed()),
            ]);
    }
}
