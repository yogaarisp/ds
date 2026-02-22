# Setup Android Studio untuk Dimsum Sarang POS WebView

## ✅ Konfigurasi Project

Project ini sudah dikonfigurasi dengan:
- **Gradle**: 8.5
- **Android Gradle Plugin**: 8.2.2
- **Java**: 17 (JDK 17.0.9)
- **Compile SDK**: 33
- **Min SDK**: 21
- **Target SDK**: 33

## 📋 Langkah Setup di Android Studio

### 1. Install Java 17 (JDK)
Pastikan Java 17 sudah terinstall di:
```
C:\Program Files\Microsoft\jdk-17.0.9.8-hotspot
```

Atau download dari: https://adoptium.net/temurin/releases/

### 2. Buka Project di Android Studio

1. Buka Android Studio
2. **File → Open**
3. Pilih folder: `C:\laragon\www\ds\android-webview`
4. Klik **OK**

### 3. Set Gradle JDK ke Java 17

1. **File → Settings** (Ctrl+Alt+S)
2. **Build, Execution, Deployment → Build Tools → Gradle**
3. **Gradle JDK**: Pilih **17** atau **jbr-17** (JANGAN pilih 21)
4. Klik **Apply** → **OK**

### 4. Invalidate Caches (Jika Error)

1. **File → Invalidate Caches...**
2. Centang semua opsi
3. Klik **Invalidate and Restart**

### 5. Sync Project

1. Klik **File → Sync Project with Gradle Files**
2. Atau klik icon **Sync** (🔄) di toolbar
3. Tunggu sampai sync selesai

### 6. Build APK Release

**Via Android Studio:**
1. **Build → Generate Signed Bundle / APK**
2. Pilih **APK**
3. Klik **Next**
4. Pilih **release**
5. Klik **Finish**

**Via Command Line (Recommended):**
```bash
# Windows PowerShell
$env:ANDROID_HOME="C:\Users\Kee\AppData\Local\Android\Sdk"
$env:JAVA_HOME="C:\Program Files\Microsoft\jdk-17.0.9.8-hotspot"
.\gradlew.bat assembleRelease
```

APK akan ada di: `app\build\outputs\apk\release\app-release-unsigned.apk`

## 🔧 Troubleshooting

### Error: "SDK location not found"
**Solusi:**
1. Pastikan file `local.properties` ada dengan isi:
```properties
sdk.dir=C:\\Users\\Kee\\AppData\\Local\\Android\\Sdk
java.home=C:\\Program Files\\Microsoft\\jdk-17.0.9.8-hotspot
```

2. Atau set environment variable:
```bash
ANDROID_HOME=C:\Users\Kee\AppData\Local\Android\Sdk
```

### Error: "Incompatible Java 21 and Gradle"
**Solusi:**
- Pastikan Gradle JDK di Android Studio set ke **Java 17** (bukan 21)
- Restart Android Studio setelah mengubah JDK

### Error: "Gradle sync failed"
**Solusi:**
1. Delete folder `.gradle` di project
2. Delete folder `app/build`
3. **File → Invalidate Caches → Invalidate and Restart**
4. Sync ulang

### Build via Command Line Gagal
**Solusi:**
```bash
# Stop semua Gradle daemon
.\gradlew.bat --stop

# Clean dan build ulang
$env:ANDROID_HOME="C:\Users\Kee\AppData\Local\Android\Sdk"
$env:JAVA_HOME="C:\Program Files\Microsoft\jdk-17.0.9.8-hotspot"
.\gradlew.bat clean assembleRelease
```

## 📱 Install APK ke Device

### Via USB:
1. Enable **Developer Options** di Android
2. Enable **USB Debugging**
3. Hubungkan device ke PC
4. Run: `adb install app-release-unsigned.apk`

### Via File Transfer:
1. Copy APK ke device
2. Buka file manager di device
3. Tap APK file
4. Install

## 🌐 WebView Configuration

APK ini adalah WebView yang load URL:
```
https://ds.keetech.my.id
```

Untuk mengubah URL, edit file:
```
app/src/main/java/com/keetech/dimsumpos/MainActivity.java
```

Line:
```java
webView.loadUrl("https://ds.keetech.my.id");
```

## 📝 Version Info

- **App Name**: Dimsum Sarang POS
- **Package**: com.keetech.dimsumpos
- **Version Code**: 2
- **Version Name**: 1.0.6

## 🚀 Quick Build Command

```bash
# Build Release APK
$env:ANDROID_HOME="C:\Users\Kee\AppData\Local\Android\Sdk"; $env:JAVA_HOME="C:\Program Files\Microsoft\jdk-17.0.9.8-hotspot"; .\gradlew.bat assembleRelease

# Copy to root
Copy-Item app\build\outputs\apk\release\app-release-unsigned.apk ..\..\ds.apk -Force
```

## ✅ Checklist Setup

- [ ] Java 17 terinstall
- [ ] Android Studio terinstall
- [ ] Android SDK terinstall
- [ ] Gradle JDK set ke Java 17
- [ ] Project sync berhasil
- [ ] Build APK berhasil
- [ ] APK bisa diinstall di device

---

**Support:** Jika ada masalah, cek file ini atau hubungi developer.
