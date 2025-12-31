# Panduan Instalasi WACloud CodeIgniter 4 Package

Panduan lengkap untuk menginstall dan mengkonfigurasi WACloud package di aplikasi CodeIgniter 4 Anda.

## 📋 Prasyarat

- CodeIgniter 4 (versi 4.3 atau lebih baru)
- PHP 8.1 atau lebih baru
- Composer terinstall
- Akun WACloud dan API Key

## 🚀 Langkah Instalasi

### 1. Install via Composer

```bash
composer require wacloud/codeigniter4-wacloud
```

### 2. Copy Config File

Copy file config ke folder `app/Config/`:

**Via Command Line:**
```bash
cp vendor/wacloud/codeigniter4-wacloud/src/Config/WACloud.php app/Config/WACloud.php
```

**Atau Manual:**
- Buka folder: `vendor/wacloud/codeigniter4-wacloud/src/Config/`
- Copy file `WACloud.php`
- Paste ke folder `app/Config/` di aplikasi CodeIgniter 4 Anda

### 3. Konfigurasi Environment

Tambahkan konfigurasi berikut ke file `.env`:

```env
# WACloud Configuration
WACLOUD_API_KEY=wacloud_your_api_key_here
WACLOUD_BASE_URL=https://app.wacloud.id/api/v1
WACLOUD_TIMEOUT=30
```

**Atau edit file `app/Config/WACloud.php`:**

```php
public string $apiKey = 'wacloud_your_api_key_here';
public string $baseUrl = 'https://app.wacloud.id/api/v1';
public int $timeout = 30;
```

### 4. Load Helper (Opsional)

Jika ingin menggunakan helper functions, tambahkan ke `app/Config/Autoload.php`:

```php
public $helpers = [
    // ... helpers lainnya
    'wacloud', // Tambahkan ini
];
```

**Atau load manual di controller:**

```php
helper('wacloud');
```

### 5. Register Service (Opsional)

Jika ingin menggunakan service, tambahkan method berikut ke `app/Config/Services.php`:

```php
public static function wacloud($getShared = true)
{
    if ($getShared) {
        return static::getSharedInstance('wacloud');
    }

    return new \WACloud\CodeIgniter4WACloud\Libraries\WACloud();
}
```

## ✅ Verifikasi Instalasi

Buat file test untuk memverifikasi instalasi:

**File: `app/Controllers/TestWACloud.php`**

```php
<?php

namespace App\Controllers;

use WACloud\CodeIgniter4WACloud\Libraries\WACloud;

class TestWACloud extends BaseController
{
    public function index()
    {
        $wacloud = new WACloud();
        $devices = $wacloud->getDevices();
        
        return $this->response->setJSON($devices);
    }
}
```

Akses via browser: `http://your-domain/testwacloud`

Jika berhasil, Anda akan melihat response JSON dengan daftar devices.

## 🔧 Troubleshooting

### Error: Class 'Config\WACloud' not found

**Solusi:** Pastikan file config sudah di-copy ke `app/Config/WACloud.php`

### Error: Call to undefined function wacloud()

**Solusi:** 
1. Pastikan helper sudah di-load di `Autoload.php` atau
2. Load manual dengan `helper('wacloud')` di controller

### Error: API Key tidak valid

**Solusi:**
1. Pastikan API Key sudah benar di `.env` atau config
2. Cek apakah API Key aktif di dashboard WACloud
3. Pastikan format API Key benar (tanpa spasi)

### Error: cURL error

**Solusi:**
1. Pastikan extension `curl` sudah aktif di PHP
2. Cek koneksi internet
3. Pastikan base URL benar

## 📚 Langkah Selanjutnya

Setelah instalasi berhasil, baca dokumentasi lengkap di [README.md](README.md) untuk:
- Contoh penggunaan lengkap
- Device management
- Mengirim berbagai jenis pesan
- Error handling
- Best practices

## 🆘 Butuh Bantuan?

Jika mengalami masalah:
1. Cek [README.md](README.md) untuk dokumentasi lengkap
2. Buka issue di [GitHub Repository](https://github.com/wacloud/codeigniter4-wacloud)
3. Hubungi support di [Dashboard WACloud](https://app.wacloud.id)

