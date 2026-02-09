# VPS Deployment Guide

## Environment Configuration untuk VPS

Untuk deployment di VPS, update file `.env` dengan konfigurasi berikut:

### Backend (.env)
```env
APP_NAME="Dimsum Sarang POS"
APP_ENV=production
APP_KEY=base64:Z7S/zUffacd4ZN/iHidcFuR2Z/BPrO2eDM2K73HhJ4Y=
APP_DEBUG=false
APP_URL=https://yourdomain.com
FRONTEND_URL=https://yourdomain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dimsum_pos
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file

# CORS Configuration
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

### Frontend Configuration
Update `frontend/src/api/index.js` untuk production:

```javascript
const API_BASE_URL = import.meta.env.VITE_API_URL || 'https://yourdomain.com/api'
```

Atau buat file `frontend/.env.production`:
```env
VITE_API_URL=https://yourdomain.com/api
```

## QR Code Configuration

QR code untuk table akan otomatis menggunakan `FRONTEND_URL` dari environment variable.

### Cara Kerja QR Code:
1. **Backend**: Model `Table` menggunakan `FRONTEND_URL` dari `.env`
2. **Frontend**: Halaman `/tables` menampilkan URL QR dan tombol download
3. **Customer**: Scan QR → redirect ke `/customer/home?table=kode-meja`

### Contoh URL yang dihasilkan:
- **Development**: `http://localhost:5173/customer/home?table=meja-1`
- **Production**: `https://yourdomain.com/customer/home?table=meja-1`

### Testing QR Code Generation

1. Buka http://localhost:5173/tables
2. Klik "Tambah Meja" dan buat meja baru
3. Lihat URL QR di card meja (menggunakan `FRONTEND_URL`)
4. Klik tombol "QR" untuk download QR code
5. Test scan QR code → harus redirect ke customer page dengan table terpilih

### Konfigurasi Environment Variables

**Development (.env):**
```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173
```

**Production (.env):**
```env
APP_URL=https://yourdomain.com
FRONTEND_URL=https://yourdomain.com
```

## Deployment Steps

### 1. Backend Deployment
```bash
# Upload files ke VPS
# Update .env dengan konfigurasi production
# Install dependencies
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 2. Frontend Deployment
```bash
# Build for production
npm run build:production

# Upload dist folder ke web server
# Configure web server untuk serve static files
```

### 3. Web Server Configuration (Nginx)
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/frontend/dist;
    index index.html;

    # Frontend routes
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API routes
    location /api {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Storage files
    location /storage {
        proxy_pass http://localhost:8000;
    }
}
```

## Important Notes

1. **HTTPS Required**: Untuk production, gunakan HTTPS untuk keamanan
2. **Database Backup**: Setup automatic database backup
3. **File Permissions**: Pastikan storage dan cache folder writable
4. **Environment Variables**: Jangan commit file `.env` ke repository
5. **QR Code URLs**: Akan otomatis menggunakan domain production

## Testing Checklist

- [ ] QR code generation menggunakan URL production
- [ ] Customer dapat scan QR dan masuk ke halaman yang benar
- [ ] Table selection berfungsi setelah scan QR
- [ ] API endpoints accessible dari frontend
- [ ] File upload (logo, images) berfungsi
- [ ] Database operations berfungsi