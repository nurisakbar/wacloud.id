# 👤 Contacts

Metode untuk mengelola kontak WhatsApp.

## ✨ Fitur

Berikut adalah daftar fitur yang tersedia berdasarkan **🏭 Engines**:

**👤 Contacts - API**

| **API**                                                     | WEBJS | NOWEB             | GOWS |
| ----------------------------------------------------------- | ----- | ----------------- | ---- |
| **Get all contacts**GET /api/contacts/all                   | ✔️    | ✔️[\*1](#heading) | ✔️   |
| **Get contact**GET /api/contacts                            | ✔️    | ✔️[\*1](#heading) | ✔️   |
| **Update contact**PUT /api/{session}/contacts/{chatId}      | ✔️    | ✔️                | ✔️   |
| **Check phone number exists**GET /api/contacts/check-exists | ✔️    | ✔️                | ✔️   |
| **Get "about" contact**GET /api/contacts/about              | ✔️    |                   |      |
| **Get profile picture**GET /api/contacts/profile-picture    | ✔️    | ✔️                | ✔️   |
| **Block contact**POST /api/contacts/block                   | ✔️    |                   |      |
| **Unblock contact**POST /api/contacts/unblock               | ✔️    |                   |      |

**Catatan:** 
1. **NOWEB** - Anda perlu **Enable Store** untuk mendapatkan **chats, contacts dan messages**

**👤 Lids - API**

| **API**                                                             | WEBJS | NOWEB             | GOWS |
| ------------------------------------------------------------------- | ----- | ----------------- | ---- |
| **Get Known LIDs**GET /api/{session}/lids                           | ✔️    | ✔️[\*1](#heading) | ✔️   |
| **Get Count of LIDs**GET /api/{session}/lids/count                  | ✔️    | ✔️[\*1](#heading) | ✔️   |
| **Get Phone Number by LID**GET /api/{session}/lids/{lid}           | ✔️    | ✔️[\*1](#heading) | ✔️   |
| **Get LID by Phone Number**GET /api/{session}/lids/pn/{phoneNumber} | ✔️    | ✔️[\*1](#heading) | ✔️   |

## 📋 @lid dan @c.us

WhatsApp menggunakan dua format ID untuk kontak:

- **@c.us** - Format standar untuk nomor telepon (contoh: `123123123@c.us`)
- **@lid** - Linked ID yang digunakan untuk menyembunyikan nomor telepon di grup publik dan tempat lain

## 📝 API Endpoints

### Get All Contacts

Mendapatkan semua kontak untuk session dengan pagination.

```http
GET /api/v1/devices/{session}/contacts
```

**Query Parameters:**

- `limit` (optional, default: 100) - Jumlah kontak yang dikembalikan
- `offset` (optional, default: 0) - Offset untuk pagination
- `sortBy` (optional, default: 'id') - Field untuk sorting (`id` atau `name`)
- `sortOrder` (optional, default: 'asc') - Urutan sorting (`asc` atau `desc`)

**Response:**

```json
[
  {
    "id": "11231231231@c.us",
    "number": "11231231231",
    "name": "Contact Name",
    "pushname": "Pushname",
    "shortName": "Shortname",
    "isMe": true,
    "isGroup": false,
    "isWAContact": true,
    "isMyContact": true,
    "isBlocked": false
  }
]
```

**Contoh Request:**

```bash
curl -X GET "http://localhost:8000/api/v1/devices/default/contacts?limit=100&offset=0&sortBy=name&sortOrder=asc" \
  -H "X-Api-Key: your-api-key"
```

### Get Contact

Mendapatkan informasi kontak tertentu.

```http
GET /api/v1/devices/{session}/contacts/{contactId}
```

**Path Parameters:**

- `session` - Session ID (contoh: `default`)
- `contactId` - Contact ID bisa berupa nomor telepon (`123123123`) atau chat ID (`123123@c.us` atau `123123@lid`)

**Response:**

```json
{
  "id": "11231231231@c.us",
  "number": "11231231231",
  "name": "Contact Name",
  "pushname": "Pushname",
  "shortName": "Shortname",
  "isMe": true,
  "isGroup": false,
  "isWAContact": true,
  "isMyContact": true,
  "isBlocked": false
}
```

### Update Contact

Memperbarui informasi kontak di **buku alamat telepon** (dan di WhatsApp).

```http
PUT /api/v1/devices/{session}/contacts/{chatId}
```

**Path Parameters:**

- `session` - Session ID (contoh: `default`)
- `chatId` - Chat ID bisa berakhiran `@c.us` atau `@lid`, atau hanya nomor telepon (`12132132130`)

**Body:**

```json
{
  "firstName": "John",
  "lastName": "Doe"
}
```

**Catatan Penting:**

- Jika Anda memiliki beberapa aplikasi **WhatsApp** yang terpasang di telepon, API mungkin hanya bekerja dengan satu akun.
- Anda mungkin perlu membuat **beberapa request API** dengan parameter yang sama dan menunggu **beberapa detik** di antara request untuk memperbarui **buku alamat telepon**.

### Check Phone Number Exists

Memeriksa apakah nomor telepon terdaftar di WhatsApp (bahkan jika nomor tidak ada di daftar kontak Anda).

```http
GET /api/v1/devices/{session}/contacts/check-exists?phone=11231231231
```

**Query Parameters:**

- `phone` - Nomor telepon yang akan dicek (contoh: `11231231231`)

**Response:**

```json
{
  "numberExists": true,
  "chatId": "123123123@c.us"
}
```

**Catatan untuk Nomor Telepon Brasil 🇧🇷**

Anda harus menggunakan endpoint `GET /api/contacts/check-exists` **sebelum mengirim pesan ke nomor telepon baru** untuk mendapatkan chatId yang benar karena penambahan 9 digit setelah tahun 2012.

### Get Contact "About"

Mendapatkan informasi "about" dari kontak.

```http
GET /api/v1/devices/{session}/contacts/{contactId}/about
```

**Path Parameters:**

- `session` - Session ID
- `contactId` - Contact ID bisa berupa nomor telepon (`123123123`) atau chat ID (`123123@c.us` atau `123123@lid`)

**Response:**

```json
{
  "about": "Hi, I use WhatsApp!"
}
```

### Get Contact Profile Picture

Mendapatkan foto profil kontak.

```http
GET /api/v1/devices/{session}/contacts/{contactId}/profile-picture?refresh=false
```

**Query Parameters:**

- `refresh` (optional, default: false) - Paksa refresh gambar. Secara default, kita cache selama 24 jam. Jangan terlalu sering refresh untuk menghindari error `rate-overlimit`.

**Response:**

```json
{
  "profilePictureURL": "https://example.com/profile.jpg"
}
```

### Block Contact

Memblokir kontak.

```http
POST /api/v1/devices/{session}/contacts/{contactId}/block
```

**Path Parameters:**

- `session` - Session ID
- `contactId` - Contact ID

**Response:**

```json
{
  "success": true,
  "data": {}
}
```

### Unblock Contact

Membuka blokir kontak.

```http
POST /api/v1/devices/{session}/contacts/{contactId}/unblock
```

**Path Parameters:**

- `session` - Session ID
- `contactId` - Contact ID

**Response:**

```json
{
  "success": true,
  "data": {}
}
```

## 🔗 API - LIDs (Linked IDs)

WhatsApp menggunakan identifier **Linked ID** (`lid`) untuk menyembunyikan nomor telepon pengguna (`pn`) dari grup publik dan tempat lain.

API di bawah ini dapat Anda gunakan untuk memetakan identifier terhubung (`@lid`) ke nomor telepon kontak (`@c.us`).

### Get All Known LIDs

Mendapatkan semua mapping LID ke nomor telepon untuk session.

```http
GET /api/v1/devices/{session}/lids
```

**Query Parameters:**

- `limit` (optional, default: 100) - Jumlah record yang dikembalikan
- `offset` (optional, default: 0) - Offset untuk pagination

**Response:**

```json
[
  {
    "lid": "123123123@lid",
    "pn": "123456789@c.us"
  }
]
```

**Catatan:** Panggil **Get all groups** atau **Refresh groups** untuk mengisi mapping lid ke nomor telepon untuk semua grup.

### Get Count of LIDs

Mendapatkan jumlah mapping LID yang diketahui untuk session.

```http
GET /api/v1/devices/{session}/lids/count
```

**Response:**

```json
{
  "count": 123
}
```

### Get Phone Number by LID

Mendapatkan nomor telepon yang terkait dengan LID tertentu.

```http
GET /api/v1/devices/{session}/lids/{lid}
```

**Path Parameters:**

- `session` - Session ID
- `lid` - LID (contoh: `123123123@lid` atau `123123123`)

👉 Ingat untuk escape `@` di `lid` dengan `%40` (`123123%40lid`) atau gunakan hanya nomor (`123123`)

**Response (Found):**

```json
{
  "lid": "123123123@lid",
  "pn": "123456789@c.us"
}
```

**Response (Not Found):**

```json
{
  "lid": "123123123@lid",
  "pn": null
}
```

### Get LID by Phone Number

Mendapatkan LID untuk nomor telepon tertentu (chat ID).

```http
GET /api/v1/devices/{session}/lids/phone/{phoneNumber}
```

**Path Parameters:**

- `session` - Session ID
- `phoneNumber` - Nomor telepon atau chat ID (contoh: `123456789@c.us` atau `123456789`)

👉 Ingat untuk escape `@` di `phoneNumber` dengan `%40` (contoh: `123123%40c.us`) atau gunakan hanya nomor (`123123`)

**Response (Found):**

```json
{
  "lid": "123123123@lid",
  "pn": "123456789@c.us"
}
```

**Response (Not Found):**

```json
{
  "lid": null,
  "pn": "123456789@c.us"
}
```

## ❓ LIDs FAQ

- Jika Anda **tidak menemukan nomor telepon berdasarkan lid** - Anda tidak memiliki nomor telepon di daftar kontak atau Anda bukan **admin** di grup.
- Untuk **👥 Groups** - coba **Refresh groups** jika Anda tidak menemukan `lid` tetapi Anda adalah **admin** di grup.

👉 Jika tidak ada yang membantu, dan **Anda melihat nomor telepon untuk peserta di aplikasi telepon Anda** - silakan **buka issue** dan beri tahu **🏭 Engine** apa yang Anda gunakan dan perilaku apa yang Anda lihat.

## 📚 Contoh Penggunaan

### Python

```python
import requests

base_url = "http://localhost:8000/api/v1"
api_key = "your-api-key"
session = "default"

headers = {
    "X-Api-Key": api_key
}

# Get all contacts
response = requests.get(
    f"{base_url}/devices/{session}/contacts",
    headers=headers,
    params={"limit": 100, "offset": 0}
)
contacts = response.json()

# Get single contact
contact_id = "123123123@c.us"
response = requests.get(
    f"{base_url}/devices/{session}/contacts/{contact_id}",
    headers=headers
)
contact = response.json()

# Check phone exists
response = requests.get(
    f"{base_url}/devices/{session}/contacts/check-exists",
    headers=headers,
    params={"phone": "123123123"}
)
exists = response.json()

# Update contact
response = requests.put(
    f"{base_url}/devices/{session}/contacts/{contact_id}",
    headers=headers,
    json={"firstName": "John", "lastName": "Doe"}
)
```

### JavaScript

```javascript
const axios = require('axios');

const baseUrl = "http://localhost:8000/api/v1";
const apiKey = "your-api-key";
const session = "default";

const headers = {
    'X-Api-Key': apiKey
};

// Get all contacts
axios.get(`${baseUrl}/devices/${session}/contacts`, {
    headers,
    params: { limit: 100, offset: 0 }
})
.then(response => console.log(response.data))
.catch(error => console.error(error));

// Get single contact
const contactId = "123123123@c.us";
axios.get(`${baseUrl}/devices/${session}/contacts/${contactId}`, { headers })
    .then(response => console.log(response.data))
    .catch(error => console.error(error));

// Check phone exists
axios.get(`${baseUrl}/devices/${session}/contacts/check-exists`, {
    headers,
    params: { phone: "123123123" }
})
.then(response => console.log(response.data))
.catch(error => console.error(error));
```

### PHP

```php
<?php
$baseUrl = "http://localhost:8000/api/v1";
$apiKey = "your-api-key";
$session = "default";

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey
]);

// Get all contacts
curl_setopt($ch, CURLOPT_URL, "$baseUrl/devices/$session/contacts?limit=100&offset=0");
$response = curl_exec($ch);
$contacts = json_decode($response, true);

// Get single contact
$contactId = "123123123@c.us";
curl_setopt($ch, CURLOPT_URL, "$baseUrl/devices/$session/contacts/$contactId");
$response = curl_exec($ch);
$contact = json_decode($response, true);

// Check phone exists
curl_setopt($ch, CURLOPT_URL, "$baseUrl/devices/$session/contacts/check-exists?phone=123123123");
$response = curl_exec($ch);
$exists = json_decode($response, true);

curl_close($ch);
?>
```

## 📚 Referensi

- [WAHA Documentation](https://waha.devlike.pro/)
- [Contacts Documentation](https://waha.devlike.pro/docs/how-to/contacts/)
- [WAHA Engines](https://waha.devlike.pro/docs/engines/)

---

**Last Updated:** 2025-01-27

