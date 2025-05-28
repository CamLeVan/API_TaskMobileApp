<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Đăng ký middleware alias
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // Cấu hình redirect cho guest users
        $middleware->redirectGuestsTo(function () {
            if (request()->is('admin') || request()->is('admin/*')) {
                return route('admin.login');
            }
            return '/'; // Redirect về trang chủ cho các route khác
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Xử lý ngoại lệ ở đây
    })->create();
