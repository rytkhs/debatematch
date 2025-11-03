<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

class SitemapController extends Controller
{
    /**
     * 1サイトマップあたりの最大URL数
     */
    private const MAX_URLS_PER_SITEMAP = 49000;

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
        if (file_exists($viewPath)) {
            return Carbon::createFromTimestamp(filemtime($viewPath))->toISOString();
        }

        // デフォルト値（アプリケーション起動時刻）
        return Carbon::now()->toISOString();
    }

    /**
     * 静的サイトマップ全体の最新更新時刻を取得
     *
     * @return string ISO 8601形式の日時文字列
     */
    private function getStaticSitemapLastModified(): string
    {
        $pages = ['welcome', 'guide', 'terms', 'privacy', 'tokushoho', 'contact'];
        $maxTimestamp = 0;

        foreach ($pages as $page) {
            $locale = app()->getLocale();
            $fallbackLocale = config('app.fallback_locale', 'en');

            // Markdownファイルをチェック
            $markdownPath = resource_path("markdown/{$locale}/{$page}.md");
            $fallbackPath = resource_path("markdown/{$fallbackLocale}/{$page}.md");

            if (file_exists($markdownPath)) {
                $maxTimestamp = max($maxTimestamp, filemtime($markdownPath));
            } elseif (file_exists($fallbackPath)) {
                $maxTimestamp = max($maxTimestamp, filemtime($fallbackPath));
            }

            // Bladeテンプレートをチェック
            $viewPath = resource_path("views/{$page}.blade.php");
            if ($page === 'contact') {
                $viewPath = resource_path("views/contact/index.blade.php");
            }
            if (file_exists($viewPath)) {
                $maxTimestamp = max($maxTimestamp, filemtime($viewPath));
            }
        }

        // rooms.indexは動的ページなので、最新ルーム更新時刻を取得
        $latestRoom = Room::whereNotIn('status', ['deleted', 'terminated', 'finished'])
            ->whereIn('status', ['waiting', 'ready', 'debating'])
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($latestRoom && $latestRoom->updated_at) {
            $maxTimestamp = max($maxTimestamp, $latestRoom->updated_at->timestamp);
        }

        return $maxTimestamp > 0
            ? Carbon::createFromTimestamp($maxTimestamp)->toISOString()
            : Carbon::now()->toISOString();
    }

    /**
     * ルームサイトマップの最新更新時刻を取得
     *
     * @param int $page ページ番号
     * @return string ISO 8601形式の日時文字列
     */
    private function getRoomsSitemapLastModified(int $page): string
    {
        $offset = ($page - 1) * self::MAX_URLS_PER_SITEMAP;

        $latestRoom = Room::whereNotIn('status', ['deleted', 'terminated', 'finished'])
            ->whereIn('status', ['waiting', 'ready', 'debating'])
            ->orderBy('updated_at', 'desc')
            ->skip($offset)
            ->take(self::MAX_URLS_PER_SITEMAP)
            ->first();

        return $latestRoom && $latestRoom->updated_at
            ? $latestRoom->updated_at->toISOString()
            : Carbon::now()->toISOString();
    }

    /**
     * サイトマップインデックスXMLを生成して返す
     */
    public function index(): Response
    {
        $sitemaps = [];

        // 静的ページ用サイトマップ
        $sitemaps[] = [
            'loc' => route('sitemap.static'),
            'lastmod' => $this->getStaticSitemapLastModified(),
        ];

        // ルーム用サイトマップの数を計算
        $activeRoomsCount = Room::whereNotIn('status', ['deleted', 'terminated', 'finished'])
            ->whereIn('status', ['waiting', 'ready', 'debating'])
            ->count();

        $totalPages = (int) ceil($activeRoomsCount / self::MAX_URLS_PER_SITEMAP);

        // ルーム用サイトマップ（複数）を追加
        for ($page = 1; $page <= $totalPages; $page++) {
            $sitemaps[] = [
                'loc' => route('sitemap.rooms', ['page' => $page]),
                'lastmod' => $this->getRoomsSitemapLastModified($page),
            ];
        }

        return response()
            ->view('sitemap-index', compact('sitemaps'))
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * 静的ページ用サイトマップXMLを生成して返す
     */
    public function staticSitemap(): Response
    {
        // rooms.indexの最新更新時刻を取得（最新ルーム更新時刻）
        $latestRoom = Room::whereNotIn('status', ['deleted', 'terminated', 'finished'])
            ->whereIn('status', ['waiting', 'ready', 'debating'])
            ->orderBy('updated_at', 'desc')
            ->first();

        $roomsIndexLastMod = $latestRoom && $latestRoom->updated_at
            ? $latestRoom->updated_at->toISOString()
            : Carbon::now()->toISOString();

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
                'url' => route('rooms.index'),
                'lastmod' => $roomsIndexLastMod,
            ]
        ];

        return response()
            ->view('sitemap', ['allPages' => $staticPages])
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * ルーム用サイトマップXMLを生成して返す（チャンク処理）
     *
     * @param int $page ページ番号（1から開始）
     */
    public function roomsSitemap(int $page = 1): Response
    {
        // ページ番号の検証
        if ($page < 1) {
            abort(404);
        }

        // アクティブなルームのみを取得（finished状態も除外）
        $query = Room::whereNotIn('status', ['deleted', 'terminated', 'finished'])
            ->whereIn('status', ['waiting', 'ready', 'debating'])
            ->orderBy('updated_at', 'desc');

        // オフセット計算
        $offset = ($page - 1) * self::MAX_URLS_PER_SITEMAP;

        // チャンク取得
        $rooms = $query->skip($offset)
            ->take(self::MAX_URLS_PER_SITEMAP)
            ->get();

        // データが存在しない場合（ページが範囲外）
        if ($rooms->isEmpty() && $page > 1) {
            abort(404);
        }

        $roomPages = [];
        foreach ($rooms as $room) {
            $roomPages[] = [
                'url' => route('rooms.preview', $room->id),
                'lastmod' => $room->updated_at->toISOString(),
            ];
        }

        return response()
            ->view('sitemap', ['allPages' => $roomPages])
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
