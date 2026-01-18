<?php

namespace App\Filament\Resources\AiFeatureLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AiFeatureLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('request_id')
                    ->required(),
                TextInput::make('feature_type')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('processing'),
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('parameters'),
                TextInput::make('response_data'),
                Textarea::make('error_message')
                    ->columnSpanFull(),
                TextInput::make('status_code')
                    ->numeric(),
                DateTimePicker::make('started_at')
                    ->required(),
                DateTimePicker::make('finished_at'),
                TextInput::make('duration_ms')
                    ->numeric(),
            ]);
    }
}
