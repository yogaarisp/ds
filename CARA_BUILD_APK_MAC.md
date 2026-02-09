# Cara Build APK di Mac

## Masalah Saat Ini

✅ Android Studio sudah terinstall  
✅ SDK directory sudah ada  
❌ **SDK Platform 33 belum terinstall**  
❌ **Build Tools belum terinstall**

## Solusi: Install SDK Components

### Langkah 1: Buka Android Studio

1. Buka **Android Studio** dari Applications
2. Jika ada project terbuka, tutup dulu (File → Close Project)

### Langkah 2: Buka SDK Manager

**Cara 1 - Dari Welcome Screen:**
- Klik **More Actions** (⋮) di pojok kanan atas
- Pilih **SDK Manager**

**Cara 2 - Dari Menu:**
- Mac: **Android Studio → Settings** (atau tekan `Cmd + ,`)
- Pilih **Appearance & Behavior → System Settings → Android SDK**

### Langkah 3: Install SDK Platform 33

Di tab **SDK Platforms**:
1. ☑️ Centang **Android 13.0 (Tiramisu)** - API Level 33
2. Klik **Apply** di pojok kanan bawah
3. Klik **OK** pada dialog konfirmasi
4. Tunggu download selesai (sekitar 100-200 MB)

### Langkah 4: Install Build Tools

Di tab **SDK Tools**:
1. ☑️ Centang **Android SDK Build-Tools 33** (atau versi terbaru)
2. ☑️ Centang **Android SDK Platform-Tools** (jika belum)
3. ☑️ Centang **Android SDK Command-line Tools** (optional tapi recommended)
4. Klik **Apply**
5. Tunggu download selesai

### Langkah 5: Verify Installation

Tutup Android Studio, lalu jalankan di terminal:
```bash
./setup-android-sdk.sh
```

Harus muncul:
```
✅ Android SDK Platform 33 is installed
✅ Build Tools are installed
```

### Langkah 6: Build APK

Setelah semua terinstall:
```bash
./build-apk.sh
```

APK akan dibuat di: `DimsumSarangPOS-v1.0.9-release.apk`

## Troubleshooting

### Error: "SDK location not found"
**Solusi:** Jalankan `./setup-android-sdk.sh` untuk update local.properties

### Error: "Java version mismatch"
**Solusi:**
```bash
export JAVA_HOME=$(/usr/libexec/java_home -v 17)
./build-apk.sh
```

### Build sangat lambat
**Normal** untuk build pertama kali. Gradle akan download dependencies (bisa 5-10 menit).
Build selanjutnya akan lebih cepat.

## Kenapa di Windows Bisa Langsung?

Di Windows PC Anda:
- ✅ Android Studio sudah ter-setup lengkap
- ✅ SDK Platform 33 sudah terinstall
- ✅ Build Tools sudah terinstall
- ✅ Environment variables sudah di-set

Di Mac ini:
- ✅ Android Studio ada
- ❌ SDK components belum terinstall (perlu setup manual)

Setelah setup SDK di Mac, build akan bisa langsung seperti di Windows!

## Estimasi Waktu

- **Setup SDK**: 5-10 menit (download + install)
- **Build APK pertama**: 5-10 menit (download dependencies)
- **Build APK selanjutnya**: 1-2 menit

## Alternative: Build di Windows

Jika tidak mau setup di Mac, bisa:
1. Copy folder project ke Windows PC
2. Build di Windows (sudah bisa langsung)
3. Copy APK hasil build kembali ke Mac

Tapi lebih baik setup di Mac juga supaya bisa build kapan saja! 😊
