<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SMSController;
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
    protected SMSController $smsController;

    public function __construct(SMSController $smsController)
    {
        $this->smsController = $smsController;
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

        $adminPhoneNumber = env('SMS_ADMIN_PHONE_NUMBER');

        if ($adminPhoneNumber) {
            $message = "新規ユーザー登録がありました。\n名前: {$user->name}\nメール: {$user->email}";

            $result = $this->smsController->sendSms($adminPhoneNumber, $message);

            if ($result) {
                Log::info("SMSを送信しました。 User ID: {$user->id}");
            } else {
                Log::warning("送信に失敗しました。 User ID: {$user->id}");
            }
        } else {
            Log::warning('SMS_ADMIN_PHONE_NUMBERが設定されていません。');
        }

        Auth::login($user);

        return redirect(route('verification.notice', absolute: false));
    }
}
