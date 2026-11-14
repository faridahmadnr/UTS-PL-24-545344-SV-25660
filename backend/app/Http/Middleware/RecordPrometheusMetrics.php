<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordPrometheusMetrics
{
    /**
     * Handle an incoming request and track HTTP metrics.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $path = trim($request->path(), '/');

            // Normalisasi pola route agar tidak menyebabkan cardinality explosion
            if (str_starts_with($path, 'api/books/')) {
                $route = '/api/books/{id}';
            } elseif (str_starts_with($path, 'api/books')) {
                $route = '/api/books';
            } elseif (str_starts_with($path, 'api/login')) {
                $route = '/api/login';
            } elseif (str_starts_with($path, 'api/register')) {
                $route = '/api/register';
            } elseif (str_starts_with($path, 'api/user')) {
                $route = '/api/user';
            } elseif ($path === 'health' || $path === 'api/health') {
                $route = '/health';
            } elseif ($path === 'metrics' || $path === 'api/metrics') {
                $route = '/metrics';
            } elseif ($path === '' || $path === '/') {
                $route = '/';
            } else {
                $route = '/' . $path;
            }

            $method = $request->method();
            $status = (string) $response->getStatusCode();
            $key = "{$method}|{$route}|{$status}";

            $metricsFile = storage_path('framework/http_metrics.json');
            $data = [];
            if (file_exists($metricsFile)) {
                $content = @file_get_contents($metricsFile);
                if ($content) {
                    $data = json_decode($content, true) ?: [];
                }
            }

            $data[$key] = ($data[$key] ?? 0) + 1;
            @file_put_contents($metricsFile, json_encode($data), LOCK_EX);
        } catch (\Throwable $e) {
            // Jangan menginterupsi siklus respons aplikasi jika terjadi kendala
        }

        return $response;
    }
}
