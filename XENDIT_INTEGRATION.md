# Integrasi Pembayaran Xendit

Dokumentasi lengkap untuk integrasi pembayaran Xendit di aplikasi WACloud.

## 📋 Overview

Aplikasi ini menggunakan Xendit sebagai payment gateway untuk pembayaran quota. Integrasi ini mencakup:

- ✅ Pembuatan invoice otomatis
- ✅ Redirect ke halaman pembayaran Xendit
- ✅ Webhook untuk update status pembayaran
- ✅ Halaman status pembayaran dengan polling
- ✅ Error handling yang lengkap

## 🔧 Konfigurasi

### 1. Environment Variables

Tambahkan ke file `.env`:

```env
XENDIT_SECRET_KEY=xnd_development_xxxxxxxxxxxxx
XENDIT_PUBLIC_KEY=xnd_public_development_xxxxxxxxxxxxx
```

### 2. Config File

Config sudah tersedia di `config/services.php`:

```php
'xendit' => [
    'secret_key' => env('XENDIT_SECRET_KEY'),
    'public_key' => env('XENDIT_PUBLIC_KEY'),
],
```

## 📁 Struktur File

### Controllers

- `app/Http/Controllers/PaymentController.php` - Controller untuk handle payment flow
- `app/Http/Controllers/QuotaController.php` - Controller untuk purchase quota (sudah ada integrasi Xendit)

### Services

- `app/Services/XenditService.php` - Service untuk interaksi dengan Xendit API

### Views

- `resources/views/payment/status.blade.php` - Halaman status pembayaran dengan polling
- `resources/views/payment/success.blade.php` - Halaman sukses pembayaran
- `resources/views/payment/failure.blade.php` - Halaman gagal pembayaran

### JavaScript

- `resources/js/xendit-payment.js` - JavaScript untuk handle payment flow di frontend

## 🚀 Flow Pembayaran

### 1. User Membuat Purchase

User mengisi form purchase quota di `/quota/create` dan memilih payment method "Xendit".

### 2. Create Purchase & Invoice

Sistem akan:
1. Membuat record `QuotaPurchase` di database
2. Membuat invoice di Xendit via `XenditService::createInvoice()`
3. Menyimpan `xendit_invoice_id` dan `xendit_invoice_url` ke purchase

### 3. Redirect ke Xendit

User di-redirect ke `xendit_invoice_url` untuk melakukan pembayaran.

### 4. Payment Status

Setelah pembayaran:
- **Success**: User di-redirect ke `/payment/{purchase}/success`
- **Failure**: User di-redirect ke `/payment/{purchase}/failure`
- **Pending**: User bisa melihat status di `/payment/{purchase}/status`

### 5. Webhook

Xendit akan mengirim webhook ke `/webhook/xendit` ketika status invoice berubah.

## 📡 API Endpoints

### Create Invoice

```http
POST /payment/create-invoice
Content-Type: application/json

{
    "purchase_id": 123
}
```

**Response:**
```json
{
    "success": true,
    "invoice_url": "https://checkout.xendit.co/web/...",
    "invoice_id": "invoice_xxx"
}
```

### Check Payment Status

```http
GET /payment/{purchase}/check-status
```

**Response:**
```json
{
    "success": true,
    "status": "completed",
    "invoice_status": "PAID",
    "paid": true
}
```

## 🔔 Webhook

### Webhook URL

```
POST /webhook/xendit
```

### Webhook Events

Sistem menangani event `invoice.paid`:

```json
{
    "event": "invoice.paid",
    "data": {
        "id": "invoice_xxx",
        "external_id": "QUOTA-XXXXXXXX-20241201",
        "status": "PAID",
        ...
    }
}
```

### Webhook Handler

Webhook handler ada di `QuotaController::webhook()` yang akan:
1. Mencari purchase berdasarkan `external_id` dan `invoice_id`
2. Memanggil `$purchase->complete()` jika status belum completed
3. Menambahkan quota ke user

## 💻 Frontend Integration

### JavaScript Class

Gunakan class `XenditPayment` untuk handle payment flow:

```javascript
// Auto-initialized on page load
// Handles form submission for Xendit payment
```

### Manual Usage

```javascript
// Check payment status
const status = await XenditPayment.checkPaymentStatus(purchaseId);
```

## 🎨 UI Components

### Payment Status Page

Halaman `/payment/{purchase}/status` menampilkan:
- Loading state saat checking
- Success state jika pembayaran berhasil
- Pending state jika masih menunggu
- Failed state jika pembayaran gagal

Auto-refresh setiap 10 detik untuk check status.

### Payment Success Page

Halaman `/payment/{purchase}/success` menampilkan:
- Konfirmasi pembayaran berhasil
- Detail purchase
- Link ke quota page

### Payment Failure Page

Halaman `/payment/{purchase}/failure` menampilkan:
- Pesan error
- Detail purchase
- Tombol untuk retry

## 🔒 Security

### Webhook Verification

Webhook verification bisa diimplementasikan di `XenditService::verifyWebhook()`:

```php
public function verifyWebhook(string $signature, string $payload): bool
{
    $expectedSignature = hash_hmac('sha256', $payload, $this->secretKey);
    return hash_equals($expectedSignature, $signature);
}
```

### CSRF Protection

Semua form sudah dilindungi dengan CSRF token Laravel.

## 📝 Testing

### Test Mode

Gunakan Xendit test mode dengan secret key development:
- Secret key: `xnd_development_...`
- Public key: `xnd_public_development_...`

### Test Cards

Untuk testing, gunakan test cards dari Xendit:
- Success: `4000000000000002`
- Failure: `4000000000000003`

## 🐛 Troubleshooting

### Invoice tidak terbuat

1. Cek `XENDIT_SECRET_KEY` di `.env`
2. Cek log di `storage/logs/laravel.log`
3. Pastikan Xendit API key valid

### Webhook tidak terpanggil

1. Cek webhook URL di Xendit dashboard
2. Pastikan URL accessible dari internet
3. Cek log untuk error

### Payment status tidak update

1. Cek webhook handler
2. Pastikan `external_id` match dengan `purchase_number`
3. Cek log untuk detail error

## 📚 Resources

- [Xendit Documentation](https://docs.xendit.co/)
- [Xendit PHP SDK](https://github.com/xendit/xendit-php)
- [Xendit Dashboard](https://dashboard.xendit.co/)

## 🔄 Update Status

Terakhir diupdate: Desember 2024


