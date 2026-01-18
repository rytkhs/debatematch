<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
    ],

    'sns' => [
        'notification_topic_arn' => env('AWS_SNS_NOTIFICATION_TOPIC_ARN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'enabled' => env('SLACK_NOTIFICATIONS_ENABLED', true),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_CHAT_MODEL', 'google/gemini-2.5-flash'),
        'evaluation_model' => env('OPENROUTER_EVALUATION_MODEL', 'google/gemini-2.5-flash'),
        'topic_generation_model' => env('OPENROUTER_TOPIC_GENERATION_MODEL', 'qwen/qwen-2.5-7b-instruct'),
        'topic_insight_model' => env('OPENROUTER_TOPIC_INSIGHT_MODEL', 'qwen/qwen-2.5-7b-instruct'),
        'referer' => config('app.url'),
        'title' => config('app.name'),
        'timeout_seconds' => env('OPENROUTER_TIMEOUT_SECONDS', 240),
        'max_attempts' => env('OPENROUTER_MAX_ATTEMPTS', 3),
        'max_tokens_cap' => env('OPENROUTER_MAX_TOKENS_CAP', 30000),
        'opponent_temperature' => env('OPENROUTER_OPPONENT_TEMPERATURE', 0.7),
        'opponent_max_tokens' => env('OPENROUTER_OPPONENT_MAX_TOKENS', 25000),
        'opponent_reasoning_enabled' => env('OPENROUTER_OPPONENT_REASONING_ENABLED'),
        'evaluation_temperature' => env('OPENROUTER_EVALUATION_TEMPERATURE', 0.2),
        'evaluation_max_tokens' => env('OPENROUTER_EVALUATION_MAX_TOKENS', 30000),
        'evaluation_reasoning_enabled' => env('OPENROUTER_EVALUATION_REASONING_ENABLED'),
        'reasoning_enabled' => env('OPENROUTER_REASONING_ENABLED', true),
        'log_reasoning' => env('OPENROUTER_LOG_REASONING', false),
        'evaluation_use_structured_output' => env('OPENROUTER_EVALUATION_USE_STRUCTURED_OUTPUT', true),
        'history_limit' => env('OPENROUTER_HISTORY_LIMIT', 60),
        'free_format_history_limit' => env('OPENROUTER_FREE_FORMAT_HISTORY_LIMIT', 30),
        'evaluation_repair_max_tokens' => env('OPENROUTER_EVALUATION_REPAIR_MAX_TOKENS', 1200),
    ],
];
