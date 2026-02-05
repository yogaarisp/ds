<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import api from '@/api'

const router = useRouter()
const route = useRoute()
const settingsStore = useSettingsStore()
const authStore = useAuthStore()
const cartStore = useCartStore()

async function detectTable(tableCode) {
  if (!tableCode) return
  
  // Only proceed if the table code is DIFFERENT from current one
  // or if no table is set yet.
  if (tableCode === cartStore.tableCode) return

  try {
    const res = await api.get(`/tables/code/${tableCode}`)
    if (res.data && res.data.id) {
      cartStore.setTable(res.data.code, res.data.name)
      console.log('✅ Table session updated:', res.data.name)
    }
  } catch (error) {
    console.warn('⚠️ Table not found:', tableCode)
  }
}

// Watch for table code changes in URL (e.g. scanning a different QR while app is open)
watch(() => route.query.table, (newTableCode) => {
  if (newTableCode) {
    detectTable(newTableCode)
  }
})

let expiryInterval
onMounted(async () => {
  settingsStore.loadSettings()
  authStore.checkAuth()
  
  // Initial Table detection from URL
  await detectTable(route.query.table)

  // Check expiry every 10 seconds
  expiryInterval = setInterval(() => {
    cartStore.checkTableExpiry()
  }, 10000)
})

onUnmounted(() => {
  if (expiryInterval) clearInterval(expiryInterval)
})

const navItems = computed(() => [
  { 
    name: 'Home', 
    path: '/customer/home', 
    icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" /><path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" /></svg>`
  },
  { 
    name: 'Menu', 
    path: '/customer/menu', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M12 10c4.97 0 9-1.79 9-4s-4.03-4-9-4-9 1.79-9 4 4.03 4 9 4z"/><path d="M3 6v10c0 2.21 4.03 4 9 4s9-1.79 9-4V6"/><path d="M3 11c0 2.21 4.03 4 9 4s9-1.79 9-4"/><path d="M12 2v8"/><path d="M18.5 4.5l-13 3"/><path d="M18.5 7.5l-13-3"/></svg>` 
  },
  { 
    name: 'Pesanan', 
    path: '/customer/order', 
    icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path fill-rule="evenodd" d="M7.5 6v.75H5.513c-.96 0-1.764.724-1.865 1.679l-1.263 12A1.875 1.875 0 004.25 22.5h15.5a1.875 1.875 0 001.865-2.071l-1.263-12a1.875 1.875 0 00-1.865-1.679H16.5V6a4.5 4.5 0 10-9 0zM12 3a3 3 0 00-3 3v.75h6V6a3 3 0 00-3-3zm-3 8.25a3 3 0 106 0v-.75a.75.75 0 011.5 0v.75a4.5 4.5 0 11-9 0v-.75a.75.75 0 011.5 0v.75z" clip-rule="evenodd" /></svg>` 
  },
  { 
    name: authStore.isAuthenticated ? 'Profile' : 'Login', 
    path: authStore.isAuthenticated ? '/customer/profil' : '/login', 
    icon: authStore.isAuthenticated 
      ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>`
      : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>`
  }
])


function isActive(path) {
  return route.path.startsWith(path)
}

function handleNavClick(item) {
  // Check if it's the order page and user is a guest without table
  if (item.path === '/customer/order' && !authStore.isAuthenticated && !cartStore.hasTable) {
    authStore.promptAuthChoice()
    return
  }
  router.push(item.path)
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 pb-[80px]">
    
    <!-- Page Content -->
    <router-view v-slot="{ Component }">
      <transition name="fade" mode="out-in">
        <div :key="route.path" class="w-full">
          <component :is="Component" />
        </div>
      </transition>
    </router-view>

    <!-- Bottom Navigation Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-xl border-t border-slate-200/50 pb-safe pt-1 px-6 rounded-t-2xl shadow-[0_-4px_20px_rgba(0,0,0,0.04)] z-50">
      <div class="flex justify-between items-center max-w-md mx-auto">
        <div 
          v-for="item in navItems" 
          :key="item.name"
          @click="handleNavClick(item)"
          class="flex flex-col items-center gap-0.5 py-1 px-2 transition-all duration-300 group cursor-pointer"
          :class="isActive(item.path) ? 'text-orange-500' : 'text-slate-400'"
        >
          <div 
            class="relative flex items-center justify-center transition-all duration-300"
            :class="(item.name === 'Home' && settingsStore.storeLogo && isActive(item.path)) ? 'h-9 w-9' : 'h-6 w-6'"
          >
            <!-- Dynamic Logo for Home (Only when active) -->
            <template v-if="item.name === 'Home' && settingsStore.storeLogo && isActive(item.path)">
              <img :src="settingsStore.storeLogo" class="w-full h-full object-contain transition-transform duration-300 group-active:scale-90" alt="Home" />
            </template>
            <!-- Default SVG Icon -->
            <template v-else>
              <div v-html="item.icon" class="w-full h-full transition-transform duration-300 group-active:scale-90"></div>
            </template>
          </div>
          <span 
            v-if="!(item.name === 'Home' && settingsStore.storeLogo && isActive(item.path))"
            class="text-[9px] font-medium tracking-wide" 
            :class="isActive(item.path) ? 'font-bold' : ''"
          >
            {{ item.name }}
          </span>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.font-outfit {
  font-family: 'Outfit', sans-serif;
}

.pb-safe {
  padding-bottom: env(safe-area-inset-bottom, 20px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
