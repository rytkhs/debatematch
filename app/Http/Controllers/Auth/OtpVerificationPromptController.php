<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OtpVerificationPromptController extends Controller
{
    /**
     * OTP検証プロンプトを表示する
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        // ユーザーが既に認証済みの場合、ダッシュボードにリダイレクトする
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // 表示のためにメールアドレスをマスクする
        $email = $request->user()->email;
        $maskedEmail = $this->maskEmail($email);

        return view('auth.verify-email', [
            'maskedEmail' => $maskedEmail,
            'otpExpiryMinutes' => 10, // OTPは10分で期限切れになる
        ]);
    }

    /**
     * セキュリティ表示のためにメールアドレスをマスクする
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1];

        // ユーザー名の最初の2文字と最後の1文字を表示する
        $usernameLength = strlen($username);
        if ($usernameLength <= 3) {
            $maskedUsername = str_repeat('*', $usernameLength);
        } else {
            $maskedUsername = substr($username, 0, 2) . str_repeat('*', $usernameLength - 3) . substr($username, -1);
        }

        // ドメインの最初の文字と最後の部分を表示する
        $domainParts = explode('.', $domain);
        $maskedDomain = substr($domainParts[0], 0, 1) . str_repeat('*', strlen($domainParts[0]) - 1);

        if (count($domainParts) > 1) {
            $maskedDomain .= '.' . end($domainParts);
        }

        return $maskedUsername . '@' . $maskedDomain;
    }
}
