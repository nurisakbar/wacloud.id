# WACloud Laravel Package

<div align="center">
  <img src="https://wacloud.id/logo.png" alt="WACloud Logo" width="200">
  
  <h3>Laravel Package untuk Integrasi WACloud WhatsApp Gateway API</h3>
  
  <p>
    <a href="https://wacloud.id">Website</a> •
    <a href="https://app.wacloud.id">Dashboard</a> •
    <a href="https://wacloud.id/docs.html">Dokumentasi</a> •
    <a href="https://www.youtube.com/@wacloudid">YouTube</a>
  </p>
</div>

[![Latest Version](https://img.shields.io/packagist/v/wacloud/laravel-wacloud.svg?style=flat-square)](https://packagist.org/packages/wacloud/laravel-wacloud)
[![Total Downloads](https://img.shields.io/packagist/dt/wacloud/laravel-wacloud.svg?style=flat-square)](https://packagist.org/packages/wacloud/laravel-wacloud)
[![License](https://img.shields.io/packagist/l/wacloud/laravel-wacloud.svg?style=flat-square)](https://packagist.org/packages/wacloud/laravel-wacloud)

Package Laravel resmi untuk mengintegrasikan WACloud WhatsApp Gateway API ke dalam aplikasi Laravel Anda. Dengan package ini, Anda dapat dengan mudah mengirim pesan WhatsApp, mengelola device/session, dan memanfaatkan semua fitur WACloud API.

## 📋 Fitur

- ✅ **Device Management** - Membuat, melihat, dan mengelola device/session WhatsApp
- ✅ **Send Text Message** - Mengirim pesan teks
- ✅ **Send Image Message** - Mengirim gambar dengan caption
- ✅ **Send Video Message** - Mengirim video dengan opsi video note
- ✅ **Send Document Message** - Mengirim dokumen (PDF, DOC, dll)
- ✅ **Facade Support** - Menggunakan Facade untuk kemudahan akses
- ✅ **Service Container** - Terintegrasi dengan Laravel Service Container
- ✅ **Config File** - Konfigurasi melalui file config dan environment variable
- ✅ **Type Hints** - Full type hints untuk IDE support

## 🚀 Instalasi

### Via Composer

```bash
composer require wacloud/laravel-wacloud
```

### Publish Config File

```bash
php artisan vendor:publish --tag=wacloud-config
```

Ini akan membuat file `config/wacloud.php` di aplikasi Anda.

## ⚙️ Konfigurasi

### 1. Environment Variables

Tambahkan konfigurasi berikut ke file `.env` Anda:

```env
WACLOUD_API_KEY=wacloud_your_api_key_here
WACLOUD_BASE_URL=https://app.wacloud.id/api/v1
WACLOUD_TIMEOUT=30
```

### 2. Mendapatkan API Key

1. Daftar/Login ke [Dashboard WACloud](https://app.wacloud.id)
2. Buka menu **API Keys**
3. Klik **Create API Key**
4. Copy API Key dan paste ke file `.env`

## 📖 Penggunaan

### Menggunakan Facade

```php
use WACloud\LaravelWACloud\Facades\WACloud;

// Device Management
$devices = WACloud::getDevices();
$device = WACloud::getDevice('device-id');
$newDevice = WACloud::createDevice('Device Name', '81234567890');
$status = WACloud::getDeviceStatus('device-id');
$qrCode = WACloud::getDeviceQrCode('device-id');
WACloud::deleteDevice('device-id');

// Send Messages
$response = WACloud::sendText('device-id', '6281234567890', 'Hello from WACloud!');
$response = WACloud::sendImage('device-id', '6281234567890', 'https://example.com/image.jpg', 'Caption');
$response = WACloud::sendVideo('device-id', '6281234567890', 'https://example.com/video.mp4', 'Caption');
$response = WACloud::sendDocument('device-id', '6281234567890', 'https://example.com/doc.pdf', 'document.pdf', 'Caption');
```

### Menggunakan Dependency Injection

```php
use WACloud\LaravelWACloud\WACloudClient;

class MessageController extends Controller
{
    protected $wacloud;

    public function __construct(WACloudClient $wacloud)
    {
        $this->wacloud = $wacloud;
    }

    public function sendMessage()
    {
        $response = $this->wacloud->sendText(
            'device-id',
            '6281234567890',
            'Hello from WACloud!'
        );

        if ($response['success']) {
            return response()->json($response['data']);
        }

        return response()->json($response, 400);
    }
}
```

### Menggunakan Helper Function (Laravel 11+)

```php
// Jika menggunakan helper function
$response = wacloud()->sendText('device-id', '6281234567890', 'Hello!');
```

### Custom Request

Jika Anda perlu membuat request custom:

```php
use WACloud\LaravelWACloud\Facades\WACloud;

// GET request
$response = WACloud::get('/custom-endpoint', ['param' => 'value']);

// POST request
$response = WACloud::post('/custom-endpoint', ['data' => 'value']);

// PUT request
$response = WACloud::put('/custom-endpoint', ['data' => 'value']);

// DELETE request
$response = WACloud::delete('/custom-endpoint');
```

### Dynamic API Key

Jika Anda perlu menggunakan API key yang berbeda untuk setiap request:

```php
use WACloud\LaravelWACloud\Facades\WACloud;

$response = WACloud::setApiKey('different-api-key')
    ->sendText('device-id', '6281234567890', 'Hello!');
```

## 📝 Contoh Lengkap

### Device Management

```php
use WACloud\LaravelWACloud\Facades\WACloud;

// Membuat device baru
$response = WACloud::createDevice('Device Utama', '81234567890');

if ($response['success']) {
    $deviceId = $response['data']['id'];
    echo "Device created: {$deviceId}\n";
    
    // Mendapatkan QR Code untuk pairing
    $qrResponse = WACloud::getDeviceQrCode($deviceId);
    if ($qrResponse['success']) {
        $qrCode = $qrResponse['data']['qr_code'];
        // Tampilkan QR code di view atau simpan ke file
    }
    
    // Cek status device
    $statusResponse = WACloud::getDeviceStatus($deviceId);
    if ($statusResponse['success']) {
        $status = $statusResponse['data']['status'];
        echo "Device status: {$status}\n";
    }
}
```

### Mengirim Pesan

```php
use WACloud\LaravelWACloud\Facades\WACloud;

// Text Message
$response = WACloud::sendText(
    'device-id',
    '6281234567890',
    'Halo, ini pesan dari WACloud API!'
);

if ($response['success']) {
    $messageId = $response['data']['message_id'];
    echo "Message sent: {$messageId}\n";
}

// Image Message
$response = WACloud::sendImage(
    'device-id',
    '6281234567890',
    'https://example.com/image.jpg',
    'Ini adalah caption gambar'
);

// Video Message
$response = WACloud::sendVideo(
    'device-id',
    '6281234567890',
    'https://example.com/video.mp4',
    'Ini adalah caption video',
    false, // as_note
    false  // convert
);

// Document Message
$response = WACloud::sendDocument(
    'device-id',
    '6281234567890',
    'https://example.com/document.pdf',
    'document.pdf',
    'Ini adalah caption dokumen'
);
```

### Error Handling

```php
use WACloud\LaravelWACloud\Facades\WACloud;

$response = WACloud::sendText('device-id', '6281234567890', 'Hello!');

if (!$response['success']) {
    // Handle error
    $error = $response['error'] ?? 'Unknown error';
    $message = $response['message'] ?? null;
    $httpCode = $response['http_code'] ?? 500;
    
    Log::error('WACloud API Error', [
        'error' => $error,
        'message' => $message,
        'http_code' => $httpCode,
        'raw' => $response['raw'] ?? null,
    ]);
}
```

## 🔧 Response Format

Semua method mengembalikan array dengan format berikut:

```php
[
    'success' => true,           // boolean
    'http_code' => 200,          // int
    'data' => [...],             // array - response data
    'message' => 'Success',      // string|null - response message
    'raw' => [...],             // array - raw response dari API
]

// Jika error:
[
    'success' => false,
    'http_code' => 400,
    'error' => 'Error message',
    'message' => 'Error description',
    'raw' => [...],
]
```

## ⚠️ Catatan Penting

1. **API Key**: Jangan pernah commit API Key ke repository publik. Gunakan environment variable.

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

## 📚 Dokumentasi Lengkap

Untuk dokumentasi lengkap tentang WACloud API, kunjungi:
- **Website**: [https://wacloud.id](https://wacloud.id)
- **Dashboard**: [https://app.wacloud.id](https://app.wacloud.id)
- **Dokumentasi API**: [https://wacloud.id/docs.html](https://wacloud.id/docs.html)
- **YouTube**: [Channel WACloud](https://www.youtube.com/@wacloudid) - Tutorial dan panduan lengkap

## 🤝 Support

Jika Anda mengalami masalah atau memiliki pertanyaan:

1. 📖 Cek dokumentasi lengkap di [docs.html](https://wacloud.id/docs.html)
2. 🎥 Tonton tutorial di [YouTube Channel](https://www.youtube.com/@wacloudid)
3. 💬 Hubungi support melalui [Dashboard](https://app.wacloud.id)
4. 🐛 Buka issue di [GitHub Repository](https://github.com/wacloud/laravel-wacloud)

## 📄 License

Package ini menggunakan lisensi [MIT License](LICENSE). Silakan lihat file LICENSE untuk detail lengkap.

## 🙏 Credits

Dibuat dengan ❤️ oleh [WACloud](https://wacloud.id)

---

<div align="center">
  <p>Dibuat dengan ❤️ oleh <a href="https://wacloud.id">WACloud</a></p>
  <p>
    <a href="https://wacloud.id">Website</a> •
    <a href="https://app.wacloud.id">Dashboard</a> •
    <a href="https://wacloud.id/docs.html">Dokumentasi</a> •
    <a href="https://www.youtube.com/@wacloudid">YouTube</a>
  </p>
</div>

