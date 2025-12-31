# Panduan Publikasi ke Packagist

Panduan ini menjelaskan langkah-langkah untuk mempublikasikan package `wacloud/laravel-wacloud` ke Packagist.

## 📋 Prasyarat

1. Akun GitHub (jika belum punya, daftar di [github.com](https://github.com))
2. Akun Packagist (daftar di [packagist.org](https://packagist.org))
3. Package sudah siap dan diuji

## 🚀 Langkah-langkah

### 1. Buat Repository GitHub

1. Buat repository baru di GitHub dengan nama `laravel-wacloud`
2. Pastikan repository adalah **public** (Packagist hanya menerima package dari repository public)
3. Copy URL repository (contoh: `https://github.com/wacloud/laravel-wacloud.git`)

### 2. Inisialisasi Git di Package

```bash
cd packages/wacloud/laravel-wacloud
git init
git add .
git commit -m "Initial commit: WACloud Laravel Package v1.0.0"
```

### 3. Push ke GitHub

```bash
git remote add origin https://github.com/wacloud/laravel-wacloud.git
git branch -M main
git push -u origin main
```

### 4. Buat Release Tag

Setelah push, buat tag untuk versi pertama:

```bash
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

Atau buat release melalui GitHub:
1. Buka repository di GitHub
2. Klik **Releases** → **Create a new release**
3. Pilih tag `v1.0.0` atau buat tag baru
4. Isi title: `v1.0.0`
5. Isi description dengan changelog
6. Klik **Publish release**

### 5. Daftar ke Packagist

1. Login ke [packagist.org](https://packagist.org)
2. Klik **Submit** di menu atas
3. Masukkan URL repository GitHub: `https://github.com/wacloud/laravel-wacloud`
4. Klik **Check** untuk memverifikasi package
5. Jika valid, klik **Submit**

### 6. Konfigurasi GitHub Hook (Opsional tapi Disarankan)

Untuk auto-update di Packagist setiap kali ada commit baru:

1. Di Packagist, buka halaman package Anda
2. Klik **Settings** → **GitHub Service Hook**
3. Copy URL webhook yang diberikan
4. Di GitHub repository:
   - Buka **Settings** → **Webhooks**
   - Klik **Add webhook**
   - Paste URL dari Packagist
   - Content type: `application/json`
   - Klik **Add webhook**

Sekarang setiap kali Anda push commit atau membuat release baru, Packagist akan otomatis update.

## 📝 Checklist Sebelum Publish

- [ ] Semua file sudah ada (README.md, LICENSE, composer.json, dll)
- [ ] `composer.json` sudah benar dan valid
- [ ] README.md lengkap dengan contoh penggunaan
- [ ] LICENSE file sudah ada
- [ ] Code sudah diuji (jika ada test)
- [ ] Version di composer.json sudah sesuai
- [ ] Repository sudah public di GitHub
- [ ] Tag release sudah dibuat

## 🔄 Update Package

Setelah package terdaftar di Packagist, untuk update:

1. Update code di repository
2. Update version di `composer.json` (semantic versioning)
3. Commit dan push ke GitHub
4. Buat tag baru untuk versi baru:
   ```bash
   git tag -a v1.0.1 -m "Release version 1.0.1"
   git push origin v1.0.1
   ```
5. Jika sudah setup webhook, Packagist akan otomatis update
6. Jika belum, klik **Update** di halaman package di Packagist

## 📌 Semantic Versioning

Gunakan semantic versioning untuk versi:
- **MAJOR** (1.0.0): Breaking changes
- **MINOR** (1.1.0): New features (backward compatible)
- **PATCH** (1.0.1): Bug fixes

## 🔗 Link Penting

- [Packagist](https://packagist.org)
- [GitHub](https://github.com)
- [Semantic Versioning](https://semver.org)
- [Composer Documentation](https://getcomposer.org/doc/)

## ⚠️ Catatan

1. Pastikan `composer.json` valid dengan menjalankan:
   ```bash
   composer validate
   ```

2. Test install package sebelum publish:
   ```bash
   composer require wacloud/laravel-wacloud:dev-main
   ```

3. Setelah publish, test install dari Packagist:
   ```bash
   composer require wacloud/laravel-wacloud
   ```

## 📞 Support

Jika mengalami masalah saat publish, hubungi:
- Packagist Support: [packagist.org/about](https://packagist.org/about)
- GitHub Support: [github.com/support](https://github.com/support)

