# Table Session Management - Implementation Summary

## ✅ **IMPLEMENTED FEATURES**

### **1. Manual Clear Table - "Ganti Meja" Button**

#### **Location:** Home Page (User Info Bar)
#### **When it appears:**
- ✅ Skenario 1 (QR): Muncul di bawah nama meja
- ✅ Skenario 2 (Manual): Muncul di bawah nama meja

#### **Functionality:**
```javascript
// Check if cart has items
if (cart.length > 0) {
  // Show confirmation with cart info
  "Anda memiliki X item di keranjang. Keranjang akan tetap tersimpan."
} else {
  // Simple confirmation
  "Anda akan melepas meja saat ini"
}

// On confirm:
cartStore.clearTable()
// Show success message
```

#### **UI:**
```
┌─────────────────────────────┐
│ 🪑 Meja Anda                │
│ Meja 2  [Ganti]  ← BUTTON  │
└─────────────────────────────┘
```

---

### **2. Auto-Clear Table After Checkout**

#### **Location:** Cart.vue - handleCheckout()
#### **When it triggers:**
- After successful order creation
- Before redirect to order detail

#### **Code:**
```javascript
const response = await api.post('/transactions', payload)

// Save guest phone
if (guestPhone.value) {
  localStorage.setItem('guest_phone', guestPhone.value)
}

// ✅ Clear Cart & Table
cartStore.clearCart()
cartStore.clearTable()  // ← AUTO CLEAR!

router.push(`/customer/order/${response.data.id}`)
```

---

### **3. Timeout Auto-Clear (45 Minutes)**

#### **Location:** Multiple files
#### **When it triggers:**
- 45 minutes after table is set
- Checked every 10 seconds

#### **Implementation:**

**A. Set Expiry When Table Selected:**

**Skenario 1 (QR Scan):**
```javascript
// CustomerLayout.vue - detectTable()
cartStore.setTable(code, name)
const expiryTime = Date.now() + (45 * 60 * 1000)
cartStore.setTableExpiry(expiryTime)
```

**Skenario 2 (Manual Select):**
```javascript
// Cart.vue - selectTable()
cartStore.setTable(table.code, table.name)
const expiryTime = Date.now() + (45 * 60 * 1000)
cartStore.setTableExpiry(expiryTime)
```

**B. Check Expiry Periodically:**
```javascript
// CustomerLayout.vue - onMounted()
expiryInterval = setInterval(() => {
  cartStore.checkTableExpiry()
}, 10000) // Check every 10 seconds
```

**C. Clear if Expired:**
```javascript
// cart.js - checkTableExpiry()
if (now > expiry) {
  console.log('⏳ Table session expired. Clearing table.')
  clearTable()
}
```

---

## 🎯 **COMPLETE FLOW**

### **Skenario 1: QR Code + Manual Clear**

```
1. Scan QR → Table set (Meja 1)
   ↓
2. Expiry set: Now + 45 min
   ↓
3. Browse menu → Add to cart
   ↓
4. Customer tidak jadi order
   ↓
5. Klik "Ganti" button
   ↓
6. Konfirmasi: "Anda memiliki 3 item di keranjang"
   ↓
7. Klik "Ya, Ganti Meja"
   ↓
8. ✅ Table cleared (cart tetap ada)
   ↓
9. Bisa scan QR baru atau pilih meja lain
```

### **Skenario 2: Manual Select + Timeout**

```
1. Login → Browse → Add to cart
   ↓
2. Click "Dine In" → Pilih Meja 2
   ↓
3. Expiry set: Now + 45 min
   ↓
4. Customer lupa checkout
   ↓
5. 45 menit berlalu...
   ↓
6. Auto-check expiry (every 10 sec)
   ↓
7. ✅ Table auto-cleared
   ↓
8. Cart tetap ada, tapi meja hilang
   ↓
9. Harus pilih meja lagi saat checkout
```

---

## 📊 **TABLE CLEAR TRIGGERS**

| Trigger | Skenario 1 | Skenario 2 | Cart Status | Table Status |
|---------|-----------|-----------|-------------|--------------|
| **After Checkout** | ✅ | ✅ | Cleared | Cleared |
| **"Ganti" Button** | ✅ | ✅ | Kept | Cleared |
| **Timeout (45 min)** | ✅ | ✅ | Kept | Cleared |

---

## 🎨 **UI CHANGES**

### **Before:**
```
┌─────────────────────────────┐
│ 🪑 Meja Anda                │
│ Meja 2                      │
└─────────────────────────────┘
```

### **After:**
```
┌─────────────────────────────┐
│ 🪑 Meja Anda                │
│ Meja 2  [Ganti]  ← NEW!    │
└─────────────────────────────┘
```

**Button Style:**
- Text: "Ganti"
- Size: 9px
- Color: Orange (#F97316)
- Style: Underline
- Hover: Darker orange

---

## 🔧 **FILES MODIFIED**

### **1. frontend/src/views/customer/Home.vue**
- ✅ Added `changeTable()` function
- ✅ Added "Ganti" button in user info bar
- ✅ Added confirmation dialogs (with/without cart items)

### **2. frontend/src/views/customer/Cart.vue**
- ✅ Updated `selectTable()` to set expiry
- ✅ Already has `cartStore.clearTable()` in checkout

### **3. frontend/src/layouts/CustomerLayout.vue**
- ✅ Updated `detectTable()` to set expiry
- ✅ Already has expiry check interval (every 10 sec)

### **4. frontend/src/stores/cart.js**
- ✅ Already has `checkTableExpiry()` function
- ✅ Already has `clearTable()` function
- ✅ Already has `setTableExpiry()` function

---

## ✨ **BENEFITS**

### **For Customers:**
- ✅ Tidak terjebak di meja yang salah
- ✅ Bisa ganti meja kapan saja
- ✅ Cart tetap aman (tidak hilang)
- ✅ Auto-clear kalau lupa

### **For Business:**
- ✅ Meja tidak "terkunci" lama
- ✅ Customer experience lebih baik
- ✅ Mengurangi konflik meja
- ✅ Data lebih akurat

---

## 🧪 **TESTING CHECKLIST**

### **Test 1: Manual Clear (With Cart)**
- [ ] Scan QR → Add items to cart
- [ ] Click "Ganti" button
- [ ] Verify confirmation shows cart count
- [ ] Click "Ya, Ganti Meja"
- [ ] Verify table cleared
- [ ] Verify cart items still there
- [ ] Verify success message shown

### **Test 2: Manual Clear (Without Cart)**
- [ ] Scan QR (no items in cart)
- [ ] Click "Ganti" button
- [ ] Verify simple confirmation
- [ ] Click "Ya, Ganti"
- [ ] Verify table cleared
- [ ] Verify success message shown

### **Test 3: Auto-Clear After Checkout**
- [ ] Scan QR → Add items → Checkout
- [ ] Verify order created
- [ ] Verify table cleared
- [ ] Verify cart cleared
- [ ] Go back to home
- [ ] Verify no table info shown

### **Test 4: Timeout (45 min)**
- [ ] Scan QR → Note the time
- [ ] Wait 45+ minutes (or modify code to 1 min for testing)
- [ ] Verify table auto-cleared
- [ ] Verify cart still there
- [ ] Verify console log: "⏳ Table session expired"

### **Test 5: Skenario 2 Manual Select**
- [ ] Login → Browse → Add to cart
- [ ] Click "Dine In" → Select table
- [ ] Click "Ganti" button
- [ ] Verify table cleared
- [ ] Select different table
- [ ] Checkout successfully

---

## 🚀 **NEXT STEPS**

### **Optional Enhancements:**
1. **Show expiry countdown** in UI (e.g., "Meja 2 (30 menit lagi)")
2. **Warning before expiry** (e.g., "Meja akan expired dalam 5 menit")
3. **Extend session** button (add 15 more minutes)
4. **History of table changes** for analytics

### **Production Considerations:**
1. Test timeout with real users
2. Adjust timeout duration based on feedback
3. Add analytics tracking for table changes
4. Monitor table session patterns

---

## 📝 **SUMMARY**

✅ **3 Ways to Clear Table:**
1. Manual: "Ganti" button (anytime)
2. Auto: After checkout (always)
3. Auto: Timeout 45 minutes (if inactive)

✅ **Works for Both Scenarios:**
- Skenario 1: QR Code scan
- Skenario 2: Manual dine-in selection

✅ **Cart Protection:**
- Cart items NEVER cleared when changing table
- Only cleared after successful checkout

✅ **User Experience:**
- Clear confirmation dialogs
- Success feedback
- No data loss
- Flexible table management

---

**Implementation Date**: February 9, 2026
**Status**: ✅ Complete & Ready for Testing
**Files Changed**: 4 files
**New Features**: 3 clear methods
