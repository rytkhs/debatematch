<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

class SitemapController extends Controller
{
    /**
     * 静的ページの更新時刻を取得
     *
     * @param string $page ページ識別子
     * @return string ISO 8601形式の日時文字列
     */
    private function getStaticPageLastModified(string $page): string
    {
        $locale = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');

        // Markdownファイルのパスを生成
        $markdownPath = resource_path("markdown/{$locale}/{$page}.md");
        $fallbackPath = resource_path("markdown/{$fallbackLocale}/{$page}.md");

        // Markdownファイルの更新時刻を取得
        if (file_exists($markdownPath)) {
            return Carbon::createFromTimestamp(filemtime($markdownPath))->toISOString();
        } elseif (file_exists($fallbackPath)) {
            return Carbon::createFromTimestamp(filemtime($fallbackPath))->toISOString();
        }

        // Bladeテンプレートの更新時刻を取得
        $viewPath = resource_path("views/{$page}.blade.php");
        if ($page === 'contact') {
            $viewPath = resource_path("views/contact/index.blade.php");
        }
        if ($page === 'issues') {
            $viewPath = resource_path("views/issues/index.blade.php");
        }
        if (file_exists($viewPath)) {
            return Carbon::createFromTimestamp(filemtime($viewPath))->toISOString();
        }

        // デフォルト値（アプリケーション起動時刻）
        return Carbon::now()->toISOString();
    }

    /**
     * 静的ページ用サイトマップXMLを生成して返す
     */
    public function staticSitemap(): Response
    {
        $staticPages = [
            [
                'url' => route('welcome'),
                'lastmod' => $this->getStaticPageLastModified('welcome'),
            ],
            [
                'url' => route('guide'),
                'lastmod' => $this->getStaticPageLastModified('guide'),
            ],
            [
                'url' => route('terms'),
                'lastmod' => $this->getStaticPageLastModified('terms'),
            ],
            [
                'url' => route('privacy'),
                'lastmod' => $this->getStaticPageLastModified('privacy'),
            ],
            [
                'url' => route('tokushoho'),
                'lastmod' => $this->getStaticPageLastModified('tokushoho'),
            ],
            [
                'url' => route('contact.index'),
                'lastmod' => $this->getStaticPageLastModified('contact'),
            ],
            [
                'url' => route('issues.index'),
                'lastmod' => $this->getStaticPageLastModified('issues'),
            ],
        ];

        return response()
            ->view('sitemap', ['allPages' => $staticPages])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

}
