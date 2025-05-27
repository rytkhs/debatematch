<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * 管理者権限を持つユーザーのみアクセスを許可する
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ユーザーが存在しない、管理者でない、またはゲストユーザーの場合はアクセス拒否
        if (!$user || !$user->isAdmin() || $user->isGuest()) {
            return redirect()->route('welcome');
        }

        return $next($request);
    }
}
