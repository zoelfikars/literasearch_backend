<?php

use App\Http\Middleware\CheckRevokedToken;
use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;



return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->alias([
            'check.revoked' => CheckRevokedToken::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,

        ]);
    })
    ->withEvents([
        __DIR__ . '/../app/Listeners/*',
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        $tokenInvalid = 'Token tidak valid atau kadaluarsa';
        $serverErrorMessage = 'Terjadi kesalahan pada server';
        $exceptions->render(
            fn(ValidationException $e)
            => ApiResponse::setErrorResponse($e->errors(), 422)
        );
        $exceptions->render(
            fn(HttpResponseException $e)
            => ApiResponse::setErrorResponse($e->getMessage(), 400)
        );
        $exceptions->render(
            fn(HttpExceptionInterface $e)
            => ApiResponse::setErrorResponse($e->getMessage(), $e->getStatusCode())
        );
        $exceptions->render(
            fn(AuthenticationException $e)
            => ApiResponse::setErrorResponse($tokenInvalid, 401, $e->getMessage())
        );
        $exceptions->render(
            // delete system_message later
            fn(Throwable $e)
            => ApiResponse::setErrorResponse($serverErrorMessage, 500, $e->getMessage())
        );
    })->create();
