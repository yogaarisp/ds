# 📘 Panduan Git - Dimsum Sarang

Panduan lengkap untuk mengelola kode menggunakan Git dan GitHub.

---

## 🚀 Push Perubahan ke GitHub

### Cara Cepat (3 Steps)

```bash
# 1. Tambahkan semua file yang berubah
git add .

# 2. Commit dengan pesan deskriptif
git commit -m "Deskripsi perubahan Anda"

# 3. Push ke GitHub
git push
```

### Shortcut (1 Baris)

```bash
git add . && git commit -m "Update fitur X" && git push
```

---

## 📋 Commands Lengkap

### Cek Status

```bash
# Lihat file apa saja yang berubah
git status

# Lihat perbedaan detail
git diff
```

### Menambahkan File

```bash
# Tambahkan semua file
git add .

# Tambahkan file tertentu saja
git add nama-file.php

# Tambahkan folder tertentu
git add backend/
```

### Commit

```bash
# Commit dengan pesan singkat
git commit -m "Pesan commit"

# Commit dengan pesan panjang (akan buka editor)
git commit
```

### Push & Pull

```bash
# Push ke GitHub
git push

# Pull dari GitHub (ambil perubahan terbaru)
git pull
```

---

## 🔄 Workflow Sehari-hari

### Sebelum Mulai Kerja

```bash
# Selalu pull dulu untuk dapat update terbaru
git pull
```

### Setelah Selesai Edit

```bash
git add .
git commit -m "Deskripsi perubahan"
git push
```

### Jika Bekerja di PC/Laptop Berbeda

```bash
# Di device baru, clone repository
git clone git@github.com:YogaAP/Dimsum-Sarang.git

# Sebelum edit, selalu pull dulu
git pull

# Setelah edit, push seperti biasa
git add . && git commit -m "Update" && git push
```

---

## 📝 Contoh Pesan Commit yang Baik

```bash
# ✅ Bagus - Jelas dan deskriptif
git commit -m "Fix bug harga tidak muncul di halaman order"
git commit -m "Tambah fitur cetak invoice"
git commit -m "Update database config untuk production"
git commit -m "Perbaiki tampilan mobile di halaman POS"

# ❌ Kurang Baik - Terlalu umum
git commit -m "Update"
git commit -m "Fix bug"
git commit -m "Changes"
```

---

## ⚠️ Troubleshooting

### Konflik (Conflict)

Jika ada konflik saat pull:

```bash
# Lihat file yang konflik
git status

# Edit file tersebut, cari tanda:
# <<<<<<< HEAD
# (kode Anda)
# =======
# (kode dari server)
# >>>>>>> origin/main

# Setelah fix konflik, commit
git add .
git commit -m "Resolve conflict"
git push
```

### Batalkan Perubahan Lokal

```bash
# Batalkan perubahan di satu file
git checkout -- nama-file.php

# Batalkan SEMUA perubahan lokal (HATI-HATI!)
git checkout -- .
```

### Reset ke Commit Terakhir

```bash
# Soft reset (perubahan tetap ada, tapi unstaged)
git reset HEAD~1

# Hard reset (SEMUA perubahan hilang! HATI-HATI!)
git reset --hard HEAD~1
```

---

## 📊 Melihat History

```bash
# Lihat log commit
git log --oneline -10

# Lihat siapa yang edit file terakhir
git blame nama-file.php

# Lihat perubahan di commit tertentu
git show abc1234
```

---

## 🔗 Informasi Repository

- **Repository:** [https://github.com/YogaAP/Dimsum-Sarang](https://github.com/YogaAP/Dimsum-Sarang)
- **Branch Utama:** `main`
- **Remote:** `origin`

---

## 📱 Quick Reference Card

| Aksi | Command |
|------|---------|
| Cek status | `git status` |
| Tambah file | `git add .` |
| Commit | `git commit -m "pesan"` |
| Push | `git push` |
| Pull | `git pull` |
| Clone | `git clone <url>` |
| Lihat log | `git log --oneline -5` |
| Batalkan edit | `git checkout -- .` |

---

*Dibuat: 4 Februari 2026*
