<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Debate;
use App\Models\Room;
use Symfony\Component\HttpFoundation\Response;

class ValidateDebateAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', __('flash.auth.login_required'));
        }

        // ルートパラメータからdebateを取得
        $debate = $request->route('debate');

        if (!$debate instanceof Debate) {
            return redirect()->route('rooms.index')->with('error', __('flash.debate.not_found'));
        }

        // ユーザーがこのディベートの参加者かチェック
        if ($debate->affirmative_user_id !== $user->id && $debate->negative_user_id !== $user->id) {
            return redirect()->route('rooms.index')->with('error', __('flash.debate.access_denied'));
        }

        // ルームの状態をチェック
        $room = $debate->room;

        // ソフトデリートされたルームの場合
        if (!$room || $room->trashed()) {
            return redirect()->route('rooms.index')->with('error', __('flash.room.not_found'));
        }

        // 削除されたルームの場合
        if ($room->status === Room::STATUS_DELETED) {
            return redirect()->route('rooms.index')->with('error', __('flash.room.deleted'));
        }

        // 終了されたルームの場合（アクセス先に応じて分岐）
        if ($room->status === Room::STATUS_TERMINATED) {
            if ($request->routeIs('debate.show')) {
                return redirect()->route('welcome')->with('error', __('flash.debate.show.terminated'));
            }
            // result画面は終了後でもアクセス可能
        }

        // ディベート進行中以外の状態で debate.show にアクセスしようとした場合
        if ($request->routeIs('debate.show') && $room->status !== Room::STATUS_DEBATING) {
            if ($room->status === Room::STATUS_FINISHED && $debate->debateEvaluation) {
                return redirect()->route('debate.result', $debate)->with('info', __('flash.debate.show.finished'));
            }
            return redirect()->route('rooms.index')->with('error', __('flash.debate.invalid_state'));
        }

        return $next($request);
    }
}
