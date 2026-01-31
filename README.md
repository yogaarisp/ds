# Dimsum Sarang POS

Sistem Point of Sale (POS) profesional untuk usaha Dimsum Sarang.

## Fitur Utama

- **Produk Satuan** - Kelola produk dimsum per pcs
- **Paket & Mix Varian** - Kombinasi preset dengan harga khusus
- **Dynamic Pricing** - Kategori harga (Normal, Reseller, Keluarga) dengan adjustment otomatis
- **Order Sekarang & Pre-Order** - Dukungan pesanan terjadwal
- **Inventory Management** - Stok bahan baku & packaging
- **Resep Produk** - Pengurangan bahan baku otomatis
- **Packaging Rules** - Pemakaian packaging otomatis per transaksi
- **Laporan Lengkap** - Penjualan harian, per produk, per kategori, stok

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue.js 3 + Vite + Tailwind CSS
- **Database**: MySQL 8

## Instalasi

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Buat database MySQL: dimsum_pos
# Sesuaikan konfigurasi database di .env

php artisan migrate
php artisan db:seed
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

## Demo Login

- **Owner**: owner@dimsum.com / password
- **Kasir**: kasir@dimsum.com / password
- **Gudang**: gudang@dimsum.com / password

## Struktur Harga

| Kategori | Adjustment |
|----------|------------|
| Normal | Rp 0 |
| Reseller | -Rp 4.000 |
| Keluarga | -Rp 7.000 |

Harga Akhir = Harga Normal + Adjustment

## API Endpoints

### Auth
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/me` - User info

### Products
- `GET /api/products` - List produk
- `POST /api/products` - Tambah produk
- `PUT /api/products/{id}` - Update produk
- `DELETE /api/products/{id}` - Hapus produk

### Packages
- `GET /api/packages` - List paket
- `POST /api/packages` - Tambah paket
- `POST /api/packages/{id}/combinations` - Tambah kombinasi

### Transactions
- `GET /api/transactions` - List transaksi
- `POST /api/transactions` - Buat transaksi
- `POST /api/transactions/{id}/complete` - Selesaikan transaksi

### Reports
- `GET /api/reports/daily-sales` - Laporan harian
- `GET /api/reports/sales-by-product` - Penjualan per produk
- `GET /api/reports/product-stock` - Stok produk
- `GET /api/reports/inventory-stock` - Stok inventory

## License

MIT
