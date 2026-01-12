<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('topic')
                    ->required(),
                Textarea::make('remarks')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('waiting'),
                TextInput::make('created_by')
                    ->numeric(),
                Toggle::make('is_ai_debate')
                    ->required(),
                TextInput::make('language')
                    ->required()
                    ->default('japanese'),
                TextInput::make('format_type')
                    ->required(),
                TextInput::make('custom_format_settings'),
                Toggle::make('evidence_allowed')
                    ->required(),
            ]);
    }
}
