<?php

namespace App\Jobs;

use App\Models\Debate;
use App\Events\EarlyTerminationExpired;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * 早期終了提案のタイムアウト処理を行うジョブ
 */
class EarlyTerminationTimeoutJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $debateId;
    public int $requestedBy;
    public string $timestamp;

    public $tries = 3;
    public $backoff = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(int $debateId, int $requestedBy, string $timestamp)
    {
        $this->debateId = $debateId;
        $this->requestedBy = $requestedBy;
        $this->timestamp = $timestamp;
    }

    /**
     * タイムアウト処理を実行
     */
    public function handle(): void
    {
        try {
            Log::info('早期終了タイムアウト処理開始', [
                'debate_id' => $this->debateId,
                'requested_by' => $this->requestedBy,
                'timestamp' => $this->timestamp
            ]);

            $debate = Debate::find($this->debateId);
            if (!$debate) {
                Log::warning('ディベートが見つかりません', ['debate_id' => $this->debateId]);
                return;
            }

            // キャッシュから現在の状態を確認
            $cacheKey = "early_termination_request_{$this->debateId}";
            $requestData = Cache::get($cacheKey);

            // キャッシュが存在し、タイムスタンプが一致する場合のみ処理
            if (
                $requestData &&
                $requestData['requested_by'] === $this->requestedBy &&
                $requestData['timestamp'] === $this->timestamp
            ) {

                // キャッシュを削除
                Cache::forget($cacheKey);

                // タイムアウトイベントをブロードキャスト
                broadcast(new EarlyTerminationExpired($this->debateId, $this->requestedBy));

                Log::info('早期終了提案がタイムアウトしました', [
                    'debate_id' => $this->debateId,
                    'requested_by' => $this->requestedBy
                ]);
            } else {
                Log::info('早期終了提案は既に処理済みです', [
                    'debate_id' => $this->debateId,
                    'requested_by' => $this->requestedBy,
                    'cache_exists' => $requestData !== null
                ]);
            }
        } catch (\Exception $e) {
            Log::error('早期終了タイムアウト処理でエラーが発生しました', [
                'debate_id' => $this->debateId,
                'requested_by' => $this->requestedBy,
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            // 再試行が必要なエラーの場合は例外を再スロー
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * ジョブ失敗時の処理
     */
    public function failed(?Throwable $exception): void
    {
        Log::critical('早期終了タイムアウトジョブが失敗しました', [
            'debate_id' => $this->debateId,
            'requested_by' => $this->requestedBy,
            'timestamp' => $this->timestamp,
            'error' => $exception ? $exception->getMessage() : '不明なエラー'
        ]);
    }
}
