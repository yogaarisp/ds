<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useSettingsStore } from '../stores/settings'
import { useRouter, useRoute } from 'vue-router'
import { clearAllCache, clearLocalStorage, getStorageInfo } from '../utils/cache'

const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const router = useRouter()
const route = useRoute()
const sidebarOpen = ref(false)
const showClearConfirm = ref(false)

// Keyboard shortcut for clear cache (Ctrl+Shift+Delete)
function handleKeydown(event) {
  if (event.ctrlKey && event.shiftKey && event.key === 'Delete') {
    event.preventDefault()
    confirmClearCache()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  settingsStore.loadSettings()
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})

const menuItems = [
  { 
    name: 'Dashboard', 
    path: '/', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>`, 
    roles: ['owner'] 
  },
  { 
    name: 'Transaksi', 
    path: '/transactions', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>`, 
    roles: ['owner', 'kasir'] 
  },
  { 
    name: 'POS', 
    path: '/pos', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>`, 
    roles: ['owner', 'kasir'] 
  },
  { 
    name: 'Customers', 
    path: '/customers', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M15.31 6.5a4.5 4.5 0 0 1 0 7"/></svg>`, 
    roles: ['owner', 'kasir'] 
  },
  { 
    name: 'Produk', 
    path: '/products', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>`, 
    roles: ['owner'] 
  },
  { 
    name: 'Paket', 
    path: '/packages', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>`, 
    roles: ['owner'] 
  },
  { 
    name: 'Inventory', 
    path: '/inventory', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M17 18h1"/><path d="M12 18h1"/><path d="M7 18h1"/></svg>`, 
    roles: ['owner', 'gudang'] 
  },
  { 
    name: 'Laporan', 
    path: '/reports', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>`, 
    roles: ['owner'] 
  },
  { 
    name: 'Pengaturan', 
    path: '/settings', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>`, 
    roles: ['owner'] 
  },
  { 
    name: 'Meja', 
    path: '/tables', 
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="18" height="12" x="3" y="4" rx="2" ry="2"/><line x1="2" x2="22" y1="20" y2="20"/><line x1="7" x2="7" y1="16" y2="20"/><line x1="17" x2="17" y1="16" y2="20"/></svg>`, 
    roles: ['owner'] 
  },
]

const filteredMenu = menuItems.filter(item => 
  item.roles.includes(authStore.user?.role)
)

function isActive(path) {
  if (path === '/') {
    return route.path === '/'
  }
  return route.path.startsWith(path)
}

function closeSidebar() {
  sidebarOpen.value = false
}

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}

function confirmClearCache() {
  showClearConfirm.value = true
}

function clearCache() {
  clearAllCache()
  showClearConfirm.value = false
}

function clearStorageOnly() {
  clearLocalStorage()
  showClearConfirm.value = false
}
</script>

<template>
  <div class="min-h-screen lg:h-screen lg:overflow-hidden lg:flex lg:flex-row bg-slate-50 font-sans">
    
    <!-- Mobile Header (Sticky & Glass) -->
    <div class="lg:hidden fixed top-0 left-0 right-0 z-30 px-5 py-3.5 flex items-center justify-between bg-white/80 backdrop-blur-md border-b border-slate-200/60 transition-all duration-300">
      <div class="flex items-center gap-3">
        <div class="relative group">
          <img v-if="settingsStore.storeLogo" :src="settingsStore.storeLogo" alt="Logo" class="relative w-[38px] h-[38px] object-cover" />
          <div v-else class="relative w-[38px] h-[38px] bg-gradient-to-br from-primary-50 to-orange-50 flex items-center justify-center">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5.5 h-5.5 text-primary-500"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
          </div>
        </div>
        <div>
          <h1 class="text-[14.5px] font-bold text-slate-800 leading-tight">{{ settingsStore.storeName }}</h1>
          <p class="text-[9.5px] font-medium text-slate-400 uppercase tracking-wider">POS System</p>
        </div>
      </div>
      
      <!-- Profile Action (Mobile) -->
      <button @click="confirmClearCache" class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
    </div>

    <!-- Backdrop when sidebar is open on mobile -->
    <transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="sidebarOpen" 
        @click="closeSidebar"
        class="lg:hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40"
      ></div>
    </transition>

    <!-- Sidebar (Desktop & Mobile Drawer) -->
    <aside 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-[240px] bg-white flex flex-col transition-all duration-300 cubic-bezier(0.4, 0, 0.2, 1)',
        'lg:translate-x-0 lg:static lg:shadow-none border-r border-slate-100',
        sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'
      ]"
    >
      <!-- Sidebar Header -->
      <div class="h-20 flex items-center px-6 border-b border-slate-50">
        <div class="flex items-center gap-3 w-full">
           <div class="relative group">
              <img v-if="settingsStore.storeLogo" :src="settingsStore.storeLogo" alt="Logo" class="relative w-8 h-8 object-cover group-hover:scale-105 transition-transform duration-300 rounded-none" />
              <div v-else class="relative w-8 h-8 bg-gradient-to-br from-primary-50 to-white flex items-center justify-center">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-primary-500"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
              </div>
           </div>
           <div class="flex flex-col">
             <h1 class="text-[15px] font-bold text-slate-800 tracking-tight leading-tight">{{ settingsStore.storeName }}</h1>
             <span class="text-[9px] font-bold text-primary-600 bg-primary-50 px-1.5 py-0.5 rounded-full w-fit mt-0.5">PRO V.2.0</span>
           </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">
        <router-link
          v-for="item in filteredMenu"
          :key="item.path"
          :to="item.path"
          @click="closeSidebar"
          class="relative flex items-center gap-3.5 px-4 py-3.5 rounded-2xl transition-all duration-200 group overflow-hidden"
          :class="[
            isActive(item.path) 
              ? 'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-200 translate-x-1' 
              : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 hover:translate-x-1'
          ]"
        >
          <!-- Active Background Pattern (Dots) -->
          <div v-if="isActive(item.path)" class="absolute inset-0 opacity-10">
             <svg width="100%" height="100%" fill="none"><pattern id="dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1" fill="currentColor"/></pattern><rect width="100%" height="100%" fill="url(#dots)"/></svg>
          </div>

          <div class="relative z-10" v-html="item.icon"></div>
          <span class="relative z-10 text-[13px] font-semibold tracking-wide">{{ item.name }}</span>
          
          <!-- Chevron for active -->
          <svg v-if="isActive(item.path)" class="w-4 h-4 ml-auto relative z-10 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
          </svg>
        </router-link>
      </nav>

      <!-- Sidebar Footer (User Profile) -->
      <div class="p-3 mb-10 lg:mb-0 mt-auto">
        <div class="relative p-2 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden group">
          <!-- Decorative blurred orb -->
          <div class="absolute -top-6 -right-6 w-16 h-16 bg-primary-200 rounded-full blur-2xl opacity-0 group-hover:opacity-50 transition duration-500"></div>

          <div class="flex items-center gap-2 relative z-10">
            <div class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center shadow-sm text-primary-600 font-bold text-sm select-none">
              {{ (authStore.user?.name || 'U').charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1 overflow-hidden">
              <p class="text-[10px] font-bold text-slate-800 truncate leading-none mb-0.5">{{ authStore.user?.name || 'User' }}</p>
              <p class="text-[8px] text-slate-500 capitalize">{{ authStore.user?.role || 'Staff' }}</p>
            </div>
            <button 
              @click="authStore.logout()" 
              class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-white hover:shadow-sm transition-all duration-200"
              title="Logout"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            </button>
          </div>
        </div>
        <div class="mt-3 text-center">
            <p class="text-[10px] font-bold text-slate-300 tracking-[0.2em] font-mono">POWERED BY KEETECH</p>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 relative bg-slate-50 lg:rounded-l-2xl lg:overflow-hidden flex flex-col min-h-screen pt-[67px] lg:pt-0 no-scrollbar">
       <!-- Scrollable wrapper for Desktop Content -->
       <div class="flex-1 w-full lg:overflow-y-auto lg:h-full pb-16 lg:pb-0 px-2 py-2 lg:px-2 lg:py-4">
          <router-view v-slot="{ Component }">
             <transition 
               enter-active-class="transition duration-200 ease-out"
               enter-from-class="opacity-0 translate-y-2 scale-[0.98]"
               enter-to-class="opacity-100 translate-y-0 scale-100"
               leave-active-class="transition duration-150 ease-in"
               leave-from-class="opacity-100 translate-y-0 scale-100"
               leave-to-class="opacity-0 translate-y-2 scale-[0.98]"
               mode="out-in"
             >
                <component :is="Component" />
             </transition>
          </router-view>
       </div>
    </main>

    <!-- Mobile Bottom Navigation (Floating Island V2) -->
    <nav class="lg:hidden fixed bottom-2.5 left-6 right-6 z-[100]">
       <div class="bg-white/95 backdrop-blur-xl border border-orange-200 rounded-3xl px-1.5 py-1 flex items-center justify-between">
          <template v-for="item in filteredMenu.slice(0, 4)" :key="item.path">
          <!-- Special POS Button -->
            <router-link
              v-if="item.path === '/pos'"
              :to="item.path"
              class="w-14 h-14 -mt-8 bg-gradient-to-br from-primary-500 to-orange-600 rounded-full flex items-center justify-center text-white shadow-lg shadow-primary-500/40 border-[4px] border-white ring-1 ring-orange-200 relative z-50 transition-transform active:scale-95 shrink-0 mx-1 group"
            >
               <span class="w-5 h-5 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full" v-html="item.icon"></span>
            </router-link>

            <!-- Standard Nav Item -->
            <router-link
              v-else
              :to="item.path"
              class="flex-1 flex flex-col items-center justify-center gap-0.5 h-11 rounded-2xl relative transition-all duration-300"
              :class="isActive(item.path) ? 'text-primary-600 bg-primary-50/50' : 'text-slate-400 hover:text-slate-600'"
            >
              <div 
                class="w-5 h-5 transition-transform duration-300"
                :class="isActive(item.path) ? '-translate-y-0.5' : ''"
                v-html="item.icon"
              ></div>
              <span v-if="isActive(item.path)" class="w-1 h-1 bg-current rounded-full absolute bottom-1.5"></span>
            </router-link>
          </template>
          
          <!-- Menu Toggle -->
          <button 
             @click="toggleSidebar"
             class="flex-1 flex flex-col items-center justify-center gap-0.5 h-11 rounded-2xl relative transition-all duration-300 text-slate-400 hover:text-slate-600"
             :class="sidebarOpen ? 'text-primary-600 bg-primary-50/50' : ''"
          >
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
             <span v-if="sidebarOpen" class="w-1 h-1 bg-primary-600 rounded-full absolute bottom-1.5"></span>
          </button>
       </div>
    </nav>

    <!-- Clear Cache Modal (Kept same functional logic, refreshed style) -->
    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
      <div v-if="showClearConfirm" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
        <div class="bg-white rounded-3xl w-full max-w-sm p-6 shadow-2xl border border-white/50">
          <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mx-auto mb-4 ring-4 ring-red-50/50">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-7 h-7"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800">Clear Cache?</h3>
            <p class="text-sm text-slate-500 mt-1">This will help fix display issues.</p>
          </div>
          <div class="space-y-3">
            <button @click="clearStorageOnly" class="w-full py-3 px-4 rounded-xl bg-orange-50 text-orange-700 font-semibold text-sm hover:bg-orange-100 transition-colors text-left flex items-center gap-3">
               <span class="w-2 h-2 rounded-full bg-orange-400"></span> Clear Local Storage
            </button>
            <button @click="clearCache" class="w-full py-3 px-4 rounded-xl bg-red-50 text-red-700 font-semibold text-sm hover:bg-red-100 transition-colors text-left flex items-center gap-3">
               <span class="w-2 h-2 rounded-full bg-red-400"></span> Clear All & Reload
            </button>
            <button @click="showClearConfirm = false" class="w-full py-3 rounded-xl bg-slate-100 text-slate-600 font-semibold text-sm hover:bg-slate-200 transition-colors mt-4">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>
