<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactSlackNotifier
{
    protected $webhookUrl;
    protected bool $enabled;

    public function __construct()
    {
        $this->webhookUrl = config('services.slack.webhook_url', env('SLACK_WEBHOOK_URL'));
        $this->enabled = env('SLACK_NOTIFICATIONS_ENABLED', true);

        if (!$this->enabled) {
            Log::info('Contact Slack通知は無効になっています。(SLACK_NOTIFICATIONS_ENABLED=false)');
        }
    }

    /**
     * 新しいお問い合わせをSlackに通知
     */
    public function notifyNewContact(Contact $contact): bool
    {
        // Slack通知が無効の場合はログのみ出力して終了
        if (!$this->enabled) {
            Log::info("Contact Slack通知（無効）: 新しいお問い合わせ #{$contact->id} - {$contact->subject}");
            return true; // 無効時も成功として扱う
        }

        if (!$this->webhookUrl) {
            Log::warning('Slack webhook URL not configured');
            return false;
        }

        $emoji = $contact->type_emoji;
        $typeName = $this->getTypeName($contact->type, $contact->language);
        $adminUrl = url("/admin/contacts/{$contact->id}");

        $message = [
            'text' => '新しいお問い合わせが届きました',
            'attachments' => [
                [
                    'color' => $contact->status_color,
                    'fields' => [
                        [
                            'title' => '種別',
                            'value' => "{$emoji} {$typeName}",
                            'short' => true
                        ],
                        [
                            'title' => 'ID',
                            'value' => "#{$contact->id}",
                            'short' => true
                        ],
                        [
                            'title' => '名前',
                            'value' => $contact->name,
                            'short' => true
                        ],
                        [
                            'title' => 'メール',
                            'value' => $contact->email,
                            'short' => true
                        ],
                        [
                            'title' => '件名',
                            'value' => $contact->subject,
                            'short' => false
                        ],
                        [
                            'title' => '言語',
                            'value' => $contact->language === 'ja' ? '🇯🇵 日本語' : '🇺🇸 English',
                            'short' => true
                        ],
                        [
                            'title' => '受信時刻',
                            'value' => $contact->created_at->format('Y-m-d H:i:s'),
                            'short' => true
                        ]
                    ],
                    'actions' => [
                        [
                            'type' => 'button',
                            'text' => '管理画面で確認',
                            'url' => $adminUrl,
                            'style' => 'primary'
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = Http::post($this->webhookUrl, $message);

            if ($response->successful()) {
                Log::info("Slack notification sent for contact #{$contact->id}");
                return true;
            } else {
                Log::error("Failed to send Slack notification for contact #{$contact->id}: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception while sending Slack notification for contact #{$contact->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 言語に応じた種別名を取得
     */
    protected function getTypeName(string $type, string $language): string
    {
        $types = config('contact.types', []);

        if (isset($types[$type]['label'][$language])) {
            return $types[$type]['label'][$language];
        }

        // フォールバック: 英語 → キー名
        return $types[$type]['label']['en'] ?? $type;
    }
}
