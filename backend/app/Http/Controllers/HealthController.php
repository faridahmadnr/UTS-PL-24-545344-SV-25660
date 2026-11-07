<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    /**
     * Memeriksa kesehatan sistem (Database MySQL dan Cache).
     * Mengembalikan HTTP 200 jika semua sehat, atau HTTP 503 jika salah satu/keduanya gagal.
     */
    public function check(Request $request)
    {
        $status = 'healthy';
        $httpCode = 200;
        $services = [
            'database' => [
                'status'  => 'healthy',
                'message' => 'Koneksi basis data MySQL berhasil',
            ],
            'cache' => [
                'status'  => 'healthy',
                'message' => 'Operasi baca/tulis cache berhasil',
            ],
        ];
        $errors = [];

        // 1. Pemeriksaan Basis Data (MySQL)
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
        } catch (Throwable $e) {
            $status = 'unhealthy';
            $httpCode = 503;
            $services['database'] = [
                'status' => 'unhealthy',
                'error'  => 'Gagal terhubung ke basis data MySQL',
            ];
            $errors[] = 'database: ' . $e->getMessage();

            // Log Terstruktur: Error gagal menghubungi basis data (Poin 2 & 3)
            Log::error('Healthcheck gagal: Tidak dapat menghubungi basis data MySQL', [
                'actor'     => $request->user()?->id ?? 'system/healthcheck',
                'client_ip' => $request->ip(),
                'action'    => 'healthcheck_database_check',
                'subsystem' => 'mysql_database',
                'status'    => 'failed',
                'reason'    => $e->getMessage(),
            ]);
        }

        // 2. Pemeriksaan Cache
        try {
            $cacheKey = 'health_ping_' . bin2hex(random_bytes(4));
            Cache::put($cacheKey, 'pong', 10);
            $cachedVal = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            if ($cachedVal !== 'pong') {
                throw new \Exception('Nilai cache tidak sesuai');
            }
        } catch (Throwable $e) {
            $status = 'unhealthy';
            $httpCode = 503;
            $services['cache'] = [
                'status' => 'unhealthy',
                'error'  => 'Layanan cache tidak merespons dengan benar',
            ];
            $errors[] = 'cache: ' . $e->getMessage();

            // Log Terstruktur: Error gagal cache
            Log::error('Healthcheck gagal: Layanan cache bermasalah', [
                'actor'     => $request->user()?->id ?? 'system/healthcheck',
                'client_ip' => $request->ip(),
                'action'    => 'healthcheck_cache_check',
                'subsystem' => 'application_cache',
                'status'    => 'failed',
                'reason'    => $e->getMessage(),
            ]);
        }

        $payload = [
            'status'    => $status,
            'timestamp' => now()->toIso8601String(),
            'services'  => $services,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $httpCode);
    }
}
