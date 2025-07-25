<?php

return [
    'failed' => 'ログイン情報が存在しません。',
    'password' => '入力されたパスワードが正しくありません。',
    'throttle' => 'ログイン試行の規定数に達しました。:seconds秒後に再度お試しください。',
    'confirm_password_message' => '続けるにはパスワードを確認してください。',
    'forgot_password_message' => 'パスワードをお忘れですか？問題ありません。メールアドレスをお知らせいただければ、パスワードリセットリンクをメールでお送りします。',
    'reset_password' => 'パスワードをリセット',
    'verify_email_message' => '登録ありがとうございます！始める前に、メールでお送りしたリンクをクリックしてメールアドレスを確認していただけますか？メールが届かない場合は、再送いたします。',
    'verification_link_sent' => '新しい確認リンクが、登録時に提供されたメールアドレスに送信されました。',
    'verification_email_delay_notice' => '認証メールの到着には数分かかる場合があります。迷惑メールフォルダもご確認ください。',
    'resend_verification_email' => '確認メールを再送',
    'login_with_google' => 'Googleアカウントでログイン',
    'login_with_x' => 'Xアカウントでログイン',
    'agree_terms_privacy' => '利用規約とプライバシーポリシーに同意して続行します。',
    'create_account' => 'アカウント作成',
    'google_login_failed' => 'Googleログインに失敗しました。再度お試しください。',
    
    // OTP関連の翻訳
    'otp_error' => 'OTP操作に失敗しました。',
    'otp_expired' => '認証コードの有効期限が切れました。新しいコードをリクエストしてください。',
    'otp_rate_limited' => 'リクエストが制限に達しました。:minutes分後に再度お試しください。',
    'otp_invalid' => '認証コードが無効です。',
    'otp_max_failures_reached' => '失敗回数が制限に達しました。新しい認証コードをリクエストしてください。',
    
    // OTPメール通知
    'otp_verification_subject' => 'メールアドレス認証コード',
    'otp_verification_greeting' => 'こんにちは！',
    'otp_verification_message' => 'メールアドレスの認証には、以下の認証コードをご使用ください：',
    'otp_sent_to_email' => 'このコードは次のメールアドレスに送信されました: :email',
    'otp_code_display' => '認証コード: **:code**',
    'otp_expiry_message' => 'このコードは10分後に有効期限が切れます。',
    'otp_security_notice' => 'この認証コードをリクエストしていない場合は、このメールを無視してください。',
    'otp_security_reminder' => 'セキュリティのため、このコードを他の人と共有しないでください。',
    'otp_single_use' => 'このコードは一度のみ使用できます。',
    'otp_no_sharing' => '電話、メール、テキストでこのコードを共有しないでください。',
    'otp_email_footer' => 'このメールは:appからアカウント認証のために送信されました。',
    'otp_no_reply' => 'これは自動送信メールです。このメールには返信しないでください。',
];
