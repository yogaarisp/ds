# 🚀 Quick Reference - VPS Deployment

## 📋 Ringkasan Cepat

### 3 Cara Deploy ke VPS:

1. **Otomatis dengan Script** ⭐ (Recommended)
2. **Manual Step-by-Step**
3. **Git Pull** (Jika sudah setup)

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

## 📝 Cara 2: Manual Step-by-Step

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

## 🔄 Cara 3: Git Pull (Jika Sudah Setup)

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

- `VPS_UPDATE_GUIDE.md` - Panduan lengkap detail
- `DEPLOYMENT_QUICK_REFERENCE.md` - Quick reference (file ini)
- `deploy-to-vps.sh` - Script deploy otomatis
- `rollback-vps.sh` - Script rollback
- `check-vps-status.sh` - Script check status

---

**Last Updated**: February 10, 2026  
**Version**: 1.0  
**Author**: Kiro AI Assistant

