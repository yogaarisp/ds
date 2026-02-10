# Thermal Printer Implementation Guide

## 📋 Overview
Implementasi auto-print untuk thermal printer RPN02N (58mm) dan RPN02 (80mm) menggunakan ESC/POS commands via Web Bluetooth API.

## ✅ What Has Been Implemented

### 1. Thermal Printer Service
- **File**: `frontend/src/services/thermalPrinter.js`
- **Features**:
  - Web Bluetooth API integration
  - ESC/POS command generation
  - Support 58mm & 80mm paper width
  - Auto-format receipt layout
  - Connection management
  - Test print function

### 2. Printer Composable
- **File**: `frontend/src/composables/usePrinter.js`
- **Features**:
  - Printer state management
  - Settings persistence (localStorage)
  - Auto-print toggle
  - Printer width selection

### 3. Printer Settings Page
- **File**: `frontend/src/views/customer/PrinterSettings.vue`
- **Features**:
  - Connect/Disconnect printer
  - Select paper width (58mm/80mm)
  - Toggle auto-print
  - Test print button
  - Connection status indicator

### 4. Auto-Print Integration
- **File**: `frontend/src/views/customer/Cart.vue`
- **Features**:
  - Auto-print after successful checkout
  - Receipt data preparation
  - Error handling (non-blocking)

## 🖨️ Supported Printers

### RPN02N (58mm)
- Paper width: 58mm
- Character per line: ~32 chars
- Connection: Bluetooth
- ESC/POS compatible

### RPN02 (80mm)
- Paper width: 80mm
- Character per line: ~48 chars
- Connection: Bluetooth
- ESC/POS compatible

## 🔧 Installation

### 1. No Additional Dependencies Required
The thermal printer feature uses native browser APIs only:
- **TextEncoder**: For string to byte conversion
- **Uint8Array**: For ESC/POS command generation
- **Web Bluetooth API**: For printer communication

No npm packages needed! The implementation is fully browser-compatible.

### 2. Files Created
- `frontend/src/services/thermalPrinter.js`
- `frontend/src/composables/usePrinter.js`
- `frontend/src/views/customer/PrinterSettings.vue`

### 3. Files Modified
- `frontend/src/views/customer/Cart.vue` - Added auto-print
- `frontend/src/views/customer/Profile.vue` - Added printer settings menu
- `frontend/src/router/index.js` - Added printer settings route

## 📱 Usage

### For Users:

#### 1. Connect Printer
```
1. Go to Profile → Pengaturan Printer
2. Click "Hubungkan Printer"
3. Select your RPN printer from list
4. Wait for connection success
```

#### 2. Configure Settings
```
1. Select paper width (58mm or 80mm)
2. Toggle "Auto Print" ON
3. Click "Test Print" to verify
```

#### 3. Auto-Print on Checkout
```
1. Add items to cart
2. Proceed to checkout
3. Complete payment
4. Receipt will auto-print (if enabled)
```

### For Developers:

#### Manual Print
```javascript
import { usePrinter } from '@/composables/usePrinter'

const { thermalPrinter } = usePrinter()

const receiptData = {
  storeName: 'DIMSUM SARANG',
  transactionNumber: 'TRX-001',
  date: new Date().toLocaleString('id-ID'),
  items: [
    { name: 'Dimsum Ayam', quantity: 2, price: 15000, subtotal: 30000 }
  ],
  total: 30000,
  paid: 50000,
  change: 20000
}

await thermalPrinter.printReceipt(receiptData)
```

## 🎨 Receipt Format

```
================================
      DIMSUM SARANG
   Jl. Contoh No. 123
   Tel: 0812-3456-7890
--------------------------------
No: TRX-001
Tanggal: 09/02/2026 22:30
Kasir: Admin
Meja: Meja 1
--------------------------------
Dimsum Ayam
  2 x Rp15.000      Rp30.000
Dimsum Udang
  1 x Rp18.000      Rp18.000
--------------------------------
Subtotal:           Rp48.000
TOTAL:              Rp48.000

Bayar:              Rp50.000
Kembali:            Rp2.000

Metode: Cash
--------------------------------
      Terima Kasih
    Selamat Menikmati!


[CUT]
```

## 🔌 Connection Methods

### Web Bluetooth API (Browser)
- **Support**: Chrome, Edge (Desktop & Mobile)
- **Requirement**: HTTPS or localhost
- **Pros**: No installation needed
- **Cons**: Limited browser support

### Android WebView (APK)
- **Support**: Android 8.0+
- **Requirement**: Bluetooth permission
- **Pros**: Native integration
- **Cons**: Need APK build

## 🔧 Configuration

### Store Information (Auto from Admin Settings)
Info toko di struk akan otomatis mengambil dari **Admin Settings**:

```
Admin Panel → Settings → Informasi Toko
- Nama Toko (store_name)
- Alamat Toko (store_address)
- Nomor Telepon (store_phone)
```

**Cara Edit:**
1. Login sebagai Admin/Owner
2. Buka menu Settings
3. Edit "Informasi Toko"
4. Klik "Simpan Pengaturan"
5. Info toko di struk akan otomatis update

**Fallback Values:**
- Jika settings kosong, akan pakai default:
  - Nama: "DIMSUM SARANG"
  - Alamat: (kosong)
  - Telepon: (kosong)

### Printer Width
```javascript
// Set in PrinterSettings.vue or programmatically
thermalPrinter.setPrinterWidth(58) // or 80
```

### Auto-Print
```javascript
// Enable/disable auto-print
setAutoPrint(true) // or false
```

**Note:** Store info (nama, alamat, telepon) tidak perlu di-set manual. Akan otomatis ambil dari Admin Settings.

## 🐛 Troubleshooting

### Printer Not Found
- ✅ Ensure Bluetooth is ON
- ✅ Printer is powered ON
- ✅ Printer is in pairing mode
- ✅ Using Chrome or Edge browser

### Connection Failed
- ✅ Check printer battery
- ✅ Restart printer
- ✅ Clear browser cache
- ✅ Re-pair Bluetooth device

### Print Failed
- ✅ Check printer paper
- ✅ Verify connection status
- ✅ Check printer width setting
- ✅ Try test print first

### Garbled Text
- ✅ Wrong character encoding
- ✅ Check printer model compatibility
- ✅ Verify ESC/POS support

## 📊 ESC/POS Commands Used

| Command | Hex | Description |
|---------|-----|-------------|
| Initialize | ESC @ | Reset printer |
| Bold ON | ESC E 1 | Enable bold text |
| Bold OFF | ESC E 0 | Disable bold text |
| Align Center | ESC a 1 | Center alignment |
| Align Left | ESC a 0 | Left alignment |
| Double Size | GS ! 11 | Double height & width |
| Normal Size | GS ! 00 | Normal size |
| Cut Paper | GS V 0 | Full cut |
| Line Feed | 0x0A | New line |

## 🚀 Future Enhancements

### Phase 1 (Current)
- ✅ Basic ESC/POS printing
- ✅ Bluetooth connection
- ✅ Auto-print toggle
- ✅ Paper width selection

### Phase 2 (Planned)
- [ ] Logo printing
- [ ] QR code on receipt
- [ ] Barcode printing
- [ ] Custom receipt templates

### Phase 3 (Advanced)
- [ ] USB printer support
- [ ] WiFi printer support
- [ ] Multiple printer profiles
- [ ] Receipt preview before print

## 📝 Testing Checklist

### Connection Test
- [ ] Can discover printer via Bluetooth
- [ ] Can connect successfully
- [ ] Can disconnect properly
- [ ] Connection status updates correctly

### Print Test
- [ ] Test print works
- [ ] Receipt format correct (58mm)
- [ ] Receipt format correct (80mm)
- [ ] All data displayed properly
- [ ] Indonesian characters print correctly

### Auto-Print Test
- [ ] Auto-print after checkout (enabled)
- [ ] No print when disabled
- [ ] Error handling works
- [ ] Doesn't block checkout flow

### Settings Test
- [ ] Width selection persists
- [ ] Auto-print toggle persists
- [ ] Settings survive page reload
- [ ] Settings work across sessions

## 🔒 Security & Privacy

### Bluetooth Permissions
- User must grant Bluetooth access
- Permission requested on first connect
- Can be revoked in browser settings

### Data Privacy
- No receipt data stored remotely
- All processing done locally
- Bluetooth connection is encrypted

## 📞 Support

### Browser Requirements
- Chrome 56+ (Desktop)
- Chrome 70+ (Android)
- Edge 79+
- **NOT supported**: Firefox, Safari

### Android Requirements
- Android 8.0 (Oreo) or higher
- Bluetooth 4.0 or higher
- Location permission (for Bluetooth scan)

### Printer Requirements
- ESC/POS compatible
- Bluetooth 2.0 or higher
- Support standard ESC/POS commands

## 📚 References

- [Web Bluetooth API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Bluetooth_API)
- [ESC/POS Commands Reference](https://reference.epson-biz.com/modules/ref_escpos/index.php)
- [TextEncoder API](https://developer.mozilla.org/en-US/docs/Web/API/TextEncoder)
- [Uint8Array](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Uint8Array)

---

**Implementation Date**: February 9, 2026
**Status**: ✅ Ready for Testing
**Next Action**: Test with actual RPN02N/RPN02 printer

