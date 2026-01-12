<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'email',
        'subject',
        'message',
        'status',
        'language',
        'user_id',
        'admin_notes',
        'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * お問い合わせ種別の定数定義
     */
    public const TYPE_BUG_REPORT = 'bug_report';
    public const TYPE_FEATURE_REQUEST = 'feature_request';
    public const TYPE_GENERAL_QUESTION = 'general_question';
    public const TYPE_ACCOUNT_ISSUES = 'account_issues';
    public const TYPE_OTHER = 'other';

    /**
     * お問い合わせ種別の選択肢
     */
    public static function getTypes(): array
    {
        $types = config('contact.types', []);
        $result = [];

        foreach ($types as $key => $config) {
            if ($config['enabled'] ?? true) {
                $locale = app()->getLocale();
                $label = $config['label'][$locale] ?? $config['label']['en'] ?? $key;
                $emoji = $config['emoji'] ?? '';
                $result[$key] = $emoji ? "{$emoji} {$label}" : $label;
            }
        }

        // 優先度順でソート
        uksort($result, function ($a, $b) use ($types) {
            $priorityA = $types[$a]['priority'] ?? 999;
            $priorityB = $types[$b]['priority'] ?? 999;
            return $priorityA <=> $priorityB;
        });

        return $result;
    }

    /**
     * 有効な種別のキーを取得
     */
    public static function getValidTypes(): array
    {
        return array_keys(self::getTypes());
    }

    /**
     * ステータスの選択肢
     */
    public static function getStatuses(): array
    {
        $statuses = config('contact.statuses', []);
        $result = [];

        foreach ($statuses as $key => $config) {
            $locale = app()->getLocale();
            $label = $config['label'][$locale] ?? $config['label']['en'] ?? $key;
            $result[$key] = $label;
        }

        return $result;
    }

    /**
     * ユーザーとの関連
     */
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 種別名を取得
     */
    public function getTypeNameAttribute(): string
    {
        $types = self::getTypes();
        return $types[$this->type] ?? $this->type;
    }

    /**
     * ステータス名を取得
     */
    public function getStatusNameAttribute(): string
    {
        $statuses = self::getStatuses();
        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * スコープ：ステータスでフィルタ
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * スコープ：種別でフィルタ
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * スコープ：最新順
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 種別の絵文字を取得
     */
    public function getTypeEmojiAttribute(): string
    {
        $types = config('contact.types', []);
        return $types[$this->type]['emoji'] ?? '📝';
    }

    /**
     * ステータスの色を取得
     */
    public function getStatusColorAttribute(): string
    {
        $statuses = config('contact.statuses', []);
        return $statuses[$this->status]['color'] ?? '#9e9e9e';
    }

    /**
     * ステータス用のTailwind CSSクラスを取得
     */
    public function getStatusCssClassAttribute(): string
    {
        $statusClasses = [
            'new' => 'bg-red-100 text-red-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'replied' => 'bg-green-100 text-green-800',
            'resolved' => 'bg-blue-100 text-blue-800',
            'closed' => 'bg-gray-100 text-gray-800',
        ];

        return $statusClasses[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}
