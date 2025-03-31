<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * ユーザーが参加しているルームのリレーション
     */
    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_users')
            ->withPivot('side');
    }

    /**
     * ユーザーが参加しているディベートのリレーション
     */
    public function debates()
    {
        return $this->hasMany(Debate::class, 'affirmative_user_id')
                    ->orWhere('negative_user_id', $this->id);
    }

    /**
     * ユーザーのディベート数を取得
     */
    public function getDebatesCountAttribute()
    {
        return $this->debates()->count();
    }

    /**
     * ユーザーの勝利数を取得
     */
    public function getWinsCountAttribute()
    {
        return Debate::whereHas('evaluations', function ($query) {
            $query->where(function ($q) {
                $q->where('winner', 'affirmative')
                    ->where('affirmative_user_id', $this->id);
            })->orWhere(function ($q) {
                $q->where('winner', 'negative')
                    ->where('negative_user_id', $this->id);
            });
        })->count();
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }
}
