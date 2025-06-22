<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Error Pages Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various error pages
    | throughout the application.
    |
    */

    // Common actions
    'go_back_page' => '前のページに戻る',
    'back_to_home' => 'ホームに戻る',
    'find_debate_room' => 'ディベートルームを探す',
    'refresh_page' => 'ページを更新',

    // 401 Unauthorized
    'error_401_title' => '認証エラー (401)',
    'error_401_message' => 'このページにアクセスするには認証が必要です。',
    'error_401_action' => 'ログインしてから再度お試しください。',

    // 403 Forbidden
    'error_403_title' => 'アクセス権限エラー (403)',
    'error_403_message' => 'このリソースへのアクセス権限がありません。',
    'error_403_action' => '別のアカウントでログインするか、ホームページに戻ってください。',

    // 404 Not Found
    'error_404_title' => 'ページが見つかりません (404)',
    'error_404_message' => 'お探しのページは存在しないか、移動された可能性があります。',
    'error_404_action' => 'URLをご確認いただくか、以下のリンクからホームページへお戻りください。',

    // 419 Page Expired
    'error_419_title' => 'ページ有効期限切れ (419)',
    'error_419_message' => 'セッションの有効期限が切れました。操作を完了できませんでした。',
    'error_419_action' => 'ページを更新して、もう一度お試しください。',

    // 429 Too Many Requests
    'error_429_title' => 'リクエスト過多 (429)',
    'error_429_message' => '短時間のうちにリクエストが集中しました。',
    'error_429_action' => 'しばらく時間をおいてから再度お試しください。',

    // 500 Server Error
    'error_500_title' => 'サーバーエラー (500)',
    'error_500_message' => 'サーバー内部でエラーが発生しました。',
    'error_500_action' => '問題が解決しない場合は、管理者にお問い合わせください。',

    // 503 Service Unavailable
    'error_503_title' => 'サービス利用不可 (503)',
    'error_503_message' => '現在、サービスはメンテナンス中です。',
    'error_503_action' => 'しばらく時間をおいてから再度アクセスしてください。',
];
