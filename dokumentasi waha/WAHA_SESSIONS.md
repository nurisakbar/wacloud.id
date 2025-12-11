# 🖥️ Sessions

**Session** mewakili **Akun WhatsApp (Nomor Telepon)** yang terhubung ke **WAHA** yang dapat Anda gunakan untuk mengirim dan menerima pesan.

## ✨ Fitur

Berikut adalah daftar fitur yang tersedia berdasarkan **🏭 Engines**:

**🖥️ Sessions - API**

| **API**                                               | WEBJS | NOWEB | GOWS |
| ----------------------------------------------------- | ----- | ----- | ---- |
| **List sessions**GET /api/sessions/                   | ✔️    | ✔️    | ✔️   |
| **Get session**GET /api/sessions/{name}               | ✔️    | ✔️    | ✔️   |
| **Create session**POST /api/sessions/                 | ✔️    | ✔️    | ✔️   |
| **Update session**POST /api/sessions/{name}/          | ✔️    | ✔️    | ✔️   |
| **Delete session**DELETE /api/sessions/{name}/        | ✔️    | ✔️    | ✔️   |
| **Start session**POST /api/sessions/{name}/start      | ✔️    | ✔️    | ✔️   |
| **Stop session**POST /api/sessions/{name}/stop        | ✔️    | ✔️    | ✔️   |
| **Restart session**POST /api/sessions/{name}/restart  | ✔️    | ✔️    | ✔️   |
| **Logout from a session**POST /api/sessions/logout    | ✔️    | ✔️    | ✔️   |
| **Get screenshot**GET /api/screenshot                 | ✔️    | ➖     | ➖    |
| **Get me**GET /api/sessions/{session}/me              | ✔️    | ✔️    | ✔️   |
| **Get QR**POST /api/{session}/auth/qr                 | ✔️    | ✔️    | ✔️   |
| **Request code**POST /api/{session}/auth/request-code | ✔️    | ✔️    | ✔️   |

## 🔄 Session Lifecycle

### Session Status

Status session dapat berubah melalui siklus berikut:

1. **STOPPED** - Session berhenti
2. **STARTING** - Session sedang dimulai
3. **SCAN_QR_CODE** - Menunggu scan QR code
4. **WORKING** - Session berjalan dan siap digunakan
5. **FAILED** - Session gagal

## ➕ Membuat Session

Untuk membuat session baru, gunakan endpoint berikut:

```http
POST /api/sessions/
```

### Body Request

```json
{
  "name": "default",
  "config": {
    "metadata": {},
    "webhooks": [],
    "ignore": [],
    "proxy": null,
    "apps": [],
    "debug": false
  }
}
```

### Postpone Start

Secara default, session akan langsung dimulai setelah dibuat. Jika Anda ingin membuat session tanpa langsung memulainya, tambahkan parameter `start: false`:

```json
{
  "name": "default",
  "start": false,
  "config": {
    "metadata": {},
    "webhooks": [],
    "ignore": [],
    "proxy": null,
    "apps": [],
    "debug": false
  }
}
```

## ⚙️ Konfigurasi Session

### Metadata

Metadata adalah data tambahan yang dapat Anda simpan untuk session. Ini berguna untuk menyimpan informasi seperti user ID, nama, dll.

```json
{
  "config": {
    "metadata": {
      "userId": "123",
      "userName": "John Doe"
    }
  }
}
```

### Webhooks

Konfigurasi webhook untuk menerima event dari WAHA:

```json
{
  "config": {
    "webhooks": [
      {
        "url": "https://webhook.site/11111111-1111-1111-1111-11111111",
        "events": ["message", "message.any"]
      }
    ]
  }
}
```

### Ignore

Daftar nomor telepon atau chat ID yang akan diabaikan:

```json
{
  "config": {
    "ignore": [
      "79111111111@c.us",
      "79111111112@c.us"
    ]
  }
}
```

### Proxy

Konfigurasi proxy untuk session:

```json
{
  "config": {
    "proxy": {
      "server": "socks5://127.0.0.1:9050",
      "username": "user",
      "password": "pass"
    }
  }
}
```

### Apps

Konfigurasi aplikasi tambahan:

```json
{
  "config": {
    "apps": [
      {
        "name": "chatwoot"
      }
    ]
  }
}
```

### WEBJS

Konfigurasi khusus untuk engine WEBJS:

```json
{
  "config": {
    "webjs": {
      "headless": true,
      "browserArgs": []
    }
  }
}
```

### NOWEB

Konfigurasi khusus untuk engine NOWEB:

```json
{
  "config": {
    "noweb": {
      "apiKey": "your-api-key"
    }
  }
}
```

### Debug

Aktifkan mode debug untuk session:

```json
{
  "config": {
    "debug": true
  }
}
```

## 🔄 Update Session

Untuk memperbarui konfigurasi session:

```http
POST /api/sessions/{name}/
```

Body sama seperti saat membuat session.

## ▶️ Start Session

Untuk memulai session:

```http
POST /api/sessions/{name}/start
```

## ⏹️ Stop Session

Untuk menghentikan session:

```http
POST /api/sessions/{name}/stop
```

## 🔁 Restart Session

Untuk me-restart session:

```http
POST /api/sessions/{name}/restart
```

## 🚪 Logout Session

Untuk logout dari session (menghapus autentikasi):

```http
POST /api/sessions/{name}/logout
```

## 🗑️ Delete Session

Untuk menghapus session:

```http
DELETE /api/sessions/{name}/
```

## 📋 List Sessions

Untuk mendapatkan daftar semua session:

```http
GET /api/sessions/
```

Response:

```json
[
  {
    "name": "default",
    "status": "WORKING",
    "config": {
      "metadata": {},
      "webhooks": [],
      "ignore": [],
      "proxy": null,
      "apps": [],
      "debug": false
    }
  }
]
```

## 📄 Get Session

Untuk mendapatkan informasi session tertentu:

```http
GET /api/sessions/{name}
```

## 📸 Get Screenshot

Untuk mengambil screenshot dari session (hanya untuk WEBJS):

```http
GET /api/sessions/{name}/screenshot
```

### Binary

Format default, Anda akan mendapatkan gambar dalam response:

```bash
GET /api/sessions/{name}/screenshot
```

### Base64

Untuk mendapatkan gambar dalam format base64, set header `Accept: application/json`:

```http
GET /api/sessions/{name}/screenshot
Accept: application/json
```

Response:

```json
{
  "mimetype": "image/png",
  "data": "base64-encoded-data"
}
```

## 👤 Get Me

Untuk mendapatkan informasi akun WhatsApp yang sedang digunakan:

```http
GET /api/sessions/{name}/me
```

Response:

```json
{
  "id": "7911111@c.us",
  "pushName": "John Doe",
  "number": "7911111",
  "isBusiness": false
}
```

## 📱 Get QR Code

Untuk mendapatkan QR code untuk autentikasi session:

```http
GET /api/sessions/{name}/auth/qr
```

Anda dapat mendapatkan QR dalam format yang berbeda:

1. **binary image** - `GET /api/{session}/auth/qr`
2. **base64 image** - `GET /api/{session}/auth/qr` dan set header `Accept: application/json`
3. **raw** - `GET /api/{session}/auth/qr?format=raw`

### Binary

Format default, Anda akan mendapatkan gambar dalam response:

```bash
GET /api/{session}/auth/qr

# ATAU
GET /api/{session}/auth/qr?format=image

# ATAU dengan header Accept
GET /api/{session}/auth/qr?format=image
Accept: image/png
```

### Base64

Untuk mendapatkan gambar dalam format base64, set header `Accept: application/json`:

```http
GET /api/{session}/auth/qr?format=image
Accept: application/json
```

Response:

```json
{
  "mimetype": "image/png",
  "data": "base64-encoded-data"
}
```

### Raw

Untuk mendapatkan data mentah yang dapat Anda gunakan untuk generate QR code sendiri:

```http
GET /api/{session}/auth/qr?format=raw
```

Response:

```json
{
  "value": "value-that-you-need-to-use-to-generate-qr-code"
}
```

## 🔢 Get Pairing Code

Lihat daftar engines **yang mendukung fitur ini ->**.

Anda dapat menghubungkan session dengan nomor telepon - buat request ke endpoint:

```http
POST /api/{session}/auth/request-code
```

Body example:

```json
{
  "phoneNumber": "12132132130"
}
```

Anda akan mendapatkan kode yang perlu dimasukkan di **aplikasi WhatsApp** untuk mengautentikasi session:

Response:

```json
{
  "code": "ABCD-ABCD"
}
```

👉 **Selalu** tambahkan ke **alur autentikasi QR code** di aplikasi Anda sebagai fallback, karena pairing code tidak selalu tersedia dan bekerja seperti yang diharapkan.

### Contoh Penggunaan

#### cURL

```bash
curl -X 'POST' \
  'http://localhost:3000/api/default/auth/request-code' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Api-Key: yoursecretkey' \
  -d '{
  "phoneNumber": "12132132130"
}'
```

#### Python

```python
import requests

url = "http://localhost:3000/api/default/auth/request-code"
headers = {
    "Content-Type": "application/json",
    "X-Api-Key": "yoursecretkey"
}
data = {
    "phoneNumber": "12132132130"
}

response = requests.post(url, json=data, headers=headers)
print(response.json())
```

#### JavaScript

```javascript
const axios = require('axios');

const url = "http://localhost:3000/api/default/auth/request-code";
const data = {
    phoneNumber: "12132132130"
};
const headers = {
    'Content-Type': 'application/json',
    'X-Api-Key': 'yoursecretkey'
};

axios.post(url, data, { headers })
    .then(response => console.log(response.data))
    .catch(error => console.error(error));
```

#### PHP

```php
<?php
$url = "http://localhost:3000/api/default/auth/request-code";
$data = [
    "phoneNumber" => "12132132130"
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Api-Key: yoursecretkey'
]);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
```

## 🔔 Events

Baca lebih lanjut tentang **🔄 Events**.

### session.status

Event `session.status` dipicu ketika status session berubah.

```json
{
  "event": "session.status",
  "session": "default",
  "me": {
    "id": "7911111@c.us",
    "pushName": "~"
  },
  "payload": {
    "status": "WORKING",
    "statuses": [ 
        {
            "status": "STOPPED",
            "timestamp": 1700000001000
        },
        {
            "status": "STARTING",
            "timestamp": 1700000002000
        },
        {
            "status": "WORKING",
            "timestamp": 1700000003000
        }
    ]
  },
  "engine": "WEBJS",
  "environment": {
    "version": "2023.10.12",
    "engine": "WEBJS",
    "tier": "PLUS"
  }
}
```

### engine.event

Event internal yang dipicu ketika engine mengeluarkan event.

```json
{
  "id": "evt_11111111111111111111111111",
  "session": "default",
  "event": "engine.event",
  "payload": {
    "session": "default",
    "event": "{{engine.EventName}}",
    "data": {
      "field": "value"
    }
  },
  "timestamp": 1742102571277,
  "metadata": {},
  "me": {
    "": null
  },
  "environment": {
    "": null
  }
}
```

## 🚀 Advanced Sessions

Dengan versi WAHA Plus, Anda dapat menyimpan state session untuk menghindari scan QR code setiap kali, mengkonfigurasi opsi autostart sehingga ketika docker container restart - itu akan memulihkan semua session yang sebelumnya berjalan!

### Session Persistent

Jika Anda ingin menyimpan session dan tidak perlu scan QR code setiap kali saat menjalankan WAHA - hubungkan session storage ke container ->

### Autostart

Secara default, WAHA melacak session mana yang telah berjalan di worker mana dan me-restart-nya ketika worker di-restart. Jika Anda ingin menonaktifkannya - set `WAHA_WORKER_RESTART_SESSIONS=False` di environment variable.

### Multiple Sessions

Jika Anda ingin menghemat CPU dan Memory server - jalankan multiple sessions dalam satu docker container! Versi Plus mendukung multiple sessions dalam satu container.

## ⚠️ DEPRECATED

Sebelum API granular baru, kami memiliki API sederhana untuk mengontrol session.

**Silakan beralih ke API baru** yang memungkinkan Anda mengontrol session **dengan cara yang lebih fleksibel**.

### Start

Endpoint ini akan **Membuat** (jika tidak ada), **Memperbarui** (jika sudah ada sebelumnya) dan **Memulai** session baru.

```http
POST /api/sessions/start
```

Menerima konfigurasi yang sama seperti API Create dan Update.

Body:

```json
{
  "name": "default",
  "config": {
    "webhooks": [
      {
        "url": "https://webhook.site/11111111-1111-1111-1111-11111111",
        "events": [
          "message"
        ]
      }
    ]
  }
}
```

### Stop

```http
POST /api/sessions/stop
```

* **Stop** jika `logout: false`
* **Stop**, **Logout** dan **Delete** session jika `logout: true`

Body:

```json
{
  "name": "default",
  "logout": true
}
```

### Logout

**Logout** dan **Delete** session.

```http
POST /api/sessions/logout
```

Body:

```json
{
  "name": "default"
}
```

---

## 📚 Referensi

- [WAHA Documentation](https://waha.devlike.pro/)
- [Sessions Documentation](https://waha.devlike.pro/docs/how-to/sessions/)
- [WAHA Engines](https://waha.devlike.pro/docs/engines/)
- [WAHA Events](https://waha.devlike.pro/docs/how-to/events/)

---

## 🔗 API Endpoints Summary

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/sessions/` | List semua session |
| GET | `/api/sessions/{name}` | Get info session |
| POST | `/api/sessions/` | Create session baru |
| POST | `/api/sessions/{name}/` | Update session |
| DELETE | `/api/sessions/{name}/` | Delete session |
| POST | `/api/sessions/{name}/start` | Start session |
| POST | `/api/sessions/{name}/stop` | Stop session |
| POST | `/api/sessions/{name}/restart` | Restart session |
| POST | `/api/sessions/{name}/logout` | Logout session |
| GET | `/api/sessions/{name}/screenshot` | Get screenshot |
| GET | `/api/sessions/{name}/me` | Get info akun |
| GET | `/api/{session}/auth/qr` | Get QR code |
| POST | `/api/{session}/auth/request-code` | Request pairing code |

---

**Last Updated:** 2025-01-27

