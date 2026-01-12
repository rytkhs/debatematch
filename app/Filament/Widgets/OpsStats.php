<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DebateEvaluation;
use App\Models\Debate;
use App\Models\DebateMessage;
use App\Models\Room;
use Carbon\Carbon;

use Livewire\Attributes\Url;

class OpsStats extends StatsOverviewWidget
{
    #[Url(as: 'range')]
    public ?string $statsRange = '7d';

    // Dashboard display order (lower is higher)
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        [$from, $label] = $this->getRangeSettings();

        $rooms = Room::query()->whereNull('deleted_at')->where('created_at', '>=', $from)->count();
        $debates = Debate::query()->whereNull('deleted_at')->where('created_at', '>=', $from)->count();
        $messages = DebateMessage::query()->whereNull('deleted_at')->where('created_at', '>=', $from)->count();
        $evaluations = DebateEvaluation::query()->whereNull('deleted_at')->where('created_at', '>=', $from)->count();

        return [
            Stat::make("Rooms ({$label})", $rooms),
            Stat::make("Debates ({$label})", $debates),
            Stat::make("Messages ({$label})", $messages),
            Stat::make("Evaluations ({$label})", $evaluations),
        ];
    }

    /**
     * ?range=today|7d|30d
     */
    private function getRangeSettings(): array
    {
        return match ($this->statsRange) {
            'today' => [Carbon::today(), 'Today'],
            '30d'   => [now()->subDays(30), '30d'],
            default => [now()->subDays(7), '7d'],
        };
    }
}
