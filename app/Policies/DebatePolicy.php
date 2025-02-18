<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Debate;
use Illuminate\Auth\Access\HandlesAuthorization;

class DebatePolicy
{
    use HandlesAuthorization;

    public function advanceTurn(User $user, Debate $debate): bool
    {
        $currentSpeaker = config('debate.turns.' . $debate->current_turn . '.speaker');

        return match ($currentSpeaker) {
            'affirmative' => $debate->affirmative_user_id === $user->id,
            'negative' => $debate->negative_user_id === $user->id,
            default => false,
        };
    }
}
