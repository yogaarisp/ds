import { ref, computed } from 'vue'

// Language files
const messages = {
  id: {
    // Navigation
    home: 'Beranda',
    menu: 'Menu',
    cart: 'Keranjang',
    profile: 'Profil',
    order: 'Pesanan',
    
    // Common
    welcome: 'Selamat Datang',
    loading: 'Memuat...',
    save: 'Simpan',
    cancel: 'Batal',
    delete: 'Hapus',
    edit: 'Edit',
    add: 'Tambah',
    back: 'Kembali',
    next: 'Selanjutnya',
    previous: 'Sebelumnya',
    search: 'Cari',
    filter: 'Filter',
    sort: 'Urutkan',
    
    // Home page
    popularMenu: 'Menu Terlaris',
    seeAll: 'Lihat Semua',
    orderNow: 'Pesan Sekarang',
    
    // Profile
    myAddresses: 'Alamat Saya',
    redeemCenter: 'Redeem Center',
    helpCenter: 'Pusat Bantuan',
    languageSettings: 'Pengaturan Bahasa',
    termsOfService: 'Ketentuan Layanan',
    settings: 'Pengaturan',
    logout: 'Keluar',
    
    // Language Settings
    currentLanguage: 'Bahasa Saat Ini',
    selectLanguage: 'Pilih Bahasa',
    languageChanged: 'Bahasa berhasil diubah ke',
    comingSoon: 'Bahasa ini akan segera tersedia!',
    
    // Settings
    notifications: 'Notifikasi',
    pushNotification: 'Push Notification',
    orderUpdates: 'Update Pesanan',
    promotions: 'Promosi & Penawaran',
    preferences: 'Preferensi',
    appTheme: 'Tema Aplikasi',
    currency: 'Mata Uang',
    privacy: 'Privasi & Keamanan',
    dataManagement: 'Manajemen Data',
    exportData: 'Export Data Saya',
    clearCache: 'Hapus Cache',
    resetDefault: 'Reset Default',
    saveSettings: 'Simpan Pengaturan',
    
    // Addresses
    addAddress: 'Tambah Alamat',
    editAddress: 'Edit Alamat',
    deleteAddress: 'Hapus Alamat',
    setAsDefault: 'Jadikan Utama',
    recipientName: 'Nama Penerima',
    phoneNumber: 'Nomor Telepon',
    fullAddress: 'Alamat Lengkap',
    district: 'Kecamatan',
    city: 'Kota',
    province: 'Provinsi',
    postalCode: 'Kode Pos',
    notes: 'Catatan',
    
    // Cart & Checkout
    checkout: 'Checkout',
    payNow: 'Bayar Sekarang',
    dineIn: 'Dine In',
    takeaway: 'Takeaway',
    delivery: 'Delivery',
    selectTable: 'Pilih Meja',
    
    // Messages
    success: 'Berhasil',
    error: 'Error',
    warning: 'Peringatan',
    info: 'Informasi'
  },
  
  en: {
    // Navigation
    home: 'Home',
    menu: 'Menu',
    cart: 'Cart',
    profile: 'Profile',
    order: 'Order',
    
    // Common
    welcome: 'Welcome',
    loading: 'Loading...',
    save: 'Save',
    cancel: 'Cancel',
    delete: 'Delete',
    edit: 'Edit',
    add: 'Add',
    back: 'Back',
    next: 'Next',
    previous: 'Previous',
    search: 'Search',
    filter: 'Filter',
    sort: 'Sort',
    
    // Home page
    popularMenu: 'Popular Menu',
    seeAll: 'See All',
    orderNow: 'Order Now',
    
    // Profile
    myAddresses: 'My Addresses',
    redeemCenter: 'Redeem Center',
    helpCenter: 'Help Center',
    languageSettings: 'Language Settings',
    termsOfService: 'Terms of Service',
    settings: 'Settings',
    logout: 'Logout',
    
    // Language Settings
    currentLanguage: 'Current Language',
    selectLanguage: 'Select Language',
    languageChanged: 'Language successfully changed to',
    comingSoon: 'This language will be available soon!',
    
    // Settings
    notifications: 'Notifications',
    pushNotification: 'Push Notification',
    orderUpdates: 'Order Updates',
    promotions: 'Promotions & Offers',
    preferences: 'Preferences',
    appTheme: 'App Theme',
    currency: 'Currency',
    privacy: 'Privacy & Security',
    dataManagement: 'Data Management',
    exportData: 'Export My Data',
    clearCache: 'Clear Cache',
    resetDefault: 'Reset Default',
    saveSettings: 'Save Settings',
    
    // Addresses
    addAddress: 'Add Address',
    editAddress: 'Edit Address',
    deleteAddress: 'Delete Address',
    setAsDefault: 'Set as Default',
    recipientName: 'Recipient Name',
    phoneNumber: 'Phone Number',
    fullAddress: 'Full Address',
    district: 'District',
    city: 'City',
    province: 'Province',
    postalCode: 'Postal Code',
    notes: 'Notes',
    
    // Cart & Checkout
    checkout: 'Checkout',
    payNow: 'Pay Now',
    dineIn: 'Dine In',
    takeaway: 'Takeaway',
    delivery: 'Delivery',
    selectTable: 'Select Table',
    
    // Messages
    success: 'Success',
    error: 'Error',
    warning: 'Warning',
    info: 'Information'
  }
}

// Current language state
const currentLanguage = ref(localStorage.getItem('app_language') || 'id')

// Translation function
export function useI18n() {
  const t = (key) => {
    const keys = key.split('.')
    let value = messages[currentLanguage.value]
    
    for (const k of keys) {
      value = value?.[k]
    }
    
    return value || key
  }
  
  const setLanguage = (lang) => {
    if (messages[lang]) {
      currentLanguage.value = lang
      localStorage.setItem('app_language', lang)
    }
  }
  
  const locale = computed(() => currentLanguage.value)
  
  return {
    t,
    setLanguage,
    locale
  }
}