<?php

namespace App\Filament\Resources\Debates\RelationManagers;

use App\Filament\Resources\Debates\DebateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DebateEvaluationRelationManager extends RelationManager
{
    protected static string $relationship = 'debateEvaluation';



    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Toggle::make('is_analyzable'),
                \Filament\Forms\Components\TextInput::make('winner'),
                \Filament\Forms\Components\Textarea::make('analysis')->columnSpanFull(),
                \Filament\Forms\Components\Textarea::make('reason')->columnSpanFull(),
                \Filament\Forms\Components\Textarea::make('feedback_for_affirmative')->columnSpanFull(),
                \Filament\Forms\Components\Textarea::make('feedback_for_negative')->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('winner')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\IconColumn::make('is_analyzable')
                    ->boolean(),
                \Filament\Tables\Columns\TextColumn::make('winner')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'affirmative' => 'success',
                        'negative' => 'danger',
                        default => 'gray',
                    }),
                \Filament\Tables\Columns\TextColumn::make('analysis')
                    ->limit(50)
                    ->wrap(),
                \Filament\Tables\Columns\TextColumn::make('reason')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make(),
            ]);
    }
}
