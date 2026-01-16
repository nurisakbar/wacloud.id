# WACloud Notification Setup

Konfigurasi untuk mengirim notifikasi WhatsApp otomatis saat order dibuat.

## Konfigurasi .env

Tambahkan konfigurasi berikut ke file `.env`:

```env
# WACloud Configuration for WhatsApp Notifications
WACLOUD_API_KEY=nIpjzBcjX5Ktwa10wfv4orzrd7WaXgUgQlRoVgBBuH1QUDZNgSHgjexmfSvgIIYs
WACLOUD_DEVICE_ID=c6f2f4cf-a91d-4648-97e9-b47d734bfbbb
WACLOUD_BASE_URL=https://app.wacloud.id/api/v1
```

## Penjelasan

- **WACLOUD_API_KEY**: API Key untuk autentikasi ke WACloud API
- **WACLOUD_DEVICE_ID**: Device ID yang digunakan untuk mengirim pesan (nomor utama WACloud)
- **WACLOUD_BASE_URL**: Base URL untuk WACloud API (default: https://app.wacloud.id/api/v1)

## Fitur

Saat user melakukan order/pembelian quota, sistem akan otomatis mengirim notifikasi WhatsApp ke nomor telepon user yang terdaftar dengan informasi:

- Nomor pembelian
- Tanggal pembelian
- Total pembayaran
- Detail quota yang dibeli (Text Quota dan/atau Multimedia Quota)
- Metode pembayaran
- Status pembayaran

## Catatan

- Notifikasi hanya akan dikirim jika user memiliki nomor telepon yang valid
- Nomor telepon akan dinormalisasi ke format internasional (62xxxxxxxxxx)
- Jika konfigurasi tidak lengkap, notifikasi akan di-skip dan dicatat di log









