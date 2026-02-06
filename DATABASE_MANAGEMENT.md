# Database Management - Fitur Export/Import Database

## Overview
Fitur Database Management memungkinkan admin untuk melakukan backup dan restore database dengan mudah melalui interface web.

## Fitur yang Tersedia

### 1. Export Database
- **Fungsi**: Membuat backup database dalam format SQL
- **Format**: File `.sql` yang kompatibel dengan MySQL
- **Lokasi**: File akan didownload otomatis ke browser
- **Nama File**: `database_backup_YYYY-MM-DD_HH-MM-SS.sql`

### 2. Import Database  
- **Fungsi**: Restore database dari file SQL backup
- **Format**: Mendukung file `.sql` dan `.txt`
- **Ukuran Max**: 50MB per file
- **Peringatan**: Import akan menimpa semua data yang ada

### 3. Informasi Database
- **Database Name**: Nama database yang sedang digunakan
- **Ukuran Database**: Total ukuran dalam MB
- **Jumlah Tabel**: Total tabel dalam database
- **Jumlah Record**: Breakdown jumlah data per tabel utama
- **Info Backup Terakhir**: Tanggal dan ukuran backup terakhir

## Cara Menggunakan

### Export Database
1. Login sebagai admin
2. Masuk ke **Settings** → Tab **Database**
3. Klik tombol **📥 Export Database**
4. Tunggu proses export selesai
5. File akan otomatis terdownload

### Import Database
1. Login sebagai admin
2. Masuk ke **Settings** → Tab **Database**
3. Klik **Choose File** dan pilih file SQL backup
4. Klik tombol **📤 Import Database**
5. Konfirmasi peringatan (data akan ditimpa)
6. Tunggu proses import selesai

## Keamanan

### Akses Control
- Hanya user dengan role **Owner** yang dapat mengakses fitur ini
- Memerlukan autentikasi token Bearer
- Middleware `auth:sanctum` melindungi semua endpoint

### Validasi File
- Hanya file dengan ekstensi `.sql` dan `.txt` yang diterima
- Maksimal ukuran file 50MB
- Validasi MIME type untuk keamanan

## Technical Details

### Backend Endpoints
```php
GET  /api/database/info     // Informasi database
GET  /api/database/export   // Export database
POST /api/database/import   // Import database
```

### Frontend API
```javascript
// Get database info
const info = await databaseApi.info()

// Export database
const blob = await databaseApi.export()

// Import database
await databaseApi.import(file)
```

### Database Export Methods
1. **Primary**: Menggunakan `mysqldump` command
2. **Fallback**: PHP-based export jika mysqldump tidak tersedia

### Database Import Methods  
1. **Primary**: Menggunakan `mysql` command
2. **Fallback**: PHP-based import dengan transaction

## File Structure
```
backend/
├── app/Http/Controllers/Api/DatabaseController.php
├── storage/app/backups/     # Export files
├── storage/app/imports/     # Temporary import files
└── routes/api.php           # Routes definition

frontend/
├── src/api/index.js         # API functions
└── src/views/Settings.vue   # UI interface
```

## Error Handling

### Common Errors
- **File too large**: Maksimal 50MB
- **Invalid file type**: Hanya .sql dan .txt
- **Database connection**: Periksa konfigurasi database
- **Permission denied**: Periksa permission folder storage

### Troubleshooting
1. **Export gagal**: Periksa mysqldump tersedia di server
2. **Import gagal**: Periksa syntax SQL file
3. **Timeout**: Untuk database besar, tingkatkan timeout limit
4. **Memory limit**: Tingkatkan PHP memory_limit

## Best Practices

### Backup Strategy
- Lakukan export rutin (harian/mingguan)
- Simpan backup di lokasi yang aman
- Test restore secara berkala
- Dokumentasikan versi backup

### Import Guidelines
- Selalu backup database sebelum import
- Verifikasi file SQL sebelum import
- Test di environment development dulu
- Monitor log error selama import

## Monitoring & Logs

### Log Files
- Laravel log: `storage/logs/laravel.log`
- Database errors akan tercatat di log

### Monitoring Points
- Ukuran database yang terus bertambah
- Waktu export/import yang lama
- Error rate pada operasi database

## Future Enhancements

### Planned Features
- Scheduled automatic backups
- Backup retention policy
- Incremental backup support
- Cloud storage integration
- Backup encryption
- Multi-database support

### Performance Improvements
- Streaming export untuk database besar
- Compressed backup files
- Progress indicator untuk operasi lama
- Background job processing