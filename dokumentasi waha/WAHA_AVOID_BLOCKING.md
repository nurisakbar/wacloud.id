# ⚠️ Cara Menghindari Pemblokiran

Panduan untuk menghindari pemblokiran di WhatsApp saat mengembangkan bot.

Penting untuk diingat bahwa WhatsApp memiliki kebijakan ketat untuk mencegah spam dan penyalahgunaan platform mereka.

Jika Anda sedang mengembangkan bot untuk WhatsApp, sangat penting untuk mengikuti panduan ini agar tidak diblokir.

## 📋 Panduan yang Harus Diikuti

### 1. Hanya Membalas Pesan

Saat mengembangkan bot untuk WhatsApp, Anda **tidak boleh memulai percakapan**. Sebaliknya, bot Anda **hanya harus membalas** pesan yang diterimanya. Ini akan mencegah bot Anda ditandai sebagai spam oleh pengguna dan algoritma WhatsApp.

> Anda dapat menggunakan short link http://wa.me/7911111111?text=Hi sehingga pengguna dapat mengkliknya dan memulai dialog dengan bot terlebih dahulu

### 2. Hindari Spam dan Mengirim Konten yang Tidak Perlu

Mengirim terlalu banyak pesan atau mengirim konten yang tidak diminta pengguna dapat menyebabkan bot Anda diblokir. Pastikan untuk hanya mengirim informasi yang relevan dan berguna kepada pengguna. Selain itu, jangan mengirim terlalu banyak pesan sekaligus, karena ini juga dapat memicu filter spam.

### 3. Pertimbangan Lainnya

Ada panduan lain yang harus diikuti saat mengembangkan bot untuk WhatsApp, seperti menghindari penggunaan kata-kata yang dilarang dan tidak membagikan konten sensitif atau tidak pantas. Pastikan untuk membaca kebijakan WhatsApp secara menyeluruh untuk memastikan bot Anda mematuhi semua aturan mereka.

## 🔄 Cara Memproses Pesan

Saat memproses pesan di bot Anda, penting untuk mengikuti langkah-langkah tertentu untuk menghindari ditandai sebagai spam. Berikut adalah proses yang direkomendasikan:

1. **Kirim seen** sebelum memproses pesan. Ini dapat dilakukan dengan mengirim request `POST /api/sendSeen/` ke WAHA API.
2. **Mulai mengetik** sebelum mengirim pesan dan tunggu interval acak tergantung pada ukuran pesan. Ini dapat dilakukan dengan mengirim request `POST /api/startTyping/`.
3. **Berhenti mengetik** sebelum mengirim pesan. Ini dapat dilakukan dengan mengirim request `POST /api/stopTyping/`.
4. **Kirim pesan teks** menggunakan request `POST /api/sendText`.

Dengan mengikuti langkah-langkah ini, Anda dapat memastikan bahwa bot Anda memproses pesan dengan cara yang sesuai dengan panduan WhatsApp dan mengurangi risiko diblokir.

## 🚫 Cara Menghindari Diblokir

WhatsApp tahu bahwa tidak umum bagi seseorang untuk mengirim begitu banyak pesan atau pesan massal kepada orang yang belum pernah diajak bicara sebelumnya, sehingga hal ini dianggap sebagai spam/marketing sampah dengan cukup cepat. Berikut adalah beberapa tips sebelum mengirim pesan ke WhatsApp:

**Yang Harus dan Tidak Boleh Dilakukan:**

1. **Penting:** JANGAN mengirim pesan yang membuat Anda dilaporkan. Selama Anda tidak mendapat laporan dari pengguna yang Anda kirimi pesan, akun Anda sebagian besar akan baik-baik saja.
2. Memiliki konten nyata, survei yang disetujui orang berbeda dengan pesan marketing pada Sabtu malam.
3. Kirim pesan yang ditulis dengan cara berbeda; Anda bisa membuat script yang menempatkan spasi secara acak dalam string AND menyertakan nama (pertama) orang tersebut.
4. Jangan pernah menggunakan waktu tetap; selalu lakukan dengan mengirim pesan pertama, tunggu waktu acak antara 30 dan 60 detik, lalu kirim pesan kedua.
5. Selalu coba kelompokkan kontak berdasarkan kode area mereka; WhatsApp mengharapkan orang biasa berbicara terutama dengan kontak yang berada dalam area yang sama dengan nomor telepon Anda.
6. Miliki foto profil; ini tidak terkait dengan WhatsApp Bots Catcher® tetapi mengirim pesan baru kepada seseorang tanpa memiliki gambar/nama/status akan meningkatkan peluang Anda untuk ditandai secara manual sebagai spam.
7. Kirim konfirmasi "seen" ke pesan atau nonaktifkan di WhatsApp.
8. Hindari mengirim tautan yang sebelumnya ditandai sebagai spam di WhatsApp atau non-HTTPS. URL shortener adalah ide yang bagus.
9. **PENTING:** Sangat buruk jika Anda mengirim pesan 24/7 tanpa memberikan waktu untuk menunggu. Penundaan acak antar pesan tidak cukup; kirim jumlah pesan yang wajar dengan mempertimbangkan tingkat konversi Anda. Misalnya: untuk satu jam, kirim maksimal 4 pesan per kontak yang telah membalas pesan Anda, dan berhenti mengirim pesan selama satu jam, lalu mulai lagi. Sekali lagi, jangan mengirim pesan tanpa berhenti sebentar di antara setiap "paket".
10. Kirim hanya satu pesan pendek saat memulai percakapan; seseorang tidak boleh mengirim teks panjang atau beberapa pesan tanpa persetujuan pengguna.

**Perlu Diingat:**

1. Untuk setiap pesan yang Anda kirim kepada seseorang yang tidak memiliki nomor Anda di daftar kontak mereka, mereka ditanya apakah itu spam. Ditandai sebagai spam beberapa kali (5-10) akan membuat Anda diblokir.
2. WhatsApp mencatat setiap gerakan yang Anda lakukan; Anda bahkan dapat memeriksa log saat mengirim email dukungan sederhana. Ini berisi semua jenis informasi, jadi bertindaklah seseringan mungkin.
3. Coba terlibat dalam percakapan; selama Anda mengirim pesan dan orang tersebut tidak secara otomatis memblokir Anda, itu akan cukup baik. Orang yang terus-menerus berbicara dengan Anda dan menambahkan Anda ke daftar kontak mereka akan membuat nomor Anda lebih kuat terhadap larangan.
4. Pikirkan seperti sistem poin: Anda mulai dengan nol poin (negatif jika perangkat Anda sebelumnya masuk daftar hitam). Jika Anda mencapai di bawah nol, Anda keluar. Jika Anda terlibat dalam percakapan, Anda mendapat poin. Jika Anda ditandai sebagai spam, Anda kehilangan beberapa poin. Jika Anda diblokir, Anda mungkin kehilangan lebih banyak poin.
5. Akhirnya, jika konten Anda adalah spam, tidak masalah apakah Anda menggunakan daftar broadcast, grup, atau kontak langsung; Anda masih akan diblokir.

Sebagai API, kami mengatakan bahwa yang tersisa untuk dilakukan sekarang adalah menyetujui kebijakan WhatsApp, tidak mengirim pesan spam, dan selalu menunggu orang lain menghubungi Anda terlebih dahulu.

Anda dapat melakukan ini dengan mengirim SMS kepada orang tersebut dengan tautan untuk memulai obrolan di WhatsApp dengan Anda melalui tautan <https://wa.me/12132132131?text=Hi>.

---

## 📚 Referensi

- [WAHA Documentation](https://waha.devlike.pro/)
- [How to Avoid Blocking - WAHA Docs](https://waha.devlike.pro/docs/overview/%EF%B8%8F-how-to-avoid-blocking/)
- [WhatsApp Business Policy](https://www.whatsapp.com/legal/business-policy)

---

## 🔗 API Endpoints yang Terkait

- `POST /api/sendSeen/` - Mengirim konfirmasi "seen"
- `POST /api/startTyping/` - Memulai indikator "typing"
- `POST /api/stopTyping/` - Menghentikan indikator "typing"
- `POST /api/sendText` - Mengirim pesan teks

---

**Last Updated:** 2025-01-27

