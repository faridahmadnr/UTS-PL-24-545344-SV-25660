<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/health', [HealthController::class, 'check']);
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
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::apiResource('books', BookController::class);
});
