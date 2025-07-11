<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'topic',
        'remarks',
        'status',
        'created_by',
        'language',
        'format_type',
        'custom_format_settings',
        'evidence_allowed',
        'is_ai_debate'
    ];

    protected $casts = [
        'custom_format_settings' => 'array',
        'evidence_allowed' => 'boolean',
        'is_ai_debate' => 'boolean',
    ];

    // 状態の定数
    public const STATUS_WAITING = 'waiting';
    public const STATUS_READY = 'ready';
    public const STATUS_DEBATING = 'debating';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_TERMINATED = 'terminated';

    // 利用可能な状態の配列
    public const AVAILABLE_STATUSES = [
        self::STATUS_WAITING,
        self::STATUS_READY,
        self::STATUS_DEBATING,
        self::STATUS_FINISHED,
        self::STATUS_DELETED,
        self::STATUS_TERMINATED
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'room_users')->withPivot('side');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function debate()
    {
        return $this->hasOne(Debate::class);
    }

    public function updateStatus(string $status): void
    {
        if ($this->status === $status) {
            return;
        }

        // 有効な状態遷移を定義
        $validTransitions = [
            self::STATUS_WAITING => [self::STATUS_WAITING, self::STATUS_READY, self::STATUS_DELETED, self::STATUS_TERMINATED],
            self::STATUS_READY => [self::STATUS_READY, self::STATUS_DEBATING, self::STATUS_WAITING, self::STATUS_DELETED, self::STATUS_TERMINATED],
            self::STATUS_DEBATING => [self::STATUS_DEBATING, self::STATUS_FINISHED, self::STATUS_DELETED, self::STATUS_TERMINATED],
            self::STATUS_FINISHED => [self::STATUS_FINISHED],
            self::STATUS_DELETED => [self::STATUS_DELETED],
            self::STATUS_TERMINATED => [self::STATUS_TERMINATED],
        ];

        // 通常の状態遷移のバリデーション
        if (!in_array($status, $validTransitions[$this->status])) {
            throw new \InvalidArgumentException("Invalid status transition: {$this->status} → {$status}");
        }

        // 状態を更新
        $this->update(['status' => $status]);
    }

    /**
     * ディベートのフォーマットを取得する(翻訳済み)
     */

    public function getDebateFormat()
    {
        // カスタムフォーマットまたはフリーフォーマットの場合はカスタム設定を返す
        if (($this->format_type === 'custom' || $this->format_type === 'free') && !empty($this->custom_format_settings)) {
            // カスタムフォーマットでも翻訳処理を行う
            $translatedFormat = [];
            foreach ($this->custom_format_settings as $index => $turn) {
                $translatedTurn = $turn;

                // ターン名が翻訳キーの場合は翻訳する
                if (isset($turn['name']) && str_starts_with($turn['name'], 'suggestion_')) {
                    $translatedTurn['name'] = __('debates.' . $turn['name']);
                } elseif (isset($turn['name'])) {
                    // 通常のターン名の場合はそのまま使用
                    $translatedTurn['name'] = $turn['name'];
                }

                $translatedFormat[$index] = $translatedTurn;
            }
            return $translatedFormat;
        }

        // 標準フォーマットの場合はconfig設定を取得
        $format = config("debate.formats.{$this->format_type}", []);

        // 各ターンの名前を翻訳
        $translatedFormat = [];
        foreach ($format as $index => $turn) {
            $translatedTurn = $turn;

            $translatedTurn['name'] = __('debates.' . $turn['name']);
            $translatedFormat[$index] = $translatedTurn;
        }
        return $translatedFormat;
    }

    /**
     * フォーマット名を取得
     */
    public function getFormatName(): string
    {
        // format_typeがconfig('debate.formats')のキーに存在する場合は、その名前を返す
        if (array_key_exists($this->format_type, config('debate.formats'))) {
            return __('debates.' . $this->format_type);
        }
        // カスタムフォーマットの場合は'カスタム'を返す
        if ($this->format_type === 'custom') {
            return __('debates.custom');
        }
        // フリーフォーマットの場合は'フリー'を返す
        if ($this->format_type === 'free') {
            return __('debates.format_name_free');
        }

        return '';
    }

    /**
     * フリーフォーマットかどうかを判定
     */
    public function isFreeFormat(): bool
    {
        return $this->format_type === 'free';
    }
}
