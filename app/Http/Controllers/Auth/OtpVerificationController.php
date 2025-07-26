<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\OtpServiceInterface;
use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpRateLimitException;
use App\Exceptions\OtpValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OtpVerificationRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class OtpVerificationController extends Controller
{
    public function __construct(
        private OtpServiceInterface $otpService
    ) {}

    /**
     * OTPコードを検証し、メールアドレスを認証済みにする
     */
    public function __invoke(OtpVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        // ユーザーが既に認証済みの場合、ダッシュボードにリダイレクトする
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('welcome', absolute: false) . '?verified=1');
        }

        try {
            // OTPを検証する
            $isValid = $this->otpService->verify($user->email, $request->validated('otp'));

            if (!$isValid) {
                // 失敗回数をインクリメントする
                $failureCount = $this->otpService->incrementFailureCount($user->email);

                Log::info('OTP検証に失敗しました', [
                    'email' => $user->email,
                    'failure_count' => $failureCount,
                ]);

                // 失敗回数が5回に達した場合、OTPを無効化する
                if ($failureCount >= 5) {
                    $this->otpService->invalidate($user->email);

                    return back()->withErrors([
                        'otp' => __('auth.otp_too_many_failures')
                    ])->withInput();
                }

                return back()->withErrors([
                    'otp' => __('auth.otp_invalid')
                ])->withInput();
            }

            // OTPが有効な場合 - メールアドレスを認証済みにする
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            // 認証成功後、OTPを無効化する
            $this->otpService->invalidate($user->email);

            Log::info('OTP検証に成功しました', [
                'email' => $user->email,
            ]);

            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1')
                ->with('status', 'email-verified');
        } catch (OtpExpiredException $e) {
            Log::info('検証中にOTPの有効期限が切れました', [
                'email' => $user->email,
            ]);

            return back()->withErrors([
                'otp' => __('auth.otp_expired')
            ])->withInput();
        } catch (OtpValidationException $e) {
            Log::warning('OTP検証例外', [
                'email' => $user->email,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'otp' => __('auth.otp_invalid')
            ])->withInput();
        } catch (OtpRateLimitException $e) {
            Log::warning('検証中にOTPレート制限を超過しました', [
                'email' => $user->email,
            ]);

            $remainingTime = $this->otpService->getRateLimitRemainingTime($user->email);
            $minutes = ceil($remainingTime / 60);

            return back()->withErrors([
                'otp' => __('auth.otp_rate_limited', ['minutes' => $minutes])
            ])->withInput();
        } catch (\Exception $e) {
            Log::error('OTP検証中に予期せぬエラーが発生しました', [
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'otp' => __('auth.otp_verification_error')
            ])->withInput();
        }
    }
}
