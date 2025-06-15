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
        'google_id',
        'email_verified_at',
        'is_guest',
        'guest_expires_at',
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
            'deleted_at' => 'datetime',
            'guest_expires_at' => 'datetime',
        ];
    }

    /**
     * ユーザーが参加しているルームのリレーション
     */
    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_users')
            ->withPivot('side')
            ->withTimestamps();
    }

    /**
     * ユーザーが肯定側として参加したディベート
     */
    public function affirmativeDebates()
    {
        return $this->hasMany(Debate::class, 'affirmative_user_id');
    }

    /**
     * ユーザーが否定側として参加したディベート
     */
    public function negativeDebates()
    {
        return $this->hasMany(Debate::class, 'negative_user_id');
    }



    public function isAdmin()
    {
        return (bool) $this->is_admin;
    }

    /**
     * ゲストユーザーかどうかを判定
     */
    public function isGuest()
    {
        return (bool) $this->is_guest;
    }

    /**
     * ゲストユーザーの期限が切れているかどうかを判定
     */
    public function isGuestExpired()
    {
        if (!$this->is_guest) {
            return false;
        }

        return $this->guest_expires_at && $this->guest_expires_at->isPast();
    }

    /**
     * ゲストユーザーが有効かどうかを判定
     */
    public function isGuestValid()
    {
        return (bool) $this->is_guest && !$this->isGuestExpired();
    }
}
