<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleLoginController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $googleEmail = $googleUser->getEmail();
            $googleName = $googleUser->getName();
            $googleId = $googleUser->getId();

            $user = User::where('email', $googleEmail)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleName,
                    'email' => $googleEmail,
                    'google_id' => $googleId,
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->update([
                    'google_id' => $googleId,
                ]);
            }

            Auth::login($user, true);

            return redirect()->intended('/')->with('success', __('flash.auth.login.success'));
        } catch (Exception $e) {
            Log::error('Google login error', [
                'exception' => get_class($e),
                'message' => $e->getMessage() ?: 'No error message provided',
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_id' => request()->header('X-Request-ID'),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return redirect('login')
                ->withErrors(['error' => __('auth.google_login_failed')]);
        }
    }
}
