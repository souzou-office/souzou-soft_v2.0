<?php

use App\Console\Commands\SendDailyTaskNotifications;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // 付録B.3: 毎朝7時に期日関連通知を一括送信
        $schedule->command(SendDailyTaskNotifications::class)
            ->dailyAt(env('NOTIFY_DAILY_RUN_HOUR', 7) . ':00')
            ->timezone('Asia/Tokyo');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
