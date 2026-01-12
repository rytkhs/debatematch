<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebateEvaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'debate_id',
        'is_analyzable',
        'winner',
        'analysis',
        'reason',
        'feedback_for_affirmative',
        'feedback_for_negative',
    ];

    // 勝者の定数
    public const WINNER_AFFIRMATIVE = 'affirmative';
    public const WINNER_NEGATIVE = 'negative';

    /** @return BelongsTo<Debate, $this> */
    public function debate(): BelongsTo
    {
        return $this->belongsTo(Debate::class);
    }
}
