<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from '@/i18n'
import Swal from 'sweetalert2'

const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

// Get user data from store
const user = ref({})
const name = computed(() => user.value.name || 'Pelanggan')
const initial = computed(() => name.value.charAt(0).toUpperCase())

onMounted(async () => {
  if (authStore.user) {
    user.value = authStore.user
  } else {
    const userData = localStorage.getItem('user')
    if (userData) {
      user.value = JSON.parse(userData)
    }
  }
  
  if (authStore.token) {
    const updatedUser = await authStore.fetchUser()
    if (updatedUser) {
      user.value = updatedUser
    }
  }
})

async function handleLogout() {
  Swal.fire({
    title: 'Keluar Akun?',
    text: 'Kamu akan keluar dari akun customer ini.',
    showCancelButton: true,
    confirmButtonText: 'Keluar',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    width: '300px',
    customClass: {
      popup: 'rounded-2xl',
      title: 'text-sm font-bold',
      confirmButton: 'px-4 py-2 text-xs rounded-xl font-bold',
      cancelButton: 'px-4 py-2 text-xs rounded-xl'
    }
  }).then(async (res) => {
    if (res.isConfirmed) {
      await authStore.logout()
    }
  })
}

const menuItems = [
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>',
    label: computed(() => t('myAddresses')),
    action: () => router.push('/customer/addresses')
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>',
    label: computed(() => t('languageSettings')),
    action: () => router.push('/customer/language-settings')
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 018.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.43.816 1.035.795 1.735a20.897 20.897 0 01-.153 9.198c-.135.57-.796.925-1.379.67l-1.93-.85a1.075 1.075 0 01-.59-1.272c.312-1.022.42-2.115.308-3.213a.908.908 0 00-.913-.837H15" /></svg>',
    label: computed(() => t('settings')),
    action: () => router.push('/customer/settings')
  }
]

function showComingSoon(e) {
  if (e) e.preventDefault()
  Swal.fire({
    title: 'Segera Hadir',
    text: 'Fitur ini akan segera tersedia.',
    icon: 'info',
    confirmButtonColor: '#f97316',
    confirmButtonText: 'Oke',
    width: '280px',
    customClass: {
      popup: 'rounded-2xl',
      title: 'text-sm font-bold',
      confirmButton: 'px-4 py-2 text-xs rounded-xl font-bold'
    }
  })
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 pb-28 text-slate-800 dark:text-slate-100 transition-colors">
    <div class="max-w-md mx-auto">
      
      <!-- ══════════════════════════════════════════
           1. CLEAN PROFILE HEADER
      ══════════════════════════════════════════ -->
      <div class="px-4 pt-6 pb-4 bg-white dark:bg-slate-800 border-b border-slate-200/60 dark:border-slate-800">
        <div class="flex items-center gap-3.5">
          <!-- Avatar -->
          <div class="w-14 h-14 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-100 text-xl font-bold flex items-center justify-center shrink-0 border border-slate-200/80 dark:border-slate-600 shadow-xs">
            {{ initial }}
          </div>
          
          <!-- User Details -->
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5">
              <h1 class="text-base font-bold text-slate-800 dark:text-gray-100 truncate">
                {{ name }}
              </h1>
              <span class="text-[9px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-1.5 py-0.5 rounded">
                Member
              </span>
            </div>
            <p class="text-xs text-slate-400 truncate mt-0.5">
              {{ user.email || user.phone || 'Pelanggan Setia' }}
            </p>
          </div>
        </div>
      </div>


      <!-- ══════════════════════════════════════════
           2. STATS SUMMARY (Points & Vouchers)
      ══════════════════════════════════════════ -->
      <div class="px-4 mt-3">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-3 border border-slate-200/70 dark:border-slate-700/80 flex items-center divide-x divide-slate-100 dark:divide-slate-700 shadow-xs">
          
          <!-- Vouchers -->
          <div class="flex-1 text-center py-0.5">
            <span class="text-[10px] text-slate-400 block font-medium">Voucher Saya</span>
            <span class="text-base font-bold text-slate-800 dark:text-gray-100 mt-0.5 block">
              {{ user.coupons_count || 0 }}
            </span>
          </div>

          <!-- Points -->
          <div class="flex-1 text-center py-0.5">
            <span class="text-[10px] text-slate-400 block font-medium">Poin Aktif</span>
            <span class="text-base font-bold text-slate-800 dark:text-gray-100 mt-0.5 block">
              {{ user.points || 0 }} <span class="text-[10px] font-normal text-slate-400">pts</span>
            </span>
          </div>

          <!-- Riwayat Pesanan Link -->
          <div class="flex-1 text-center py-0.5 cursor-pointer" @click="router.push('/customer/order')">
            <span class="text-[10px] text-slate-400 block font-medium">Pesanan</span>
            <span class="text-xs font-bold text-orange-600 dark:text-orange-400 mt-0.5 block">
              Lihat →
            </span>
          </div>

        </div>
      </div>


      <!-- ══════════════════════════════════════════
           3. MENU LIST (Clean, Grouped)
      ══════════════════════════════════════════ -->
      <div class="px-4 mt-4 space-y-3">
        
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/70 dark:border-slate-700/80 divide-y divide-slate-100 dark:divide-slate-700/60 overflow-hidden shadow-xs">
          <button 
            v-for="(item, index) in menuItems" 
            :key="index"
            @click="item.action"
            class="w-full p-3.5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="text-slate-500 dark:text-slate-400" v-html="item.icon"></div>
              <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                {{ item.label.value || item.label }}
              </span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>

        <!-- Logout Button -->
        <button 
          @click="handleLogout"
          class="w-full bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 p-3.5 rounded-2xl flex items-center justify-between text-rose-600 hover:bg-rose-50/50 dark:hover:bg-rose-950/20 active:scale-98 transition-all shadow-xs"
        >
          <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
            <span class="text-xs font-bold">{{ t('logout') }}</span>
          </div>
          <span class="text-[10px] text-slate-400">Keluar Sesi</span>
        </button>

      </div>

    </div>
  </div>
</template>
