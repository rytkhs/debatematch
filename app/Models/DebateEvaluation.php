<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebateEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'debate_id',
        'is_analyzable',
        'winner',
        'analysis',
        'reason',
        'feedback_for_affirmative',
        'feedback_for_negative',
    ];

    public function debate()
    {
        return $this->belongsTo(Debate::class);
    }
}
