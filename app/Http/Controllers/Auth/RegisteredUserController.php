<?php

namespace App\Http\Controllers\Auth;

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
    public function __construct(private SlackNotifier $slackNotifier)
    {
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

        // 新規ユーザー登録通知を送信
        $message = "新規ユーザー登録がありました。\n"
            . "名前: {$user->name}\n";

        // 通知を送信（メール）
        // $result = $this->snsController->sendNotification(
        //     $message,
        //     "【DebateMatch】新規ユーザー登録"
        // );
        $result = $this->slackNotifier->send($message);

        if ($result) {
            Log::info("通知を送信しました。 User ID: {$user->id}");
        } else {
            Log::warning("通知の送信に失敗しました。 User ID: {$user->id}");
        }

        Auth::login($user);

        return redirect(route('verification.notice', absolute: false));
    }
}
