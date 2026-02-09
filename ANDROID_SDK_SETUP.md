# Android SDK Setup & APK Build Guide

## Status Saat Ini

✅ **Konfigurasi APK sudah diperbaiki:**
- AndroidManifest.xml: `usesCleartextTraffic="true"` 
- build.gradle: targetSdk 33, versionCode 5, versionName "1.0.9"
- Keystore baru: `release-v2.keystore` (valid)
- Gradle: 8.2 dengan Java 17

❌ **Android SDK belum terinstall di sistem**

## Cara Install Android SDK

### Opsi 1: Install Android Studio (Recommended)
1. Download Android Studio dari: https://developer.android.com/studio
2. Install Android Studio
3. Buka Android Studio > Settings > Appearance & Behavior > System Settings > Android SDK
4. Install SDK Platform 33 (Android 13)
5. Install SDK Build Tools 33.0.0 atau lebih tinggi
6. Catat lokasi SDK (biasanya: `/Users/macbook/Library/Android/sdk`)

### Opsi 2: Install Command Line Tools Only
```bash
# Download command line tools
cd ~/Downloads
wget https://dl.google.com/android/repository/commandlinetools-mac-9477386_latest.zip

# Extract
unzip commandlinetools-mac-9477386_latest.zip

# Create SDK directory
mkdir -p ~/Library/Android/sdk/cmdline-tools
mv cmdline-tools ~/Library/Android/sdk/cmdline-tools/latest

# Set environment variables (tambahkan ke ~/.zshrc)
export ANDROID_HOME=~/Library/Android/sdk
export PATH=$PATH:$ANDROID_HOME/cmdline-tools/latest/bin
export PATH=$PATH:$ANDROID_HOME/platform-tools

# Install required packages
sdkmanager "platform-tools" "platforms;android-33" "build-tools;33.0.0"
```

### Opsi 3: Gunakan Homebrew
```bash
brew install --cask android-commandlinetools
```

## Setelah SDK Terinstall

### 1. Update local.properties
```bash
cd android-webview
echo "sdk.dir=$HOME/Library/Android/sdk" > local.properties
```

### 2. Build APK
```bash
# Set Java 17
export JAVA_HOME=$(/usr/libexec/java_home -v 17)

# Build
./gradlew clean assembleRelease
```

### 3. APK Location
APK akan tersimpan di:
```
android-webview/app/build/outputs/apk/release/app-release.apk
```

## Build Script (Setelah SDK Terinstall)

Gunakan script yang sudah dibuat:
```bash
./build-apk.sh
```

Script ini akan:
1. Set Java 17
2. Clean previous builds
3. Build release APK
4. Copy ke root dengan nama `DimsumSarangPOS-v1.0.9-release.apk`

## Troubleshooting

### Error: SDK location not found
**Solusi:**
```bash
# Cek apakah SDK ada
ls -la ~/Library/Android/sdk

# Jika tidak ada, install SDK terlebih dahulu
# Jika ada, pastikan local.properties benar
echo "sdk.dir=$HOME/Library/Android/sdk" > android-webview/local.properties
```

### Error: Java version mismatch
**Solusi:**
```bash
# Gunakan Java 17
export JAVA_HOME=$(/usr/libexec/java_home -v 17)
java -version  # Harus menampilkan Java 17
```

### Error: Gradle daemon issues
**Solusi:**
```bash
cd android-webview
./gradlew --stop
./gradlew clean
```

## APK yang Sudah Ada

Saat ini sudah ada APK yang ter-sign:
- **File**: `DimsumSarangPOS-v1.0.8-release.apk` (6.2 MB)
- **Tanggal**: Feb 7, 09:25
- **Status**: Sudah ter-sign dan siap diinstall

APK ini menggunakan konfigurasi lama (v1.0.8). Untuk mendapatkan APK dengan konfigurasi baru (v1.0.9), perlu install Android SDK terlebih dahulu.

## Perubahan di v1.0.9

1. ✅ `usesCleartextTraffic="true"` - Fix error "Paket tidak valid"
2. ✅ targetSdk 33 - Fix error "Tidak kompatibel dengan sistem"
3. ✅ Keystore baru yang valid - Fix signing issues
4. ✅ MultiDex enabled - Support library yang lebih besar
5. ✅ Gradle 8.2 + Java 17 - Kompatibilitas build

## Next Steps

1. **Jika ingin build APK baru:**
   - Install Android SDK (pilih salah satu opsi di atas)
   - Jalankan `./build-apk.sh`

2. **Jika ingin pakai APK yang ada:**
   - Gunakan `DimsumSarangPOS-v1.0.8-release.apk`
   - APK ini sudah ter-sign dan siap diinstall
   - Konfigurasi masih menggunakan versi lama

## Rekomendasi

Untuk mendapatkan APK dengan semua perbaikan, **install Android SDK** terlebih dahulu menggunakan salah satu cara di atas, kemudian build ulang APK.
