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

    // Baca akumulasi hitungan permintaan HTTP backend Laravel
    $metricsFile = storage_path('framework/http_metrics.json');
    $httpMetrics = [];
    if (file_exists($metricsFile)) {
        $content = @file_get_contents($metricsFile);
        if ($content) {
            $httpMetrics = json_decode($content, true) ?: [];
        }
    }

    // Pastikan hitungan metrik /metrics sendiri terdata
    $selfKey = 'GET|/metrics|200';
    $httpMetrics[$selfKey] = ($httpMetrics[$selfKey] ?? 0) + 1;
    @file_put_contents($metricsFile, json_encode($httpMetrics), LOCK_EX);

    $output = "# HELP app_up Status hidup aplikasi Laravel\n";
    $output .= "# TYPE app_up gauge\n";
    $output .= "app_up 1\n\n";

    $output .= "# HELP app_database_up Status koneksi basis data MySQL\n";
    $output .= "# TYPE app_database_up gauge\n";
    $output .= "app_database_up {$dbStatus}\n\n";

    $output .= "# HELP app_books_total Jumlah koleksi buku di basis data\n";
    $output .= "# TYPE app_books_total gauge\n";
    $output .= "app_books_total {$booksCount}\n\n";

    $output .= "# HELP app_http_requests_total Total permintaan HTTP yang diterima backend Laravel\n";
    $output .= "# TYPE app_http_requests_total counter\n";
    foreach ($httpMetrics as $key => $count) {
        $parts = explode('|', $key);
        if (count($parts) === 3) {
            [$method, $route, $status] = $parts;
            $output .= "app_http_requests_total{method=\"{$method}\",route=\"{$route}\",status=\"{$status}\"} {$count}\n";
        }
    }

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



