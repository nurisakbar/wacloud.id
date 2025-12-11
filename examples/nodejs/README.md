# Contoh Kode Node.js untuk WACloud API

<div align="center">
  <img src="../../landing-page/logo-wacloud.png" alt="WACloud Logo" width="200">
</div>

Koleksi contoh kode Node.js untuk mengintegrasikan WACloud API ke aplikasi Anda.

## 🌐 Informasi

- **Website**: [https://wacloud.id](https://wacloud.id)
- **Dashboard**: [https://app.wacloud.id](https://app.wacloud.id)
- **Dokumentasi**: [https://wacloud.id/docs.html](https://wacloud.id/docs.html)
- **YouTube**: [Channel WACloud](https://www.youtube.com/@wacloudid) - Tutorial dan panduan lengkap

## 📋 Daftar Contoh

1. **device-management.js** - Mengelola device/session WhatsApp
   - Membuat device baru
   - Mendapatkan QR code untuk pairing
   - Mengecek status device
   - Mendapatkan daftar device

2. **send-text-message.js** - Mengirim pesan text
   - Mengirim pesan teks biasa

3. **send-image-message.js** - Mengirim pesan gambar
   - Mengirim gambar dengan caption

4. **send-document-message.js** - Mengirim pesan dokumen
   - Mengirim file PDF, DOC, dll

5. **send-video-message.js** - Mengirim pesan video
   - Mengirim video dengan caption
   - Opsi untuk mengirim sebagai video note

## 🚀 Cara Menggunakan

### 1. Installasi

Install dependencies menggunakan npm:

```bash
npm install
```

Atau menggunakan yarn:

```bash
yarn install
```

### 2. Konfigurasi

1. Copy file `config.example.js` menjadi `config.js`:
   ```bash
   cp config.example.js config.js
   ```

2. Buka file `config.js` dan ganti `YOUR_API_KEY` dengan API Key Anda dari dashboard

3. Pastikan `BASE_URL` sudah benar (default: `https://app.wacloud.id/api/v1`)

```javascript
const API_KEY = 'wacloud_your_actual_api_key_here';
```

### 3. Menjalankan Contoh

Jalankan file JavaScript yang ingin Anda coba:

```bash
# Menggunakan npm scripts
npm run device
npm run text
npm run image
npm run document
npm run video

# Atau langsung dengan node
node device-management.js
node send-text-message.js
node send-image-message.js
```

## 📝 Contoh Penggunaan

### Device Management

```javascript
const { makeRequest, displayResponse } = require('./config');

// Membuat device baru
const deviceData = {
    name: 'Device Utama',
    phone_number: '81234567890'
};

const response = await makeRequest('POST', '/devices', deviceData);

if (response.success) {
    const deviceId = response.data.id;
    console.log(`Device created: ${deviceId}`);
}
```

### Mengirim Pesan Text

```javascript
const { makeRequest } = require('./config');

const messageData = {
    device_id: '550e8400-e29b-41d4-a716-446655440000',
    to: '6281234567890',
    message_type: 'text',
    text: 'Halo dari API!'
};

const response = await makeRequest('POST', '/messages', messageData);

if (response.success) {
    console.log(`Message sent: ${response.data.message_id}`);
}
```

### Mengirim Pesan Gambar

```javascript
const { makeRequest } = require('./config');

const messageData = {
    device_id: '550e8400-e29b-41d4-a716-446655440000',
    to: '6281234567890',
    message_type: 'image',
    image_url: 'https://example.com/image.jpg',
    caption: 'Caption gambar'
};

const response = await makeRequest('POST', '/messages', messageData);
```

## ⚠️ Catatan Penting

1. **API Key**: Jangan pernah commit API Key ke repository publik. File `config.js` sudah di-ignore oleh Git.

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
- **YouTube**: [Channel WACloud](https://www.youtube.com/@wacloudid) - Tutorial dan panduan lengkap
- **Postman Collection**: [https://wacloud.id/docs.html#postman](https://wacloud.id/docs.html#postman)

## 📞 Support

Jika Anda mengalami masalah atau memiliki pertanyaan:

1. 📖 Cek dokumentasi lengkap di [docs.html](https://wacloud.id/docs.html)
2. 🎥 Tonton tutorial di [YouTube Channel](https://www.youtube.com/@wacloudid)
3. 💬 Hubungi support melalui [Dashboard](https://app.wacloud.id)
4. 🐛 Buka issue di GitHub repository ini

## 📄 License

Contoh kode ini tersedia untuk digunakan sebagai referensi. Silakan modifikasi sesuai kebutuhan Anda.

