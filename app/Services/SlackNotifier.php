<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SlackNotifier
{
    protected ?string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = Config::get('services.slack.webhook_url') ?: env('SLACK_WEBHOOK_URL');

        if (empty($this->webhookUrl)) {
            Log::warning('Slack Webhook URLが設定されていません。(SLACK_WEBHOOK_URL)');
        }
    }

    /**
     * Slackチャンネルにメッセージを送信する
     *
     * @param string $message 送信するメッセージ
     * @param string|null $channel 送信先のチャンネル
     * @param string|null $username ボット名
     * @param string|null $iconEmoji アイコン絵文字
     * @return bool 成功時はtrue、失敗時はfalse
     */
    public function send(string $message, ?string $channel = null, ?string $username = 'DebateMatch Bot', ?string $iconEmoji = ':robot_face:'): bool
    {
        if (empty($this->webhookUrl)) {
            Log::error('Slack通知送信失敗: Webhook URLが未設定です。');
            return false;
        }

        $payload = [
            'text' => $message,
        ];

        if ($channel) {
            $payload['channel'] = $channel;
        }
        if ($username) {
            $payload['username'] = $username;
        }
        if ($iconEmoji) {
            $payload['icon_emoji'] = $iconEmoji;
        }

        try {
            $response = Http::post($this->webhookUrl, $payload);

            if ($response->successful()) {
                Log::info("Slack通知送信成功: {$message}");
                return true;
            } else {
                Log::error("Slack通知送信失敗: Status={$response->status()}, Response={$response->body()}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Slack通知送信中に予期せぬエラーが発生: Error={$e->getMessage()}");
            return false;
        }
    }
}
