<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Debate;


class DebateTab extends Component
{
    public Debate $debate;
    public string $activeTab = 'all';

    public function mount(Debate $debate): void
    {
        $this->debate = $debate;
    }

    /**
     * タブが変更されたときのイベントハンドラ
     */
    public function updatedActiveTab(string $value): void
    {
        $this->dispatch('update-active-tab', activeTab: $value);
    }

    /**
     * 準備時間を除外したターン一覧を取得
     */
    private function getFilteredTurns(): array
    {
        $turns = $this->debate->getTurns();
        return array_filter($turns, fn($turn) => !$turn['is_prep_time']);
    }

    public function render()
    {
        return view('livewire.debate-tab', [
            'turns' => $this->getFilteredTurns(),
        ]);
    }
}
