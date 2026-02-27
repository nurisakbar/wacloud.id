# WACloud ID - WhatsApp SaaS Management

WACloud ID adalah platform SaaS untuk mengelola API WhatsApp menggunakan engine WAHA (WhatsApp HTTP API) Plus. Project ini mencakup manajemen sesi, routing pesan otomatis, dan dashboard monitoring.

## 🚀 Cara Menjalankan Project

### 1. Menjalankan Infrastruktur (Docker)
Pastikan Docker Desktop sudah aktif. Infrastruktur terdiri dari WAHA Plus dan Redis.

```bash
# Pull image WAHA Plus (Membutuhkan kredensial di .env)
./pull-waha-plus.sh

# Jalankan semua container
./waha.sh start

# Cek status
./waha.sh status
```

### 2. Menjalankan Frontend (Laravel)
Buka terminal baru di folder `frontend`:

```bash
cd frontend
composer install
php artisan migrate
php artisan serve
```

### 3. Menjalankan Background Job (Queue)
Untuk memproses pengiriman pesan secara asinkron, jalankan worker:

```bash
cd frontend
php artisan queue:work --queue=highest,high,default
```

---

## 💾 Manajemen Backup Image

Halaman ini mencakup cara membackup **Aplikasi (Image)** agar bisa digunakan secara offline atau dipindahkan ke server lain tanpa perlu download ulang dari Docker Hub.

### 1. Membuat Backup Image ke File
Gunakan script backup yang tersedia di root project:

```bash
./backup-image.sh
```
Script ini akan menghasilkan file `waha-plus-image.tar.gz` (Ukuran ~700MB - 1GB). Simpan file ini di tempat yang aman (Cloud/Harddisk).

### 2. Cara Restore Image (Load)
Jika Anda berada di server baru atau ingin memuat kembali image:

```bash
docker load -i waha-plus-image.tar.gz
```
Setelah di-load, Anda bisa langsung menjalankan `./waha.sh start` tanpa perlu pull ulang.

---

## 🧪 Testing Pengiriman Pesan
Tersedia CLI helper untuk melakukan test pengiriman pesan dengan cepat via terminal:

```bash
# Test Kirim Teks
php artisan whatsapp:test-send 08123456789 --message="Halo ini test"

# Test Kirim PDF
php artisan whatsapp:test-send 08123456789 --document="https://example.com/file.pdf" --message="Ini caption PDF"
```

---

## ⚠️ Catatan Penting Data Sesi
*   **Software** tersimpan di Image Docker.
*   **Data Login (Sessions)** tersimpan di folder lokal `docker-data/`.
*   **WAJIB BACKUP** folder `docker-data/` secara rutin jika ingin menjaga status login WhatsApp Anda tetap aktif saat pindah server.

---
© 2026 WACloud ID Team
