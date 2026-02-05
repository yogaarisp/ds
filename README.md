# 🥟 Dimsum Sarang POS & Online Ordering

Sistem Point of Sale (POS) dan Pemesanan Online profesional yang dirancang khusus untuk ekosistem Dimsum Sarang. Solusi terintegrasi ini mencakup pengelolaan stok, transaksi kasir, hingga portal pemesanan mandiri untuk pelanggan.

---

## 🌟 Fitur Utama

### 🛒 Point of Sale (POS) - Untuk Admin/Kasir
- **Produk Satuan & Paket**: Kelola produk per pcs atau paket kombinasi (mix varian) dengan mudah.
- **Dynamic Pricing**: Penyesuaian harga otomatis berdasarkan kategori pelanggan (**Normal**, **Reseller**, **Keluarga**).
- **Frozen vs Hot Pricing**: Penyesuaian harga otomatis (Diskon Rp 1.000) untuk pilihan produk *Frozen*.
- **Offline & Online Support**: Optimasi asset dan image loading untuk performa maksimal.

### 🍱 Portal Pelanggan (Online Ordering) - Untuk Customer
- **Self-Service Order**: Pelanggan dapat melihat menu, memilih varian, dan melakukan order langsung dari smartphone.
- **Order Tracking**: Riwayat pesanan yang transparan untuk pelanggan.
- **Responsive Design**: Tampilan premium dengan *glassmorphism* dan navigasi yang intuitif baik di web maupun mobile.

### 📦 Manajemen Inventori & Produksi
- **Stock Management**: Pantau stok bahan baku dan packaging secara real-time.
- **Automated Recipes**: Stok bahan baku berkurang otomatis berdasarkan resep setiap produk yang terjual.
- **Smart Packaging**: Perhitungan pemakaian box/packaging otomatis per transaksi berdasarkan jumlah item.

---

## 🔄 Alur Kerja (Workflow)

### 👨‍💼 Alur Admin & Kasir
1. **Login**: Admin/Kasir masuk ke dashboard.
2. **Setup**: Mengelola produk, kategori harga, dan stok awal di menu Inventori.
3. **Transaksi POS**: 
   - Memilih produk atau paket (Mix varian).
   - Memasukkan data pelanggan untuk penyesuaian harga otomatis.
   - Sistem otomatis menghitung kebutuhan packaging dan memotong stok bahan baku.
4. **Pembayaran**: Mendukung Full Payment atau DP (Down Payment).
5. **Monitoring**: Memantau laporan penjualan harian secara real-time.

### 📱 Alur Customer (Online Order)
1. **Pilih Menu**: Customer mengakses URL website/scan QR menu.
2. **Kustomisasi**: Memilih varian isi paket (misal: isi paket mix) dan cara penyajian (Hangat/Frozen).
3. **Checkout**: Mengisi data diri dan melakukan pesanan.
4. **Verifikasi**: Pesanan masuk ke sistem POS Admin untuk dikonfirmasi.
5. **Selsai**: Customer bisa memantau status pesanan mereka melalui halaman Riwayat Pesanan.

---

## 🛠️ Tech Stack

- **Backend**: Laravel 11 (PHP 8.2+) dengan RESTful API.
- **Frontend**: Vue.js 3 (Composition API) + Vite.
- **Styling**: Tailwind CSS dengan kustomisasi desain premium.
- **State Management**: Pinia.
- **Database**: MySQL 8.
- **Mobile Compatibility**: Capacitor (Dukungan penuh untuk build Android/iOS).

---

## 🚀 Instalasi & Pengembangan

### Prasyarat
- PHP 8.2+
- Node.js 18+
- Composer
- MySQL

### Backend Setup
```bash
cd backend
composer install
cp .env.example .env # Sesuaikan DB_DATABASE, DB_USERNAME, dll
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

### Frontend Setup
```bash
cd frontend
npm install
npm run dev
```

### Production Build (Deployment ke VPS)
```bash
# Jalankan script build otomatis (Mac/Linux)
chmod +x build-production.sh
./build-production.sh
```

---

## 🔑 Akses Demo

| Role | Username | Password |
|------|----------|----------|
| **Owner** | `owner@dimsum.com` | `password` |
| **Kasir** | `kasir@dimsum.com` | `password` |
| **Gudang** | `gudang@dimsum.com` | `password` |
| **Customer** | `guest@dimsum.com` | `password` |

---

## 📐 Struktur Harga & Aturan

### Kategori Pelanggan
| Tipe | Penyesuaian (Adjustment) |
|------|--------------------------|
| **Normal** | + Rp 0 |
| **Reseller** | - Rp 4.000 |
| **Keluarga** | - Rp 7.000 |

### Aturan Tambahan
- **Frozen Discount**: Pemotongan Rp 1.000 per porsi untuk sajian beku.
- **Automated Packaging**: Sistem memilih ukuran box (Box 4, Box 6, Box 10) secara cerdas berdasarkan total kuantitas order.

---

## 📝 Lisensi

Proyek ini dikembangkan secara eksklusif untuk **Dimsum Sarang**. Hak cipta dilindungi.
