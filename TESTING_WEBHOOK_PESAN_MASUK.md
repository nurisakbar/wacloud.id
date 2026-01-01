# 🧪 Panduan Testing Webhook - Pesan Masuk

Panduan praktis untuk melakukan testing webhook ketika ada pesan masuk ke WhatsApp.

## 🎯 Tujuan

Menguji apakah webhook berfungsi dengan baik ketika ada pesan masuk ke device WhatsApp yang terhubung.

---

## 📋 Prasyarat

1. ✅ Device WhatsApp sudah **Connected** (bukan pairing atau disconnected)
2. ✅ Webhook sudah dibuat dan **Aktif**
3. ✅ Event **Message Events** sudah dipilih di webhook

---

## 🚀 Metode Testing (Pilih Salah Satu)

### **Metode 1: Menggunakan Webhook.site (PALING MUDAH) ⭐**

#### Langkah-langkah:

1. **Buka Webhook.site**
   ```
   https://webhook.site
   ```
   - Klik link di atas atau buka di browser
   - Anda akan langsung mendapatkan URL unik seperti:
     ```
     https://webhook.site/abc123-def456-ghi789
     ```

2. **Copy URL Webhook**
   - Copy URL yang diberikan (contoh: `https://webhook.site/abc123-def456-ghi789`)
   - URL ini akan digunakan untuk menerima webhook

3. **Buat/Edit Webhook di Aplikasi**
   - Login ke aplikasi: `http://localhost:8000`
   - Buka menu **Webhooks**
   - Klik **Create Webhook** atau edit webhook yang sudah ada
   - Isi form:
     - **Name**: `Test Webhook - Pesan Masuk`
     - **URL**: Paste URL dari webhook.site (contoh: `https://webhook.site/abc123-def456-ghi789`)
     - **Device**: Pilih device yang sudah **Connected** atau biarkan "All Devices"
     - **Events**: ✅ Centang **Message Events**
     - **Active**: ✅ Pastikan dicentang
   - Klik **Create Webhook**

4. **Kirim Pesan WhatsApp**
   - Buka WhatsApp di nomor lain (bukan nomor device yang terhubung)
   - Kirim pesan ke nomor device yang terhubung
   - Contoh: Kirim "Halo, ini test pesan masuk"

5. **Lihat Hasil di Webhook.site**
   - Kembali ke halaman webhook.site
   - Refresh halaman (F5)
   - Anda akan melihat request baru muncul
   - Klik request tersebut untuk melihat detail:
     - **Headers**: Informasi header request
     - **Body**: Payload JSON yang dikirim
     - **Timestamp**: Waktu request diterima

6. **Verifikasi Payload**
   - Pastikan payload berisi:
     ```json
     {
       "event": "message",
       "session": "session_id_anda",
       "payload": {
         "id": "...",
         "from": "6281234567890@c.us",
         "fromMe": false,
         "to": "6289876543210@c.us",
         "body": "Halo, ini test pesan masuk",
         "hasMedia": false
       },
       "timestamp": "2025-11-28T12:00:00Z"
     }
     ```

✅ **Jika payload muncul di webhook.site = Webhook BERHASIL!**

---

### **Metode 2: Testing dengan ngrok (Untuk Local Development)**

Jika aplikasi Anda berjalan di localhost dan ingin test dengan webhook real:

1. **Install ngrok**
   ```bash
   # Download dari https://ngrok.com/download
   # Atau via Homebrew (Mac)
   brew install ngrok
   ```

2. **Start ngrok**
   ```bash
   # Expose port 8000 (sesuaikan dengan port aplikasi)
   ngrok http 8000
   ```

3. **Dapatkan URL Public**
   - ngrok akan memberikan URL seperti: `https://abc123.ngrok.io`
   - Copy URL tersebut

4. **Buat Webhook**
   - URL: `https://abc123.ngrok.io/webhook/receive/{session_id}`
   - Atau gunakan webhook.site dengan URL ngrok

5. **Test**
   - Kirim pesan WhatsApp
   - Webhook akan dikirim ke ngrok
   - ngrok akan forward ke local server

---

### **Metode 3: Testing Manual dengan cURL**

Untuk testing cepat tanpa setup server eksternal:

```bash
curl -X POST "http://localhost:8000/webhook/receive/YOUR_SESSION_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "message",
    "session": "YOUR_SESSION_ID",
    "payload": {
      "id": "test_message_123",
      "timestamp": 1234567890,
      "from": "6281234567890@c.us",
      "fromMe": false,
      "to": "6289876543210@c.us",
      "body": "Test pesan masuk dari webhook",
      "hasMedia": false
    }
  }'
```

**Ganti:**
- `YOUR_SESSION_ID` dengan session ID device Anda
- Nomor telepon dengan nomor yang sesuai

**Cek hasil:**
- Lihat Laravel logs: `tail -f storage/logs/laravel.log`
- Cek halaman Messages di aplikasi
- Cek webhook logs di detail webhook

---

## 🔍 Verifikasi Hasil Testing

### 1. **Cek Webhook.site**
- ✅ Request muncul di webhook.site
- ✅ Payload JSON lengkap dan benar
- ✅ Timestamp sesuai

### 2. **Cek Halaman Messages**
- Buka menu **Messages** di aplikasi
- Pastikan pesan muncul di list
- Cek:
  - ✅ Direction: **Incoming**
  - ✅ Status: **Delivered**
  - ✅ Content: Sesuai dengan pesan yang dikirim

### 3. **Cek Webhook Logs**
- Buka detail webhook
- Scroll ke bawah ke section **Webhook Logs**
- Pastikan:
  - ✅ Status code: **200** (hijau)
  - ✅ Event: **message**
  - ✅ Timestamp tercatat

### 4. **Cek Laravel Logs**
```bash
# Lihat logs real-time
tail -f storage/logs/laravel.log

# Cari log webhook
grep "Webhook" storage/logs/laravel.log
```

Pastikan ada log:
```
Webhook received
Webhook: Message saved
WebhookDelivery Job: Webhook delivered successfully
```

---

## 🐛 Troubleshooting

### ❌ Webhook Tidak Terkirim

**Kemungkinan penyebab:**
1. Device tidak **Connected**
   - **Solusi**: Pastikan device status = Connected, bukan pairing atau disconnected

2. Webhook tidak **Aktif**
   - **Solusi**: Edit webhook, pastikan checkbox "Aktifkan Webhook" dicentang

3. Event tidak sesuai
   - **Solusi**: Pastikan **Message Events** sudah dipilih di webhook

4. URL webhook tidak dapat diakses
   - **Solusi**: 
     - Untuk local, gunakan ngrok
     - Test URL dengan browser atau curl
     - Pastikan URL menggunakan HTTPS (untuk production)

5. Session ID tidak sesuai
   - **Solusi**: Pastikan session ID di webhook sesuai dengan session ID device

### ❌ Webhook Terkirim Tapi Error

**Cek Response Status:**
- Status 200-299 = ✅ Success
- Status 400-499 = ❌ Client Error (URL salah, auth required, dll)
- Status 500-599 = ❌ Server Error (server webhook error)

**Cek Response Body:**
- Lihat di webhook logs, response body berisi apa
- Bisa jadi server webhook mengembalikan error

### ❌ Pesan Tidak Tersimpan di Database

**Kemungkinan penyebab:**
1. Webhook dari WAHA tidak sampai ke aplikasi
   - **Solusi**: Cek Laravel logs, pastikan ada log "Webhook received"

2. Session tidak ditemukan
   - **Solusi**: Pastikan session ID di WAHA sesuai dengan session_id di database

3. Error saat menyimpan pesan
   - **Solusi**: Cek Laravel logs untuk error detail

---

## 📝 Checklist Testing

Gunakan checklist ini untuk memastikan testing lengkap:

- [ ] Device WhatsApp sudah **Connected**
- [ ] Webhook sudah dibuat dan **Aktif**
- [ ] Event **Message Events** sudah dipilih
- [ ] URL webhook dapat diakses (test dengan browser/curl)
- [ ] Pesan WhatsApp sudah dikirim ke device
- [ ] Webhook request muncul di webhook.site
- [ ] Payload JSON lengkap dan benar
- [ ] Pesan muncul di halaman **Messages**
- [ ] Webhook logs mencatat status 200
- [ ] Laravel logs tidak ada error

---

## 🎯 Contoh Skenario Testing

### Skenario 1: Test Pesan Text Masuk

1. Setup webhook dengan webhook.site
2. Kirim pesan text: "Halo, ini test pesan"
3. Verifikasi:
   - ✅ Payload di webhook.site berisi body: "Halo, ini test pesan"
   - ✅ Pesan muncul di Messages dengan direction: Incoming
   - ✅ Status: Delivered

### Skenario 2: Test Pesan Gambar Masuk

1. Setup webhook dengan webhook.site
2. Kirim gambar dengan caption: "Lihat gambar ini"
3. Verifikasi:
   - ✅ Payload di webhook.site berisi hasMedia: true
   - ✅ Payload berisi media.url
   - ✅ Pesan muncul di Messages dengan type: image
   - ✅ Caption tersimpan: "Lihat gambar ini"

### Skenario 3: Test Pesan dari Group

1. Setup webhook dengan webhook.site
2. Kirim pesan di group WhatsApp
3. Verifikasi:
   - ✅ Payload berisi participant (nomor pengirim di group)
   - ✅ Pesan tersimpan dengan from_number = participant
   - ✅ Chat type = group

---

## 💡 Tips

1. **Gunakan Webhook.site untuk Testing Cepat**
   - Tidak perlu setup server
   - Dapat melihat payload langsung
   - Gratis dan mudah digunakan

2. **Test dengan Berbagai Jenis Pesan**
   - Text
   - Gambar
   - Video
   - Dokumen
   - Voice note

3. **Monitor Logs**
   - Selalu cek Laravel logs saat testing
   - Cek webhook logs di aplikasi
   - Cek response di webhook.site

4. **Test Error Handling**
   - Test dengan URL yang tidak valid
   - Test dengan server webhook yang down
   - Test dengan timeout

---

## 📚 Referensi

- [Webhook.site](https://webhook.site) - Tool untuk testing webhook
- [ngrok](https://ngrok.com) - Expose local server ke internet
- [WAHA Documentation](https://waha.devlike.pro/docs/how-to/receive-messages/) - Dokumentasi WAHA

---

**Selamat Testing! 🎉**

Jika ada masalah, cek bagian Troubleshooting atau lihat Laravel logs untuk detail error.










