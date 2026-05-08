# Prompt Master: Integrasi Printer Thermal Hybrid (APK + Browser) ke PHP Native

## Instruksi Awal: Riset Core Proyek (WAJIB)
Sebelum menulis kode apa pun, tolong teliti dulu struktur proyek PHP Native saya:
1.  Cek bagaimana struktur folder proyek ini (di mana letak folder assets/js, css, dan file koneksi database).
2.  Cek bagaimana alur transaksi saat ini (cari file simpan transaksi atau cetak struk yang sudah ada).
3.  Pastikan Bapak memahami cara data dikirim dari PHP ke JavaScript di proyek ini.

---

## Bagian 1: Persiapan Android (Native Bridge) - Jalur APK
Tolong bantu saya mengimplementasikan Native Bridge di aplikasi Android (Java/Kotlin) agar JavaScript bisa memanggil fungsi printer hardware (Bluetooth/USB) saat aplikasi dibuka sebagai APK.

*(Sertakan kode MainActivity.java, WebAppInterface, dan gradle dependencies seperti library ESCPOS-ThermalPrinter-Android)*

---

## Bagian 2: Implementasi Web Bluetooth API - Jalur Browser
Agar sistem bisa mencetak langsung dari **Browser (Google Chrome/Laptop)** tanpa APK, implementasikan **Web Bluetooth API** di dalam file JavaScript.
1.  **GATT Services**: Gunakan UUID standar printer thermal (misal: `000018f0-0000-1000-8000-00805f9b34fb`).
2.  **Chunk Writing**: Implementasikan sistem pengiriman data dalam potongan (misal 512 bytes) agar printer tidak macet saat menerima data besar.
3.  **Permissions**: Berikan panduan pengecekan `navigator.bluetooth.requestDevice()`.

---

## Bagian 3: File JavaScript Universal Hybrid (`printer.js`)
Buat file JavaScript yang cerdas (Hybrid) yang bisa menentukan jalur cetak secara otomatis:
- **Deteksi Otomatis**: Jika ada `window.AndroidInterface`, gunakan jalur **Native Bridge** (untuk Bluetooth/USB di APK).
- **Fallback Browser**: Jika tidak ada, gunakan jalur **Web Bluetooth API** (untuk Bluetooth di Browser).
- **Format ESC/POS**: Buat satu sistem perakitan struk (ESC/POS) yang sama yang bisa dikirim ke kedua jalur tersebut (Native maupun Web).

---

## Bagian 4: Halaman Pengaturan (Settings UI)
Buat halaman pengaturan di PHP Native yang bisa menangani kedua mode:
1.  **Mode APK**: Menampilkan list printer Bluetooth/USB yang sudah dipairing di Android.
2.  **Mode Browser**: Menampilkan tombol "Scan Bluetooth" bawaan browser.
3.  **Test Print**: Tombol cetak struk dummy "TEST SUCCESS" untuk memvalidasi kedua jalur tersebut.
4.  **Save Local**: Simpan MAC Address atau Device Name ke `localStorage`.

---

**Output yang diharapkan:**
1. Analisis singkat struktur proyek PHP saya.
2. Kode Java Lengkap (untuk Android Bridge).
3. Kode JavaScript Hybrid Lengkap (Web Bluetooth + Android Interface).
4. Kode HTML/JS untuk UI Pengaturan di PHP Native.
5. Contoh integrasi file `.php` (cetak otomatis setelah simpan).
