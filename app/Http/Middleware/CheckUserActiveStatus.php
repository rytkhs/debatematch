<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Debate;
use App\Models\Room;

class CheckUserActiveStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 認証チェック
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // 除外ルート（このミドルウェアの処理を行わないルート）
        $excludedRoutes = [

        ];

        // 除外ルートにマッチする場合は処理を終了
        foreach ($excludedRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }

        // ディベート中かチェック
        $activeDebate = $this->getActiveDebate($user);
        if ($activeDebate) {
            // ディベートページへのアクセスなら処理を続行
            if ($request->is('debate/' . $activeDebate->id)) {
                return $next($request);
            }

            // ディベートページ以外へのアクセスはリダイレクト
            return redirect()->route('debate.show', $activeDebate->id)
                ->with('warning', __('flash.middleware.active_debate'));
        }

        // ルーム待機中かチェック
        $activeRoom = $this->getActiveRoom($user);
        if ($activeRoom) {
            // ルームページへのアクセスなら処理を続行
            if ($request->is('rooms/' . $activeRoom->id)) {
                return $next($request);
            }

            // ルームページ以外へのアクセスはリダイレクト
            return redirect()->route('rooms.show', $activeRoom->id)
                ->with('warning', __('flash.middleware.active_room'));
        }

        return $next($request);
    }

    /**
     * ユーザーが参加中のアクティブなディベートを取得する
     *
     * @param  \App\Models\User  $user
     * @return \App\Models\Debate|null
     */
    private function getActiveDebate($user)
    {
        // ユーザーが参加中のルームを取得
        $rooms = $user->rooms;

        // 各ルームについて、ディベート中かどうかを確認
        foreach ($rooms as $room) {
            if ($room->status === Room::STATUS_DEBATING && $room->debate) {
                return $room->debate; // ディベート中の場合はそのディベートを返す
            }
        }

        return null;
    }

    /**
     * ユーザーが参加中の待機中ルームを取得する
     *
     * @param  \App\Models\User  $user
     * @return \App\Models\Room|null
     */
    private function getActiveRoom($user)
    {
        // ユーザーが参加中のルームで、ステータスが「待機中」または「準備完了」のものを取得
        return $user->rooms()
            ->whereIn('rooms.status', [Room::STATUS_WAITING, Room::STATUS_READY])
            ->first();
    }
}
