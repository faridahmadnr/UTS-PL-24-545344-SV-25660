<?php

use App\Http\Controllers\HealthController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Endpoint Pemeriksaan Kesehatan Sistem (Healthcheck)
// Bebaskan dari seluruh session, cookie, dan CSRF agar healthcheck tetap dapat berjalan saat database down
Route::get('/health', [HealthController::class, 'check'])
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        ValidateCsrfToken::class,
    ]);

// Endpoint Metrik Prometheus Aplikasi (P11)
Route::get('/metrics', function () {
    $dbStatus = 1;
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
    } catch (\Throwable $e) {
        $dbStatus = 0;
    }

    $booksCount = 0;
    try {
        $booksCount = \App\Models\Book::count();
    } catch (\Throwable $e) {}

    $output = "# HELP app_up Status hidup aplikasi Laravel\n";
    $output .= "# TYPE app_up gauge\n";
    $output .= "app_up 1\n\n";
    $output .= "# HELP app_database_up Status koneksi basis data MySQL\n";
    $output .= "# TYPE app_database_up gauge\n";
    $output .= "app_database_up {$dbStatus}\n\n";
    $output .= "# HELP app_books_total Jumlah koleksi buku di basis data\n";
    $output .= "# TYPE app_books_total gauge\n";
    $output .= "app_books_total {$booksCount}\n";

    return response($output, 200, [
        'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8'
    ]);
})->withoutMiddleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    ValidateCsrfToken::class,
]);



