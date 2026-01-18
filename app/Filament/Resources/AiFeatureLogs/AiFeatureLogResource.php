<?php

namespace App\Filament\Resources\AiFeatureLogs;

use App\Filament\Resources\AiFeatureLogs\Pages\CreateAiFeatureLog;
use App\Filament\Resources\AiFeatureLogs\Pages\EditAiFeatureLog;
use App\Filament\Resources\AiFeatureLogs\Pages\ListAiFeatureLogs;
use App\Filament\Resources\AiFeatureLogs\Pages\ViewAiFeatureLog;
use App\Filament\Resources\AiFeatureLogs\Schemas\AiFeatureLogForm;
use App\Filament\Resources\AiFeatureLogs\Schemas\AiFeatureLogInfolist;
use App\Filament\Resources\AiFeatureLogs\Tables\AiFeatureLogsTable;
use App\Models\AiFeatureLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiFeatureLogResource extends Resource
{
    protected static ?string $model = AiFeatureLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'request_id';

    public static function form(Schema $schema): Schema
    {
        return AiFeatureLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AiFeatureLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiFeatureLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiFeatureLogs::route('/'),
            'create' => CreateAiFeatureLog::route('/create'),
            'view' => ViewAiFeatureLog::route('/{record}'),
            'edit' => EditAiFeatureLog::route('/{record}/edit'),
        ];
    }
}
