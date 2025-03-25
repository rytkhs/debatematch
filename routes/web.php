<?php

use App\Http\Controllers\RoomController;
use App\Http\Controllers\DebateController;
use App\Http\Controllers\DebateRecordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PusherWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Pusher\Pusher;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

// 基本ルート
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 認証不要なルーム関連ルート
Route::prefix('rooms')->name('rooms.')->group(function () {
    Route::get('/', [RoomController::class, 'index'])->name('index');
    Route::get('/create', [RoomController::class, 'create'])->name('create');
});

// 認証が必要なルートグループ
Route::middleware('auth')->group(function () {
    // プロフィール関連
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // ルーム関連
    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::post('/', [RoomController::class, 'store'])->name('store');
        Route::get('/{room}', [RoomController::class, 'show'])->name('show');
        Route::get('/{room}/preview', [RoomController::class, 'preview'])->name('preview');
        Route::post('/{room}/exit', [RoomController::class, 'exit'])->name('exit');
        Route::post('/{room}/join', [RoomController::class, 'join'])->name('join');
    });

    // ディベート関連
    Route::prefix('debate')->name('debate.')->group(function () {
        Route::post('/rooms/{room}/start', [DebateController::class, 'start'])->name('start');
        Route::get('/{debate}', [DebateController::class, 'show'])->name('show');
        Route::get('/{debate}/result', [DebateController::class, 'result'])->name('result');
    });

    // 履歴関連ルート
    Route::prefix('records')->name('records.')->group(function () {
        Route::get('/', [DebateRecordController::class, 'index'])->name('index');
        Route::get('/{debate}', [DebateRecordController::class, 'show'])->name('show');
    });
});

// pusher関連
Route::post('/webhook/pusher', [PusherWebhookController::class, 'handle'])->withoutMiddleware([ValidateCsrfToken::class]);


Route::post('/pusher/auth', function (Request $request) {
    $pusher = new Pusher(
        config('broadcasting.connections.pusher.key'),
        config('broadcasting.connections.pusher.secret'),
        config('broadcasting.connections.pusher.app_id'),
        config('broadcasting.connections.pusher.options')
    );

    return $pusher->presence_auth(
        $request->input('channel_name'),
        $request->input('socket_id'),
        auth()->user()->id, // ユーザーIDを渡す
        ['user_info' => ['name' => auth()->user()->name]]
    );
})->middleware('auth')->withoutMiddleware([ValidateCsrfToken::class]);

require __DIR__ . '/auth.php';
