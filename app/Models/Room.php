<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'topic', 'remarks', 'status', 'created_by', 'language', 'format_type', 'custom_format_settings'];
    protected $touches = ['users'];

    protected $casts = [
        'custom_format_settings' => 'array',
    ];

    public const STATUS_WAITING = 'waiting';

    public const STATUS_READY = 'ready';

    public const STATUS_DEBATING = 'debating';

    public const STATUS_FINISHED = 'finished';

    public const STATUS_DELETED = 'deleted';

    public const STATUS_TERMINATED = 'terminated';

    public function users()
    {
        return $this->belongsToMany(User::class, 'room_users')->withPivot('side', 'role');
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
        // 有効な状態遷移を定義
        // terminatedはどこからでも遷移できるように、validTransitionsを更新
        $validTransitions = [
            'waiting' => ['ready', 'deleted', 'terminated'],
            'ready' => ['debating', 'waiting', 'deleted', 'terminated'],
            'debating' => ['finished', 'terminated'],
            'finished' => [],
            'deleted' => [],
            'terminated' => [],
        ];

        // 通常の状態遷移のバリデーション
        if (!in_array($status, $validTransitions[$this->status])) {
            throw new \InvalidArgumentException("Invalid status transition: {$this->status} → {$status}");
        }

        // 状態を更新
        $this->update(['status' => $status]);
    }

    /**
     * ディベートのフォーマットを取得する
     */
    public function getDebateFormat()
    {
        // カスタムフォーマットの場合はカスタム設定を返す
        if ($this->format_type === 'custom' && !empty($this->custom_format_settings)) {
            return $this->custom_format_settings;
        }

        // それ以外は選択されたフォーマットタイプのconfig設定を返す
        return config("debate.formats.{$this->format_type}", []);
    }

    /**
     * フォーマット名を取得
     */
    public function getFormatName(): string
    {
        // format_typeがconfig('debate.formats')のキーに存在する場合は、その名前を返す
        if (array_key_exists($this->format_type, config('debate.formats'))) {
            return $this->format_type;
        }
        // カスタムフォーマットの場合は'カスタム'を返す
        if ($this->format_type === 'custom') {
            return 'カスタム';
        }

        return '';
    }
}
