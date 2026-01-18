<?php

namespace App\Filament\Resources\AiFeatureLogs\Pages;

use App\Filament\Resources\AiFeatureLogs\AiFeatureLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiFeatureLogs extends ListRecords
{
    protected static string $resource = AiFeatureLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
