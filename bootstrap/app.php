<?php

use App\Http\Middleware\EnsurePendaftaranTerverifikasi;
use App\Http\Middleware\EnsureRkmNotLocked;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'pendaftaran.terverifikasi' => EnsurePendaftaranTerverifikasi::class,
            'ensure.pendaftaran.terverifikasi' => EnsurePendaftaranTerverifikasi::class,
            'ensure.rkm.unlocked' => EnsureRkmNotLocked::class,
            'rkm.unlocked' => EnsureRkmNotLocked::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {

                // 1. HTTP 422: Validasi Form Request Gagal
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'ERR_VALIDATION_FAILED',
                        'message' => 'Data yang Anda masukkan tidak valid. Silakan periksa kembali field formulir Anda.',
                        'errors' => $e->errors(),
                        'meta' => [
                            'timestamp' => now()->toIso8601String(),
                            'path' => $request->path(),
                        ],
                    ], 422);
                }

                // 2. HTTP 401: Token Sanctum Tidak Ada / Kedaluwarsa
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'ERR_UNAUTHENTICATED',
                        'message' => 'Sesi Anda telah berakhir atau token autentikasi tidak valid. Silakan login kembali.',
                        'errors' => null,
                        'meta' => [
                            'timestamp' => now()->toIso8601String(),
                            'path' => $request->path(),
                        ],
                    ], 401);
                }

                // 3. HTTP 403: Role / Hak Akses Tidak Memadai
                if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'ERR_FORBIDDEN_ROLE',
                        'message' => $e->getMessage() ?: 'Anda tidak memiliki hak akses (permission) untuk menggunakan fitur ini.',
                        'errors' => null,
                        'meta' => [
                            'timestamp' => now()->toIso8601String(),
                            'path' => $request->path(),
                        ],
                    ], 403);
                }

                // 4. HTTP 404: Model / Route Tidak Ditemukan
                if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                    $modelName = $e instanceof ModelNotFoundException ? class_basename($e->getModel()) : 'Data';

                    return response()->json([
                        'success' => false,
                        'error_code' => 'ERR_RESOURCE_NOT_FOUND',
                        'message' => "Resource {$modelName} atau endpoint yang Anda minta tidak ditemukan pada sistem.",
                        'errors' => null,
                        'meta' => [
                            'timestamp' => now()->toIso8601String(),
                            'path' => $request->path(),
                        ],
                    ], 404);
                }

                // 5. HTTP 409: Database Constraint Violation (Duplicate Entry & Foreign Key)
                if ($e instanceof QueryException) {
                    $errorCode = $e->errorInfo[1] ?? 0;
                    $sqlState = $e->errorInfo[0] ?? '';

                    if ($errorCode === 1062 || $sqlState === '23505') {
                        return response()->json([
                            'success' => false,
                            'error_code' => 'ERR_DUPLICATE_ENTRY',
                            'message' => 'Data yang dimasukkan duplikat atau sudah terdaftar sebelumnya dalam sistem.',
                            'errors' => null,
                            'meta' => [
                                'timestamp' => now()->toIso8601String(),
                                'path' => $request->path(),
                            ],
                        ], 409);
                    }

                    if ($errorCode === 1451 || $sqlState === '23503') {
                        return response()->json([
                            'success' => false,
                            'error_code' => 'ERR_FOREIGN_KEY_CONSTRAINT',
                            'message' => 'Data tidak dapat dihapus karena masih terhubung dan digunakan oleh transaksi lain.',
                            'errors' => null,
                            'meta' => [
                                'timestamp' => now()->toIso8601String(),
                                'path' => $request->path(),
                            ],
                        ], 409);
                    }
                }

                // 6. HTTP 413: Ukuran Upload Melebihi php.ini / Post Size Max
                if ($e instanceof PostTooLargeException) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'ERR_FILE_TOO_LARGE',
                        'message' => 'Ukuran berkas yang diunggah melebihi batas maksimal server (Maksimal 5 MB).',
                        'errors' => [
                            'file' => ['Ukuran berkas terlalu besar. Batas maksimal adalah 5MB.'],
                        ],
                        'meta' => [
                            'timestamp' => now()->toIso8601String(),
                            'path' => $request->path(),
                        ],
                    ], 413);
                }

                // 7. HTTP 429: Rate Limit Terlampaui
                if ($e instanceof ThrottleRequestsException) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'ERR_RATE_LIMIT_EXCEEDED',
                        'message' => 'Terlalu banyak request dalam waktu singkat. Mohon tunggu beberapa saat sebelum mencoba kembali.',
                        'errors' => null,
                        'meta' => [
                            'timestamp' => now()->toIso8601String(),
                            'path' => $request->path(),
                            'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
                        ],
                    ], 429);
                }

                // 8. HTTP Custom HttpException (misal: abort(403, 'Pesan'))
                if ($e instanceof HttpException) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'ERR_HTTP_'.$e->getStatusCode(),
                        'message' => $e->getMessage() ?: 'Terjadi kesalahan pada request HTTP.',
                        'errors' => null,
                        'meta' => [
                            'timestamp' => now()->toIso8601String(),
                            'path' => $request->path(),
                        ],
                    ], $e->getStatusCode());
                }

                // 9. HTTP 500: Fallback Internal Server Error Tak Terduga
                $isProduction = ! config('app.debug');

                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_INTERNAL_SERVER',
                    'message' => $isProduction
                        ? 'Terjadi kesalahan internal pada server. Tim teknis Fasilkom telah diberitahu.'
                        : $e->getMessage(),
                    'errors' => $isProduction ? null : [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => array_slice($e->getTrace(), 0, 5),
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                        'path' => $request->path(),
                    ],
                ], 500);
            }
        });
    })->create();
