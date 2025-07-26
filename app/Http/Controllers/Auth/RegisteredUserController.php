<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\OtpServiceInterface;
use App\Http\Controllers\Controller;
use App\Services\SlackNotifier;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    public function __construct(
        private SlackNotifier $slackNotifier,
        private OtpServiceInterface $otpService
    ) {
        //
    }

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // OTPを生成して送信
        try {
            $this->otpService->sendOtp($user);
            Log::info('ユーザー登録後にOTPを送信しました', ['email' => $user->email]);
        } catch (\Exception $e) {
            Log::error('ユーザー登録後のOTP送信に失敗しました', [
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }

        // 新規ユーザー登録通知を送信
        $message = "新規ユーザー登録がありました。\n"
            . "名前: {$user->name}\n";

        $result = $this->slackNotifier->send($message);

        if ($result) {
            Log::info("通知を送信しました。 User ID: {$user->id}");
        } else {
            Log::warning("通知の送信に失敗しました。 User ID: {$user->id}");
        }

        return redirect(route('verification.notice', absolute: false));
    }
}
