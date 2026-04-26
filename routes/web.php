<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MatterController;
use App\Http\Controllers\MatrixController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');

    // 4.1 マイビュー（自分専用画面）
    Route::get('/my', [UserDashboardController::class, 'me'])->name('my');

    // 4.2 チームビュー（所長向け俯瞰）
    Route::get('/team', [TeamController::class, 'index'])->name('team');

    // 4.3 個別担当者ビュー
    Route::get('/users/{user}', [UserDashboardController::class, 'show'])->name('users.show');

    // 3.3 進捗マトリクス
    Route::get('/matrix', [MatrixController::class, 'index'])->name('matrix');

    // 事件
    Route::resource('matters', MatterController::class)->parameters(['matters' => 'matter']);
    Route::get('/matters/{matter}/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');

    // タスク操作（インライン編集を想定したJSONレスポンス）
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::post('/tasks/{task}/return', [TaskController::class, 'returnTask'])->name('tasks.return');
    Route::post('/tasks/{task}/comments', [TaskController::class, 'addComment'])->name('tasks.comments.store');

    // 6章 書類生成
    Route::get('/matters/{matter}/documents/{template}/form', [DocumentController::class, 'form'])->name('documents.form');
    Route::post('/matters/{matter}/documents/{template}/generate', [DocumentController::class, 'generate'])->name('documents.generate');

    // 通知
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
});

require __DIR__ . '/auth.php';
