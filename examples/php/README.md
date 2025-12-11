# Contoh Kode PHP untuk WACloud API

<div align="center">
  <img src="../../landing-page/logo-wacloud.png" alt="WACloud Logo" width="200">
</div>

Koleksi contoh kode PHP untuk mengintegrasikan WACloud API ke aplikasi Anda.

## 🌐 Informasi

- **Website**: [https://wacloud.id](https://wacloud.id)
- **Dashboard**: [https://app.wacloud.id](https://app.wacloud.id)
- **Dokumentasi**: [https://wacloud.id/docs.html](https://wacloud.id/docs.html)
- **YouTube**: [Channel WACloud](https://www.youtube.com/@wacloudid) - Tutorial dan panduan lengkap

## 📋 Daftar Contoh

1. **device-management.php** - Mengelola device/session WhatsApp
   - Membuat device baru
   - Mendapatkan QR code untuk pairing
   - Mengecek status device
   - Mendapatkan daftar device

2. **send-text-message.php** - Mengirim pesan text
   - Mengirim pesan teks biasa

3. **send-image-message.php** - Mengirim pesan gambar
   - Mengirim gambar dengan caption

4. **send-document-message.php** - Mengirim pesan dokumen
   - Mengirim file PDF, DOC, dll

5. **send-video-message.php** - Mengirim pesan video
   - Mengirim video dengan caption
   - Opsi untuk mengirim sebagai video note

## 🚀 Cara Menggunakan

### 1. Installasi

Tidak ada dependency khusus yang diperlukan. Pastikan PHP Anda sudah terinstall dengan extension `curl` dan `json`.

```bash
# Cek apakah curl extension sudah aktif
php -m | grep curl

# Cek apakah json extension sudah aktif
php -m | grep json
```

### 2. Konfigurasi

1. Buka file `config.php`
2. Ganti `YOUR_API_KEY` dengan API Key Anda dari dashboard
3. Pastikan `BASE_URL` sudah benar (default: `https://app.wacloud.id/api/v1`)

```php
define('API_KEY', 'wacloud_your_actual_api_key_here');
```

### 3. Menjalankan Contoh

Jalankan file PHP yang ingin Anda coba:

```bash
# Contoh: Mengirim pesan text
php send-text-message.php

# Contoh: Device management
php device-management.php

# Contoh: Mengirim gambar
php send-image-message.php
```

## 📝 Contoh Penggunaan

### Device Management

```php
require_once 'config.php';

// Membuat device baru
$deviceData = [
    'name' => 'Device Utama',
    'phone_number' => '81234567890'
];

$response = makeRequest('POST', '/devices', $deviceData);

if ($response['success']) {
    $deviceId = $response['data']['id'];
    echo "Device created: {$deviceId}\n";
}
```

### Mengirim Pesan Text

```php
require_once 'config.php';

$messageData = [
    'device_id' => '550e8400-e29b-41d4-a716-446655440000',
    'to' => '6281234567890',
    'message_type' => 'text',
    'text' => 'Halo dari API!'
];

$response = makeRequest('POST', '/messages', $messageData);

if ($response['success']) {
    echo "Message sent: {$response['data']['message_id']}\n";
}
```

### Mengirim Pesan Gambar

```php
require_once 'config.php';

$messageData = [
    'device_id' => '550e8400-e29b-41d4-a716-446655440000',
    'to' => '6281234567890',
    'message_type' => 'image',
    'image_url' => 'https://example.com/image.jpg',
    'caption' => 'Caption gambar'
];

$response = makeRequest('POST', '/messages', $messageData);
```

## ⚠️ Catatan Penting

1. **API Key**: Jangan pernah commit API Key ke repository publik. Gunakan environment variable atau file konfigurasi yang di-ignore oleh Git.

2. **Device Status**: Pastikan device dalam status `connected` sebelum mengirim pesan. Device yang masih `pairing` tidak dapat mengirim pesan.

3. **Format Nomor**: 
   - Gunakan format tanpa leading 0
   - Sertakan kode negara
   - Contoh: `6281234567890` (bukan `081234567890`)

4. **URL Media**: 
   - URL gambar, video, dan dokumen harus dapat diakses secara publik
   - Gunakan HTTPS untuk keamanan
   - Pastikan server dapat diakses dari internet

5. **Rate Limiting**: 
   - API memiliki rate limiting untuk keamanan
   - Jangan mengirim terlalu banyak request dalam waktu singkat

## 🔗 Link Berguna

- **Website**: [https://wacloud.id](https://wacloud.id)
- **Dashboard**: [https://app.wacloud.id](https://app.wacloud.id)
- **Dokumentasi Lengkap**: [https://wacloud.id/docs.html](https://wacloud.id/docs.html)
- **YouTube**: [Channel WACloud](https://www.youtube.com/@wacloud) *(jika ada)*
- **Postman Collection**: [https://wacloud.id/docs.html#postman](https://wacloud.id/docs.html#postman)

## 📞 Support

Jika Anda mengalami masalah atau memiliki pertanyaan:

1. 📖 Cek dokumentasi lengkap di [docs.html](https://wacloud.id/docs.html)
2. 🎥 Tonton tutorial di [YouTube Channel](https://www.youtube.com/@wacloudid)
3. 💬 Hubungi support melalui [Dashboard](https://app.wacloud.id)
4. 🐛 Buka issue di GitHub repository ini

## 📚 Informasi Lebih Lanjut

Untuk informasi lebih lengkap tentang WACloud, kunjungi:
- **Website**: [https://wacloud.id](https://wacloud.id)
- **YouTube**: [Channel WACloud](https://www.youtube.com/@wacloud)
- Lihat file [ABOUT.md](ABOUT.md) untuk informasi lengkap

## 📄 License

Contoh kode ini tersedia untuk digunakan sebagai referensi. Silakan modifikasi sesuai kebutuhan Anda.

