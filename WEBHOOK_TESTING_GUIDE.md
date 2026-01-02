# 🧪 Panduan Testing Webhook

Panduan lengkap untuk melakukan testing webhook di aplikasi WAHA Gateway.

## 📋 Daftar Isi

1. [Tools untuk Testing](#tools-untuk-testing)
2. [Metode Testing](#metode-testing)
3. [Testing dengan Webhook.site](#testing-dengan-webhooksite)
4. [Testing dengan ngrok](#testing-dengan-ngrok)
5. [Testing Manual dengan Postman/cURL](#testing-manual-dengan-postmancurl)
6. [Testing Real-time dengan WhatsApp](#testing-real-time-dengan-whatsapp)
7. [Melihat Logs Webhook](#melihat-logs-webhook)
8. [Troubleshooting](#troubleshooting)

---

## 🛠️ Tools untuk Testing

### 1. **Webhook.site** (Recommended untuk Testing Cepat)
- **URL**: https://webhook.site
- **Keuntungan**: 
  - Gratis, tidak perlu install
  - Dapat URL webhook instan
  - Menampilkan request real-time
  - Dapat melihat headers, body, dan query parameters

### 2. **ngrok** (Untuk Testing Local)
- **URL**: https://ngrok.com
- **Keuntungan**:
  - Expose local server ke internet
  - Cocok untuk testing di development
  - Support HTTPS

### 3. **Postman** (Untuk Testing Manual)
- **URL**: https://www.postman.com
- **Keuntungan**:
  - Testing endpoint secara manual
  - Dapat membuat mock server
  - Collection untuk berbagai skenario

---

## 🎯 Metode Testing

### Metode 1: Testing dengan Webhook.site (Paling Mudah)

#### Langkah-langkah:

1. **Buka Webhook.site**
   - Kunjungi https://webhook.site
   - Anda akan mendapatkan URL unik seperti: `https://webhook.site/unique-id-here`
   - Copy URL tersebut

2. **Buat Webhook di Aplikasi**
   - Login ke aplikasi
   - Buka menu **Webhooks**
   - Klik **Create Webhook**
   - Isi form:
     - **Name**: Test Webhook
     - **URL**: Paste URL dari webhook.site
     - **Events**: Pilih event yang ingin ditest (misal: Message Events)
     - **Device**: Pilih device atau biarkan "All Devices"
   - Klik **Create Webhook**

3. **Trigger Webhook**
   - Kirim pesan melalui WhatsApp ke device yang terhubung
   - Atau kirim pesan melalui API
   - Webhook akan otomatis terkirim ke webhook.site

4. **Lihat Hasil di Webhook.site**
   - Refresh halaman webhook.site
   - Anda akan melihat request yang masuk
   - Klik request untuk melihat detail:
     - Headers
     - Body (JSON payload)
     - Timestamp

---

### Metode 2: Testing dengan ngrok (Untuk Local Development)

#### Setup ngrok:

1. **Install ngrok**
   ```bash
   # Download dari https://ngrok.com/download
   # Atau via Homebrew (Mac)
   brew install ngrok
   ```

2. **Start ngrok**
   ```bash
   # Expose port 8000 (sesuaikan dengan port aplikasi Anda)
   ngrok http 8000
   ```

3. **Dapatkan URL Public**
   - ngrok akan memberikan URL seperti: `https://abc123.ngrok.io`
   - Copy URL tersebut

4. **Buat Webhook di Aplikasi**
   - URL webhook: `https://abc123.ngrok.io/webhook/receive/{session_id}`
   - Atau gunakan URL ngrok untuk webhook user Anda

5. **Test Webhook**
   - Kirim pesan melalui WhatsApp
   - Webhook akan dikirim ke ngrok
   - ngrok akan forward ke local server Anda

---

### Metode 3: Testing Manual dengan Postman/cURL

#### Testing Endpoint Webhook Langsung:

**Endpoint**: `POST /webhook/receive/{session_id}`

**Contoh dengan cURL:**

```bash
curl -X POST "http://localhost:8000/webhook/receive/YOUR_SESSION_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "message",
    "session": "YOUR_SESSION_ID",
    "payload": {
      "id": "true_6281234567890@c.us_1234567890",
      "timestamp": 1234567890,
      "from": "6281234567890@c.us",
      "fromMe": false,
      "to": "6289876543210@c.us",
      "body": "Test message",
      "hasMedia": false
    }
  }'
```

**Contoh dengan Postman:**

1. **Setup Request**
   - Method: `POST`
   - URL: `http://localhost:8000/webhook/receive/YOUR_SESSION_ID`
   - Headers:
     - `Content-Type: application/json`

2. **Body (JSON)**
   ```json
   {
     "event": "message",
     "session": "YOUR_SESSION_ID",
     "payload": {
       "id": "true_6281234567890@c.us_1234567890",
       "timestamp": 1234567890,
       "from": "6281234567890@c.us",
       "fromMe": false,
       "to": "6289876543210@c.us",
       "body": "Test message",
       "hasMedia": false
     }
   }
   ```

3. **Send Request**
   - Klik Send
   - Lihat response (harus return `{"success": true}`)

---

### Metode 4: Testing Real-time dengan WhatsApp

#### Langkah-langkah:

1. **Pastikan Device Terhubung**
   - Buka menu **Devices**
   - Pastikan device status = **Connected**
   - Jika belum, scan QR code untuk pairing

2. **Buat Webhook**
   - Buat webhook dengan URL webhook.site atau ngrok
   - Pilih event: **Message Events**

3. **Kirim Pesan**
   - Kirim pesan WhatsApp dari nomor lain ke device yang terhubung
   - Atau kirim pesan dari device ke nomor lain

4. **Verifikasi**
   - Cek webhook.site untuk melihat request
   - Cek halaman **Messages** di aplikasi untuk melihat pesan tersimpan
   - Cek **Webhook Logs** di detail webhook

---

## 📊 Melihat Logs Webhook

### 1. **Melalui Halaman Webhook Detail**

1. Buka menu **Webhooks**
2. Klik webhook yang ingin dilihat logs-nya
3. Scroll ke bawah, ada section **Webhook Logs**
4. Lihat:
   - Waktu trigger
   - Event type
   - Status code (200 = success, lainnya = error)
   - Response body

### 2. **Melalui Laravel Logs**

```bash
# Lihat logs real-time
tail -f storage/logs/laravel.log

# Cari webhook logs
grep "Webhook" storage/logs/laravel.log
```

### 3. **Melalui Database**

```sql
-- Lihat webhook logs
SELECT * FROM webhook_logs 
WHERE webhook_id = 'YOUR_WEBHOOK_ID' 
ORDER BY triggered_at DESC 
LIMIT 20;

-- Lihat webhook yang paling sering dipicu
SELECT webhook_id, COUNT(*) as trigger_count 
FROM webhook_logs 
GROUP BY webhook_id 
ORDER BY trigger_count DESC;
```

---

## 🔍 Troubleshooting

### Webhook Tidak Terkirim

1. **Cek Status Webhook**
   - Pastikan webhook **Active** (bukan Inactive)
   - Cek di halaman detail webhook

2. **Cek URL Webhook**
   - Pastikan URL dapat diakses dari internet
   - Test URL dengan browser atau curl
   - Untuk local, gunakan ngrok

3. **Cek Event Configuration**
   - Pastikan event yang dipilih sesuai dengan event yang terjadi
   - Cek di webhook detail, events apa saja yang dikonfigurasi

4. **Cek Device Status**
   - Pastikan device status = **Connected**
   - Webhook hanya aktif untuk device yang connected

5. **Cek Logs**
   - Lihat Laravel logs untuk error
   - Cek webhook logs di database
   - Cek queue jobs (jika menggunakan queue)

### Webhook Terkirim Tapi Error

1. **Cek Response Status**
   - Lihat di webhook logs, status code berapa?
   - 200-299 = Success
   - 400-499 = Client Error (URL salah, auth required, dll)
   - 500-599 = Server Error (server webhook error)

2. **Cek Response Body**
   - Lihat response body di webhook logs
   - Bisa jadi server webhook mengembalikan error

3. **Cek Timeout**
   - Webhook timeout = 10 detik
   - Jika server webhook lambat, bisa timeout

### Webhook Terkirim Tapi Payload Kosong

1. **Cek Event Type**
   - Pastikan event yang terjadi sesuai dengan event yang dikonfigurasi
   - Cek payload di Laravel logs

2. **Cek Session ID**
   - Pastikan session ID benar
   - Webhook hanya untuk session yang sesuai

---

## 📝 Contoh Payload Webhook

### Message Event

```json
{
  "event": "message",
  "session": "session_id_here",
  "payload": {
    "id": "true_6281234567890@c.us_1234567890",
    "timestamp": 1234567890,
    "from": "6281234567890@c.us",
    "fromMe": false,
    "to": "6289876543210@c.us",
    "body": "Hello, this is a test message",
    "hasMedia": false
  },
  "timestamp": "2025-11-28T12:00:00Z"
}
```

### Message with Media

```json
{
  "event": "message",
  "session": "session_id_here",
  "payload": {
    "id": "true_6281234567890@c.us_1234567890",
    "timestamp": 1234567890,
    "from": "6281234567890@c.us",
    "fromMe": false,
    "to": "6289876543210@c.us",
    "body": "Check this image",
    "hasMedia": true,
    "media": {
      "url": "http://localhost:3000/api/files/...",
      "mimetype": "image/jpeg",
      "filename": null
    }
  },
  "timestamp": "2025-11-28T12:00:00Z"
}
```

### Message ACK Event

```json
{
  "event": "message.ack",
  "session": "session_id_here",
  "payload": {
    "id": "true_6281234567890@c.us_1234567890",
    "ack": 2,
    "timestamp": 1234567890
  },
  "timestamp": "2025-11-28T12:00:00Z"
}
```

---

## ✅ Checklist Testing

- [ ] Webhook dapat dibuat dengan URL valid
- [ ] Webhook aktif dan dapat menerima event
- [ ] Event message dapat diterima dan disimpan
- [ ] Event message.ack dapat update status pesan
- [ ] Webhook logs tersimpan dengan benar
- [ ] Response status code tercatat
- [ ] Payload webhook lengkap dan benar
- [ ] Webhook dapat dihapus
- [ ] Webhook dapat diaktifkan/nonaktifkan

---

## 🚀 Tips Testing

1. **Gunakan Webhook.site untuk Testing Cepat**
   - Tidak perlu setup server
   - Dapat melihat payload langsung

2. **Gunakan ngrok untuk Testing Local**
   - Cocok untuk development
   - Dapat test dengan aplikasi local

3. **Test dengan Berbagai Event**
   - Test message event
   - Test message.ack event
   - Test message.reaction event
   - Test message.edited event
   - Test message.revoked event

4. **Monitor Logs**
   - Selalu cek Laravel logs
   - Cek webhook logs di database
   - Cek queue jobs jika menggunakan queue

5. **Test Error Handling**
   - Test dengan URL yang tidak valid
   - Test dengan server webhook yang down
   - Test dengan timeout
   - Test dengan response error

---

## 📚 Referensi

- [WAHA Documentation - Receive Messages](https://waha.devlike.pro/docs/how-to/receive-messages/)
- [Webhook.site](https://webhook.site)
- [ngrok Documentation](https://ngrok.com/docs)

---

**Selamat Testing! 🎉**











