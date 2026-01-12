<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns;

class OpsAlerts extends TableWidget
{
    protected static ?int $sort = 2;

    // Use full width layout
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Contact::query()
                    ->where('status', 'new')
                    ->orderByDesc('created_at')
            )
            ->columns([
                Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Columns\TextColumn::make('type')->badge(),
                Columns\TextColumn::make('subject')->searchable()->limit(50),
                Columns\TextColumn::make('language')->toggleable(),
                Columns\TextColumn::make('email')->toggleable(),
            ])
            ->defaultPaginationPageOption(10)
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
