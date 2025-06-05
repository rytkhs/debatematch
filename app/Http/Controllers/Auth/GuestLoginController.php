<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SlackNotifier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GuestLoginController extends Controller
{
    protected SlackNotifier $slackNotifier;

    public function __construct(SlackNotifier $slackNotifier)
    {
        $this->slackNotifier = $slackNotifier;
    }

    /**
     * ゲストログインを実行
     */
    public function login(Request $request)
    {
        // ゲストユーザーを作成
        $guestUser = User::create([
            'name' => 'Guest_' . random_int(10000000, 99999999),
            'email' => null,
            'password' => null,
            'is_guest' => true,
            'guest_expires_at' => Carbon::now()->addMinutes(120), // セッション期限と同じ120分（2時間）
            'email_verified_at' => now(),
        ]);

        Auth::login($guestUser);

        $message = "ゲストユーザーがログインしました。\n"
            . "ユーザー名: {$guestUser->name}\n"
            . "有効期限: {$guestUser->guest_expires_at->format('Y-m-d H:i:s')}";

        $result = $this->slackNotifier->send($message);

        if ($result) {
            Log::info("ゲストログイン通知を送信しました。 User ID: {$guestUser->id}");
        } else {
            Log::warning("ゲストログイン通知の送信に失敗しました。 User ID: {$guestUser->id}");
        }

        return redirect()->intended(route('welcome'))->with('success', __('flash.auth.guest_login.success'));
    }
}
