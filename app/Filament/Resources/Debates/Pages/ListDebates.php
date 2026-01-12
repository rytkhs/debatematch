<?php

namespace App\Filament\Resources\Debates\Pages;

use App\Filament\Resources\Debates\DebateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDebates extends ListRecords
{
    protected static string $resource = DebateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
