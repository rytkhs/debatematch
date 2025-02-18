<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'topic', 'remarks', 'status', 'created_by'];
    protected $touches = ['users'];

    public const STATUS_WAITING = 'waiting';

    public const STATUS_READY = 'ready';

    public const STATUS_DEBATING = 'debating';

    public const STATUS_FINISHED = 'finished';

    public function users()
    {
        return $this->belongsToMany(User::class, 'room_users')->withPivot('side', 'role', 'status')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function debate()
    {
        return $this->hasOne(Debate::class);
    }

    public function updateStatus(string $status): void
    {
        $validTransitions = [
            'waiting' => ['ready'],
            'ready' => ['debating', 'waiting'],
            'debating' => ['finished'],
            'finished' => []
        ];

        if (!in_array($status, $validTransitions[$this->status])) {
            throw new \InvalidArgumentException("Invalid status transition: {$this->status} → {$status}");
        }

        $this->update(['status' => $status]);
    }

    public function shouldBeReady(): bool
    {
        return $this->users->count() === 2 && $this->status === 'waiting';
    }

    public function shouldRevertToWaiting(): bool
    {
        return $this->users->count() < 2 && $this->status === 'ready';
    }
}
