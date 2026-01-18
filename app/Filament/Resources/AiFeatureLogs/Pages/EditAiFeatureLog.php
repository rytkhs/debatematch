<?php

namespace App\Filament\Resources\AiFeatureLogs\Pages;

use App\Filament\Resources\AiFeatureLogs\AiFeatureLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAiFeatureLog extends EditRecord
{
    protected static string $resource = AiFeatureLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
