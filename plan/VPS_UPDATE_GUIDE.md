# 🚀 Panduan Update VPS Production

## 📋 Overview
Panduan lengkap untuk deploy semua perubahan dari development lokal ke VPS production server.

---

## ⚠️ PENTING - Backup Dulu!

Sebelum update, **WAJIB backup** database dan files:

```bash
# 1. Login ke VPS
ssh root@your-vps-ip

# 2. Backup Database
cd /var/www/html/dimsumpos
php artisan db:export

# 3. Download backup ke lokal (dari terminal lokal)
scp root@your-vps-ip:/var/www/html/dimsumpos/database-backup-*.sql ~/Desktop/

# 4. Backup files penting (dari VPS)
cp backend/.env backend/.env.backup
cp -r backend/storage/app/public backend/storage/app/public.backup
```

---

## 🔄 Tahapan Update VPS

### **TAHAP 1: Persiapan Lokal**

#### 1.1. Pastikan Semua Sudah Di-commit
```bash
# Cek status git
git status

# Jika ada yang belum di-commit
git add -A
git commit -m "Final changes before deployment"
git push origin main
```

#### 1.2. Build Frontend Production
```bash
# Masuk ke folder frontend
cd frontend

# Install dependencies (jika ada yang baru)
npm install

# Build untuk production
npm run build

# Hasil build ada di folder: frontend/dist/
```

#### 1.3. Compress Build Files
```bash
# Dari root project
cd ..

# Compress frontend build
tar -czf frontend-build.tar.gz -C frontend/dist .

# Compress backend (tanpa vendor dan node_modules)
tar -czf backend-files.tar.gz \
  --exclude='backend/vendor' \
  --exclude='backend/node_modules' \
  --exclude='backend/storage/logs/*' \
  --exclude='backend/.env' \
  backend/
```

---

### **TAHAP 2: Upload ke VPS**

#### 2.1. Upload Files
```bash
# Upload frontend build
scp frontend-build.tar.gz root@your-vps-ip:/tmp/

# Upload backend files
scp backend-files.tar.gz root@your-vps-ip:/tmp/
```

---

### **TAHAP 3: Update di VPS**

#### 3.1. Login ke VPS
```bash
ssh root@your-vps-ip
```

#### 3.2. Stop Services (Optional - untuk update aman)
```bash
# Stop Nginx (optional)
sudo systemctl stop nginx

# Atau set maintenance mode
cd /var/www/html/dimsumpos/backend
php artisan down --message="Updating system, please wait..."
```

#### 3.3. Update Backend

```bash
# Masuk ke folder project
cd /var/www/html/dimsumpos

# Backup current backend
mv backend backend.backup.$(date +%Y%m%d_%H%M%S)

# Extract new backend
mkdir backend
tar -xzf /tmp/backend-files.tar.gz -C /var/www/html/dimsumpos/

# Restore .env
cp backend.backup.*/backend/.env backend/.env

# Install/Update Composer dependencies
cd backend
composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data /var/www/html/dimsumpos/backend
sudo chmod -R 755 /var/www/html/dimsumpos/backend
sudo chmod -R 775 /var/www/html/dimsumpos/backend/storage
sudo chmod -R 775 /var/www/html/dimsumpos/backend/bootstrap/cache
```

#### 3.4. Run Database Migrations

```bash
cd /var/www/html/dimsumpos/backend

# Check pending migrations
php artisan migrate:status

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 3.5. Update Frontend

```bash
# Backup current frontend
cd /var/www/html/dimsumpos
mv backend/public/assets backend/public/assets.backup.$(date +%Y%m%d_%H%M%S)
mv backend/public/index.html backend/public/index.html.backup

# Extract new frontend build
tar -xzf /tmp/frontend-build.tar.gz -C /var/www/html/dimsumpos/backend/public/

# Set permissions
sudo chown -R www-data:www-data /var/www/html/dimsumpos/backend/public
sudo chmod -R 755 /var/www/html/dimsumpos/backend/public
```

#### 3.6. Restore Storage Links

```bash
cd /var/www/html/dimsumpos/backend

# Remove old symlink if exists
rm -f public/storage

# Create new symlink
php artisan storage:link

# Verify
ls -la public/storage
```

#### 3.7. Start Services

```bash
# Exit maintenance mode
php artisan up

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Start Nginx
sudo systemctl start nginx

# Or restart Nginx
sudo systemctl restart nginx
```

---

### **TAHAP 4: Verifikasi**

#### 4.1. Check Services Status
```bash
# Check Nginx
sudo systemctl status nginx

# Check PHP-FPM
sudo systemctl status php8.2-fpm

# Check MySQL
sudo systemctl status mysql
```

#### 4.2. Check Logs
```bash
# Laravel logs
tail -f /var/www/html/dimsumpos/backend/storage/logs/laravel.log

# Nginx error logs
tail -f /var/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

#### 4.3. Test Website
```bash
# Test dari VPS
curl -I http://localhost

# Test dari browser
# Buka: http://your-domain.com
```

---

## 🧪 Testing Checklist

### Backend API:
- [ ] Login works: `http://your-domain.com/api/login`
- [ ] Products load: `http://your-domain.com/api/products`
- [ ] Packages load: `http://your-domain.com/api/packages`
- [ ] Payment methods load: `http://your-domain.com/api/payment-methods`
- [ ] Settings load: `http://your-domain.com/api/settings`

### Frontend:
- [ ] Homepage loads
- [ ] Customer menu loads
- [ ] Admin login works
- [ ] POS page works
- [ ] Cart page works
- [ ] Checkout works
- [ ] Transfer bank grouping works (customer & admin)
- [ ] Images load correctly

### Database:
- [ ] New migrations applied
- [ ] New columns exist (check with phpMyAdmin or MySQL)
- [ ] Data intact

---

## 🔧 Troubleshooting

### Problem 1: 500 Internal Server Error

**Solution:**
```bash
# Check Laravel logs
tail -50 /var/www/html/dimsumpos/backend/storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data /var/www/html/dimsumpos/backend
sudo chmod -R 775 /var/www/html/dimsumpos/backend/storage

# Clear all caches
cd /var/www/html/dimsumpos/backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Problem 2: Database Connection Error

**Solution:**
```bash
# Check .env file
cat /var/www/html/dimsumpos/backend/.env | grep DB_

# Test MySQL connection
mysql -u your_db_user -p your_db_name

# Restart MySQL
sudo systemctl restart mysql
```

### Problem 3: Frontend Not Loading / Blank Page

**Solution:**
```bash
# Check if files exist
ls -la /var/www/html/dimsumpos/backend/public/

# Check Nginx config
sudo nginx -t

# Check Nginx logs
tail -50 /var/log/nginx/error.log

# Restart Nginx
sudo systemctl restart nginx
```

### Problem 4: Images Not Loading

**Solution:**
```bash
# Check storage link
ls -la /var/www/html/dimsumpos/backend/public/storage

# Recreate symlink
cd /var/www/html/dimsumpos/backend
rm -f public/storage
php artisan storage:link

# Check permissions
sudo chmod -R 775 storage/app/public
sudo chown -R www-data:www-data storage/app/public
```

### Problem 5: Migration Failed

**Solution:**
```bash
# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback --step=1

# Try again
php artisan migrate --force

# If still fails, check SQL syntax in migration file
```

---

## 📝 Quick Command Reference

### One-Line Update (After files uploaded)
```bash
cd /var/www/html/dimsumpos/backend && \
php artisan down && \
tar -xzf /tmp/backend-files.tar.gz -C /var/www/html/dimsumpos/ && \
composer install --no-dev --optimize-autoloader && \
php artisan migrate --force && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
tar -xzf /tmp/frontend-build.tar.gz -C /var/www/html/dimsumpos/backend/public/ && \
sudo chown -R www-data:www-data /var/www/html/dimsumpos && \
sudo systemctl restart php8.2-fpm nginx && \
php artisan up
```

### Clear All Caches
```bash
cd /var/www/html/dimsumpos/backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Optimize for Production
```bash
cd /var/www/html/dimsumpos/backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 🔐 Security Checklist

After deployment:
- [ ] `.env` file has correct production values
- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production` in `.env`
- [ ] Database credentials secure
- [ ] File permissions correct (755 for files, 775 for storage)
- [ ] Storage symlink working
- [ ] HTTPS enabled (if applicable)
- [ ] Firewall configured

---

## 📊 Post-Deployment Monitoring

### Monitor for 24 hours:
```bash
# Watch Laravel logs
tail -f /var/www/html/dimsumpos/backend/storage/logs/laravel.log

# Watch Nginx access logs
tail -f /var/log/nginx/access.log

# Watch Nginx error logs
tail -f /var/log/nginx/error.log

# Check disk space
df -h

# Check memory usage
free -m

# Check CPU usage
top
```

---

## 🎯 Rollback Plan (Jika Ada Masalah)

### Quick Rollback:
```bash
# Stop services
cd /var/www/html/dimsumpos/backend
php artisan down

# Restore backend
rm -rf backend
mv backend.backup.YYYYMMDD_HHMMSS backend

# Restore frontend
rm -rf backend/public/assets
rm backend/public/index.html
mv backend/public/assets.backup.YYYYMMDD_HHMMSS backend/public/assets
mv backend/public/index.html.backup backend/public/index.html

# Restore database (if needed)
mysql -u your_db_user -p your_db_name < ~/database-backup-YYYYMMDD_HHMMSS.sql

# Clear caches
php artisan config:clear
php artisan cache:clear

# Restart services
sudo systemctl restart php8.2-fpm nginx
php artisan up
```

---

## 📱 Alternative: Using Git Pull (Recommended)

### Setup (One-time only):

```bash
# On VPS
cd /var/www/html/dimsumpos

# Initialize git (if not already)
git init
git remote add origin https://github.com/YogaAP/Dimsum-Sarang.git

# Create .gitignore for production
cat > .gitignore << 'EOF'
backend/.env
backend/vendor/
backend/node_modules/
backend/storage/logs/*
backend/storage/framework/cache/*
backend/storage/framework/sessions/*
backend/storage/framework/views/*
frontend/node_modules/
frontend/dist/
EOF
```

### Update Using Git:

```bash
# On VPS
cd /var/www/html/dimsumpos

# Maintenance mode
php artisan down

# Pull latest changes
git fetch origin main
git reset --hard origin/main

# Update backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend on VPS (if Node.js installed)
cd ../frontend
npm install
npm run build

# Copy build to public
cp -r dist/* ../backend/public/

# Set permissions
cd ..
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 backend/storage
sudo chmod -R 775 backend/bootstrap/cache

# Restart services
sudo systemctl restart php8.2-fpm nginx

# Exit maintenance
cd backend
php artisan up
```

---

## ✅ Final Checklist

Before closing:
- [ ] Backup completed
- [ ] Files uploaded
- [ ] Backend updated
- [ ] Migrations run
- [ ] Frontend updated
- [ ] Permissions set
- [ ] Services restarted
- [ ] Website tested
- [ ] No errors in logs
- [ ] Customer can order
- [ ] Admin can checkout
- [ ] Transfer grouping works
- [ ] Images loading
- [ ] Performance good

---

## 📞 Support

Jika ada masalah:
1. Check logs first
2. Try clearing caches
3. Check permissions
4. Restart services
5. If still fails, rollback

---

**Last Updated**: February 10, 2026  
**Author**: Kiro AI Assistant  
**Version**: 1.0

