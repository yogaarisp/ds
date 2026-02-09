# 📁 Panduan Export Database

## Lokasi File Export

Ketika Anda melakukan export database dari halaman Settings → Database, file `.sql` akan otomatis terdownload ke **folder Downloads** browser Anda.

### 🔍 Cara Menemukan File Export:

#### **Google Chrome:**
- Tekan `Ctrl + J` (Windows/Linux) atau `Cmd + Shift + J` (Mac)
- Atau klik ikon download di pojok kanan atas browser
- File akan muncul dengan nama: `database_backup_YYYY-MM-DD_HH-mm-ss.sql`

#### **Firefox:**
- Tekan `Ctrl + Shift + Y` (Windows/Linux) atau `Cmd + Shift + Y` (Mac)
- Atau klik menu → Downloads

#### **Safari:**
- Tekan `Cmd + Option + L` (Mac)
- Atau klik View → Show Downloads

### 📂 Lokasi Default Folder Downloads:

#### **Windows:**
```
C:\Users\[Username]\Downloads\
```

#### **Mac:**
```
/Users/[Username]/Downloads/
```

#### **Linux:**
```
/home/[username]/Downloads/
```

### 📄 Format File Export:

File yang diexport berformat `.sql` standar MySQL yang dapat:
- ✅ Diimport kembali melalui halaman Settings → Database
- ✅ Digunakan di phpMyAdmin
- ✅ Dijalankan di MySQL Command Line
- ✅ Digunakan untuk backup dan migrasi

### 🔧 Troubleshooting:

**Jika file tidak muncul di Downloads:**
1. Cek apakah browser memblokir download
2. Periksa pengaturan download browser
3. Pastikan ada space disk yang cukup
4. Coba export ulang

**Jika file corrupt atau tidak bisa dibuka:**
1. Pastikan export selesai sempurna (tunggu notifikasi sukses)
2. Cek ukuran file (tidak boleh 0 KB)
3. Coba buka dengan text editor untuk memverifikasi isi SQL

### 💡 Tips:

- File export berisi seluruh struktur tabel dan data
- Ukuran file tergantung jumlah data di database
- Proses export bisa memakan waktu untuk database besar
- Selalu backup sebelum melakukan import database baru