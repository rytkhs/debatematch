<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\OtpServiceInterface;
use App\Exceptions\OtpRateLimitException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtpResendController extends Controller
{
    public function __construct(
        private OtpServiceInterface $otpService
    ) {}

    /**
     * OTP確認コードを再送信する
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // ユーザーが既に認証済みの場合、ダッシュボードにリダイレクトする
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('welcome', absolute: false));
        }

        try {
            // レート制限をチェック
            if ($this->otpService->isRateLimited($user->email)) {
                $remainingTime = $this->otpService->getRateLimitRemainingTime($user->email);
                $minutes = ceil($remainingTime / 60);

                Log::info('OTP再送信がレート制限によりブロックされました', [
                    'email' => $user->email,
                    'remaining_time' => $remainingTime,
                ]);

                return back()->withErrors([
                    'resend' => __('auth.otp_rate_limited', ['minutes' => $minutes])
                ]);
            }

            // 新しいOTPを生成する前に既存のOTPを無効化する
            $this->otpService->invalidate($user->email);

            // 新しいOTPを送信する
            $this->otpService->sendOtp($user);

            Log::info('OTPが正常に再送信されました', [
                'email' => $user->email,
            ]);

            return back()->with('status', 'otp-resent');
        } catch (OtpRateLimitException $e) {
            Log::warning('OTP再送信のレート制限を超過しました', [
                'email' => $user->email,
            ]);

            $remainingTime = $this->otpService->getRateLimitRemainingTime($user->email);
            $minutes = ceil($remainingTime / 60);

            return back()->withErrors([
                'resend' => __('auth.otp_rate_limited', ['minutes' => $minutes])
            ]);
        } catch (\Exception $e) {
            Log::error('OTP再送信中に予期せぬエラーが発生しました', [
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'resend' => __('auth.otp_resend_error')
            ]);
        }
    }
}
