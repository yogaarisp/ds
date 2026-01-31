# Panduan Migrasi Proyek (Windows ke macOS)

Panduan ini dibuat berdasarkan pengalaman migrasi proyek Dimsum Sarang POS dari lingkungan Windows ke macOS. Ikuti langkah-langkah ini setelah Anda menyalin semua file proyek ke Mac Anda.

## 1. Persiapan Awal (Backend)

Buka terminal dan arahkan ke folder `backend`.

### A. Perbaiki Izin File (Permissions)
Di macOS/Linux, file script perlu izin eksekusi.
```bash
chmod +x artisan
```

### B. Bersihkan Cache & Config Lama
Hapus folder/file yang digenerate oleh sistem Windows lama agar tidak konflik.
1. Hapus folder `vendor` (jika ada).
2. Hapus file `composer.lock`.
3. Hapus folder `public/storage` (ini adalah shortcut Windows yang rusak di Mac).

### C. Install Dependencies
```bash
composer install
```

### D. Setup Environment & Database
1. Pastikan file `.env` sudah ada (copy dari `.env.example` jika belum).
2. Pastikan konfigurasi database di `.env` sesuai (untuk SQLite):
   ```env
   DB_CONNECTION=sqlite
   # DB_HOST, DB_PORT, dll bisa dikomentari (#)
   ```
3. Buat file database kosong:
   ```bash
   touch database/database.sqlite
   ```

### E. Jalankan Migrasi & Seeding
```bash
php artisan migrate:fresh --seed
```
*Catatan: Jika terjadi error pada migrasi tertentu (misal syntax SQL yang tidak kompatibel antara MySQL/SQLite), file migrasi perlu disesuaikan.*

### F. Fix Storage Link (PENTING untuk Gambar)
Shortcut folder storage dari Windows tidak akan bekerja di Mac. Buat ulang symlink-nya:
```bash
php artisan storage:link
```

## 2. Persiapan Frontend

Buka terminal baru dan arahkan ke folder `frontend`.

### A. Bersihkan Node Modules (WAJIB)
Folder `node_modules` dari Windows mengandung binary yang **tidak akan jalan** di Mac (contoh error: `exec format error` atau `missing rollup-darwin-x64`).

Hapus folder dan file lock lama:
```bash
rm -rf node_modules package-lock.json
```

### B. Install Dependencies Baru
```bash
npm install
```

### C. Jalankan Server
```bash
npm run dev
```

## 3. Troubleshooting Umum

### Logo/Gambar Tidak Muncul
Jika database di-reset, referensi gambar di database mungkin hilang meskipun filenya ada di storage.
1. Pastikan `php artisan storage:link` sudah dijalankan.
2. Jika data hilang, jalankan seeder khusus atau update database untuk menunjuk ke file yang benar (seperti yang kita lakukan dengan `FixImagesSeeder`).

### Error "Permission Denied" pada Vite
Jika `npm run dev` gagal karena permission:
```bash
chmod +x node_modules/.bin/*
```

### Error Database "Connection Refused"
Biasanya terjadi jika `.env` masih menunjuk ke MySQL (127.0.0.1) tapi server MySQL belum jalan. Ubah ke `sqlite` di `.env` jika ingin setup yang lebih simpel tanpa install MySQL server.
