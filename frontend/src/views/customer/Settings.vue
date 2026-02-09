<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import { customerApi } from '@/api'
import Swal from 'sweetalert2'

const router = useRouter()
const authStore = useAuthStore()
const { theme, setTheme } = useTheme()

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
    dateFormat: 'DD/MM/YYYY',
    timeFormat: '24h'
  }
})

const themeOptions = [
  { value: 'light', label: 'Terang', icon: '☀️' },
  { value: 'dark', label: 'Gelap', icon: '🌙' },
  { value: 'auto', label: 'Otomatis', icon: '🔄' }
]

// Password change state
const showPasswordModal = ref(false)
const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})
const passwordLoading = ref(false)

onMounted(() => {
  loadSettings()
  // Sync theme with composable
  settings.value.preferences.theme = theme.value
})

function loadSettings() {
  // Load settings from localStorage
  const savedSettings = localStorage.getItem('customer_settings')
  if (savedSettings) {
    settings.value = { ...settings.value, ...JSON.parse(savedSettings) }
  }
  // Sync theme with composable
  settings.value.preferences.theme = theme.value
}

function saveSettings() {
  localStorage.setItem('customer_settings', JSON.stringify(settings.value))
  // Note: Theme functionality disabled for now
  // Show success message
  Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: 'Pengaturan berhasil disimpan',
    confirmButtonColor: '#F97316',
    confirmButtonText: 'Oke',
    width: '280px',
    padding: '1.2em',
    customClass: {
      popup: 'rounded-2xl',
      title: 'font-bold text-sm mb-1',
      htmlContainer: 'text-xs text-slate-500 m-0',
      confirmButton: 'px-4 py-2 text-xs rounded-lg font-medium'
    }
  })
}

function handleThemeChange(newTheme) {
  settings.value.preferences.theme = newTheme
  setTheme(newTheme)
}

function resetSettings() {
  Swal.fire({
    title: 'Reset Pengaturan?',
    text: 'Apakah Anda yakin ingin mengembalikan semua pengaturan ke default?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#F97316',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Reset',
    cancelButtonText: 'Batal',
    width: '320px',
    padding: '1.2em',
    customClass: {
      popup: 'rounded-2xl',
      title: 'font-bold text-sm mb-1',
      htmlContainer: 'text-xs text-slate-500 m-0',
      confirmButton: 'px-4 py-2 text-xs rounded-lg font-medium',
      cancelButton: 'px-4 py-2 text-xs rounded-lg font-medium'
    }
  }).then((result) => {
    if (result.isConfirmed) {
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
          dateFormat: 'DD/MM/YYYY',
          timeFormat: '24h'
        }
      }
      saveSettings()
    }
  })
}

function clearCache() {
  Swal.fire({
    title: 'Hapus Cache?',
    text: 'Hapus cache aplikasi? Ini akan menghapus data sementara dan mungkin memperlambat loading awal.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    width: '320px',
    padding: '1.2em',
    customClass: {
      popup: 'rounded-2xl',
      title: 'font-bold text-sm mb-1',
      htmlContainer: 'text-xs text-slate-500 m-0',
      confirmButton: 'px-4 py-2 text-xs rounded-lg font-medium',
      cancelButton: 'px-4 py-2 text-xs rounded-lg font-medium'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      // Clear various cache items but keep important user data
      const keysToKeep = ['token', 'user', 'customer_settings', 'app_language']
      const allKeys = Object.keys(localStorage)
      
      allKeys.forEach(key => {
        if (!keysToKeep.includes(key)) {
          localStorage.removeItem(key)
        }
      })
      
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Cache berhasil dihapus!',
        confirmButtonColor: '#F97316',
        confirmButtonText: 'Oke',
        width: '280px',
        padding: '1.2em',
        customClass: {
          popup: 'rounded-2xl',
          title: 'font-bold text-sm mb-1',
          htmlContainer: 'text-xs text-slate-500 m-0',
          confirmButton: 'px-4 py-2 text-xs rounded-lg font-medium'
        }
      })
    }
  })
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

function showComingSoon() {
  Swal.fire({
    icon: 'info',
    title: 'Segera Hadir!',
    text: 'Fitur tema akan segera tersedia dalam update mendatang!',
    confirmButtonColor: '#F97316',
    confirmButtonText: 'Oke',
    width: '280px',
    padding: '1.2em',
    customClass: {
      popup: 'rounded-2xl',
      title: 'font-bold text-sm mb-1',
      htmlContainer: 'text-xs text-slate-500 m-0',
      confirmButton: 'px-4 py-2 text-xs rounded-lg font-medium'
    }
  })
}

function openPasswordModal() {
  passwordForm.value = {
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
  }
  showPasswordModal.value = true
}

async function changePassword() {
  // Validate form
  if (!passwordForm.value.current_password || !passwordForm.value.new_password || !passwordForm.value.new_password_confirmation) {
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: 'Semua field harus diisi',
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Oke',
      width: '280px',
      padding: '1.2em',
      customClass: {
        popup: 'rounded-2xl',
        title: 'font-bold text-sm mb-1',
        htmlContainer: 'text-xs text-slate-500 m-0',
        confirmButton: 'px-4 py-2 text-xs rounded-lg font-medium'
      }
    })
    return
  }

  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: 'Konfirmasi password tidak cocok',
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Oke',
      width: '280px',
      padding: '1.2em',
      customClass: {
        popup: 'rounded-2xl',
        title: 'font-bold text-sm mb-1',
        htmlContainer: 'text-xs text-slate-500 m-0',
        confirmButton: 'px-4 py-2 text-xs rounded-lg font-medium'
      }
    })
    return
  }

  if (passwordForm.value.new_password.length < 6) {
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: 'Password baru minimal 6 karakter',
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Oke',
      width: '280px',
      padding: '1.2em',
      customClass: {
        popup: 'rounded-2xl',
        title: 'font-bold text-sm mb-1',
        htmlContainer: 'text-xs text-slate-500 m-0',
        confirmButton: 'px-4 py-2 text-xs rounded-lg font-medium'
      }
    })
    return
  }

  passwordLoading.value = true
  try {
    await customerApi.changePassword({
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password,
      new_password_confirmation: passwordForm.value.new_password_confirmation
    })

    showPasswordModal.value = false
    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Password berhasil diubah',
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Oke',
      width: '280px',
      padding: '1.2em',
      customClass: {
        popup: 'rounded-2xl',
        title: 'font-bold text-sm mb-1',
        htmlContainer: 'text-xs text-slate-500 m-0',
        confirmButton: 'px-4 py-2 text-xs rounded-lg font-medium'
      }
    })
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal mengubah password'
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: message,
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Oke',
      width: '280px',
      padding: '1.2em',
      customClass: {
        popup: 'rounded-2xl',
        title: 'font-bold text-sm mb-1',
        htmlContainer: 'text-xs text-slate-500 m-0',
        confirmButton: 'px-4 py-2 text-xs rounded-lg font-medium'
      }
    })
  } finally {
    passwordLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 bg-white dark:bg-slate-800 z-50 px-4 py-3 shadow-sm border-b border-slate-100 dark:border-slate-700 flex items-center gap-3 h-14">
      <button @click="router.back()" class="p-2 -ml-2 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-full active:scale-90 transition-transform">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </button>
      <h1 class="text-lg font-bold text-slate-800 dark:text-slate-100">Pengaturan</h1>
    </div>

    <!-- Content -->
    <div class="pt-16 pb-8 px-4 space-y-6">
      
      <!-- Notifications Section -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <h2 class="font-bold text-slate-800 dark:text-slate-100 text-sm mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.868 19.718A10.951 10.951 0 0112 22c6.075 0 11-4.925 11-11S18.075 1 12 1 1 5.925 1 12c0 2.485.827 4.775 2.218 6.61L1 22l2.868-2.282z" />
          </svg>
          Notifikasi
        </h2>
        
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-700 dark:text-slate-200 text-sm">Push Notification</p>
              <p class="text-xs text-slate-400 dark:text-slate-500">Notifikasi langsung ke perangkat</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.notifications.push" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-700 dark:text-slate-200 text-sm">Update Pesanan</p>
              <p class="text-xs text-slate-400 dark:text-slate-500">Status pesanan dan pengiriman</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.notifications.orderUpdates" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-700 dark:text-slate-200 text-sm">Promosi & Penawaran</p>
              <p class="text-xs text-slate-400 dark:text-slate-500">Diskon dan promo menarik</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.notifications.promotions" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
          </div>
        </div>
      </div>

      <!-- Preferences Section -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <h2 class="font-bold text-slate-800 dark:text-slate-100 text-sm mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100-4m0 4v2m0-6V4" />
          </svg>
          Preferensi
        </h2>

        <div class="space-y-4">
          <!-- Theme -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tema Aplikasi</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="themeOption in themeOptions"
                :key="themeOption.value"
                @click="showComingSoon"
                class="p-3 border-2 rounded-xl flex flex-col items-center gap-1 transition-all opacity-60 cursor-not-allowed"
                :class="'border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300'"
              >
                <span class="text-lg">{{ themeOption.icon }}</span>
                <span class="text-xs font-medium">{{ themeOption.label }}</span>
              </button>
            </div>
            <div class="mt-2 bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 rounded-lg p-2 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 dark:text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-xs text-blue-600 dark:text-blue-400">Fitur tema akan segera tersedia dalam update mendatang</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Privacy Section -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
        <h2 class="font-bold text-slate-800 dark:text-slate-100 text-sm mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Privasi & Keamanan
        </h2>

        <div class="space-y-3">
          <button 
            @click="openPasswordModal"
            class="w-full p-3 bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors flex items-center justify-between"
          >
            <div class="flex items-center gap-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
              </svg>
              <span>Ubah Password</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
          </button>

          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-700 dark:text-slate-200 text-sm">Analitik Aplikasi</p>
              <p class="text-xs text-slate-400 dark:text-slate-500">Bantu tingkatkan pengalaman aplikasi</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="settings.privacy.analytics" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-700 dark:text-slate-200 text-sm">Bagikan Data</p>
              <p class="text-xs text-slate-400 dark:text-slate-500">Untuk rekomendasi yang lebih baik</p>
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
    </div>

    <!-- Password Change Modal -->
    <div v-if="showPasswordModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[200] p-4">
      <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
        <!-- Modal Header -->
        <div class="px-6 pt-6 pb-3 flex justify-between items-center border-b border-slate-100 dark:border-slate-700">
          <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Ubah Password</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Masukkan password lama dan password baru</p>
          </div>
          <button @click="showPasswordModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-500 dark:hover:text-red-400 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Modal Content -->
        <div class="p-6">
          <form @submit.prevent="changePassword" class="space-y-4">
            <!-- Current Password -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Password Saat Ini</label>
              <input
                v-model="passwordForm.current_password"
                type="password"
                required
                class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                placeholder="Masukkan password saat ini"
              >
            </div>

            <!-- New Password -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Password Baru</label>
              <input
                v-model="passwordForm.new_password"
                type="password"
                required
                minlength="6"
                class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                placeholder="Masukkan password baru (min. 6 karakter)"
              >
            </div>

            <!-- Confirm New Password -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Konfirmasi Password Baru</label>
              <input
                v-model="passwordForm.new_password_confirmation"
                type="password"
                required
                minlength="6"
                class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                placeholder="Ulangi password baru"
              >
            </div>
          </form>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 pb-6 pt-3 border-t border-slate-100 dark:border-slate-700">
          <div class="flex gap-3">
            <button 
              @click="showPasswordModal = false" 
              type="button"
              class="flex-1 py-3 rounded-xl text-slate-400 dark:text-slate-500 font-bold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
            >
              Batal
            </button>
            <button 
              @click="changePassword"
              :disabled="passwordLoading"
              class="flex-[2] bg-orange-500 py-3 rounded-xl text-white font-bold text-sm shadow-lg shadow-orange-500/20 hover:bg-orange-600 transition-all active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <svg v-if="passwordLoading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ passwordLoading ? 'Mengubah...' : 'Ubah Password' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>