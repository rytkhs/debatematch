<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiFeatureLog extends Model
{
    /**
     * テーブル名
     */
    protected $table = 'ai_feature_logs';

    /**
     * 一括代入可能な属性
     */
    protected $fillable = [
        'request_id',
        'feature_type',
        'status',
        'user_id',
        'parameters',
        'response_data',
        'error_message',
        'status_code',
        'started_at',
        'finished_at',
        'duration_ms',
    ];

    /**
     * 属性のキャスト
     */
    protected $casts = [
        'parameters' => 'array',
        'response_data' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * タイムスタンプを無効化（カスタムタイムスタンプを使用）
     */
    public $timestamps = false;
}
