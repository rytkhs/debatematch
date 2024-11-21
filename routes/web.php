<?php

use App\Http\Controllers\RoomController;
use App\Http\Controllers\DebateController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// use App\Http\Controllers\ChatController;
// use App\Events\ChatEvent;
// use Illuminate\Http\Request;
// use App\Http\Controllers\MatchingController;


Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');



// ルーム一覧ページ
Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');





Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    // ルーム作成ページ
    Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
});


Route::middleware('auth')->group(function () {
Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
Route::post('/rooms/{room}/startDebate', [RoomController::class, 'startDebate'])->name('rooms.startDebate');
Route::post('/rooms/{room}/exitRoom', [RoomController::class, 'exitRoom'])->name('rooms.exitRoom');
Route::post('/rooms/{room}/joinRoom', [RoomController::class, 'joinRoom'])->name('rooms.joinRoom');
Route::get('/debate/{debate}', [DebateController::class, 'show'])->name('debate.show');
});



// Route::get('/', [ChatController::class, 'index']);

// Route::post('/', function (Request $request) {
//     ChatEvent::dispatch($request->message);
// });

// Route::post('/matching', [MatchingController::class, 'startMatch'])->name('match.start');
// Route::get('/waiting', function () {
//     return view('waiting');
// });


require __DIR__ . '/auth.php';
