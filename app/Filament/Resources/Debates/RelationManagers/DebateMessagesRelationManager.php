<?php

namespace App\Filament\Resources\Debates\RelationManagers;

use App\Filament\Resources\Debates\DebateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DebateMessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'debateMessages';



    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('turn')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('turn')
                    ->sortable()
                    ->badge(),
                \Filament\Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),
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
            ])
            ->defaultSort('turn')
            ->paginated(false);
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
}
