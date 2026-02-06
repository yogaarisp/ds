<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// Settings state
const settings = ref({
  notifications: {
    push: true,
    email: false,
    sms: false,
    orderUpdates: true,
    promotions: true,
    newsletter: false
  },
  privacy: {
    profileVisibility: 'private',
    shareData: false,
    analytics: true
  },
  preferences: {
    theme: 'light',
    currency: 'IDR',
    dateFormat: 'DD/MM/YYYY',
    timeFormat: '24h'
  }
})

const themeOptions = [
  { value: 'light', label: 'Terang', icon: '☀️' },
  { value: 'dark', label: 'Gelap', icon: '🌙' },
  { value: 'auto', label: 'Otomatis', icon: '🔄' }
]

const currencyOptions = [
  { value: 'IDR', label: 'Rupiah (IDR)', symbol: 'Rp' },
  { value: 'USD', label: 'US Dollar (USD)', symbol: '$' },
  { value: 'EUR', label: 'Euro (EUR)', symbol: '€' }
]

onMounted(() => {
  loadSettings()
})

function loadSettings() {
  // Load settings from localStorage
  const savedSettings = localStorage.getItem('customer_settings')
  if (savedSettings) {
    settings.value = { ...settings.value, ...JSON.parse(savedSettings) }
  }
}

function saveSettings() {
  localStorage.setItem('customer_settings', JSON.stringify(settings.value))
  // Show success message
  alert('Pengaturan berhasil disimpan!')
}

function resetSettings() {
  if (confirm('Apakah Anda yakin ingin mengembalikan semua pengaturan ke default?')) {
    settings.value = {
      notifications: {
        push: true,
        email: false,
        sms: false,
        orderUpdates: true,
        promotions: true,
        newsletter: false
      },
      privacy: {
        profileVisibility: 'private',
        shareData: false,
        analytics: true
      },
      preferences: {
        theme: 'light',
        currency: 'IDR',
        dateFormat: 'DD/MM/YYYY',
        timeFormat: '24h'
      }
    }
    saveSettings()
  }
}

function clearCache() {
  if (confirm('Hapus cache aplikasi? Ini akan menghapus data sementara dan mungkin memperlambat loading awal.')) {
    // Clear various cache items but keep important user data
    const keysToKeep = ['token', 'user', 'customer_settings', 'app_language']
    const allKeys = Object.keys(localStorage)
    
    allKeys.forEach(key => {
      if (!keysToKeep.includes(key)) {
        localStorage.removeItem(key)
      }
    })
    
    alert('Cache berhasil dihapus!')
  }
}

function exportData() {
  const userData = {
    user: authStore.user,
    settings: settings.value,
    exportDate: new Date().toISOString()
  }
  
  const dataStr = JSON.stringify(userData, null, 2)
  const dataBlob = new Blob([dataStr], { type: 'application/json' })
  const url = URL.createObjectURL(dataBlob)
  
  const link = document.createElement('a')
  link.href = url
  link.download = `dimsum-data-${new Date().toISOString().split('T')[0]}.json`
  link.click()
  
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 bg-white z-50 px-4 py-3 shadow-sm border-b border-slate-100 flex items-center gap-3 h-14">
      <button @click="router.back()" class="p-2 -ml-2 text-slate-600 hover:bg-slate-50 rounded-full active:scale-90 transition-transform">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </button>
      <h1 class="text-lg font-bold text-slate-800">Pengaturan</h1>
    </div>

    <!-- Content -->
    <div class="pt-16 pb-8 px-4 space-y-6">
      
      <!-- Notifications Section -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
        <h2 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.868 19.718A10.951 10.951 0 0112 22c6.075 0 11-4.925 11-11S18.075 1 12 1 1 5.925 1 12c0 2.485.827 4.775 2.218 6.61L1 22l2.868-2.282z" />
          </svg>
          Notifikasi
        </h2>
        
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-700 text-sm">Push Notification</p>
              <p class="text-xs text-slate-400">Notifikasi langsung ke perangkat</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.notifications.push" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-700 text-sm">Update Pesanan</p>
              <p class="text-xs text-slate-400">Status pesanan dan pengiriman</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.notifications.orderUpdates" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-700 text-sm">Promosi & Penawaran</p>
              <p class="text-xs text-slate-400">Diskon dan promo menarik</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.notifications.promotions" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
          </div>
        </div>
      </div>

      <!-- Preferences Section -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
        <h2 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100-4m0 4v2m0-6V4" />
          </svg>
          Preferensi
        </h2>

        <div class="space-y-4">
          <!-- Theme -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Tema Aplikasi</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="theme in themeOptions"
                :key="theme.value"
                @click="settings.preferences.theme = theme.value"
                class="p-3 border-2 rounded-xl flex flex-col items-center gap-1 transition-all"
                :class="settings.preferences.theme === theme.value 
                  ? 'border-orange-500 bg-orange-50 text-orange-600' 
                  : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
              >
                <span class="text-lg">{{ theme.icon }}</span>
                <span class="text-xs font-medium">{{ theme.label }}</span>
              </button>
            </div>
          </div>

          <!-- Currency -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Mata Uang</label>
            <select 
              v-model="settings.preferences.currency"
              class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
            >
              <option v-for="currency in currencyOptions" :key="currency.value" :value="currency.value">
                {{ currency.label }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Privacy Section -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
        <h2 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Privasi & Keamanan
        </h2>

        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-700 text-sm">Analitik Aplikasi</p>
              <p class="text-xs text-slate-400">Bantu tingkatkan pengalaman aplikasi</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.privacy.analytics" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-700 text-sm">Bagikan Data</p>
              <p class="text-xs text-slate-400">Untuk rekomendasi yang lebih baik</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.privacy.shareData" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
          </div>
        </div>
      </div>

      <!-- Data Management Section -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
        <h2 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
          </svg>
          Manajemen Data
        </h2>

        <div class="space-y-3">
          <button 
            @click="exportData"
            class="w-full p-3 bg-slate-50 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-100 transition-colors flex items-center justify-between"
          >
            <span>Export Data Saya</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </button>

          <button 
            @click="clearCache"
            class="w-full p-3 bg-slate-50 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-100 transition-colors flex items-center justify-between"
          >
            <span>Hapus Cache</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-3">
        <button 
          @click="resetSettings"
          class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-medium hover:bg-slate-200 transition-colors"
        >
          Reset Default
        </button>
        <button 
          @click="saveSettings"
          class="flex-[2] py-3 bg-orange-500 text-white rounded-xl font-bold shadow-lg shadow-orange-500/20 hover:bg-orange-600 transition-all active:scale-[0.98]"
        >
          Simpan Pengaturan
        </button>
      </div>

      <!-- App Info -->
      <div class="bg-slate-100 rounded-2xl p-4 text-center">
        <p class="text-xs text-slate-500 mb-1">Dimsum Sarang POS</p>
        <p class="text-xs text-slate-400">Versi 1.0.5 • Build 2026.02.06</p>
      </div>
    </div>
  </div>
</template>