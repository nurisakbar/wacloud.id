# WACloud CodeIgniter 4 Package

<div align="center">
  <img src="https://wacloud.id/logo.png" alt="WACloud Logo" width="200">
  
  <h3>CodeIgniter 4 Package untuk Integrasi WACloud WhatsApp Gateway API</h3>
  
  <p>
    <a href="https://wacloud.id">Website</a> •
    <a href="https://app.wacloud.id">Dashboard</a> •
    <a href="https://wacloud.id/docs.html">Dokumentasi</a> •
    <a href="https://www.youtube.com/@wacloudid">YouTube</a>
  </p>
</div>

[![Latest Version](https://img.shields.io/packagist/v/wacloud/codeigniter4-wacloud.svg?style=flat-square)](https://packagist.org/packages/wacloud/codeigniter4-wacloud)
[![Total Downloads](https://img.shields.io/packagist/dt/wacloud/codeigniter4-wacloud.svg?style=flat-square)](https://packagist.org/packages/wacloud/codeigniter4-wacloud)
[![License](https://img.shields.io/packagist/l/wacloud/codeigniter4-wacloud.svg?style=flat-square)](https://packagist.org/packages/wacloud/codeigniter4-wacloud)

Package CodeIgniter 4 resmi untuk mengintegrasikan WACloud WhatsApp Gateway API ke dalam aplikasi CodeIgniter 4 Anda. Dengan package ini, Anda dapat dengan mudah mengirim pesan WhatsApp, mengelola device/session, dan memanfaatkan semua fitur WACloud API.

## 📋 Fitur

- ✅ **Device Management** - Membuat, melihat, dan mengelola device/session WhatsApp
- ✅ **Send Text Message** - Mengirim pesan teks
- ✅ **Send Image Message** - Mengirim gambar dengan caption
- ✅ **Send Video Message** - Mengirim video dengan opsi video note
- ✅ **Send Document Message** - Mengirim dokumen (PDF, DOC, dll)
- ✅ **Helper Functions** - Helper functions untuk kemudahan penggunaan
- ✅ **Service Support** - Terintegrasi dengan CodeIgniter 4 Service
- ✅ **Config File** - Konfigurasi melalui file config dan environment variable
- ✅ **Type Hints** - Full type hints untuk IDE support

## 🚀 Instalasi

### Via Composer

```bash
composer require wacloud/codeigniter4-wacloud
```

### Copy Config File

Copy file config ke folder `app/Config/`:

```bash
cp vendor/wacloud/codeigniter4-wacloud/src/Config/WACloud.php app/Config/WACloud.php
```

Atau copy manual:
- Dari: `vendor/wacloud/codeigniter4-wacloud/src/Config/WACloud.php`
- Ke: `app/Config/WACloud.php`

### Load Helper (Opsional)

Jika ingin menggunakan helper functions, tambahkan ke `app/Config/Autoload.php`:

```php
public $helpers = [
    // ... helpers lainnya
    'wacloud', // Tambahkan ini
];
```

Atau load manual di controller:

```php
helper('wacloud');
```

### Register Service (Opsional)

Jika ingin menggunakan service, tambahkan ke `app/Config/Services.php`:

```php
public static function wacloud($getShared = true)
{
    if ($getShared) {
        return static::getSharedInstance('wacloud');
    }

    return new \WACloud\CodeIgniter4WACloud\Libraries\WACloud();
}
```

## ⚙️ Konfigurasi

### 1. Environment Variables

Tambahkan konfigurasi berikut ke file `.env` Anda:

```env
WACLOUD_API_KEY=wacloud_your_api_key_here
WACLOUD_BASE_URL=https://app.wacloud.id/api/v1
WACLOUD_TIMEOUT=30
```

### 2. Config File

Edit file `app/Config/WACloud.php`:

```php
public string $apiKey = 'wacloud_your_api_key_here';
public string $baseUrl = 'https://app.wacloud.id/api/v1';
public int $timeout = 30;
```

### 3. Mendapatkan API Key

1. Daftar/Login ke [Dashboard WACloud](https://app.wacloud.id)
2. Buka menu **API Keys**
3. Klik **Create API Key**
4. Copy API Key dan paste ke file `.env` atau config

## 📖 Penggunaan

### Menggunakan Helper Functions

```php
// Load helper (jika belum di autoload)
helper('wacloud');

// Device Management
$wacloud = wacloud();
$devices = $wacloud->getDevices();
$device = $wacloud->getDevice('device-id');
$newDevice = $wacloud->createDevice('Device Name', '81234567890');
$status = $wacloud->getDeviceStatus('device-id');
$qrCode = $wacloud->getDeviceQrCode('device-id');
$wacloud->deleteDevice('device-id');

// Send Messages
$response = wacloud_send_text('device-id', '6281234567890', 'Hello from WACloud!');
$response = wacloud_send_image('device-id', '6281234567890', 'https://example.com/image.jpg', 'Caption');
$response = wacloud_send_video('device-id', '6281234567890', 'https://example.com/video.mp4', 'Caption');
$response = wacloud_send_document('device-id', '6281234567890', 'https://example.com/doc.pdf', 'document.pdf', 'Caption');
```

### Menggunakan Library Langsung

```php
use WACloud\CodeIgniter4WACloud\Libraries\WACloud;

class MessageController extends BaseController
{
    protected $wacloud;

    public function __construct()
    {
        $this->wacloud = new WACloud();
    }

    public function sendMessage()
    {
        $response = $this->wacloud->sendText(
            'device-id',
            '6281234567890',
            'Hello from WACloud!'
        );

        if ($response['success']) {
            return $this->response->setJSON($response['data']);
        }

        return $this->response->setJSON($response)->setStatusCode(400);
    }
}
```

### Menggunakan Service

```php
// Jika sudah register service di Services.php
$wacloud = service('wacloud');

// Atau menggunakan helper
$wacloud = wacloud();

$response = $wacloud->sendText('device-id', '6281234567890', 'Hello!');
```

### Custom Request

Jika Anda perlu membuat request custom:

```php
$wacloud = wacloud();

// GET request
$response = $wacloud->get('/custom-endpoint', ['param' => 'value']);

// POST request
$response = $wacloud->post('/custom-endpoint', ['data' => 'value']);

// PUT request
$response = $wacloud->put('/custom-endpoint', ['data' => 'value']);

// DELETE request
$response = $wacloud->delete('/custom-endpoint');
```

### Dynamic API Key

Jika Anda perlu menggunakan API key yang berbeda:

```php
$wacloud = wacloud();
$response = $wacloud->setApiKey('different-api-key')
    ->sendText('device-id', '6281234567890', 'Hello!');
```

## 📝 Contoh Lengkap

### Device Management

```php
helper('wacloud');

$wacloud = wacloud();

// Membuat device baru
$response = $wacloud->createDevice('Device Utama', '81234567890');

if ($response['success']) {
    $deviceId = $response['data']['id'];
    echo "Device created: {$deviceId}\n";
    
    // Mendapatkan QR Code untuk pairing
    $qrResponse = $wacloud->getDeviceQrCode($deviceId);
    if ($qrResponse['success']) {
        $qrCode = $qrResponse['data']['qr_code'];
        // Tampilkan QR code di view atau simpan ke file
    }
    
    // Cek status device
    $statusResponse = $wacloud->getDeviceStatus($deviceId);
    if ($statusResponse['success']) {
        $status = $statusResponse['data']['status'];
        echo "Device status: {$status}\n";
    }
}
```

### Mengirim Pesan

```php
helper('wacloud');

// Text Message
$response = wacloud_send_text(
    'device-id',
    '6281234567890',
    'Halo, ini pesan dari WACloud API!'
);

if ($response['success']) {
    $messageId = $response['data']['message_id'];
    echo "Message sent: {$messageId}\n";
}

// Image Message
$response = wacloud_send_image(
    'device-id',
    '6281234567890',
    'https://example.com/image.jpg',
    'Ini adalah caption gambar'
);

// Video Message
$response = wacloud_send_video(
    'device-id',
    '6281234567890',
    'https://example.com/video.mp4',
    'Ini adalah caption video',
    false, // as_note
    false  // convert
);

// Document Message
$response = wacloud_send_document(
    'device-id',
    '6281234567890',
    'https://example.com/document.pdf',
    'document.pdf',
    'Ini adalah caption dokumen'
);
```

### Error Handling

```php
helper('wacloud');

$response = wacloud_send_text('device-id', '6281234567890', 'Hello!');

if (!$response['success']) {
    // Handle error
    $error = $response['error'] ?? 'Unknown error';
    $message = $response['message'] ?? null;
    $httpCode = $response['http_code'] ?? 500;
    
    log_message('error', 'WACloud API Error: ' . json_encode([
        'error' => $error,
        'message' => $message,
        'http_code' => $httpCode,
        'raw' => $response['raw'] ?? null,
    ]));
}
```

### Di Controller

```php
<?php

namespace App\Controllers;

use WACloud\CodeIgniter4WACloud\Libraries\WACloud;

class MessageController extends BaseController
{
    protected $wacloud;

    public function __construct()
    {
        $this->wacloud = new WACloud();
    }

    public function sendText()
    {
        $deviceId = $this->request->getPost('device_id');
        $to = $this->request->getPost('to');
        $text = $this->request->getPost('text');

        $response = $this->wacloud->sendText($deviceId, $to, $text);

        if ($response['success']) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $response['data']
            ]);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => $response['error'] ?? 'Unknown error'
        ])->setStatusCode(400);
    }
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

1. **API Key**: Jangan pernah commit API Key ke repository publik. Gunakan environment variable atau config file yang di-ignore.

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
4. 🐛 Buka issue di [GitHub Repository](https://github.com/wacloud/codeigniter4-wacloud)

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

