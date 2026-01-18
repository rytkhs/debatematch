<?php

namespace App\Filament\Resources\AiFeatureLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class AiFeatureLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'processing' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('feature_type')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('user_id')
                    ->label('User ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status_code')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->numeric()
                    ->suffix('ms')
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('request_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'processing' => 'Processing',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('feature_type')
                    ->options([
                        'topic_generate' => 'Topic Generate',
                        'topic_info' => 'Topic Info',
                    ]),
                Filter::make('started_at')
                    ->form([
                        DatePicker::make('started_from'),
                        DatePicker::make('started_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['started_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '>=', $date),
                            )
                            ->when(
                                $data['started_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
