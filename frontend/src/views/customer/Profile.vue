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
  // Load user from auth store or local storage
  if (authStore.user) {
    user.value = authStore.user
  } else {
    const userData = localStorage.getItem('user')
    if (userData) {
      user.value = JSON.parse(userData)
    }
  }
  
  // Fetch latest user data (points & coupons)
  if (authStore.token) {
    const updatedUser = await authStore.fetchUser()
    if (updatedUser) {
      user.value = updatedUser
    }
  }
})

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}

const menuItems = [
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" /></svg>',
    label: 'Pengaturan Printer',
    action: () => router.push('/customer/printer-settings')
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>',
    label: computed(() => t('myAddresses')),
    action: () => router.push('/customer/addresses')
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>',
    label: computed(() => t('redeemCenter')),
    action: () => showComingSoon()
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>',
    label: computed(() => t('helpCenter')),
    action: () => showComingSoon()
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>',
    label: computed(() => t('languageSettings')),
    action: () => router.push('/customer/language-settings')
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>',
    label: computed(() => t('termsOfService')),
    action: () => showComingSoon()
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 018.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.43.816 1.035.795 1.735a20.897 20.897 0 01-.153 9.198c-.135.57-.796.925-1.379.67l-1.93-.85a1.075 1.075 0 01-.59-1.272c.312-1.022.42-2.115.308-3.213a.908.908 0 00-.913-.837H15" /></svg>',
    label: computed(() => t('settings')),
    action: () => router.push('/customer/settings')
  }
]

function showComingSoon(e) {
  if (e) e.preventDefault()
  const message = t('locale') === 'en' ? 'Coming Soon!' : 'Segera Hadir!'
  const text = t('locale') === 'en' ? 'This feature will be available soon.' : 'Fitur ini akan segera hadir.'
  
  Swal.fire({
    title: message,
    text: text,
    icon: 'info',
    confirmButtonColor: '#f97316',
    confirmButtonText: t('locale') === 'en' ? 'OK' : 'Oke',
    width: '280px',
    customClass: {
      popup: 'rounded-xl',
      title: 'text-lg font-bold',
      htmlContainer: 'text-sm',
      confirmButton: 'px-4 py-1.5 text-sm rounded-lg'
    }
  })
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-gray-900">
    <!-- Header Section -->
    <div class="bg-orange-50 pt-12 pb-24 px-6 relative overflow-hidden dark:bg-orange-900/20">
      <!-- Decorative Background Elements -->
      <div class="absolute top-[-50px] right-[-50px] w-48 h-48 bg-orange-200/40 rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 left-[-20px] w-32 h-32 bg-orange-300/20 rounded-full blur-2xl"></div>

      <div class="relative z-10 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <!-- Avatar -->
          <div class="w-16 h-16 rounded-full bg-orange-200 flex items-center justify-center text-orange-600 text-2xl font-bold shadow-sm border-2 border-white">
            {{ initial }}
          </div>
          <!-- User Info -->
          <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-gray-100">{{ name }}</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Pelanggan Setia</p>
          </div>
        </div>
        
        <!-- Illustration Placeholder (Cat/Coffee) -->
        <div class="w-24 h-24 opacity-90">
             <!-- Simple SVG Illustration of a Coffee Cup -->
             <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full drop-shadow-md">
                <path fill="#FFEDD5" d="M45.7,-76.3C58.9,-69.3,69.1,-58.3,76.3,-46.1C83.5,-33.9,87.6,-20.5,86.4,-7.6C85.2,5.3,78.7,17.7,70.3,28.6C61.9,39.5,51.6,48.9,40.6,56.3C29.6,63.7,17.9,69.1,5.6,71.3C-6.7,73.5,-19.6,72.5,-31.6,67.1C-43.6,61.7,-54.7,51.9,-63.4,40.3C-72.1,28.7,-78.4,15.3,-78.6,1.8C-78.8,-11.7,-72.9,-25.3,-64.1,-36.8C-55.3,-48.3,-43.6,-57.7,-31.5,-65.2C-19.4,-72.7,-6.9,-78.3,6.3,-79.8C19.5,-81.3,32.5,-78.7,45.7,-76.3Z" transform="translate(100 100)" />
                <path fill="#F97316" d="M140 100 L60 100 L70 160 C70 175 130 175 130 160 Z" /> <!-- Cup Body -->
                <path fill="#F97316" d="M130 110 C150 110 150 140 130 140" fill-opacity="0" stroke="#F97316" stroke-width="8" /> <!-- Handle -->
             </svg>
        </div>
      </div>
    </div>

    <!-- Stats Card (Floating) -->
    <div class="px-6 -mt-16 relative z-20">
      <div class="bg-white rounded-3xl p-6 shadow-xl shadow-slate-200/60 border border-slate-100 flex justify-between items-center dark:bg-gray-800 dark:border-gray-700 dark:shadow-gray-900/50">
        <!-- Voucher -->
        <div class="text-center flex-1 border-r border-slate-100 dark:border-gray-700">
          <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mb-1">Voucher</div>
          <div class="flex items-center justify-center gap-1 text-orange-500 dark:text-orange-400 font-bold text-lg">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
              <path fill-rule="evenodd" d="M1.5 6.375c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v3.026a.75.75 0 01-.375.65 2.249 2.249 0 000 3.898.75.75 0 01.375.65v3.026c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 011.5 17.625v-3.026a.75.75 0 01.374-.65 2.249 2.249 0 000-3.898.75.75 0 01-.374-.65V6.375zm15-1.125a.75.75 0 01.75.75v.75a.75.75 0 01-1.5 0V6a.75.75 0 01.75-.75zm.75 4.5a.75.75 0 00-1.5 0v.75a.75.75 0 001.5 0v-.75zm-.75 3a.75.75 0 01.75.75v.75a.75.75 0 01-1.5 0v-.75a.75.75 0 01.75-.75z" clip-rule="evenodd" />
            </svg>
            {{ user.coupons_count || 0 }}
          </div>
        </div>
        
        <!-- Points -->
        <div class="text-center flex-1 border-r border-slate-100 dark:border-gray-700">
          <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mb-1">Points</div>
          <div class="flex items-center justify-center gap-1 text-orange-500 dark:text-orange-400 font-bold text-lg">
            <div class="w-5 h-5 rounded-full border-2 border-orange-500 dark:border-orange-400 flex items-center justify-center text-[10px]">P</div>
            {{ user.points || 0 }} <span class="text-xs font-normal text-slate-400 ml-0.5">pts</span>
          </div>
        </div>

        <!-- QR Code -->
        <div class="text-center flex-1 cursor-pointer" @click="showComingSoon">
          <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mb-1">Kode QR</div>
          <div class="flex items-center justify-center text-orange-500 dark:text-orange-400 hover:text-orange-600 dark:hover:text-orange-500 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Achievement Banner -->
    <div class="px-6 mt-6">
      <div class="bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl p-4 text-white flex justify-between items-center shadow-lg shadow-orange-500/20">
        <div>
           <div class="font-bold text-lg">SEMUA SERBA 28K</div>
           <div class="text-xs text-orange-100">Untuk setiap rekomendasimu!</div>
        </div>
        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </div>
      </div>
    </div>

    <!-- Menu List -->
    <div class="px-6 mt-8 space-y-1">
      <button 
        v-for="(item, index) in menuItems" 
        :key="index"
        @click="item.action"
        class="w-full bg-white p-4 rounded-2xl flex items-center justify-between group active:scale-[0.98] transition-all duration-200 border border-transparent hover:border-orange-100 hover:shadow-sm dark:bg-gray-800 dark:hover:border-orange-800 dark:border-gray-700"
      >
        <div class="flex items-center gap-4">
          <div class="text-slate-400 group-hover:text-orange-500 transition-colors" v-html="item.icon"></div>
          <span class="text-slate-700 font-medium text-sm">{{ item.label.value || item.label }}</span>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-slate-300 group-hover:text-orange-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
      </button>

      <!-- Logout Button -->
      <button 
        @click="handleLogout"
        class="w-full bg-white dark:bg-gray-800 dark:border-gray-700 p-4 rounded-2xl flex items-center justify-between group active:scale-[0.98] transition-all duration-200 mt-4 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
      >
        <div class="flex items-center gap-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
          </svg>
          <span class="font-medium text-sm">{{ t('logout') }}</span>
        </div>
      </button>
    </div>

    <!-- Footer Space -->
    <div class="h-8"></div>
  </div>
</template>
