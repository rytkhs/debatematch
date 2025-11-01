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

            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->update([
                    'google_id' => $googleUser->id,
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
