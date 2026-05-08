# 🚀 Quick Reference - VPS Deployment

## 📋 Ringkasan Cepat

### 4 Cara Deploy ke VPS:

1. **Otomatis dengan Script** ⭐ (Recommended - Butuh SSH)
2. **Manual Upload via FTP/FileZilla** 🌟 (Tanpa SSH)
3. **Manual Step-by-Step** (Via SSH)
4. **Git Pull** (Jika sudah setup)

---

## ⚡ Cara 1: Deploy Otomatis (TERMUDAH)

### Persiapan (Sekali saja):
```bash
# 1. Edit script, ganti IP VPS
nano deploy-to-vps.sh
# Ubah: VPS_IP="your-vps-ip" → VPS_IP="123.456.789.0"
# Save: Ctrl+O, Enter, Ctrl+X

# 2. Edit script rollback
nano rollback-vps.sh
# Ubah: VPS_IP="your-vps-ip" → VPS_IP="123.456.789.0"

# 3. Edit script check status
nano check-vps-status.sh
# Ubah: VPS_IP="your-vps-ip" → VPS_IP="123.456.789.0"
```

### Deploy:
```bash
# Jalankan script
./deploy-to-vps.sh

# Script akan otomatis:
# ✓ Check git status
# ✓ Build frontend
# ✓ Compress files
# ✓ Upload ke VPS
# ✓ Deploy di VPS
# ✓ Restart services
# ✓ Test website
```

### Rollback (Jika Ada Masalah):
```bash
./rollback-vps.sh
# Pilih backup yang ingin di-restore
```

### Check Status:
```bash
./check-vps-status.sh
# Lihat status services, logs, dll
```

---

## 🌟 Cara 2: Manual Upload via FTP/FileZilla (TANPA SSH)

### Persiapan Files:
```bash
# Jalankan script untuk prepare files
./prepare-manual-upload.sh

# Script akan:
# ✓ Build frontend
# ✓ Copy backend files (exclude vendor, node_modules)
# ✓ Create folder "upload-to-vps"
# ✓ Create ZIP files
# ✓ Create instructions
```

### Upload via FileZilla:

1. **Install FileZilla:** https://filezilla-project.org/
2. **Connect ke VPS:**
   - Protocol: SFTP
   - Host: your-vps-ip
   - Port: 22
   - User: root
   - Password: your-password

3. **Backup files lama:**
   - Navigate ke: `/var/www/html/dimsumpos/`
   - Rename `backend` → `backend-backup-20260210`

4. **Upload:**
   - Drag & drop folder `upload-to-vps/backend` ke server
   - Atau upload `backend-upload.zip` lalu extract

5. **Restore .env:**
   - Copy `.env` dari backup folder
   - Paste ke backend folder baru

6. **Set permissions:**
   - Right-click `backend/storage` → Permissions → 775
   - Right-click `backend/bootstrap/cache` → Permissions → 775

7. **Run commands** (butuh SSH atau cPanel Terminal):
   ```bash
   cd /var/www/html/dimsumpos/backend
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan storage:link
   sudo systemctl restart php8.2-fpm nginx
   ```

### Upload via cPanel File Manager:

1. **Login cPanel:** `http://your-domain.com:2083`
2. **Open File Manager**
3. **Navigate ke:** `public_html/dimsumpos/`
4. **Backup:** Rename `backend` → `backend-backup-20260210`
5. **Upload:** Click "Upload" → Upload `backend-upload.zip`
6. **Extract:** Right-click ZIP → Extract
7. **Restore .env** dari backup
8. **Set permissions:** 775 untuk storage & cache
9. **Run commands** via cPanel Terminal

**Detail lengkap:** Baca `VPS_DEPLOY_MANUAL_UPLOAD.md`

---

## 📝 Cara 3: Manual Step-by-Step (Via SSH)

### Di Lokal:

```bash
# 1. Build frontend
cd frontend
npm install
npm run build
cd ..

# 2. Compress files
tar -czf frontend-build.tar.gz -C frontend/dist .
tar -czf backend-files.tar.gz \
  --exclude='backend/vendor' \
  --exclude='backend/node_modules' \
  --exclude='backend/storage/logs/*' \
  --exclude='backend/.env' \
  backend/

# 3. Upload ke VPS
scp frontend-build.tar.gz root@your-vps-ip:/tmp/
scp backend-files.tar.gz root@your-vps-ip:/tmp/
```

### Di VPS:

```bash
# 1. Login
ssh root@your-vps-ip

# 2. Backup
cd /var/www/html/dimsumpos
mkdir -p backups/backup-$(date +%Y%m%d_%H%M%S)
cp -r backend backups/backup-$(date +%Y%m%d_%H%M%S)/

# 3. Maintenance mode
cd backend
php artisan down

# 4. Extract backend
cd /var/www/html/dimsumpos
tar -xzf /tmp/backend-files.tar.gz

# 5. Install dependencies
cd backend
composer install --no-dev --optimize-autoloader

# 6. Run migrations
php artisan migrate --force

# 7. Clear & cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Extract frontend
cd /var/www/html/dimsumpos
rm -rf backend/public/assets
tar -xzf /tmp/frontend-build.tar.gz -C backend/public/

# 9. Fix permissions
sudo chown -R www-data:www-data /var/www/html/dimsumpos
sudo chmod -R 755 /var/www/html/dimsumpos
sudo chmod -R 775 backend/storage

# 10. Restart services
sudo systemctl restart php8.2-fpm nginx

# 11. Exit maintenance
cd backend
php artisan up
```

---

## 🔄 Cara 4: Git Pull (Jika Sudah Setup)

### Setup (Sekali saja):
```bash
# Di VPS
ssh root@your-vps-ip
cd /var/www/html/dimsumpos
git init
git remote add origin https://github.com/YogaAP/Dimsum-Sarang.git
```

### Deploy:
```bash
# Di VPS
ssh root@your-vps-ip
cd /var/www/html/dimsumpos

# Pull & deploy
php artisan down
git fetch origin main
git reset --hard origin/main
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
cd ../frontend
npm install
npm run build
cp -r dist/* ../backend/public/
cd ..
sudo chown -R www-data:www-data .
sudo chmod -R 775 backend/storage
sudo systemctl restart php8.2-fpm nginx
cd backend
php artisan up
```

---

## 🆘 Troubleshooting Cepat

### Website Error 500:
```bash
ssh root@your-vps-ip
cd /var/www/html/dimsumpos/backend
tail -50 storage/logs/laravel.log
php artisan config:clear
php artisan cache:clear
sudo systemctl restart php8.2-fpm nginx
```

### Images Tidak Muncul:
```bash
ssh root@your-vps-ip
cd /var/www/html/dimsumpos/backend
rm -f public/storage
php artisan storage:link
sudo chmod -R 775 storage/app/public
sudo chown -R www-data:www-data storage/app/public
```

### Database Error:
```bash
ssh root@your-vps-ip
cd /var/www/html/dimsumpos/backend
php artisan migrate:status
php artisan migrate --force
```

### Permission Error:
```bash
ssh root@your-vps-ip
cd /var/www/html/dimsumpos
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 backend/storage
sudo chmod -R 775 backend/bootstrap/cache
```

---

## 📊 Command Cheat Sheet

### Check Status:
```bash
# Services
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql

# Logs
tail -f /var/www/html/dimsumpos/backend/storage/logs/laravel.log
tail -f /var/log/nginx/error.log

# Disk space
df -h

# Memory
free -m
```

### Clear Caches:
```bash
cd /var/www/html/dimsumpos/backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Optimize:
```bash
cd /var/www/html/dimsumpos/backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Restart Services:
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo systemctl restart mysql
```

---

## ✅ Pre-Deployment Checklist

- [ ] Backup database
- [ ] Backup files
- [ ] Test locally
- [ ] Commit all changes
- [ ] Push to GitHub
- [ ] Build frontend
- [ ] Check .env production

## ✅ Post-Deployment Checklist

- [ ] Website loads
- [ ] Login works
- [ ] Images load
- [ ] API works
- [ ] No errors in logs
- [ ] Test checkout
- [ ] Test transfer grouping
- [ ] Monitor for 1 hour

---

## 🎯 Recommended Workflow

### Development → Production:

1. **Develop & Test Locally**
   ```bash
   # Make changes
   # Test thoroughly
   git add -A
   git commit -m "Your changes"
   git push origin main
   ```

2. **Deploy to VPS**
   ```bash
   ./deploy-to-vps.sh
   ```

3. **Verify**
   ```bash
   ./check-vps-status.sh
   # Test website manually
   ```

4. **Monitor**
   ```bash
   ssh root@your-vps-ip
   tail -f /var/www/html/dimsumpos/backend/storage/logs/laravel.log
   ```

5. **Rollback if Needed**
   ```bash
   ./rollback-vps.sh
   ```

---

## 📞 Emergency Contacts

### Quick Rollback:
```bash
./rollback-vps.sh
```

### Force Restart Everything:
```bash
ssh root@your-vps-ip
sudo systemctl restart php8.2-fpm nginx mysql
```

### Nuclear Option (Last Resort):
```bash
ssh root@your-vps-ip
cd /var/www/html/dimsumpos
# Restore from latest backup
ls -la backups/
# Then manually restore
```

---

## 📚 Documentation Files

- `VPS_UPDATE_GUIDE.md` - Panduan lengkap detail (via SSH)
- `VPS_DEPLOY_MANUAL_UPLOAD.md` - Panduan upload manual (tanpa SSH) 🌟
- `DEPLOYMENT_QUICK_REFERENCE.md` - Quick reference (file ini)
- `deploy-to-vps.sh` - Script deploy otomatis (butuh SSH)
- `prepare-manual-upload.sh` - Script prepare files untuk upload manual 🌟
- `rollback-vps.sh` - Script rollback
- `check-vps-status.sh` - Script check status

---

**Last Updated**: February 10, 2026  
**Version**: 1.0  
**Author**: Kiro AI Assistant

