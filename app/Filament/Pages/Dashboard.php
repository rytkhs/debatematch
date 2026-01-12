<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Livewire\Attributes\Url;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    #[Url]
    public ?string $range = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('today')
                ->label('Today')
                ->color(request('range') === 'today' ? 'primary' : 'gray')
                ->url(fn () => route('filament.admin.pages.dashboard', ['range' => 'today'])),
            Action::make('7d')
                ->label('7 Days')
                ->color((!request('range') || request('range') === '7d') ? 'primary' : 'gray')
                ->url(fn () => route('filament.admin.pages.dashboard', ['range' => '7d'])),
            Action::make('30d')
                ->label('30 Days')
                ->color(request('range') === '30d' ? 'primary' : 'gray')
                ->url(fn () => route('filament.admin.pages.dashboard', ['range' => '30d'])),
        ];
    }
}
