<?php

namespace App\Filament\Resources\AiFeatureLogs\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;

class AiFeatureLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('feature_type')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'success' => 'success',
                                'processing' => 'warning',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('user_id')
                            ->label('User ID')
                            ->placeholder('-'),
                        TextEntry::make('status_code')
                            ->placeholder('-'),
                        TextEntry::make('duration_ms')
                            ->label('Duration')
                            ->suffix('ms')
                            ->placeholder('-'),
                        TextEntry::make('started_at')
                            ->dateTime(),
                        TextEntry::make('finished_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('request_id')
                            ->label('Request ID')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Error Details')
                    ->schema([
                        TextEntry::make('error_message')
                            ->label('Message')
                            ->color('danger')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => $state), // Ensure simple text
                    ])
                    ->visible(fn ($record) => $record->status === 'failed' || !empty($record->error_message)),

                Section::make('Payloads')
                    ->schema([
                        TextEntry::make('parameters')
                            ->label('Request Parameters')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                            ->fontFamily(FontFamily::Mono),
                        TextEntry::make('response_data')
                            ->label('Response Data')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                            ->fontFamily(FontFamily::Mono),
                    ]),
            ]);
    }
}
