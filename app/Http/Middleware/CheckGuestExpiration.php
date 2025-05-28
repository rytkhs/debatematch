<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckGuestExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // ゲストユーザーで期限が切れている場合
            if ($user->isGuest() && $user->isGuestExpired()) {
                Auth::logout();

                return redirect()->route('login')->with('message', 'ゲストセッションの有効期限が切れました。');
            }
        }

        return $next($request);
    }
}
