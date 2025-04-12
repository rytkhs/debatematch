<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = null;
        // デフォルト値として ['ja', 'en'] を設定
        $supportedLocales = Config::get('app.supported_locales', ['ja', 'en']);

        // 1. ユーザーが手動で言語を選択した場合 (セッションに保存されているか確認)
        if (Session::has('locale') && in_array(Session::get('locale'), $supportedLocales)) {
            $locale = Session::get('locale');
        }
        // 2. 手動選択がない場合、ブラウザの言語設定を確認 (Accept-Language ヘッダー)
        else {
            // リクエストヘッダーから、サポートしている言語のうち最も優先度の高いものを取得
            $browserLocale = $request->getPreferredLanguage($supportedLocales);
            if ($browserLocale) {
                $locale = $browserLocale; // ブラウザの言語を優先
                Session::put('locale', $locale); // 今後のリクエストのためにセッションにも保存
            }
        }

        // 3. 上記で見つかった、またはデフォルトのロケールをアプリケーションに設定
        // $locale が有効な値であればそれを使い、そうでなければ config/app.php のデフォルト ('fallback_locale' または 'locale') を使う
        if ($locale) {
            App::setLocale($locale);
        } else {
            // デフォルトロケール（config/app.php の locale）を使用
            $defaultLocale = Config::get('app.locale');
            App::setLocale($defaultLocale);
            // デフォルトロケールをセッションに保存（ブラウザ設定もなかった場合）
            Session::put('locale', $defaultLocale);
        }

        return $next($request);
    }
}
