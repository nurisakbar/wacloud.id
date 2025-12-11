# Contoh Kode Python untuk WACloud API

<div align="center">
  <img src="../../landing-page/logo-wacloud.png" alt="WACloud Logo" width="200">
</div>

Koleksi contoh kode Python untuk mengintegrasikan WACloud API ke aplikasi Anda.

## 🌐 Informasi

- **Website**: [https://wacloud.id](https://wacloud.id)
- **Dashboard**: [https://app.wacloud.id](https://app.wacloud.id)
- **Dokumentasi**: [https://wacloud.id/docs.html](https://wacloud.id/docs.html)
- **YouTube**: [Channel WACloud](https://www.youtube.com/@wacloudid) - Tutorial dan panduan lengkap

## 📋 Daftar Contoh

1. **device_management.py** - Mengelola device/session WhatsApp
   - Membuat device baru
   - Mendapatkan QR code untuk pairing
   - Mengecek status device
   - Mendapatkan daftar device

2. **send_text_message.py** - Mengirim pesan text
   - Mengirim pesan teks biasa

3. **send_image_message.py** - Mengirim pesan gambar
   - Mengirim gambar dengan caption

4. **send_document_message.py** - Mengirim pesan dokumen
   - Mengirim file PDF, DOC, dll

5. **send_video_message.py** - Mengirim pesan video
   - Mengirim video dengan caption
   - Opsi untuk mengirim sebagai video note

## 🚀 Cara Menggunakan

### 1. Installasi

Install dependencies menggunakan pip:

```bash
pip install -r requirements.txt
```

Atau menggunakan pip3:

```bash
pip3 install -r requirements.txt
```

### 2. Konfigurasi

1. Copy file `config.example.py` menjadi `config.py`:
   ```bash
   cp config.example.py config.py
   ```

2. Buka file `config.py` dan ganti `YOUR_API_KEY` dengan API Key Anda dari dashboard

3. Pastikan `BASE_URL` sudah benar (default: `https://app.wacloud.id/api/v1`)

```python
API_KEY = 'wacloud_your_actual_api_key_here'
```

### 3. Menjalankan Contoh

Jalankan file Python yang ingin Anda coba:

```bash
# Menggunakan Python 3
python3 device_management.py
python3 send_text_message.py
python3 send_image_message.py
python3 send_document_message.py
python3 send_video_message.py

# Atau menggunakan Python
python device_management.py
```

## 📝 Contoh Penggunaan

### Device Management

```python
from config import make_request, display_response

# Membuat device baru
device_data = {
    'name': 'Device Utama',
    'phone_number': '81234567890'
}

response = make_request('POST', '/devices', device_data)

if response.get('success'):
    device_id = response['data']['id']
    print(f"Device created: {device_id}")
```

### Mengirim Pesan Text

```python
from config import make_request

message_data = {
    'device_id': '550e8400-e29b-41d4-a716-446655440000',
    'to': '6281234567890',
    'message_type': 'text',
    'text': 'Halo dari API!'
}

response = make_request('POST', '/messages', message_data)

if response.get('success'):
    print(f"Message sent: {response['data']['message_id']}")
```

### Mengirim Pesan Gambar

```python
from config import make_request

message_data = {
    'device_id': '550e8400-e29b-41d4-a716-446655440000',
    'to': '6281234567890',
    'message_type': 'image',
    'image_url': 'https://example.com/image.jpg',
    'caption': 'Caption gambar'
}

response = make_request('POST', '/messages', message_data)
```

## ⚠️ Catatan Penting

1. **API Key**: Jangan pernah commit API Key ke repository publik. File `config.py` sudah di-ignore oleh Git.

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

6. **Python Version**: 
   - Contoh kode ini menggunakan Python 3.6+
   - Pastikan Python versi terbaru terinstall

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

