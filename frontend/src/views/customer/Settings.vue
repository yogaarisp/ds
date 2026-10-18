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
  { value: 'auto', label: 'Sistem', icon: '⚙️' }
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
  settings.value.preferences.theme = theme.value
})

function loadSettings() {
  const savedSettings = localStorage.getItem('customer_settings')
  if (savedSettings) {
    settings.value = { ...settings.value, ...JSON.parse(savedSettings) }
  }
  settings.value.preferences.theme = theme.value
}

function saveSettings() {
  localStorage.setItem('customer_settings', JSON.stringify(settings.value))
  Swal.fire({
    icon: 'success',
    title: 'Disimpan',
    text: 'Pengaturan berhasil diperbarui',
    confirmButtonColor: '#F97316',
    confirmButtonText: 'Selesai',
    width: '280px',
    customClass: {
      popup: 'rounded-2xl',
      title: 'font-bold text-sm mb-1',
      confirmButton: 'px-4 py-2 text-xs rounded-xl font-bold'
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
    text: 'Kembalikan semua preferensi ke pengaturan awal?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#F97316',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Reset',
    cancelButtonText: 'Batal',
    width: '300px',
    customClass: {
      popup: 'rounded-2xl',
      title: 'font-bold text-sm mb-1',
      confirmButton: 'px-4 py-2 text-xs rounded-xl font-bold',
      cancelButton: 'px-4 py-2 text-xs rounded-xl'
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
    title: 'Hapus Cache Aplikasi?',
    text: 'Cache sementara akan dibersihkan tanpa menghapus sesi login akunmu.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#F97316',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Hapus',
    cancelButtonText: 'Batal',
    width: '300px',
    customClass: {
      popup: 'rounded-2xl',
      title: 'font-bold text-sm mb-1',
      confirmButton: 'px-4 py-2 text-xs rounded-xl font-bold',
      cancelButton: 'px-4 py-2 text-xs rounded-xl'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      const keysToKeep = ['token', 'user', 'customer_settings', 'app_language', 'customer_table_code', 'customer_table_name']
      const allKeys = Object.keys(localStorage)
      
      allKeys.forEach(key => {
        if (!keysToKeep.includes(key)) {
          localStorage.removeItem(key)
        }
      })
      
      Swal.fire({
        icon: 'success',
        title: 'Cache Bersih',
        text: 'Data sementara berhasil dihapus',
        timer: 1600,
        showConfirmButton: false,
        width: '280px',
        customClass: { popup: 'rounded-2xl', title: 'font-bold text-sm' }
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
  link.download = `customer-data-${new Date().toISOString().split('T')[0]}.json`
  link.click()
  
  URL.revokeObjectURL(url)
}

function showComingSoon() {
  Swal.fire({
    title: 'Fitur Tema',
    text: 'Pilihan tema tampilan akan segera tersedia dalam pembaruan berikutnya.',
    icon: 'info',
    confirmButtonColor: '#F97316',
    confirmButtonText: 'Mengerti',
    width: '280px',
    customClass: {
      popup: 'rounded-2xl',
      title: 'font-bold text-sm mb-1',
      confirmButton: 'px-4 py-2 text-xs rounded-xl font-bold'
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
  if (!passwordForm.value.current_password || !passwordForm.value.new_password || !passwordForm.value.new_password_confirmation) {
    Swal.fire({
      icon: 'error',
      title: 'Perhatian',
      text: 'Semua kolom password wajib diisi',
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Tutup',
      width: '280px',
      customClass: { popup: 'rounded-2xl', title: 'text-sm font-bold' }
    })
    return
  }

  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    Swal.fire({
      icon: 'error',
      title: 'Tidak Cocok',
      text: 'Konfirmasi password baru tidak cocok',
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Tutup',
      width: '280px',
      customClass: { popup: 'rounded-2xl', title: 'text-sm font-bold' }
    })
    return
  }

  if (passwordForm.value.new_password.length < 6) {
    Swal.fire({
      icon: 'error',
      title: 'Terlalu Pendek',
      text: 'Password baru minimal 6 karakter',
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Tutup',
      width: '280px',
      customClass: { popup: 'rounded-2xl', title: 'text-sm font-bold' }
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
      title: 'Berhasil',
      text: 'Password akun berhasil diubah',
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Selesai',
      width: '280px',
      customClass: { popup: 'rounded-2xl', title: 'text-sm font-bold' }
    })
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal mengubah password'
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: message,
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Tutup',
      width: '280px',
      customClass: { popup: 'rounded-2xl', title: 'text-sm font-bold' }
    })
  } finally {
    passwordLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 pb-28 text-slate-800 dark:text-slate-100 transition-colors">
    <div class="max-w-md mx-auto">
      
      <!-- ══════════════════════════════════════════
           1. STICKY TOP APP BAR (Aligned with Home)
      ══════════════════════════════════════════ -->
      <header class="sticky top-0 z-30 bg-slate-50/95 dark:bg-slate-900/95 backdrop-blur-md px-4 py-3 border-b border-slate-200/50 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button 
            @click="router.back()" 
            aria-label="Kembali"
            class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-orange-500 active:scale-90 transition-all"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
          </button>
          <h1 class="text-sm font-bold text-slate-800 dark:text-slate-100">
            Pengaturan Akun
          </h1>
        </div>

        <button 
          @click="saveSettings"
          class="text-xs font-bold text-orange-600 hover:text-orange-700 dark:text-orange-400 active:scale-95 transition-all"
        >
          Simpan
        </button>
      </header>


      <!-- ══════════════════════════════════════════
           2. GROUPED SETTINGS (iOS/Clean Style)
      ══════════════════════════════════════════ -->
      <div class="p-4 space-y-5">
        
        <!-- Group 1: Notifikasi -->
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-1 mb-2 block">
            Notifikasi
          </span>
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/70 dark:border-slate-700/80 divide-y divide-slate-100 dark:divide-slate-700/60 shadow-xs overflow-hidden">
            
            <div class="p-3.5 flex items-center justify-between gap-3">
              <div>
                <p class="font-semibold text-slate-800 dark:text-slate-100 text-xs">Push Notification</p>
                <p class="text-[10px] text-slate-400">Pemberitahuan langsung di perangkat</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer shrink-0">
                <input v-model="settings.notifications.push" type="checkbox" class="sr-only peer">
                <div class="w-10 h-5.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4.5 after:w-4.5 after:transition-all peer-checked:bg-orange-500"></div>
              </label>
            </div>

            <div class="p-3.5 flex items-center justify-between gap-3">
              <div>
                <p class="font-semibold text-slate-800 dark:text-slate-100 text-xs">Update Status Pesanan</p>
                <p class="text-[10px] text-slate-400">Pemberitahuan saat pesanan diproses & selesai</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer shrink-0">
                <input v-model="settings.notifications.orderUpdates" type="checkbox" class="sr-only peer">
                <div class="w-10 h-5.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4.5 after:w-4.5 after:transition-all peer-checked:bg-orange-500"></div>
              </label>
            </div>

            <div class="p-3.5 flex items-center justify-between gap-3">
              <div>
                <p class="font-semibold text-slate-800 dark:text-slate-100 text-xs">Promo & Voucher</p>
                <p class="text-[10px] text-slate-400">Info diskon berkala dan voucher baru</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer shrink-0">
                <input v-model="settings.notifications.promotions" type="checkbox" class="sr-only peer">
                <div class="w-10 h-5.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4.5 after:w-4.5 after:transition-all peer-checked:bg-orange-500"></div>
              </label>
            </div>

          </div>
        </div>

        <!-- Group 2: Keamanan & Akun -->
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-1 mb-2 block">
            Keamanan & Privasi
          </span>
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/70 dark:border-slate-700/80 divide-y divide-slate-100 dark:divide-slate-700/60 shadow-xs overflow-hidden">
            
            <button 
              @click="openPasswordModal"
              class="w-full p-3.5 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
            >
              <div>
                <p class="font-semibold text-slate-800 dark:text-slate-100 text-xs">Ubah Password</p>
                <p class="text-[10px] text-slate-400">Perbarui kata sandi akun masuk</p>
              </div>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
              </svg>
            </button>

            <div class="p-3.5 flex items-center justify-between gap-3">
              <div>
                <p class="font-semibold text-slate-800 dark:text-slate-100 text-xs">Analitik Performa</p>
                <p class="text-[10px] text-slate-400">Bantu tingkatkan kecepatan & kestabilan app</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer shrink-0">
                <input v-model="settings.privacy.analytics" type="checkbox" class="sr-only peer">
                <div class="w-10 h-5.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4.5 after:w-4.5 after:transition-all peer-checked:bg-orange-500"></div>
              </label>
            </div>

          </div>
        </div>

        <!-- Group 3: Tampilan & Preferensi -->
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-1 mb-2 block">
            Preferensi
          </span>
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/70 dark:border-slate-700/80 p-3.5 shadow-xs">
            <div class="flex items-center justify-between mb-2.5">
              <div>
                <p class="font-semibold text-slate-800 dark:text-slate-100 text-xs">Tema Antarmuka</p>
                <p class="text-[10px] text-slate-400">Pilihan nuansa tampilan</p>
              </div>
              <span class="text-[9px] bg-slate-100 dark:bg-slate-700 text-slate-500 px-2 py-0.5 rounded-full font-medium">
                Segera Hadir
              </span>
            </div>

            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="opt in themeOptions"
                :key="opt.value"
                @click="showComingSoon"
                class="py-2.5 px-2 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-700/40 flex flex-col items-center gap-1 opacity-70"
              >
                <span class="text-sm">{{ opt.icon }}</span>
                <span class="text-[10px] font-medium text-slate-600 dark:text-slate-300">{{ opt.label }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Group 4: Data & Penyimpanan -->
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-1 mb-2 block">
            Data Aplikasi
          </span>
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/70 dark:border-slate-700/80 divide-y divide-slate-100 dark:divide-slate-700/60 shadow-xs overflow-hidden">
            
            <button 
              @click="clearCache"
              class="w-full p-3.5 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
            >
              <div>
                <p class="font-semibold text-slate-800 dark:text-slate-100 text-xs">Bersihkan Cache</p>
                <p class="text-[10px] text-slate-400">Hapus file dan gambar sementara</p>
              </div>
              <span class="text-xs text-slate-400">Bersihkan</span>
            </button>

            <button 
              @click="exportData"
              class="w-full p-3.5 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
            >
              <div>
                <p class="font-semibold text-slate-800 dark:text-slate-100 text-xs">Ekspor Data Saya</p>
                <p class="text-[10px] text-slate-400">Unduh data akun dalam format JSON</p>
              </div>
              <span class="text-xs text-slate-400">Unduh</span>
            </button>

          </div>
        </div>

        <!-- Action Footer -->
        <div class="pt-2 flex items-center gap-2.5">
          <button 
            @click="resetSettings"
            class="flex-1 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-xl hover:bg-slate-50 active:scale-98 transition-all"
          >
            Reset Default
          </button>
          <button 
            @click="saveSettings"
            class="flex-[2] py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold rounded-xl shadow-xs active:scale-98 transition-all"
          >
            Simpan Perubahan
          </button>
        </div>

      </div>

      <!-- Password Modal (Clean & Cohesive) -->
      <div v-if="showPasswordModal" class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-sm shadow-xl border border-slate-200/80 dark:border-slate-700 overflow-hidden">
          
          <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Ubah Password</h3>
            <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
          </div>

          <form @submit.prevent="changePassword" class="p-4 space-y-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Password Saat Ini</label>
              <input
                v-model="passwordForm.current_password"
                type="password"
                required
                class="w-full px-3 py-2 text-xs border border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-700 text-slate-800 dark:text-slate-100 rounded-xl focus:ring-1 focus:ring-orange-500 focus:border-orange-500 outline-none"
                placeholder="Masukkan password saat ini"
              >
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Password Baru</label>
              <input
                v-model="passwordForm.new_password"
                type="password"
                required
                minlength="6"
                class="w-full px-3 py-2 text-xs border border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-700 text-slate-800 dark:text-slate-100 rounded-xl focus:ring-1 focus:ring-orange-500 focus:border-orange-500 outline-none"
                placeholder="Minimal 6 karakter"
              >
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">Ulangi Password Baru</label>
              <input
                v-model="passwordForm.new_password_confirmation"
                type="password"
                required
                minlength="6"
                class="w-full px-3 py-2 text-xs border border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-700 text-slate-800 dark:text-slate-100 rounded-xl focus:ring-1 focus:ring-orange-500 focus:border-orange-500 outline-none"
                placeholder="Ketik ulang password baru"
              >
            </div>

            <div class="pt-2 flex gap-2">
              <button 
                type="button" 
                @click="showPasswordModal = false"
                class="flex-1 py-2 rounded-xl text-xs font-semibold text-slate-500 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 transition-colors"
              >
                Batal
              </button>
              <button 
                type="submit" 
                :disabled="passwordLoading"
                class="flex-1 py-2 rounded-xl text-xs font-bold text-white bg-orange-500 hover:bg-orange-600 transition-colors disabled:opacity-50"
              >
                {{ passwordLoading ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>

        </div>
      </div>

    </div>
  </div>
</template>