<?php

use App\Exceptions\ExceptionRegistrar;
use App\Http\Middleware\CheckAiQuestionPermission;
use App\Http\Middleware\CheckImportPermission;
use App\Http\Middleware\CheckPermission;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => CheckPermission::class,
            'import.permission' => CheckImportPermission::class,
            'ai.question.permission' => CheckAiQuestionPermission::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        app(ExceptionRegistrar::class)->handle($exceptions);
    })
    ->create();
