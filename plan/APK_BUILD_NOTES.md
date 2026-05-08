# APK Build Notes - Dimsum Sarang POS v1.0.9

## Perubahan yang Dilakukan untuk Memperbaiki Error Instalasi

### 1. **AndroidManifest.xml**
- ✅ Mengubah `usesCleartextTraffic="false"` menjadi `"true"` 
  - Diperlukan untuk mengakses localhost dan HTTP connections

### 2. **build.gradle (App Level)**
- ✅ Menurunkan `compileSdk` dari 34 ke 33
- ✅ Menurunkan `targetSdk` dari 34 ke 33
- ✅ Menaikkan `versionCode` dari 4 ke 5
- ✅ Mengubah `versionName` dari "1.0.8" ke "1.0.9"
- ✅ Menambahkan `multiDexEnabled true`
- ✅ Menambahkan dependency `androidx.multidex:multidex:2.0.1`
- ✅ Menggunakan keystore baru: `release-v2.keystore`

### 3. **build.gradle (Project Level)**
- ✅ Menurunkan Android Gradle Plugin dari 8.7.3 ke 8.0.2

### 4. **gradle-wrapper.properties**
- ✅ Menurunkan Gradle dari 8.10.2 ke 8.0

### 5. **Keystore**
- ✅ Membuat keystore baru: `release-v2.keystore`
  - Alias: `dimsumpos`
  - Password: `dimsum123`
  - Validity: 10,000 days

## Cara Build APK

### Opsi 1: Menggunakan Script (Recommended)
```bash
./build-apk.sh
```

### Opsi 2: Manual
```bash
cd android-webview
./gradlew clean
./gradlew assembleRelease
```

APK akan tersimpan di:
- `android-webview/app/build/outputs/apk/release/app-release.apk`
- Atau di root: `DimsumSarangPOS-v1.0.9-release.apk`

## Troubleshooting

### Error: "Paket tidak valid"
- **Penyebab**: Keystore tidak valid atau APK tidak ter-sign dengan benar
- **Solusi**: Gunakan keystore baru `release-v2.keystore`

### Error: "Tidak kompatibel dengan sistem"
- **Penyebab**: targetSdk terlalu tinggi atau minSdk terlalu rendah
- **Solusi**: Sudah diperbaiki dengan targetSdk 33

### Error: "Java version mismatch"
- **Penyebab**: Gradle version tidak kompatibel dengan Java version
- **Solusi**: Sudah diperbaiki dengan Gradle 8.0

## Minimum Requirements
- **Android Version**: 5.0 (Lollipop) - API Level 21
- **Target Android**: 13 (Tiramisu) - API Level 33
- **Java**: JDK 8 atau lebih tinggi
- **Gradle**: 8.0

## Testing
Setelah build, test APK di:
1. ✅ Android 5.0 - 7.0 (API 21-24)
2. ✅ Android 8.0 - 10.0 (API 26-29)
3. ✅ Android 11 - 13 (API 30-33)

## Notes
- APK sudah di-sign dengan keystore baru
- Cleartext traffic diizinkan untuk development
- MultiDex enabled untuk support library yang lebih besar
