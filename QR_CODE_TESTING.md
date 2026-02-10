# QRIS QR Code Feature - Testing Guide

## 📋 Overview
Complete testing guide for QRIS QR Code upload and display feature.

---

## 🧪 Test Scenarios

### 1. Admin - Upload QR Code

#### Test Case 1.1: Upload New QRIS Payment Method
**Steps:**
1. Login as Admin/Owner
2. Navigate to Settings → Tab "Bayar"
3. Click "Tambah Metode"
4. Fill in:
   - Nama Metode: "QRIS BCA"
   - Tipe Pembayaran: "QRIS"
   - No. Rek / ID: "1234567890" (optional)
   - Atas Nama: "Dimsum Sarang" (optional)
5. Click "Upload QR Code" button
6. Select a QR code image (PNG/JPG, < 2MB)
7. Verify preview shows correctly
8. Click "Simpan Metode"

**Expected Result:**
- ✅ QR code preview displays before save
- ✅ Success notification appears
- ✅ Payment method appears in list with QR code
- ✅ File saved to `storage/app/public/qr-codes/`

#### Test Case 1.2: Update Existing QR Code
**Steps:**
1. Click "Edit" on existing QRIS payment method
2. Click "Ganti QR Code"
3. Select new QR code image
4. Verify new preview shows
5. Click "Simpan Metode"

**Expected Result:**
- ✅ Old QR code file deleted from storage
- ✅ New QR code saved
- ✅ Preview updates correctly

#### Test Case 1.3: Remove QR Code
**Steps:**
1. Edit QRIS payment method with QR code
2. Click "X" button on QR code preview
3. Click "Simpan Metode"

**Expected Result:**
- ✅ QR code removed from database
- ✅ File deleted from storage
- ✅ Payment method still exists without QR code

#### Test Case 1.4: Delete Payment Method with QR Code
**Steps:**
1. Click delete button on QRIS payment method
2. Confirm deletion

**Expected Result:**
- ✅ Payment method deleted
- ✅ QR code file auto-deleted from storage

#### Test Case 1.5: File Validation
**Steps:**
1. Try uploading file > 2MB
2. Try uploading non-image file (PDF, TXT)
3. Try uploading unsupported format (GIF, BMP)

**Expected Result:**
- ✅ Error message for oversized file
- ✅ Error message for invalid file type
- ✅ Only JPG/PNG/JPEG accepted

---

### 2. Customer - View QR Code

#### Test Case 2.1: QR Code Display on Checkout
**Steps:**
1. Open customer app (http://localhost:5173/customer)
2. Add items to cart
3. Go to checkout
4. Select QRIS payment method

**Expected Result:**
- ✅ QR code section appears below payment methods
- ✅ QR code image displays clearly (192x192px)
- ✅ Payment method name shown
- ✅ Blue instruction box visible
- ✅ Smooth fade-in animation

#### Test Case 2.2: QR Code Not Shown for Non-QRIS
**Steps:**
1. In checkout, select "Cash" payment
2. Select "Transfer" payment

**Expected Result:**
- ✅ QR code section hidden
- ✅ Only shows for QRIS type

#### Test Case 2.3: QR Code Not Shown if Not Uploaded
**Steps:**
1. Create QRIS payment method without QR code
2. Select it in customer checkout

**Expected Result:**
- ✅ QR code section not displayed
- ✅ No broken image or error

#### Test Case 2.4: Dark Mode Support
**Steps:**
1. Enable dark mode in customer settings
2. Go to checkout
3. Select QRIS payment

**Expected Result:**
- ✅ QR code displays with proper dark mode styling
- ✅ Border and background colors adapt
- ✅ Text readable in dark mode

#### Test Case 2.5: Mobile Responsiveness
**Steps:**
1. Open checkout on mobile device (or resize browser)
2. Select QRIS payment
3. Verify QR code display

**Expected Result:**
- ✅ QR code scales appropriately
- ✅ Instruction text readable
- ✅ No horizontal scroll
- ✅ Touch-friendly layout

---

### 3. Integration Tests

#### Test Case 3.1: End-to-End Payment Flow
**Steps:**
1. Admin uploads QR code for QRIS payment
2. Customer adds items to cart
3. Customer selects QRIS payment
4. Customer scans QR code with mobile banking app
5. Customer completes payment in banking app
6. Customer clicks "Bayar Sekarang"

**Expected Result:**
- ✅ QR code scannable by banking apps
- ✅ Payment amount correct
- ✅ Order created successfully
- ✅ Receipt generated

#### Test Case 3.2: Multiple QRIS Methods
**Steps:**
1. Create 2+ QRIS payment methods with different QR codes
2. Customer selects first QRIS method
3. Verify correct QR code shows
4. Customer switches to second QRIS method
5. Verify QR code updates

**Expected Result:**
- ✅ Each QRIS method shows its own QR code
- ✅ QR codes don't mix up
- ✅ Switching is smooth

#### Test Case 3.3: QR Code Update Reflects Immediately
**Steps:**
1. Customer opens checkout with QRIS selected
2. Admin updates QR code
3. Customer refreshes page

**Expected Result:**
- ✅ New QR code displays after refresh
- ✅ No caching issues

---

## 🔍 Backend Verification

### Database Check
```sql
-- Check payment methods table
SELECT id, name, type, qr_code_image FROM payment_methods WHERE type = 'qris';

-- Verify qr_code_image column exists
DESCRIBE payment_methods;
```

### File System Check
```bash
# Check QR code storage directory
ls -la backend/storage/app/public/qr-codes/

# Verify symlink exists
ls -la backend/public/storage
```

### API Testing
```bash
# Get all payment methods
curl http://localhost:8000/api/payment-methods

# Create QRIS with QR code (use Postman/Insomnia for file upload)
POST http://localhost:8000/api/payment-methods
Content-Type: multipart/form-data
{
  "name": "QRIS Test",
  "type": "qris",
  "qr_code_image": [file],
  "is_active": true
}
```

---

## 🐛 Common Issues & Solutions

### Issue 1: QR Code Not Displaying
**Symptoms:** Broken image icon or no QR code section
**Solutions:**
- Check if `qr_code_image` field has value in database
- Verify file exists in `storage/app/public/qr-codes/`
- Check storage symlink: `php artisan storage:link`
- Verify `STORAGE_BASE_URL` in frontend API config

### Issue 2: Upload Fails
**Symptoms:** Error message on save
**Solutions:**
- Check file size (must be < 2MB)
- Verify file type (JPG/PNG/JPEG only)
- Check storage folder permissions: `chmod 775 storage/app/public/qr-codes`
- Check Laravel logs: `tail -f storage/logs/laravel.log`

### Issue 3: Old QR Code Not Deleted
**Symptoms:** Multiple QR code files accumulate
**Solutions:**
- Check ImageService delete method
- Verify file path is correct
- Manual cleanup: `rm backend/storage/app/public/qr-codes/old-file.jpg`

### Issue 4: QR Code Not Scannable
**Symptoms:** Banking app can't read QR code
**Solutions:**
- Verify QR code image quality (not blurry)
- Check QR code is valid QRIS format
- Test with multiple banking apps
- Ensure QR code not expired (if dynamic)

---

## ✅ Test Completion Checklist

### Backend
- [ ] Migration applied successfully
- [ ] QR code uploads to correct folder
- [ ] Old QR codes deleted on update
- [ ] QR codes deleted with payment method
- [ ] API returns QR code URL correctly
- [ ] File validation works (size, type)

### Frontend - Admin
- [ ] Upload button appears for QRIS type only
- [ ] File picker opens on click
- [ ] Preview shows before save
- [ ] Remove button works
- [ ] Success notification appears
- [ ] Payment method list updates

### Frontend - Customer
- [ ] QR code displays for QRIS payment
- [ ] QR code hidden for other payment types
- [ ] QR code hidden if not uploaded
- [ ] Image loads correctly
- [ ] Instructions clear and visible
- [ ] Dark mode works
- [ ] Mobile responsive

### Integration
- [ ] End-to-end flow works
- [ ] QR code scannable by banking apps
- [ ] Multiple QRIS methods work
- [ ] Updates reflect immediately
- [ ] No console errors
- [ ] No broken images

---

## 📊 Test Results Template

```
Test Date: ___________
Tester: ___________
Environment: [ ] Local [ ] Staging [ ] Production

Backend Tests: ___/6 passed
Admin Tests: ___/6 passed
Customer Tests: ___/5 passed
Integration Tests: ___/3 passed

Total: ___/20 passed

Issues Found:
1. ___________
2. ___________
3. ___________

Notes:
___________
___________
```

---

## 🚀 Ready for Production?

Before deploying to production, ensure:
- ✅ All test cases pass
- ✅ No console errors
- ✅ QR codes scannable by major banking apps
- ✅ File storage permissions correct
- ✅ Backup database before migration
- ✅ Test on production-like environment first

---

**Last Updated:** February 10, 2026  
**Feature Status:** Ready for Testing  
**Documentation:** Complete
