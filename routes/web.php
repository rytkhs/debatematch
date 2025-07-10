<?php

use App\Http\Controllers\RoomController;
use App\Http\Controllers\DebateController;
use App\Http\Controllers\DebateRecordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PusherWebhookController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use App\Http\Middleware\CheckUserActiveStatus;
use App\Http\Controllers\HeartbeatController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\Admin\ConnectionAnalyticsController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\AIDebateController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Broadcast;

// 基本ルート
Route::middleware([CheckUserActiveStatus::class])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('welcome');
    Route::get('/terms', function () {
        return view('terms');
    })->name('terms');

    Route::get('/privacy', function () {
        return view('privacy');
    })->name('privacy');

    Route::get('/guide', function () {
        return view('guide');
    })->name('guide');

    // お問い合わせページ
    Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
    
    // サイトマップ
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
});

Route::get('/dashboard', function () {
    return view('welcome');
})->middleware(['auth', 'verified'])->name('dashboard');

// 認証不要なルーム関連ルート
Route::middleware([CheckUserActiveStatus::class])->group(function () {
    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/', [RoomController::class, 'index'])->name('index');
        Route::get('/{room}/preview', [RoomController::class, 'preview'])->name('preview');
    });
});

Route::middleware(['auth', CheckUserActiveStatus::class])->group(function () {
    // プロフィール関連
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // 履歴関連ルート
    Route::prefix('records')->name('records.')->group(function () {
        Route::get('/', [DebateRecordController::class, 'index'])->name('index');
        Route::get('/{debate}', [DebateRecordController::class, 'show'])->name('show');
    });
});

// 認証が必要なルートグループ
Route::middleware(['auth', 'verified', CheckUserActiveStatus::class])->group(function () {

    // ルーム関連
    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::post('/', [RoomController::class, 'store'])->name('store');
        Route::get('/create', [RoomController::class, 'create'])->name('create');
        Route::get('/{room}', [RoomController::class, 'show'])->name('show');
    });

    // ディベート関連
    Route::prefix('debate')->name('debate.')->group(function () {
        Route::get('/{debate}', [DebateController::class, 'show'])->name('show');
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    Broadcast::routes();

    Route::post('/{room}/exit', [RoomController::class, 'exit'])->name('rooms.exit');
    Route::post('/{room}/join', [RoomController::class, 'join'])->name('rooms.join');
    Route::post('/{room}/start', [RoomController::class, 'startDebate'])->name('rooms.start');
    Route::post('/{debate}/exit', [DebateController::class, 'exit'])->name('debate.exit');
    Route::post('/{debate}/terminate', [DebateController::class, 'terminate'])->name('debate.terminate');



    // AIディベート退出ルート
    Route::post('/ai/debate/{debate}/exit', [AIDebateController::class, 'exit'])->name('ai.debate.exit');

    Route::prefix('debate')->name('debate.')->group(function () {
        Route::get('/{debate}/result', [DebateController::class, 'result'])->name('result');
    });
});

// AIディベートルート
Route::middleware(['auth', 'verified', CheckUserActiveStatus::class])->prefix('ai/debate')->name('ai.debate.')->group(function () {
    Route::get('/create', [AIDebateController::class, 'create'])->name('create');
    Route::post('/', [AIDebateController::class, 'store'])->name('store');
});

// pusher関連
Route::post('/webhook/pusher', [PusherWebhookController::class, 'handle'])->withoutMiddleware([ValidateCsrfToken::class]);


// ハートビートエンドポイント
Route::post('/api/heartbeat', [HeartbeatController::class, 'store'])
    ->middleware(['auth', 'verified', 'throttle:60,1']);

// 管理者用ルート
Route::middleware(['auth', 'verified', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    // 接続分析関連
    Route::prefix('connection')->name('connection.')->group(function () {
        Route::get('/analytics', [ConnectionAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/user/{user}', [ConnectionAnalyticsController::class, 'userDetail'])->name('user-detail');
    });

    // お問い合わせ管理
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [AdminContactController::class, 'index'])->name('index');
        Route::get('/{contact}', [AdminContactController::class, 'show'])->name('show');
        Route::patch('/{contact}/status', [AdminContactController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{contact}', [AdminContactController::class, 'destroy'])->name('destroy');
    });
});

// 言語切り替えルート
Route::get('language/{locale}', [LocaleController::class, 'changeLocale'])->name('language.switch');

Route::get('/auth/google', [GoogleLoginController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('auth.google.callback');

require __DIR__ . '/auth.php';
