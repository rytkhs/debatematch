<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
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
    /**
     * @return array{email_verified_at: 'datetime', password: 'hashed', deleted_at: 'datetime', guest_expires_at: 'datetime', is_admin: 'bool'}
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
            'guest_expires_at' => 'datetime',
            'is_admin' => 'bool',
        ];
    }

    /**
     * ユーザーが参加しているルームのリレーション
     */
    /**
     * @return BelongsToMany<Room, $this>
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_users')
            ->withPivot('side')
            ->withTimestamps();
    }

    /**
     * ユーザーが肯定側として参加したディベート
     */
    /**
     * @return HasMany<Debate, $this>
     */
    public function affirmativeDebates(): HasMany
    {
        return $this->hasMany(Debate::class, 'affirmative_user_id');
    }

    /**
     * ユーザーが否定側として参加したディベート
     */
    /**
     * @return HasMany<Debate, $this>
     */
    public function negativeDebates(): HasMany
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

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->isAdmin();
    }

    /**
     * メール認証通知を送信
     *
     * デフォルトのメール認証をオーバーライドしてOTP認証を使用
     */
    public function sendEmailVerificationNotification()
    {
        app(\App\Contracts\OtpServiceInterface::class)->sendOtp($this);
    }
}
