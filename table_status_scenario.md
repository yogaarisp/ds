# Skenario Implementasi Indikator Status Meja (Available/Occupied)

Dokumen ini menjelaskan alur kerja otomatisasi status meja untuk sistem Dimsum Sarang, mulai dari sisi Admin POS hingga pengalaman Pelanggan.

---

## 1. Ringkasan Fitur

Menambahkan indikator visual pada setiap meja secara real-time menggunakan sistem **3 Fase (Traffic Light)**. Status ini berubah **otomatis** mengikuti siklus transaksi, kecuali langkah terakhir (bersihkan meja) yang dilakukan **manual** oleh kasir/pelayan.

---

## 2. Sistem 3 Fase Status Meja (Traffic Light)

```
🟢 AVAILABLE  (Tersedia)      → Meja kosong, siap digunakan pelanggan baru
🔴 OCCUPIED   (Terisi)        → Ada pesanan aktif yang belum dibayar
🟡 FINISHING  (Selesai Bayar)  → Sudah dibayar, pelanggan masih di meja (makan/ngobrol)
```

### Mengapa 3 Fase?

**Masalah:** Jika hanya 2 status (available/occupied), meja akan langsung `available` setelah bayar — padahal pelanggan masih duduk makan. Ini menyebabkan:
- Kasir bisa salah assign pelanggan baru ke meja yang masih ada orang
- Data meja tidak akurat

**Solusi:** Fase `FINISHING` (kuning) menjadi **buffer**. Meja baru benar-benar `AVAILABLE` setelah kasir/pelayan **melihat secara fisik** meja sudah kosong dan menekan tombol "Bersihkan Meja".

---

## 3. Alur Perubahan Status Otomatis

| # | Aksi / Trigger | Status Sebelum | Status Sesudah | Dilakukan Oleh |
|---|:---|:---:|:---:|:---|
| 1 | Pesanan baru dibuat dengan `table_id` | � Available | �🔴 **Occupied** | ⚙️ Otomatis (Sistem) |
| 2 | Pesanan dibayar lunas (completed) | 🔴 Occupied | 🟡 **Finishing** | ⚙️ Otomatis (Sistem) |
| 3 | Pesanan dibatalkan (cancelled) | 🔴 Occupied | 🟢 **Available** | ⚙️ Otomatis (Sistem) |
| 4 | Kasir klik "Bersihkan Meja" | 🟡 Finishing | 🟢 **Available** | 👤 Manual (Kasir/Pelayan) |
| 5 | Admin set reservasi | Any | 🔵 **Reserved** | � Manual (Admin) — Opsional |

### Catatan Penting:
- **Step 2→3:** Setelah bayar, meja TIDAK langsung available. Ini mencegah orang lain "merebut" meja.
- **Step 4:** Satu-satunya langkah manual. Kasir menekan tombol setelah melihat meja benar-benar sudah kosong secara fisik.
- Jika ada **beberapa pesanan aktif** di 1 meja, meja baru berubah ke `finishing` setelah SEMUA pesanan selesai.

---

## 4. Alur Pengguna (User Journey)

### A. Sisi Admin — Halaman Meja (`/tables`)

**Tampilan Grid Meja:**
```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│   🟢 Meja 1  │  │   🔴 Meja 2  │  │   🟡 Meja 3  │
│   TERSEDIA   │  │   TERISI     │  │ SELESAI BAYAR │
│              │  │  Budi - 2org │  │  Ani - 4org   │
│              │  │  ⏱ 25 menit  │  │  ⏱ 45 menit   │
│  [Assign]    │  │  [Detail]    │  │ [Bersihkan]   │
└──────────────┘  └──────────────┘  └──────────────┘
```

- **Meja Hijau (Available):** Tampilkan tombol "Assign" untuk membuka POS
- **Meja Merah (Occupied):** Tampilkan nama pelanggan, jumlah orang, durasi, dan tombol "Lihat Detail"
- **Meja Kuning (Finishing):** Tampilkan info pesanan yang sudah selesai dan tombol **"Bersihkan Meja"**

### B. Sisi Admin — POS (`/pos`)

- Saat kasir memilih meja di POS:
  - Meja 🟢 Available → Bisa dipilih langsung
  - Meja 🔴 Occupied → Ditandai "Sedang Digunakan", tidak bisa dipilih (atau konfirmasi dulu)
  - Meja 🟡 Finishing → Ditandai "Belum Dibersihkan", tidak bisa dipilih

### C. Sisi Pelanggan — Scan QR

1. **Scan Meja Tersedia (🟢):**
   - Pelanggan masuk ke menu → Pesan → Bayar → Meja jadi 🔴 lalu 🟡 otomatis
   
2. **Scan Meja Terisi (🔴 atau 🟡):**
   - **Pelanggan yang sama** (berdasarkan session/customer_id) → Masuk ke halaman pesanan mereka yang sedang berjalan
   - **Pelanggan berbeda** → Tampilkan pesan: *"Mohon maaf, meja ini sedang digunakan. Silakan hubungi kasir."*

---

## 5. Langkah Teknis Implementasi

### Step 1: Backend — Database & Model
- **Kolom `status`** di tabel `tables` sudah ada. Tambahkan value `finishing` (saat ini: `available`, `occupied`, `reserved`).
- **Transaction Observer:** Buat `TransactionObserver` agar setiap kali transaksi dibuat/diupdate/dibatalkan, status meja ter-update otomatis.
- **Relasi `activeTransactions`:** Tambahkan relasi di model `Table` untuk mendapatkan semua transaksi yang sedang berlangsung di meja tersebut.

### Step 2: Backend — API Enhancement
- **`GET /tables`:** Sertakan `active_transactions_count` dan data pelanggan aktif agar frontend bisa menampilkan info langsung di kartu meja.
- **`POST /tables/{id}/clear`:** Endpoint baru untuk kasir menekan "Bersihkan Meja" → Set status ke `available`.
- **Validasi Create Transaction:** Tolak pembuatan order ke meja yang sedang `occupied` (kecuali oleh pelanggan yang sama).

### Step 3: Frontend — Admin UI (`Tables.vue`)
- Redesign kartu meja dengan badge warna status yang premium.
- Tambahkan tombol **"Bersihkan Meja"** yang hanya muncul pada status `finishing`.
- Tambahkan info pelanggan aktif (nama, durasi) pada meja yang `occupied`.

### Step 4: Frontend — POS Integration
- Filter/indikator pada pemilihan meja di halaman POS.
- Disable meja yang `occupied`/`finishing`.

### Step 5: Frontend — Customer UI
- Tambahkan validasi status meja saat pelanggan scan QR (cek via API `GET /tables/code/{code}`).
- Tampilkan notifikasi yang sesuai jika meja tidak tersedia.

---

## 6. Rangkuman Visual

```
[Pelanggan Pesan]──→ 🟢→🔴 Occupied (Otomatis)
        │
        ▼
[Pelanggan Bayar]──→ 🔴→🟡 Finishing (Otomatis)
        │
        ▼
[Kasir: "Bersihkan"]─→ 🟡→🟢 Available (Manual)
```

---

**Status Dokumen:** Menunggu Persetujuan  
**Tanggal:** 2026-02-11  
**Target:** Transparansi operasional meja & pencegahan kesalahan input
