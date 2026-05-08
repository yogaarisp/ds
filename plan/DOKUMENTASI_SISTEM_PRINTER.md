# 🖨️ Dokumentasi Sistem Printer Thermal (Hybrid Web-Native)

Dokumentasi ini menjelaskan alur kerja sistem printer thermal pada aplikasi POS Dimsum Sarang, yang mendukung koneksi via **Web Bluetooth API** (Browser) dan **Android Native Bridge** (APK).

---

## 🛠️ 1. Arsitektur Koneksi (Hybrid)

Sistem menggunakan pendekatan dua jalur (Dual-Path) untuk memastikan kompatibilitas maksimal:

| Jalur | Lingkungan | Teknologi | Keunggulan |
| :--- | :--- | :--- | :--- |
| **Path A: Web** | Browser (Chrome/Edge) | Web Bluetooth API | Tanpa instalasi, praktis. |
| **Path B: Native** | Aplikasi Android (APK) | Android Interface Bridge | Sangat stabil, akses hardware langsung. |

---

## 🔄 2. Alur Kerja (Workflow) Utama

### A. Scanning & First Connection
Saat pengguna menekan tombol **"Scan & Hubungkan"**:
1. **Deteksi Interface:** Sistem mengecek keberadaan `window.AndroidInterface`.
2. **Web Path:** Browser memunculkan dialog pencarian perangkat Bluetooth. Filter dijalankan untuk hanya menampilkan perangkat dengan prefix standar printer (RPN, MPT, Bluetooth Printer).
3. **Native Path:** Memanggil fungsi native `scanPrinters()` pada Android untuk mencari perangkat via Bluetooth/USB lokal.
4. **Binding:** Begitu terpilih, ID perangkat disimpan dan jalur komunikasi (`GATT Server` atau `Native Handle`) dibuka.

### B. Auto-Reconnect (Saat Refresh Halaman)
Sistem dirancang agar "tidak merepotkan" kasir.
1. **Trigger:** `onMounted` pada Vue component memicu `autoConnect()`.
2. **Jeda (Cooling Down):** Jeda **500ms** diberikan sebelum mencoba koneksi agar trafik Bluetooth pada OS stabil setelah refresh.
3. **Pemuatan Izin:** Sistem mengecek `localStorage` untuk melihat apakah printer sebelumnya belum diputus secara manual.
4. **Re-binding:** Tanpa perlu dialog pop-up kembali, sistem mencoba melakukan *handshake* ke perangkat terakhir yang diingat.

### C. Unexpected Disconnect (Auto-Retry)
Jika printer mati mendadak atau di luar jangkauan:
1. **Event Listener:** `gattserverdisconnected` atau `Native Callback` akan terpicu.
2. **State Update:** Icon printer di UI berubah menjadi merah (Offline).
3. **Retry Strategy:**
   - Sistem masuk ke fase **Loop Retry** (Mencoba kembali setiap 1 - 1.5 detik).
   - Maksimal percobaan: **5 kali** (Web) atau terus-menerus (Android).
4. **Auto-Recovery:** Begitu perangkat kembali online/terdeteksi, struk yang tertunda (jika ada) dapat dicetak kembali.

### D. Manual Disconnect (Reset)
Saat pengguna menekan **"Putuskan Koneksi"**:
1. **Flagging:** `printer_auto_reconnect` diubah menjadi `false` (Ini mencegah sistem mencoba menyambung sendiri).
2. **Teardown:** Jalur pengiriman data ditutup secara bersih.
3. **Forget Device:** Browser diperintahkan untuk "melupakan" izin akses ke perangkat tersebut demi keamanan.

---

## 💾 3. Detail Implementasi Teknis

### 1. Data Chunking (Anti-Lag)
Printer thermal memiliki buffer memori yang terbatas. Untuk mencegah printer "mogok" atau data terpotong saat mencetak struk panjang:
- Data dikirim dalam potongan (chunks) sebesar **512 bytes**.
- Ada jeda **50ms** di setiap pengiriman potongan agar printer memiliki waktu untuk memproses data.

### 2. Persistensi Settings
Settings berikut disimpan di `localStorage` agar tidak hilang saat aplikasi ditutup:
- `printer_width`: Pilihan lebar kertas (58mm atau 80mm).
- `printer_auto_reconnect`: Status izin auto-koneksi.
- `auto_print`: Status cetak otomatis setelah transaksi.

### 3. Native Bridge Settings
Saat menggunakan APK, pengaturan internal printer (DPI, Nama Printer) disinkronkan langsung ke sistem Android melalui:
```javascript
window.AndroidInterface.setPrinterSettings(203, width_mm, chars_per_line);
```

---

## ⚠️ 4. Troubleshooting & Best Practices

1. **Gagal Scan di Browser:** Pastikan menggunakan **HTTPS** (Web Bluetooth tidak bekerja di HTTP biasa).
2. **Teks Terpotong pada 58mm:** Gunakan fungsi `pad()` manual untuk memastikan perataan tengah (*center alignment*) sempurna karena printer 58mm sering memiliki margin yang berbeda.
3. **Status Online Tapi Tidak Mencetak:** Biasanya terjadi *Stale Connection* (koneksi basi). Lakukan **Putuskan Koneksi** lalu hubungkan kembali.

---

*Dibuat oleh: Antigravity AI Assistant*
*Terakhir diupdate: 2026-03-06*
