<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Jangan redirect ke rute login web saat request unauthenticated
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Seluruh endpoint dengan awalan api/* WAJIB selalu merespon JSON
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

        // Tangani AuthenticationException secara eksplisit untuk rute api/* dengan status 401 dan log warning
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                Log::warning('Akses API ditolak: Permintaan tidak menyertakan token otorisasi valid', [
                    'actor'     => 'guest/unauthenticated',
                    'client_ip' => $request->ip(),
                    'action'    => 'api_unauthenticated_access',
                    'endpoint'  => $request->path(),
                    'method'    => $request->method(),
                    'status'    => 401,
                    'reason'    => 'Token missing or invalid',
                ]);

                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });

        // Tangani QueryException jika terjadi kegagalan koneksi atau query basis data
        $exceptions->render(function (QueryException $e, Request $request) {
            Log::error('Operasi basis data MySQL gagal dieksekusi', [
                'actor'     => $request->user()?->id ?? 'system/guest',
                'client_ip' => $request->ip(),
                'action'    => 'database_query_error',
                'endpoint'  => $request->path(),
                'status'    => 'failed',
                'reason'    => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan komunikasi dengan basis data.',
                'error'   => $e->getMessage(),
            ], 500);
        });
    })->create();
