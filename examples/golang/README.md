# Contoh Kode Golang untuk WACloud API

<div align="center">
  <img src="../../landing-page/logo-wacloud.png" alt="WACloud Logo" width="200">
</div>

Koleksi contoh kode Golang untuk mengintegrasikan WACloud API ke aplikasi Anda.

## 🌐 Informasi

- **Website**: [https://wacloud.id](https://wacloud.id)
- **Dashboard**: [https://app.wacloud.id](https://app.wacloud.id)
- **Dokumentasi**: [https://wacloud.id/docs.html](https://wacloud.id/docs.html)
- **YouTube**: [Channel WACloud](https://www.youtube.com/@wacloudid) - Tutorial dan panduan lengkap

## 📋 Daftar Contoh

1. **device_management.go** - Mengelola device/session WhatsApp
   - Membuat device baru
   - Mendapatkan QR code untuk pairing
   - Mengecek status device
   - Mendapatkan daftar device

2. **send_text_message.go** - Mengirim pesan text
   - Mengirim pesan teks biasa

3. **send_image_message.go** - Mengirim pesan gambar
   - Mengirim gambar dengan caption

4. **send_document_message.go** - Mengirim pesan dokumen
   - Mengirim file PDF, DOC, dll

5. **send_video_message.go** - Mengirim pesan video
   - Mengirim video dengan caption
   - Opsi untuk mengirim sebagai video note

## 🚀 Cara Menggunakan

### 1. Installasi

Pastikan Go sudah terinstall (versi 1.21 atau lebih baru):

```bash
go version
```

Install dependencies:

```bash
go mod download
```

### 2. Konfigurasi

1. Copy file `config.example.go` menjadi `config.go`:
   ```bash
   cp config.example.go config.go
   ```

2. Buka file `config.go` dan ganti `YOUR_API_KEY` dengan API Key Anda dari dashboard

3. Pastikan `BASE_URL` sudah benar (default: `https://app.wacloud.id/api/v1`)

```go
const (
    API_KEY = "wacloud_your_actual_api_key_here"
    BASE_URL = "https://app.wacloud.id/api/v1"
)
```

### 3. Menjalankan Contoh

Jalankan file Go yang ingin Anda coba:

```bash
# Device management
go run device_management.go config.go

# Send text message
go run send_text_message.go config.go

# Send image message
go run send_image_message.go config.go

# Send document message
go run send_document_message.go config.go

# Send video message
go run send_video_message.go config.go
```

Atau build terlebih dahulu:

```bash
go build -o device-management device_management.go config.go
./device-management
```

## 📝 Contoh Penggunaan

### Device Management

```go
package main

import "fmt"

func main() {
    deviceData := map[string]interface{}{
        "name": "Device Utama",
        "phone_number": "81234567890",
    }

    response, err := MakeRequest("POST", "/devices", deviceData)
    if err != nil {
        fmt.Printf("Error: %v\n", err)
        return
    }

    if response.Success {
        if deviceID, ok := response.Data["id"].(string); ok {
            fmt.Printf("Device created: %s\n", deviceID)
        }
    }
}
```

### Mengirim Pesan Text

```go
package main

func main() {
    messageData := map[string]interface{}{
        "device_id": "550e8400-e29b-41d4-a716-446655440000",
        "to": "6281234567890",
        "message_type": "text",
        "text": "Halo dari API!",
    }

    response, err := MakeRequest("POST", "/messages", messageData)
    if err != nil {
        fmt.Printf("Error: %v\n", err)
        return
    }

    if response.Success {
        if messageID, ok := response.Data["message_id"].(string); ok {
            fmt.Printf("Message sent: %s\n", messageID)
        }
    }
}
```

### Mengirim Pesan Gambar

```go
package main

func main() {
    messageData := map[string]interface{}{
        "device_id": "550e8400-e29b-41d4-a716-446655440000",
        "to": "6281234567890",
        "message_type": "image",
        "image_url": "https://example.com/image.jpg",
        "caption": "Caption gambar",
    }

    response, err := MakeRequest("POST", "/messages", messageData)
    if err != nil {
        fmt.Printf("Error: %v\n", err)
        return
    }
}
```

## ⚠️ Catatan Penting

1. **API Key**: Jangan pernah commit API Key ke repository publik. File `config.go` sudah di-ignore oleh Git.

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

6. **Go Version**: 
   - Contoh kode ini menggunakan Go 1.21+
   - Pastikan Go versi terbaru terinstall

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

