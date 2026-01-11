<?php

namespace App\Filament\Resources\Debates;

use App\Filament\Resources\Debates\Pages\CreateDebate;
use App\Filament\Resources\Debates\Pages\EditDebate;
use App\Filament\Resources\Debates\Pages\ListDebates;
use App\Filament\Resources\Debates\Pages\ViewDebate;
use App\Filament\Resources\Debates\Schemas\DebateForm;
use App\Filament\Resources\Debates\Schemas\DebateInfolist;
use App\Filament\Resources\Debates\Tables\DebatesTable;
use App\Models\Debate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DebateResource extends Resource
{
    protected static ?string $model = Debate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return DebateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DebateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DebatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DebateMessagesRelationManager::class,
            RelationManagers\DebateEvaluationRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDebates::route('/'),
            'create' => CreateDebate::route('/create'),
            'view' => ViewDebate::route('/{record}'),
            'edit' => EditDebate::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
