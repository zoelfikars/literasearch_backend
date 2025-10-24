<?php
use App\Providers\AppServiceProvider;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        AppServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withEvents([
        __DIR__ . '/../app/Listeners/*',
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        $messages = [
            'tokenInvalid' => 'Token tidak valid atau kadaluarsa',
            'serverError' => 'Terjadi kesalahan pada server',
            'unauthorized' => 'Anda tidak memiliki hak akses',
            'notFound' => 'Data tidak ditemukan',
        ];
        $exceptions->render(function (Throwable $e) use ($messages) {
            switch (true) {
                case $e instanceof ValidationException:
                    return ApiResponse::setErrorResponse($e->errors(), 422);
                case $e instanceof AuthenticationException:
                    return ApiResponse::setErrorResponse($messages['tokenInvalid'] ?? 'Tidak terautentikasi.', 401);
                case $e instanceof AuthorizationException:
                case $e instanceof AccessDeniedHttpException:
                case $e instanceof UnauthorizedException:
                    return ApiResponse::setErrorResponse($messages['unauthorized'] ?? 'Tidak diizinkan.', 403);
                case $e instanceof ThrottleRequestsException:
                case $e instanceof TooManyRequestsHttpException:
                    return ApiResponse::setErrorResponse($messages['tooMany'] ?? 'Terlalu banyak permintaan.', 429);
                case $e instanceof MethodNotAllowedHttpException:
                    return ApiResponse::setErrorResponse($messages['methodNotAllowed'] ?? 'Metode tidak diizinkan.', 405);
                case $e instanceof BadRequestHttpException:
                case $e instanceof SuspiciousOperationException:
                    return ApiResponse::setErrorResponse($messages['badRequest'] ?? 'Permintaan tidak valid.', 400);
                case $e instanceof PostTooLargeException:
                    return ApiResponse::setErrorResponse($messages['payloadTooLarge'] ?? 'Payload terlalu besar.', 413);
                case $e instanceof UnprocessableEntityHttpException:
                    return ApiResponse::setErrorResponse($messages['unprocessable'] ?? 'Data tidak dapat diproses.', 422);
                case $e instanceof ConflictHttpException:
                    return ApiResponse::setErrorResponse($messages['conflict'] ?? 'Terjadi konflik.', 409);
                case $e instanceof ServiceUnavailableHttpException:
                case $e instanceof MaintenanceModeException:
                    return ApiResponse::setErrorResponse($messages['serviceUnavailable'] ?? 'Layanan tidak tersedia.', 503);
                case $e instanceof NotFoundHttpException: {
                    $prev = $e->getPrevious();
                    if (
                        $prev instanceof ModelNotFoundException
                        || $prev instanceof RecordsNotFoundException
                    ) {
                        return ApiResponse::setErrorResponse($messages['notFound'] ?? 'Data tidak ditemukan.', 404, $e->getMessage());
                    }
                    return ApiResponse::setErrorResponse($messages['routeNotFound'] ?? 'Endpoint tidak ditemukan.', 404);
                }
                case $e instanceof MultipleRecordsFoundException:
                    return ApiResponse::setErrorResponse($messages['multipleFound'] ?? 'Data ganda ditemukan.', 409);
                case $e instanceof QueryException: {
                    Log::warning('QueryException: ' . $e->getMessage(), [
                        'sqlState' => $e->errorInfo[0] ?? null,
                        'code' => $e->errorInfo[1] ?? null,
                    ]);
                    $dbCode = $e->errorInfo[1] ?? null;
                    if ($dbCode === 1062) {
                        return ApiResponse::setErrorResponse($messages['duplicate'] ?? 'Data sudah ada.', 409);
                    }
                    if ($dbCode === 1451 || $dbCode === 1452) {
                        return ApiResponse::setErrorResponse($messages['constraint'] ?? 'Melanggar batasan referensial.', 409);
                    }
                    return ApiResponse::setErrorResponse($messages['serverError'] ?? 'Terjadi kesalahan pada server.', 500, $e->getMessage());
                }
                case $e instanceof PDOException:
                    Log::error('PDOException: ' . $e->getMessage());
                    return ApiResponse::setErrorResponse($messages['serverError'] ?? 'Terjadi kesalahan pada server.', 500, $e->getMessage());
                case $e instanceof HttpResponseException: {
                    $resp = $e->getResponse();
                    $status = method_exists($resp, 'getStatusCode') ? $resp->getStatusCode() : 400;
                    $msg = method_exists($resp, 'getContent') ? $resp->getContent() : ($e->getMessage() ?: 'Permintaan gagal.');
                    return ApiResponse::setErrorResponse($msg, $status);
                }
                case $e instanceof HttpExceptionInterface:
                    return ApiResponse::setErrorResponse($e->getMessage() ?: ($messages['badRequest'] ?? 'Permintaan tidak valid.'), $e->getStatusCode());
                default:
                    Log::error('Throwable: ' . $e->getMessage(), ['exception' => $e]);
                    return ApiResponse::setErrorResponse($messages['serverError'] ?? 'Terjadi kesalahan pada server.', 500, $e->getMessage());
            }
        });
    })->create();
