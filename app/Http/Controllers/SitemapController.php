<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

class SitemapController extends Controller
{
    /**
     * サイトマップXMLを生成して返す
     */
    public function index(): Response
    {
        // 静的ページのURL設定
        $staticPages = [
            [
                'url' => route('welcome'),
                'lastmod' => Carbon::now()->toISOString(),
                'changefreq' => 'weekly',
                'priority' => '1.0'
            ],
            [
                'url' => route('guide'),
                'lastmod' => Carbon::now()->toISOString(),
                'changefreq' => 'monthly',
                'priority' => '0.8'
            ],
            [
                'url' => route('terms'),
                'lastmod' => Carbon::now()->toISOString(),
                'changefreq' => 'yearly',
                'priority' => '0.3'
            ],
            [
                'url' => route('privacy'),
                'lastmod' => Carbon::now()->toISOString(),
                'changefreq' => 'yearly',
                'priority' => '0.3'
            ],
            [
                'url' => route('contact.index'),
                'lastmod' => Carbon::now()->toISOString(),
                'changefreq' => 'monthly',
                'priority' => '0.5'
            ],
            [
                'url' => route('rooms.index'),
                'lastmod' => Carbon::now()->toISOString(),
                'changefreq' => 'hourly',
                'priority' => '0.9'
            ]
        ];

        // 動的ページ（ルーム）の取得
        $rooms = Room::whereNotIn('status', ['deleted', 'terminated'])
                     ->orderBy('updated_at', 'desc')
                     ->get();

        $roomPages = [];
        foreach ($rooms as $room) {
            $roomPages[] = [
                'url' => route('rooms.preview', $room->id),
                'lastmod' => $room->updated_at->toISOString(),
                'changefreq' => 'daily',
                'priority' => '0.7'
            ];
        }

        $allPages = array_merge($staticPages, $roomPages);

        return response()
            ->view('sitemap', compact('allPages'))
            ->header('Content-Type', 'text/xml');
    }
}