<?php

namespace App\Filament\Resources\AiFeatureLogs\Pages;

use App\Filament\Resources\AiFeatureLogs\AiFeatureLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAiFeatureLog extends ViewRecord
{
    protected static string $resource = AiFeatureLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
