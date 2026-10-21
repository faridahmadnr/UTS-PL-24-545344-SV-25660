# UTS Konstruksi dan Evolusi Perangkat Lunak (KEPL) 2026/2027

**Nama Mahasiswa:** Farid Ahmad Nur Rahman  
**NIM:** 24/545344/SV/25660  
**Program Studi:** Rekayasa Perangkat Lunak  

---

## Deskripsi Proyek
Proyek ini merupakan sistem aplikasi full-stack modern yang terdiri dari:
1. **Backend:** RESTful API Laravel dengan autentikasi token Laravel Sanctum dan berjalan di atas runtime server **FrankenPHP** (Bonus Nilai).
2. **Frontend:** Single Page Application (SPA) berbasis Vue.js 3 + Vite, Vue Router, dan Axios.
3. **Basis Data:** MySQL 8.0 dengan Named Volume untuk persistensi data.
4. **Containerization:** Multi-container orchestration menggunakan **Docker Compose** dan **Multi-Stage Build** pada frontend Dockerfile.
5. **CI/CD:** Otomasi pengujian dan deployment ke GitHub Container Registry (`ghcr.io`) menggunakan **GitHub Actions**.

---

## Arsitektur Container (Docker Compose)
* **Frontend:** Nginx 1.27 (`port 80`)
* **Backend:** Laravel on FrankenPHP (`port 8000`)
* **Basis Data:** MySQL 8.0 (`port 3306`)
* **Network:** `kepl-network` (bridge)
* **Volume:** `db_data` (Named volume)

---

## Cara Menjalankan Proyek (Local Docker)

### 1. Salin Konfigurasi Environment
```bash
cp .env.example .env
```

### 2. Jalankan Seluruh Layanan dengan Docker Compose
```bash
docker compose up -d --build
```

### 3. Jalankan Migrasi Database di Dalam Container
```bash
docker compose exec backend php artisan migrate --force
```

### 4. Akses Aplikasi
* **Frontend Web:** [http://localhost](http://localhost)
* **Backend API:** [http://localhost:8000/api](http://localhost:8000/api)
