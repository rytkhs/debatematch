<?php

namespace App\Filament\Resources\Debates\Pages;

use App\Filament\Resources\Debates\DebateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDebate extends ViewRecord
{
    protected static string $resource = DebateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
