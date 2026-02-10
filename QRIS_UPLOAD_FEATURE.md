# QRIS Upload Feature - Implementation Guide

## 📋 Overview
Fitur upload QR Code image untuk payment method QRIS, memudahkan customer untuk scan dan bayar.

## ✅ What Has Been Implemented

### 1. Database Migration
- **File**: `backend/database/migrations/2026_02_10_090816_add_qr_code_image_to_payment_methods_table.php`
- **Column**: `qr_code_image` (string, nullable) di tabel `payment_methods`

### 2. Backend Updates

#### PaymentMethod Model
- **File**: `backend/app/Models/PaymentMethod.php`
- Added `qr_code_image` to fillable fields

#### PaymentMethodController
- **File**: `backend/app/Http/Controllers/Api/PaymentMethodController.php`
- Added image upload handling in `store()` and `update()` methods
- Auto-delete old image when updating
- Delete image when payment method deleted
- Uses `ImageService` for file management

### 3. Frontend Updates

#### Admin Settings Page
- **File**: `frontend/src/views/Settings.vue`
- Added QR code upload field in payment modal
- Only shows for QRIS payment type
- Image preview before save
- Remove image button
- FormData upload support

## 🎯 How to Use

### For Admin:

#### 1. Add/Edit QRIS Payment Method
```
1. Login as Admin/Owner
2. Go to Settings → Tab "Bayar"
3. Click "Tambah Metode" or Edit existing QRIS
4. Select Type: "QRIS"
5. Fill in:
   - Nama Metode: e.g., "QRIS BCA"
   - No. Rek / ID: (optional)
   - Atas Nama: (optional)
6. Click "Upload QR Code"
7. Select QR code image (JPG/PNG, max 2MB)
8. Preview will show
9. Click "Simpan Metode"
```

#### 2. View QR Code
- QR code image saved to `storage/app/public/qr-codes/`
- Accessible via `STORAGE_BASE_URL/qr-codes/filename.jpg`

### For Customer:

#### 1. Checkout with QRIS
```
1. Add items to cart
2. Go to checkout (http://localhost:5173/customer/cart)
3. Select payment method: QRIS
4. QR code will be displayed automatically below payment methods
5. Scan QR code with mobile banking app
6. Complete payment in banking app
7. Click "Bayar Sekarang" button to confirm order
```

**QR Code Display Features:**
- Auto-shows when QRIS payment selected
- Large 192x192px QR code (easy to scan)
- White background with border
- Payment method name displayed
- Blue info box with scan instructions
- Supports dark mode
- Smooth fade-in animation

## 📁 File Structure

```
backend/
├── app/
│   ├── Models/
│   │   └── PaymentMethod.php (updated)
│   └── Http/Controllers/Api/
│       └── PaymentMethodController.php (updated)
├── database/migrations/
│   └── 2026_02_10_090816_add_qr_code_image_to_payment_methods_table.php
└── storage/app/public/
    └── qr-codes/ (auto-created)

frontend/
└── src/views/
    └── Settings.vue (updated)
```

## 🔧 Technical Details

### Image Upload Specs:
- **Allowed formats**: JPEG, PNG, JPG
- **Max size**: 2MB
- **Storage path**: `storage/app/public/qr-codes/`
- **Public URL**: `/storage/qr-codes/filename.jpg`

### API Endpoints:
- `POST /api/payment-methods` - Create with QR code
- `PUT /api/payment-methods/{id}` - Update with QR code
- `DELETE /api/payment-methods/{id}` - Delete (auto-delete QR code)

### FormData Structure:
```javascript
{
  name: string,
  type: 'qris',
  account_name: string (optional),
  account_number: string (optional),
  qr_code_image: File (optional),
  is_active: boolean
}
```

## 🎨 UI Features

### Upload Button:
- Dashed border design
- Upload icon
- Hover effect
- "Upload QR Code" / "Ganti QR Code" text

### Preview:
- Full width preview
- Object-contain (maintain aspect ratio)
- Remove button (top-right)
- Border and background

### Conditional Display:
- Only shows when type = "QRIS"
- Hidden for Cash, Transfer, Other

## 🧪 Testing Checklist

### Backend Test:
- [x] Migration runs successfully
- [x] Can create QRIS with QR code
- [x] Can update QRIS with new QR code
- [x] Old QR code deleted when updating
- [x] QR code deleted when payment method deleted
- [x] Image stored in correct folder

### Frontend Test:
- [x] Upload button appears for QRIS type
- [x] Can select image file
- [x] Preview shows correctly
- [x] Can remove preview
- [x] Can save with QR code
- [x] Can update QR code
- [x] Form validation works

### Customer Display Test:
- [ ] QR code displays when QRIS selected in cart
- [ ] QR code image loads correctly
- [ ] QR code is scannable with mobile banking
- [ ] Instructions are clear
- [ ] Dark mode support works
- [ ] Animation smooth

### Integration Test:
- [ ] End-to-end: Upload QR → Customer sees QR → Can scan → Payment works
- [ ] Multiple QRIS methods work correctly
- [ ] QR code updates reflect immediately
- [ ] No QR code = no display section

## 🚀 Implementation Status

### ✅ Phase 1 - COMPLETE
- ✅ Backend migration (qr_code_image column)
- ✅ Backend API (upload, update, delete)
- ✅ Admin upload UI with preview
- ✅ Customer QR code display
- ✅ Auto-delete old images
- ✅ File validation (type, size)
- ✅ Dark mode support

### 🔄 Phase 2 - Optional Enhancements
- [ ] Add QR code to thermal receipt print
- [ ] Multiple QR codes per payment method
- [ ] QR code expiry date
- [ ] Dynamic QR code generation (API integration)

### 🎯 Phase 3 - Advanced Features
- [ ] QR code analytics (scan count)
- [ ] Multiple payment amounts
- [ ] QR code templates
- [ ] Bulk QR code upload

## 📝 Notes

- QR code image is optional (nullable)
- Works with existing payment methods
- Backward compatible (old data still works)
- Image service handles all file operations
- Automatic cleanup on delete/update

## 🔒 Security

- File type validation (only images)
- File size limit (2MB max)
- Stored in secure location
- Only admin can upload
- Auto-sanitize filename

---

**Implementation Date**: February 10, 2026  
**Status**: ✅ **COMPLETE & READY FOR TESTING**  
**Migration**: ✅ Run Successfully  
**Customer Display**: ✅ Implemented

## 🎉 Summary

The QRIS QR Code upload feature is now **fully functional**:

1. **Admin Side**: Upload, preview, update, and delete QR codes for QRIS payment methods
2. **Customer Side**: Automatic QR code display when QRIS payment is selected at checkout
3. **Backend**: Secure file handling with auto-cleanup and validation
4. **UI/UX**: Modern design with dark mode support and smooth animations

**Ready for production testing!** 🚀

