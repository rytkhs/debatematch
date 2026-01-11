<?php

namespace App\Filament\Resources\Debates\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DebateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('room_id')
                    ->required()
                    ->numeric(),
                TextInput::make('affirmative_user_id')
                    ->numeric(),
                TextInput::make('negative_user_id')
                    ->numeric(),
                TextInput::make('current_turn')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('turn_end_time'),
            ]);
    }
}
