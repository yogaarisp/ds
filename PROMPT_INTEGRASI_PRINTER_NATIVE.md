# Prompt: Integrasi Printer Thermal Native untuk APK Android

## Konteks Proyek
Saya memiliki aplikasi POS (Point of Sale) berbasis **Vue.js + Laravel** yang dibungkus dalam WebView Android. Proyek Android-nya ada di folder `android-webview/`. Saya ingin menambahkan fitur **printer thermal native** (Bluetooth & USB) agar bisa mencetak struk langsung dari APK tanpa bergantung pada Web Bluetooth API.

## Library yang Digunakan
- **ESCPOS-ThermalPrinter-Android** versi 3.3.0 dari DantSu (via JitPack)
- Repository: `https://github.com/DantSu/ESCPOS-ThermalPrinter-Android`

---

## File yang Perlu Diubah

### 1. `android-webview/build.gradle` (Top-level)

Tambahkan repository **JitPack** di blok `allprojects > repositories`:

```gradle
allprojects {
    repositories {
        google()
        mavenCentral()
        maven { url 'https://jitpack.io' }  // <-- TAMBAHKAN INI
    }
}
```

---

### 2. `android-webview/app/build.gradle` (App-level)

Tambahkan dependency library printer di blok `dependencies`:

```gradle
dependencies {
    implementation 'androidx.appcompat:appcompat:1.6.1'
    implementation 'androidx.core:core:1.12.0'
    implementation 'androidx.multidex:multidex:2.0.1'
    
    // Thermal Printer Library  <-- TAMBAHKAN INI
    implementation 'com.github.DantSu:ESCPOS-ThermalPrinter-Android:3.3.0'
}
```

---

### 3. `android-webview/app/src/main/AndroidManifest.xml`

Tambahkan izin Bluetooth dan USB **sebelum tag `<application>`**:

```xml
<!-- Printing Permissions -->
<uses-permission android:name="android.permission.BLUETOOTH" android:maxSdkVersion="30" />
<uses-permission android:name="android.permission.BLUETOOTH_ADMIN" android:maxSdkVersion="30" />
<uses-permission android:name="android.permission.BLUETOOTH_SCAN" android:usesPermissionFlags="neverForLocation" />
<uses-permission android:name="android.permission.BLUETOOTH_CONNECT" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.USB_PERMISSION" />
```

---

### 4. `MainActivity.java` — Bagian Import & Variabel

Tambahkan import berikut di bagian atas file (setelah import yang sudah ada):

```java
import android.Manifest;
import android.content.pm.PackageManager;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import java.util.ArrayList;
import java.util.List;
import org.json.JSONArray;
import org.json.JSONObject;

import com.dantsu.escposprinter.EscPosPrinter;
import com.dantsu.escposprinter.connection.DeviceConnection;
import com.dantsu.escposprinter.connection.bluetooth.BluetoothConnection;
import com.dantsu.escposprinter.connection.bluetooth.BluetoothPrintersConnections;
import com.dantsu.escposprinter.connection.usb.UsbConnection;
import com.dantsu.escposprinter.connection.usb.UsbPrintersConnections;
import com.dantsu.escposprinter.exceptions.EscPosBarcodeException;
import com.dantsu.escposprinter.exceptions.EscPosConnectionException;
import com.dantsu.escposprinter.exceptions.EscPosParserException;
import com.dantsu.escposprinter.exceptions.EscPosEncodingException;
```

Tambahkan variabel instance di dalam class `MainActivity`:

```java
private DeviceConnection selectedPrinter = null;
private int printerDpi = 203;
private float printerWidthMm = 48f;
private int printerNbrCharactersPerLine = 32;
```

---

### 5. `MainActivity.java` — Method Bridge di `WebAppInterface`

Tambahkan method-method berikut di dalam inner class `WebAppInterface` (yang sudah ada di `MainActivity.java`). Class ini sudah terdaftar sebagai `AndroidInterface` via `webView.addJavascriptInterface(new WebAppInterface(), "AndroidInterface")`.

```java
@android.webkit.JavascriptInterface
public String scanPrinters() {
    JSONArray printerList = new JSONArray();
    try {
        // Bluetooth Printers
        if (checkBluetoothPermissions()) {
            BluetoothConnection[] bluetoothDevices = BluetoothPrintersConnections.getPairedBluetoothPrinters();
            if (bluetoothDevices != null) {
                for (BluetoothConnection device : bluetoothDevices) {
                    JSONObject obj = new JSONObject();
                    obj.put("name", device.getDevice().getName());
                    obj.put("address", device.getDevice().getAddress());
                    obj.put("type", "bluetooth");
                    printerList.put(obj);
                }
            }
        }

        // USB Printers
        UsbConnection[] usbDevices = UsbPrintersConnections.getList(MainActivity.this);
        if (usbDevices != null) {
            for (UsbConnection device : usbDevices) {
                JSONObject obj = new JSONObject();
                obj.put("name", "USB Printer");
                obj.put("address", "usb");
                obj.put("type", "usb");
                printerList.put(obj);
            }
        }
    } catch (Exception e) {
        e.printStackTrace();
    }
    return printerList.toString();
}

@android.webkit.JavascriptInterface
public void setPrinterSettings(int dpi, float widthMm, int charPerLine) {
    printerDpi = dpi;
    printerWidthMm = widthMm;
    printerNbrCharactersPerLine = charPerLine;
}

@android.webkit.JavascriptInterface
public String connectBluetoothPrinter(String address) {
    try {
        if (!checkBluetoothPermissions()) {
            return "ERROR: Permission denied";
        }
        BluetoothConnection[] bluetoothDevices = BluetoothPrintersConnections.getPairedBluetoothPrinters();
        if (bluetoothDevices != null) {
            for (BluetoothConnection device : bluetoothDevices) {
                if (device.getDevice().getAddress().equals(address)) {
                    selectedPrinter = device;
                    return "SUCCESS";
                }
            }
        }
        return "ERROR: Device not found";
    } catch (Exception e) {
        return "ERROR: " + e.getMessage();
    }
}

@android.webkit.JavascriptInterface
public String connectUsbPrinter() {
    try {
        UsbConnection[] usbDevices = UsbPrintersConnections.getList(MainActivity.this);
        if (usbDevices != null && usbDevices.length > 0) {
            selectedPrinter = usbDevices[0];
            return "SUCCESS";
        }
        return "ERROR: No USB printer found";
    } catch (Exception e) {
        return "ERROR: " + e.getMessage();
    }
}

@android.webkit.JavascriptInterface
public String printFormattedText(String text) {
    if (selectedPrinter == null) {
        return "ERROR: No printer connected";
    }
    try {
        EscPosPrinter printer = new EscPosPrinter(selectedPrinter, printerDpi, printerWidthMm,
                printerNbrCharactersPerLine);
        printer.printFormattedTextAndCut(text);
        return "SUCCESS";
    } catch (Exception e) {
        e.printStackTrace();
        return "ERROR: " + e.getMessage();
    }
}

@android.webkit.JavascriptInterface
public String disconnectPrinter() {
    selectedPrinter = null;
    return "SUCCESS";
}

private boolean checkBluetoothPermissions() {
    if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.S) {
        if (ContextCompat.checkSelfPermission(MainActivity.this,
                Manifest.permission.BLUETOOTH_CONNECT) != PackageManager.PERMISSION_GRANTED ||
                ContextCompat.checkSelfPermission(MainActivity.this,
                        Manifest.permission.BLUETOOTH_SCAN) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(MainActivity.this,
                    new String[] { Manifest.permission.BLUETOOTH_CONNECT, Manifest.permission.BLUETOOTH_SCAN },
                    PERMISSION_ALL);
            return false;
        }
    } else {
        if (ContextCompat.checkSelfPermission(MainActivity.this,
                Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            ActivityCompat.requestPermissions(MainActivity.this,
                    new String[] { Manifest.permission.ACCESS_FINE_LOCATION }, PERMISSION_ALL);
            return false;
        }
    }
    return true;
}
```

---

### 6. `frontend/src/services/thermalPrinter.js` — Method Native

Tambahkan method-method berikut di class `ThermalPrinterService`:

```javascript
// Tambahkan property di constructor:
// this.type = 'web' // 'web' or 'native'

/**
 * Check if running inside the Android APK
 */
isAndroidApp() {
    return !!window.AndroidInterface
}

/**
 * Scan native printers (Bluetooth paired & USB)
 */
async scanNative() {
    if (!this.isAndroidApp()) return []
    try {
        const data = window.AndroidInterface.scanPrinters()
        return JSON.parse(data || '[]')
    } catch (error) {
        console.error('Scan native failed:', error)
        return []
    }
}

/**
 * Connect to native printer
 */
async connectNative(printer) {
    if (!this.isAndroidApp()) return false
    try {
        let result = ''
        if (printer.type === 'bluetooth') {
            result = window.AndroidInterface.connectBluetoothPrinter(printer.address)
        } else {
            result = window.AndroidInterface.connectUsbPrinter()
        }

        if (result === 'SUCCESS') {
            this.connected = true
            this.type = 'native'

            // Sync settings
            window.AndroidInterface.setPrinterSettings(
                203, // DPI
                this.printerWidth === 58 ? 48.0 : 72.0, // Width MM
                this.printerWidth === 58 ? 32 : 48 // Chars per line
            )
            return true
        }
        throw new Error(result)
    } catch (error) {
        console.error('Connect native failed:', error)
        throw error
    }
}
```

Modifikasi method `connect()` agar skip Web Bluetooth saat running di APK:

```javascript
async connect() {
    try {
        if (this.isAndroidApp()) {
            return true // Delegate to scanNative/connectNative
        }
        // ... sisa kode Web Bluetooth yang sudah ada ...
    }
}
```

Modifikasi method `disconnect()`:

```javascript
async disconnect() {
    if (this.isAndroidApp()) {
        window.AndroidInterface.disconnectPrinter()
        this.connected = false
        return
    }
    // ... sisa kode Web Bluetooth disconnect yang sudah ada ...
}
```

Modifikasi method `printReceipt()` agar support native:

```javascript
async printReceipt(receiptData) {
    try {
        if (this.isAndroidApp() && this.type === 'native') {
            const text = this.generateNativeFormattedText(receiptData)
            const result = window.AndroidInterface.printFormattedText(text)
            if (result !== 'SUCCESS') throw new Error(result)
            return true
        }
        // ... sisa kode Web Bluetooth print yang sudah ada ...
    }
}
```

Tambahkan method `generateNativeFormattedText()` untuk format struk:

```javascript
generateNativeFormattedText(data) {
    const settingsStore = useSettingsStore()
    const storeName = data.storeName || settingsStore.storeName || 'DIMSUM SARANG'
    const storeAddress = data.storeAddress || settingsStore.settings.store_address || ''
    const storePhone = data.storePhone || settingsStore.settings.store_phone || ''

    const divider = '[C]' + '-'.repeat(this.printerWidth === 58 ? 32 : 48) + '\n'
    let t = ""

    t += `[C]<b><font size='big'>${storeName.toUpperCase()}</font></b>\n`
    if (storeAddress) t += `[C]${storeAddress}\n`
    if (storePhone) t += `[C]Tel: ${storePhone}\n`
    t += divider

    t += `[L]No: ${data.transactionNumber || '-'}\n`
    t += `[L]Tanggal: ${data.date || '-'}\n`
    t += `[L]Kasir: ${data.cashier || '-'}\n`
    if (data.tableName) t += `[L]Meja: ${data.tableName}\n`
    if (data.customerName) t += `[L]Customer: ${data.customerName}\n`
    t += divider

    data.items.forEach(item => {
        t += `[L]<b>${item.name}</b>\n`
        const qtyPrice = `  ${item.quantity} x ${this.formatCurrency(item.price)}`
        const sub = this.formatCurrency(item.subtotal)
        t += `[L]${qtyPrice}[R]${sub}\n`
    })

    t += divider
    t += `[L]Subtotal:[R]${this.formatCurrency(data.subtotal)}\n`
    if (data.discount > 0) t += `[L]Diskon:[R]-${this.formatCurrency(data.discount)}\n`
    if (data.tax > 0) t += `[L]Pajak:[R]${this.formatCurrency(data.tax)}\n`
    t += `[L]<b>TOTAL:[R]${this.formatCurrency(data.total)}</b>\n`
    t += '\n'

    t += `[L]Bayar:[R]${this.formatCurrency(data.paid)}\n`
    t += `[L]Kembali:[R]${this.formatCurrency(data.change)}\n`
    t += `[L]Metode:[R]${data.paymentMethod || 'Cash'}\n`

    t += '\n'
    t += `[C]Terima Kasih\n`
    t += `[C]Selamat Menikmati!\n`
    t += '\n'

    return t
}
```

---

### 7. `Settings.vue` — UI Scan Printer

Tambahkan state di bagian `<script setup>`:

```javascript
const discoveredPrinters = ref([])
const showNativeScanResult = ref(false)
```

Modifikasi fungsi `handleConnect()` untuk mendukung scan native:

```javascript
async function handleConnect() {
    if (thermalPrinter.isAndroidApp()) {
        connecting.value = true
        try {
            const printers = await thermalPrinter.scanNative()
            discoveredPrinters.value = printers
            showNativeScanResult.value = true
        } catch (error) {
            // Show error
        } finally {
            connecting.value = false
        }
        return
    }
    // ... sisa kode Web Bluetooth connect ...
}
```

Tambahkan fungsi `connectToNativePrinter()`:

```javascript
async function connectToNativePrinter(printer) {
    loading.value = true
    try {
        await thermalPrinter.connectNative(printer)
        printerConnected.value = true
        showNativeScanResult.value = false
        // Show success message
    } catch (error) {
        // Show error
    } finally {
        loading.value = false
    }
}
```

Modifikasi `handleDisconnect()`:

```javascript
async function handleDisconnect() {
    if (thermalPrinter.isAndroidApp()) {
        try {
            await thermalPrinter.disconnect()
            printerConnected.value = false
            // Show success
        } catch (error) {
            // Show error
        }
        return
    }
    // ... sisa kode disconnect ...
}
```

Tambahkan modal untuk menampilkan daftar printer yang ditemukan di bagian `<template>`:

```html
<!-- Native Printer Scan Result Modal -->
<div v-if="showNativeScanResult" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60">
    <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="font-bold">Pilih Printer</h3>
            <button @click="showNativeScanResult = false">✕</button>
        </div>
        <div class="p-4 max-h-[60vh] overflow-y-auto space-y-3">
            <div v-if="discoveredPrinters.length === 0" class="text-center py-10 text-gray-400">
                <p>Tidak ada printer ditemukan. Pastikan Bluetooth aktif & sudah dipairing.</p>
            </div>
            <button 
                v-for="printer in discoveredPrinters" 
                :key="printer.address"
                @click="connectToNativePrinter(printer)"
                class="w-full flex items-center gap-4 p-4 rounded-2xl border hover:bg-orange-50"
            >
                <div class="text-left flex-1">
                    <div class="font-bold">{{ printer.name }}</div>
                    <div class="text-xs text-gray-400">{{ printer.address }} ({{ printer.type }})</div>
                </div>
            </button>
        </div>
        <div class="p-4 border-t">
            <button @click="handleConnect" class="w-full py-2 text-sm font-bold text-gray-500">
                Refresh List
            </button>
        </div>
    </div>
</div>
```

---

## Cara Build APK di Android Studio (MacBook)

1. Buka folder `android-webview/` di Android Studio
2. Sync Gradle (File > Sync Project with Gradle Files)
3. Pastikan tidak ada error
4. Build > Build Bundle(s) / APK(s) > Build APK(s)
5. APK akan muncul di `android-webview/app/build/outputs/apk/release/`
6. Sign APK jika perlu menggunakan keystore yang sudah ada

---

## Cara Kerja Alur Printer

```
[Settings.vue] → handleConnect()
    ↓ (jika APK)
[thermalPrinter.js] → scanNative()
    ↓
[AndroidInterface] → scanPrinters() (Java)
    ↓ (return JSON array)
[Settings.vue] → tampilkan modal pilih printer
    ↓ (user pilih printer)
[thermalPrinter.js] → connectNative(printer)
    ↓
[AndroidInterface] → connectBluetoothPrinter(address) atau connectUsbPrinter()
    ↓
Printer tersambung! Siap cetak struk.
```

Saat mencetak:
```
[POS/Checkout] → printReceipt(data)
    ↓
[thermalPrinter.js] → generateNativeFormattedText(data) → printFormattedText(text)
    ↓
[AndroidInterface] → EscPosPrinter.printFormattedTextAndCut(text)
    ↓
Struk tercetak!
```
