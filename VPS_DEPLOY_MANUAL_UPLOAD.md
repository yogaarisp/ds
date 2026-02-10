# 📤 Deploy ke VPS - Manual Upload (Tanpa SSH)

## 📋 Overview
Panduan deploy ke VPS menggunakan FTP/SFTP atau File Manager (cPanel/Plesk) tanpa perlu SSH command line.

---

## 🎯 3 Cara Upload Manual

1. **FileZilla (FTP/SFTP)** ⭐ Recommended
2. **cPanel File Manager**
3. **Plesk File Manager**

---

## 📦 Persiapan Files (Di Lokal)

### Step 1: Build Frontend

```bash
# Buka Terminal/Command Prompt
cd frontend

# Install dependencies (jika belum)
npm install

# Build untuk production
npm run build

# Hasil ada di folder: frontend/dist/
```

### Step 2: Siapkan Backend Files

**PENTING:** Jangan upload folder-folder ini:
- ❌ `backend/vendor/` (akan di-install di server)
- ❌ `backend/node_modules/`
- ❌ `backend/storage/logs/` (akan di-generate)
- ❌ `backend/.env` (sudah ada di server)

**Yang perlu di-upload:**
- ✅ `backend/app/`
- ✅ `backend/bootstrap/`
- ✅ `backend/config/`
- ✅ `backend/database/`
- ✅ `backend/public/` (kecuali assets lama)
- ✅ `backend/resources/`
- ✅ `backend/routes/`
- ✅ `backend/composer.json`
- ✅ `backend/composer.lock`
- ✅ `backend/artisan`

---

## 🚀 Cara 1: Upload dengan FileZilla (RECOMMENDED)

### A. Install FileZilla

**Download:** https://filezilla-project.org/download.php?type=client

### B. Connect ke VPS

1. **Buka FileZilla**
2. **Klik "File" → "Site Manager"**
3. **Klik "New Site"**, beri nama (misal: "My VPS")
4. **Isi koneksi:**
   ```
   Protocol: SFTP - SSH File Transfer Protocol
   Host: [IP VPS Anda, misal: 103.123.45.67]
   Port: 22
   Logon Type: Normal
   User: root (atau username VPS Anda)
   Password: [password VPS Anda]
   ```
5. **Klik "Connect"**

### C. Backup Files Lama

**Di FileZilla (panel kanan = server):**

1. Navigate ke: `/var/www/html/dimsumpos/`
2. **Klik kanan** pada folder `backend` → **Rename** → `backend-backup-20260210`
3. **Klik kanan** pada folder `backend/public/assets` → **Rename** → `assets-backup-20260210`

### D. Upload Backend Files

**Di FileZilla:**

1. **Panel kiri (lokal):** Navigate ke folder project Anda
2. **Panel kanan (server):** Navigate ke `/var/www/html/dimsumpos/`
3. **Drag & drop** folder `backend` dari kiri ke kanan
4. **SKIP** file `.env` jika ditanya (jangan overwrite)
5. **Tunggu upload selesai** (bisa 5-15 menit tergantung koneksi)

### E. Upload Frontend Build

**Di FileZilla:**

1. **Panel kanan (server):** Navigate ke `/var/www/html/dimsumpos/backend/public/`
2. **Hapus folder lama:**
   - Klik kanan `assets` → Delete
   - Klik kanan `index.html` → Delete
3. **Panel kiri (lokal):** Navigate ke `frontend/dist/`
4. **Select semua files** di dalam `dist/` (Ctrl+A / Cmd+A)
5. **Drag & drop** ke `/var/www/html/dimsumpos/backend/public/`
6. **Tunggu upload selesai**

### F. Restore .env File

**Di FileZilla:**

1. Navigate ke backup: `/var/www/html/dimsumpos/backend-backup-20260210/`
2. **Klik kanan** `.env` → **Copy**
3. Navigate ke: `/var/www/html/dimsumpos/backend/`
4. **Klik kanan** di area kosong → **Paste**

### G. Set Permissions

**Di FileZilla (klik kanan → File permissions):**

1. **Folder `backend/storage/`:**
   - Numeric value: `775`
   - ✅ Recurse into subdirectories
   - Apply to: Directories and files

2. **Folder `backend/bootstrap/cache/`:**
   - Numeric value: `775`
   - ✅ Recurse into subdirectories

### H. Jalankan Commands di Server

**Sekarang perlu SSH untuk run commands. Alternatif:**

#### Option 1: SSH Terminal (Recommended)
```bash
ssh root@your-vps-ip

cd /var/www/html/dimsumpos/backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
sudo systemctl restart php8.2-fpm nginx
```

#### Option 2: cPanel Terminal (Jika ada cPanel)
- Login cPanel
- Cari "Terminal" atau "SSH Access"
- Jalankan commands di atas

#### Option 3: Minta Hosting Provider
- Kirim email ke support hosting
- Minta mereka jalankan commands di atas

---

## 🌐 Cara 2: Upload dengan cPanel File Manager

### A. Login cPanel

1. Buka browser: `http://your-domain.com:2083` atau `http://your-ip:2083`
2. Login dengan username & password cPanel

### B. Buka File Manager

1. Cari icon **"File Manager"**
2. Klik untuk membuka

### C. Navigate ke Project Folder

1. Klik folder `public_html` atau `www` atau `html`
2. Cari folder `dimsumpos`
3. Masuk ke folder tersebut

### D. Backup Files Lama

1. **Klik kanan** folder `backend` → **Rename** → `backend-backup-20260210`
2. **Klik kanan** folder `backend/public/assets` → **Rename** → `assets-backup-20260210`

### E. Upload Backend Files

1. **Klik tombol "Upload"** di toolbar atas
2. **Drag & drop** atau **Select files:**
   - Pilih semua folder backend (app, bootstrap, config, dll)
   - JANGAN upload: vendor, node_modules, .env
3. **Tunggu upload selesai**
4. **Klik "Go Back"**

### F. Extract Files (Jika Upload ZIP)

**Alternatif lebih cepat: Upload sebagai ZIP**

1. **Di lokal:** Compress folder backend jadi `backend.zip`
   ```bash
   # Mac/Linux
   zip -r backend.zip backend/ -x "backend/vendor/*" -x "backend/node_modules/*" -x "backend/.env"
   
   # Windows: Klik kanan backend → Send to → Compressed folder
   # Lalu hapus manual folder vendor, node_modules dari zip
   ```

2. **Upload `backend.zip`** via cPanel File Manager

3. **Klik kanan `backend.zip`** → **Extract**

4. **Hapus `backend.zip`** setelah extract

### G. Upload Frontend Build

1. **Navigate ke:** `backend/public/`
2. **Hapus folder `assets` dan file `index.html`**
3. **Klik "Upload"**
4. **Di lokal:** Buka folder `frontend/dist/`
5. **Select semua files** di dalam dist
6. **Upload semua files**

### H. Set Permissions

1. **Klik kanan** folder `backend/storage` → **Change Permissions**
2. **Set:** `775` (atau centang: Owner: RWX, Group: RWX, Public: R-X)
3. **✅ Recurse into subdirectories**
4. **Klik "Change Permissions"**

5. **Ulangi untuk:** `backend/bootstrap/cache`

### I. Run Commands

**Cari "Terminal" di cPanel:**

1. Di cPanel, cari **"Terminal"** atau **"SSH Access"**
2. Klik untuk membuka terminal
3. Jalankan commands:
   ```bash
   cd public_html/dimsumpos/backend
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan storage:link
   ```

---

## 🔧 Cara 3: Upload dengan WinSCP (Windows)

### A. Install WinSCP

**Download:** https://winscp.net/eng/download.php

### B. Connect ke VPS

1. **Buka WinSCP**
2. **Isi koneksi:**
   ```
   File protocol: SFTP
   Host name: [IP VPS]
   Port number: 22
   User name: root
   Password: [password VPS]
   ```
3. **Klik "Login"**

### C. Upload Files

**Sama seperti FileZilla:**
- Panel kiri = lokal
- Panel kanan = server
- Drag & drop untuk upload

---

## 📋 Checklist Upload Manual

### Persiapan:
- [ ] Build frontend (`npm run build`)
- [ ] Backup database VPS
- [ ] Backup files VPS (rename folder)
- [ ] Siapkan list files yang akan di-upload

### Upload Backend:
- [ ] Upload folder `app/`
- [ ] Upload folder `bootstrap/`
- [ ] Upload folder `config/`
- [ ] Upload folder `database/`
- [ ] Upload folder `public/` (kecuali assets lama)
- [ ] Upload folder `resources/`
- [ ] Upload folder `routes/`
- [ ] Upload `composer.json`
- [ ] Upload `composer.lock`
- [ ] Upload `artisan`
- [ ] **SKIP** `.env` (jangan overwrite)
- [ ] **SKIP** `vendor/` (akan di-install)

### Upload Frontend:
- [ ] Hapus `backend/public/assets/` lama
- [ ] Hapus `backend/public/index.html` lama
- [ ] Upload semua files dari `frontend/dist/`

### Set Permissions:
- [ ] `backend/storage/` → 775
- [ ] `backend/bootstrap/cache/` → 775

### Run Commands:
- [ ] `composer install`
- [ ] `php artisan migrate --force`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan storage:link`
- [ ] Restart PHP-FPM & Nginx

### Verify:
- [ ] Website bisa diakses
- [ ] Login works
- [ ] Images loading
- [ ] No errors

---

## 🆘 Troubleshooting

### Problem: Upload Lambat

**Solution:**
1. **Compress jadi ZIP** dulu di lokal
2. **Upload ZIP** (lebih cepat)
3. **Extract di server** via File Manager

### Problem: Permission Denied

**Solution:**
1. **Via File Manager:** Klik kanan → Change Permissions → 775
2. **Via Terminal:** `chmod -R 775 storage/`

### Problem: Composer Install Gagal

**Solution:**
1. **Check PHP version:** `php -v` (harus 8.1+)
2. **Check memory:** Edit `php.ini` → `memory_limit = 512M`
3. **Try:** `composer install --no-scripts`

### Problem: Migration Gagal

**Solution:**
1. **Check database connection** di `.env`
2. **Check migration status:** `php artisan migrate:status`
3. **Try one by one:** `php artisan migrate --step`

---

## 💡 Tips & Tricks

### Tip 1: Upload ZIP untuk Lebih Cepat

**Di lokal:**
```bash
# Compress backend (exclude vendor, node_modules)
zip -r backend.zip backend/ \
  -x "backend/vendor/*" \
  -x "backend/node_modules/*" \
  -x "backend/storage/logs/*" \
  -x "backend/.env"

# Compress frontend build
cd frontend/dist
zip -r ../../frontend-build.zip .
cd ../..
```

**Di server:**
- Upload `backend.zip` dan `frontend-build.zip`
- Extract via File Manager
- Jalankan composer install

### Tip 2: Gunakan .ftpignore

Buat file `.ftpignore` di root project:
```
backend/vendor/
backend/node_modules/
backend/storage/logs/
backend/.env
frontend/node_modules/
frontend/dist/
.git/
```

### Tip 3: Partial Upload

Jika hanya update beberapa file:
1. **Upload hanya file yang berubah**
2. **Tidak perlu upload semua**
3. **Lebih cepat!**

Contoh: Jika hanya update `CheckoutModal.vue`:
- Upload: `backend/public/assets/CheckoutModal-*.js`
- Skip yang lain

---

## 🔄 Alternative: Git Pull di Server

**Jika VPS punya Git:**

### Setup (Sekali saja):
```bash
# Via SSH atau cPanel Terminal
cd /var/www/html/dimsumpos
git init
git remote add origin https://github.com/YogaAP/Dimsum-Sarang.git
```

### Update (Setiap deploy):
```bash
cd /var/www/html/dimsumpos
git fetch origin main
git reset --hard origin/main
cd backend
composer install --no-dev
php artisan migrate --force
php artisan config:cache
cd ../frontend
npm install
npm run build
cp -r dist/* ../backend/public/
```

**Keuntungan:**
- ✅ Tidak perlu upload manual
- ✅ Lebih cepat
- ✅ Otomatis sync dengan GitHub

---

## 📞 Jika Tidak Bisa SSH/Terminal

**Hubungi hosting provider:**

Email template:
```
Subject: Request to Run Laravel Commands

Hi Support Team,

I need to update my Laravel application at /var/www/html/dimsumpos/backend

Could you please run these commands for me:

cd /var/www/html/dimsumpos/backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
sudo systemctl restart php8.2-fpm nginx

Thank you!
```

---

## ✅ Summary

**Cara Tercepat:**
1. ✅ Build frontend di lokal
2. ✅ Compress jadi ZIP (backend.zip + frontend-build.zip)
3. ✅ Upload via FileZilla/cPanel
4. ✅ Extract di server
5. ✅ Run commands via Terminal/SSH
6. ✅ Done!

**Tanpa SSH:**
- Upload manual via FTP/File Manager
- Minta hosting provider run commands
- Atau gunakan cPanel Terminal (jika ada)

---

**Last Updated**: February 10, 2026  
**Author**: Kiro AI Assistant  
**Version**: 1.0

