# Guest Order Tracking Implementation Guide

## 📋 Overview
Implementasi sistem tracking pesanan untuk customer guest (tidak login) menggunakan nomor HP.

## ✅ What Has Been Implemented

### 1. Backend Changes

#### Migration File
- **File**: `backend/database/migrations/2026_02_09_000001_add_customer_phone_to_transactions_table.php`
- **Purpose**: Menambah kolom `customer_phone` di tabel `transactions`
- **Column**: `customer_phone` (string, 20 chars, nullable, indexed)

#### Controller Updates
- **File**: `backend/app/Http/Controllers/Api/TransactionController.php`
- **Changes**:
  1. Added `customer_phone` validation in `store()` method
  2. Added new method `getGuestOrders()` untuk query orders by phone number
  
#### API Routes
- **File**: `backend/routes/api.php`
- **New Route**: `GET /api/transactions/guest/by-phone?phone=08123456789`
- **Access**: Public (no authentication required)

### 2. Frontend Changes

#### Cart Page (Checkout)
- **File**: `frontend/src/views/customer/Cart.vue`
- **Changes**:
  1. Added `guestPhone` ref with localStorage persistence
  2. Added phone number input field in Summary section (for guest only)
  3. Added phone validation before checkout
  4. Updated `handleCheckout()` to include `customer_phone` in payload
  5. Auto-save phone to localStorage after successful checkout
  6. **IMPORTANT**: Added `cartStore.clearTable()` after checkout ✅

#### Order Page (History)
- **File**: `frontend/src/views/customer/Order.vue`
- **Changes**:
  1. Added guest phone tracking with localStorage
  2. Added phone input modal for guest users
  3. Added `fetchGuestOrders()` function to load orders by phone
  4. Added "Ganti" button to change phone number
  5. Auto-load orders if phone exists in localStorage
  6. Dark mode support

## 🎯 User Flow

### Scenario 1: First Time Guest Order
```
1. Customer scan QR → Browse menu → Add to cart
2. Go to Cart page → See phone input field (required)
3. Enter phone number (e.g., 08123456789)
4. Checkout → Order created with phone number
5. Phone saved to localStorage
6. Table session cleared ✅
7. Redirect to order detail
```

### Scenario 2: Returning Guest (Same Day/Next Day)
```
1. Customer scan QR again (same or different table)
2. Browse menu → Add to cart
3. Go to Cart page → Phone auto-filled from localStorage
4. Checkout → Order created
5. Go to Order page → Orders auto-loaded by phone
6. Can see all previous orders (up to 20 orders)
```

### Scenario 3: View Order History
```
1. Customer open app → Go to Order tab
2. If phone exists in localStorage → Auto-load orders
3. If no phone → Show phone input modal
4. Enter phone → Load all orders with that phone
5. Can click "Ganti" to change phone number
```

## 🔧 Installation Steps

### 1. Run Migration
```bash
cd backend
php artisan migrate
```

This will add the `customer_phone` column to `transactions` table.

### 2. Clear Caches (Optional)
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 3. Test the Implementation

#### Test Guest Checkout:
1. Open browser in incognito mode (to simulate guest)
2. Scan QR or go to `/customer/home`
3. Add items to cart
4. Go to cart → Enter phone number
5. Checkout → Verify order created with phone

#### Test Order History:
1. Go to `/customer/order`
2. Enter phone number used in checkout
3. Verify orders are displayed

#### Test Phone Persistence:
1. Checkout with phone number
2. Close browser
3. Open again → Go to cart
4. Verify phone is auto-filled

## 📱 Features

### For Guest Users:
- ✅ Track orders without registration
- ✅ View order history by phone number
- ✅ Phone number auto-saved for convenience
- ✅ Cross-device access (same phone number)
- ✅ Up to 20 recent orders displayed
- ✅ Table session auto-cleared after checkout

### For Registered Users:
- ✅ No changes - existing flow still works
- ✅ Orders tracked by customer_id as before

## 🔒 Security & Privacy

### Data Storage:
- Phone numbers stored in database (transactions table)
- Phone numbers indexed for fast queries
- Only guest orders (customer_id = null) are queryable by phone

### Privacy:
- No authentication required to view orders by phone
- Anyone with the phone number can view orders
- Consider this when implementing in production

### Recommendations:
1. Add SMS verification for sensitive operations
2. Implement rate limiting on guest orders endpoint
3. Add CAPTCHA to prevent abuse
4. Consider adding order PIN/password for extra security

## 🎨 UI/UX Highlights

### Cart Page:
- Phone input with orange border (stands out)
- Helper text: "No. HP untuk melacak pesanan Anda"
- Validation before checkout
- Dark mode support

### Order Page:
- Clean phone input modal with icon
- Auto-load if phone exists
- "Ganti" button to change phone
- Shows phone number in header
- Empty state with helpful message

## 🐛 Known Issues & Limitations

### Limitations:
1. **No SMS verification** - Anyone with phone can view orders
2. **20 orders limit** - Only shows last 20 orders
3. **No order PIN** - No extra security layer
4. **Phone format** - No validation for phone format (can be added)

### Future Enhancements:
1. Add SMS OTP verification
2. Add order PIN for security
3. Add phone number formatting
4. Add "Forgot phone?" feature
5. Encourage registration with benefits

## 📊 Database Schema

### transactions table (new column):
```sql
customer_phone VARCHAR(20) NULL INDEX
```

### Example Query:
```sql
SELECT * FROM transactions 
WHERE customer_phone = '08123456789' 
AND customer_id IS NULL 
ORDER BY created_at DESC 
LIMIT 20;
```

## 🚀 Next Steps

### Immediate:
1. ✅ Run migration
2. ✅ Test guest checkout flow
3. ✅ Test order history
4. ✅ Verify table session clearing

### Short Term:
1. Add phone number formatting (e.g., 0812-3456-789)
2. Add phone validation (must start with 08, etc.)
3. Add loading states
4. Add error handling for network issues

### Long Term:
1. Implement SMS OTP verification
2. Add order PIN feature
3. Add "Convert to Account" feature
4. Add analytics for guest vs registered users
5. Add marketing features (SMS notifications, etc.)

## 📝 Testing Checklist

- [ ] Migration runs successfully
- [ ] Guest can checkout with phone number
- [ ] Phone number saved to localStorage
- [ ] Table session cleared after checkout ✅
- [ ] Guest can view order history by phone
- [ ] Phone auto-filled on next visit
- [ ] "Ganti" button works
- [ ] Orders displayed correctly
- [ ] Dark mode works
- [ ] Responsive on mobile
- [ ] Works on different browsers
- [ ] Works after clearing localStorage

## 🎉 Benefits

### For Customers:
- ✅ No registration required
- ✅ Easy order tracking
- ✅ Cross-device access
- ✅ Privacy-friendly (no email required)

### For Business:
- ✅ Lower barrier to entry
- ✅ Collect phone numbers for marketing
- ✅ Encourage repeat orders
- ✅ Data for customer insights
- ✅ Can promote registration later

## 📞 Support

If you encounter any issues:
1. Check browser console for errors
2. Verify migration ran successfully
3. Check API endpoint is accessible
4. Verify phone number format
5. Clear browser cache and localStorage

---

**Implementation Date**: February 9, 2026
**Status**: ✅ Ready for Testing
**Next Action**: Run migration and test
