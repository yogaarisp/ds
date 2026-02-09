# QR Code Testing Guide

## ✅ Status: QR Code Generation Sudah Berfungsi

QR code untuk table sudah dikonfigurasi dengan benar dan menggunakan `FRONTEND_URL` dari environment variable.

## Current Configuration

**Backend (.env):**
```env
FRONTEND_URL=http://localhost:5173
```

**Generated QR URLs:**
- Table 1: `http://localhost:5173/customer/home?table=DS-Table1`
- Table 2: `http://localhost:5173/customer/home?table=ds-table2`

## Testing Steps

### 1. Test QR Code Generation
1. Buka http://localhost:5173/tables
2. Login sebagai admin/owner
3. Lihat existing tables atau buat table baru
4. Setiap table card akan menampilkan QR URL
5. Klik tombol "QR" untuk download QR code

### 2. Test QR Code Scanning
1. Scan QR code yang sudah didownload
2. Atau buka URL langsung: `http://localhost:5173/customer/home?table=DS-Table1`
3. Halaman customer akan terbuka dengan table sudah terpilih
4. Cek di halaman customer home - nama table akan muncul

### 3. Test Table Selection Flow
1. Dari QR scan → customer home page
2. Table sudah otomatis terpilih (lihat di header/greeting)
3. Customer bisa langsung order tanpa pilih table lagi
4. Saat checkout, table ID sudah otomatis terisi

## VPS Deployment

Untuk deployment di VPS, update `.env`:

```env
# Production
APP_URL=https://yourdomain.com
FRONTEND_URL=https://yourdomain.com
```

QR code akan otomatis generate URL production:
`https://yourdomain.com/customer/home?table=meja-1`

## Features yang Sudah Berfungsi

✅ **QR Code Generation**: Menggunakan FRONTEND_URL dari .env  
✅ **Table Parameter**: URL include parameter table code  
✅ **Customer Page**: Otomatis pilih table dari QR scan  
✅ **Download QR**: Button untuk download QR code image  
✅ **VPS Ready**: Tinggal update FRONTEND_URL untuk production  

## Next Steps untuk VPS

1. Update `FRONTEND_URL` di `.env` production
2. Build frontend untuk production
3. Test QR code generation di VPS
4. Pastikan customer page bisa akses table parameter
5. Test full flow: QR scan → order → checkout

## Troubleshooting

**QR URL salah?**
- Cek `FRONTEND_URL` di `.env`
- Restart Laravel server setelah update .env
- Clear config cache: `php artisan config:clear`

**Customer page tidak detect table?**
- Cek URL parameter: `?table=kode-meja`
- Cek customer page JavaScript untuk handle parameter
- Cek table code di database (harus match dengan QR URL)