# 🔗 Panduan Setup Webhook WAHA

## 📋 Overview

Webhook WAHA dikonfigurasi **otomatis** saat session dibuat. Webhook ini digunakan untuk menerima pesan masuk secara real-time dari WAHA ke aplikasi Laravel.

## 🔧 Konfigurasi Otomatis

Saat session dibuat, webhook URL otomatis dikonfigurasi dengan format:
```
http://[host]/webhook/receive/[session_id]
```

### URL Webhook Berdasarkan Environment

#### 1. Development (Laravel di Host, WAHA di Docker)
```
http://host.docker.internal:8000/webhook/receive/[session_id]
```
- **macOS/Windows**: Menggunakan `host.docker.internal` (otomatis)
- **Linux**: Perlu set `DOCKER_HOST_IP` di `.env`

#### 2. Production (Keduanya di Docker)
```
http://[laravel-container-name]:8000/webhook/receive/[session_id]
```
Atau jika menggunakan service name:
```
http://laravel-app:8000/webhook/receive/[session_id]
```

#### 3. Development (Keduanya di Host)
```
http://localhost:8000/webhook/receive/[session_id]
```

## ✅ Cara Memastikan Webhook Berjalan

### 1. Test Webhook dengan Command Artisan

```bash
# Test webhook untuk session terbaru
php artisan webhook:test

# Test webhook untuk session tertentu
php artisan webhook:test [session_id]

# Test dari Docker container
php artisan webhook:test [session_id] --from-docker
```

### 2. Test Webhook dengan Script Bash

```bash
# Test webhook
./test-webhook.sh [session_id]
```

### 3. Test Manual dengan cURL

```bash
# Test dari host
curl -X POST "http://localhost:8000/webhook/receive/[session_id]" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "message",
    "payload": {
      "from": "6281234567890@c.us",
      "body": "Test message"
    }
  }'

# Test dari Docker container
docker exec waha-api curl -X POST "http://host.docker.internal:8000/webhook/receive/[session_id]" \
  -H "Content-Type: application/json" \
  -d '{"event":"test","payload":{}}'
```

### 4. Cek Log Laravel

```bash
# Monitor webhook logs
tail -f frontend/storage/logs/laravel.log | grep -i webhook

# Atau semua logs
tail -f frontend/storage/logs/laravel.log
```

### 5. Cek Konfigurasi WAHA Session

```bash
# Cek session config di WAHA
curl -X GET "http://localhost:3002/api/sessions/default" \
  -H "X-Api-Key: [your-api-key]"

# Periksa bagian "webhooks" di response
```

## 🔍 Troubleshooting

### Problem 1: Webhook tidak menerima pesan

**Gejala:**
- Pesan masuk di WhatsApp tapi tidak muncul di aplikasi
- Tidak ada log webhook di Laravel logs

**Solusi:**
1. **Cek URL webhook di WAHA session config**
   ```bash
   curl -X GET "http://localhost:3002/api/sessions/default" \
     -H "X-Api-Key: [api-key]" | jq '.config.webhooks'
   ```

2. **Pastikan URL dapat diakses dari Docker**
   ```bash
   # Test dari dalam container WAHA
   docker exec waha-api curl -v http://host.docker.internal:8000/webhook/receive/[session_id]
   ```

3. **Cek firewall/network**
   - Pastikan port 8000 terbuka
   - Pastikan Docker dapat mengakses host

4. **Cek Laravel app running**
   ```bash
   curl http://localhost:8000
   ```

### Problem 2: Error "Connection refused" dari Docker

**Gejala:**
- WAHA tidak bisa mengirim webhook
- Error di WAHA logs: "Connection refused"

**Solusi:**

#### Untuk macOS/Windows:
```env
# .env - sudah otomatis menggunakan host.docker.internal
APP_URL=http://localhost:8000
```

#### Untuk Linux:
```env
# .env - set IP host atau gunakan host.docker.internal
APP_URL=http://localhost:8000
DOCKER_HOST_IP=host.docker.internal

# Atau gunakan IP host
DOCKER_HOST_IP=192.168.1.100
```

Cara mendapatkan IP host:
```bash
# Linux
ip addr show docker0 | grep inet

# Atau
hostname -I | awk '{print $1}'
```

### Problem 3: Webhook URL salah di WAHA

**Gejala:**
- Webhook URL di WAHA masih menggunakan `localhost` padahal WAHA di Docker

**Solusi:**
1. **Hapus dan buat ulang session**
   ```bash
   # Hapus session
   curl -X DELETE "http://localhost:3002/api/sessions/default" \
     -H "X-Api-Key: [api-key]"
   
   # Buat ulang dari aplikasi Laravel
   ```

2. **Atau update webhook manual** (jika menggunakan WAHA Plus)
   ```bash
   curl -X POST "http://localhost:3002/api/sessions/default/webhook" \
     -H "X-Api-Key: [api-key]" \
     -H "Content-Type: application/json" \
     -d '{
       "url": "http://host.docker.internal:8000/webhook/receive/[session_id]",
       "events": ["message", "message.any"]
     }'
   ```

### Problem 4: Webhook menerima tapi tidak save ke database

**Gejala:**
- Ada log webhook di Laravel tapi pesan tidak tersimpan

**Solusi:**
1. **Cek log error**
   ```bash
   tail -f frontend/storage/logs/laravel.log | grep -i error
   ```

2. **Cek database connection**
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

3. **Cek session exists di database**
   ```bash
   php artisan tinker
   >>> App\Models\WhatsAppSession::where('session_id', '[session_id]')->first();
   ```

## 📊 Monitoring Webhook

### 1. Real-time Monitoring

```bash
# Monitor semua webhook activity
tail -f frontend/storage/logs/laravel.log | grep -E "webhook|receive"

# Monitor hanya pesan masuk
tail -f frontend/storage/logs/laravel.log | grep -E "message.*incoming"
```

### 2. Check Webhook Status

```bash
# Cek webhook config di WAHA
curl -X GET "http://localhost:3002/api/sessions/default" \
  -H "X-Api-Key: [api-key]" | jq '.config.webhooks'

# Cek session status
curl -X GET "http://localhost:3002/api/sessions/default" \
  -H "X-Api-Key: [api-key]" | jq '.status'
```

### 3. Test Webhook Endpoint

```bash
# Test endpoint accessibility
curl -X POST "http://localhost:8000/webhook/receive/[session_id]" \
  -H "Content-Type: application/json" \
  -d '{"event":"test","payload":{}}'
```

## 🔐 Security Notes

1. **CSRF Protection**: Webhook endpoint sudah di-exclude dari CSRF protection
2. **Authentication**: Webhook endpoint adalah public, tapi memvalidasi session_id
3. **Rate Limiting**: Pertimbangkan menambahkan rate limiting untuk webhook endpoint

## 📝 Environment Variables

```env
# Laravel .env
APP_URL=http://localhost:8000
DOCKER_HOST_IP=host.docker.internal  # Untuk Linux, bisa diisi IP host

# WAHA .env (docker)
WAHA_API_KEY=your-api-key-here
WAHA_PORT=3002
```

## 🎯 Best Practices

1. **Selalu test webhook setelah membuat session**
   ```bash
   php artisan webhook:test [session_id]
   ```

2. **Monitor logs secara berkala**
   ```bash
   tail -f frontend/storage/logs/laravel.log
   ```

3. **Gunakan host.docker.internal untuk development**
   - Otomatis bekerja di macOS/Windows
   - Untuk Linux, set DOCKER_HOST_IP

4. **Pastikan Laravel app selalu running**
   - Gunakan supervisor atau systemd untuk production
   - Gunakan `php artisan serve` untuk development

5. **Backup webhook configuration**
   - Simpan webhook URL di database
   - Log setiap perubahan webhook config

## 📚 References

- [WAHA Webhook Documentation](https://waha.devlike.pro/docs/how-to/receive-messages/)
- [WAHA Events Documentation](https://waha.devlike.pro/docs/how-to/events/)
- [Docker Networking](https://docs.docker.com/network/)

---

**Last Updated:** 2026-01-02



