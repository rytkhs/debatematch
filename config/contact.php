<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contact Types Configuration
    |--------------------------------------------------------------------------
    |
    | お問い合わせ種別の設定
    |
    */
    'types' => [
        'bug_report' => [
            'key' => 'bug_report',
            'label' => [
                'ja' => 'バグ報告',
                'en' => 'Bug Report',
            ],
            'emoji' => '🐛',
            'priority' => 1,
            'enabled' => true,
        ],
        'feature_request' => [
            'key' => 'feature_request',
            'label' => [
                'ja' => '機能要望',
                'en' => 'Feature Request',
            ],
            'emoji' => '💡',
            'priority' => 2,
            'enabled' => true,
        ],
        'general_question' => [
            'key' => 'general_question',
            'label' => [
                'ja' => '一般的な質問',
                'en' => 'General Question',
            ],
            'emoji' => '❓',
            'priority' => 3,
            'enabled' => true,
        ],
        'account_issues' => [
            'key' => 'account_issues',
            'label' => [
                'ja' => 'アカウント関連',
                'en' => 'Account Issues',
            ],
            'emoji' => '👤',
            'priority' => 4,
            'enabled' => true,
        ],
        'other' => [
            'key' => 'other',
            'label' => [
                'ja' => 'その他',
                'en' => 'Other',
            ],
            'emoji' => '📝',
            'priority' => 5,
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Status Configuration
    |--------------------------------------------------------------------------
    |
    | お問い合わせステータスの設定
    |
    */
    'statuses' => [
        'new' => [
            'key' => 'new',
            'label' => [
                'ja' => '新規',
                'en' => 'New',
            ],
            'color' => '#ff6b6b',
            'priority' => 1,
        ],
        'in_progress' => [
            'key' => 'in_progress',
            'label' => [
                'ja' => '確認中',
                'en' => 'In Progress',
            ],
            'color' => '#ffa726',
            'priority' => 2,
        ],
        'replied' => [
            'key' => 'replied',
            'label' => [
                'ja' => '回答済み',
                'en' => 'Replied',
            ],
            'color' => '#66bb6a',
            'priority' => 3,
        ],
        'resolved' => [
            'key' => 'resolved',
            'label' => [
                'ja' => '解決済み',
                'en' => 'Resolved',
            ],
            'color' => '#42a5f5',
            'priority' => 4,
        ],
        'closed' => [
            'key' => 'closed',
            'label' => [
                'ja' => 'クローズ',
                'en' => 'Closed',
            ],
            'color' => '#9e9e9e',
            'priority' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack Notification Settings
    |--------------------------------------------------------------------------
    |
    | Slack通知の設定
    |
    */
    'slack' => [
        'enabled' => env('CONTACT_SLACK_ENABLED', true),
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'channel' => env('CONTACT_SLACK_CHANNEL', '#general'),
        'username' => env('CONTACT_SLACK_USERNAME', 'DebateMatch Bot'),
        'icon_emoji' => env('CONTACT_SLACK_ICON', ':robot_face:'),
    ],
];
