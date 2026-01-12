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
                    ->wrap()
                    ->action(
                        \Filament\Actions\Action::make('viewAnalysis')
                            ->modalHeading('Analysis')
                            ->modalContent(fn ($record) => new \Illuminate\Support\HtmlString('<div>' . nl2br(e($record->analysis)) . '</div>'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                    ),
                \Filament\Tables\Columns\TextColumn::make('reason')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->action(
                        \Filament\Actions\Action::make('viewReason')
                            ->modalHeading('Reason')
                            ->modalContent(fn ($record) => new \Illuminate\Support\HtmlString('<div>' . nl2br(e($record->reason)) . '</div>'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                    ),
                \Filament\Tables\Columns\TextColumn::make('feedback_for_affirmative')
                    ->label('Feedback (Aff.)')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->action(
                        \Filament\Actions\Action::make('viewFeedbackAff')
                            ->modalHeading('Feedback for Affirmative')
                            ->modalContent(fn ($record) => new \Illuminate\Support\HtmlString('<div>' . nl2br(e($record->feedback_for_affirmative)) . '</div>'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                    ),
                \Filament\Tables\Columns\TextColumn::make('feedback_for_negative')
                    ->label('Feedback (Neg.)')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->action(
                        \Filament\Actions\Action::make('viewFeedbackNeg')
                            ->modalHeading('Feedback for Negative')
                            ->modalContent(fn ($record) => new \Illuminate\Support\HtmlString('<div>' . nl2br(e($record->feedback_for_negative)) . '</div>'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                    ),
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
