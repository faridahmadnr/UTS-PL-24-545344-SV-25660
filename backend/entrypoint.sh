#!/bin/sh
set -e

# Verifikasi koneksi basis data MySQL bila digunakan
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "==> [Entrypoint] Menunggu service database MySQL siap di $DB_HOST:$DB_PORT..."
    until php -r '
    try {
        $host = getenv("DB_HOST") ?: "db";
        $port = getenv("DB_PORT") ?: "3306";
        $db   = getenv("DB_DATABASE") ?: "db_uts_kepl";
        $user = getenv("DB_USERNAME") ?: "root";
        $pass = getenv("DB_PASSWORD") ?: "";
        $pdo  = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, [PDO::ATTR_TIMEOUT => 3]);
        exit(0);
    } catch (Throwable $e) {
        exit(1);
    }
    '; do
        echo "==> [Entrypoint] Basis data belum siap, menunggu 1 detik..."
        sleep 1
    done
    echo "==> [Entrypoint] Koneksi basis data MySQL berhasil terhubung!"
fi

# Jalankan migrasi tabel secara otomatis
echo "==> [Entrypoint] Menjalankan migrasi database otomatis (php artisan migrate --force)..."
php artisan migrate --force

# Jalankan seeding akun demo dan data buku awal
echo "==> [Entrypoint] Menjalankan seeder akun demo (php artisan db:seed --force)..."
php artisan db:seed --force

# Jalankan server aplikasi FrankenPHP
echo "==> [Entrypoint] Memulai server FrankenPHP pada port 8000..."
exec frankenphp run --config /etc/caddy/Caddyfile
