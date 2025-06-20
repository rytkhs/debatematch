<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Config;

class LocaleController extends Controller
{
    /**
     * Change the application locale and redirect back.
     *
     * @param  string  $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeLocale($locale)
    {
        // サポートされている言語かチェック (config/app.php の supported_locales を参照)
        $supportedLocales = Config::get('app.supported_locales', ['ja', 'en']);

        if (in_array($locale, $supportedLocales)) {
            // セッションに選択された言語を保存
            Session::put('locale', $locale);
            // アプリケーションのロケールも即時反映（リダイレクト後の表示のため）
            App::setLocale($locale);
        } else {
        }

        return Redirect::back();
    }
}
