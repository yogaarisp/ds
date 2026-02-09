<script setup>
import { ref, onMounted, computed } from 'vue'
import api, { STORAGE_BASE_URL } from '@/api'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import LazyImage from '@/components/LazyImage.vue'

const router = useRouter()
const authStore = useAuthStore()
const orders = ref([])
const loading = ref(false)
const activeTab = ref('all') // all, pending, completed, cancelled
const guestPhone = ref(localStorage.getItem('guest_phone') || '')
const showPhoneInput = ref(false)
const phoneInputValue = ref('')

const tabs = [
  { id: 'all', label: 'Semua' },
  { id: 'pending', label: 'Proses' }, // Maps to 'pending' status
  { id: 'completed', label: 'Selesai' }, // Maps to 'completed' status
  { id: 'cancelled', label: 'Batal' } // Maps to 'cancelled' status
]

const user = ref({})

onMounted(() => {
  // Get user
  if (authStore.user) {
    user.value = authStore.user
    fetchOrders()
  } else {
    const userData = localStorage.getItem('user')
    if (userData) {
      user.value = JSON.parse(userData)
      fetchOrders()
    } else {
      // Guest user - check if phone exists
      if (guestPhone.value) {
        fetchGuestOrders(guestPhone.value)
      } else {
        showPhoneInput.value = true
      }
    }
  }
})

async function fetchOrders() {
  if (!user.value.id) return

  loading.value = true
  try {
    const response = await api.get('/transactions', {
      params: {
        customer_id: user.value.id,
        per_page: 50 // Get reasonable amount
      }
    })
    orders.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch orders:', error)
  } finally {
    loading.value = false
  }
}

async function fetchGuestOrders(phone) {
  loading.value = true
  try {
    const response = await api.get('/transactions/guest/by-phone', {
      params: { phone }
    })
    orders.value = response.data.data
    guestPhone.value = phone
    localStorage.setItem('guest_phone', phone)
    showPhoneInput.value = false
  } catch (error) {
    console.error('Failed to fetch guest orders:', error)
    orders.value = []
  } finally {
    loading.value = false
  }
}

function handlePhoneSubmit() {
  if (phoneInputValue.value) {
    fetchGuestOrders(phoneInputValue.value)
  }
}

function changePhone() {
  showPhoneInput.value = true
  phoneInputValue.value = ''
  orders.value = []
}

const filteredOrders = computed(() => {
  if (activeTab.value === 'all') return orders.value
  
  if (activeTab.value === 'pending') {
    return orders.value.filter(o => o.status === 'pending')
  }
  if (activeTab.value === 'completed') {
    return orders.value.filter(o => o.status === 'completed')
  }
  if (activeTab.value === 'cancelled') {
    return orders.value.filter(o => o.status === 'cancelled')
  }
  return orders.value
})

function formatCurrency(value) {
  const val = Number(value) || 0
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(val)
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

function getStatusColor(status) {
  switch (status) {
    case 'pending': return 'bg-yellow-100 text-yellow-700 border-yellow-200'
    case 'completed': return 'bg-green-100 text-green-700 border-green-200'
    case 'cancelled': return 'bg-red-100 text-red-700 border-red-200'
    default: return 'bg-slate-100 text-slate-700 border-slate-200'
  }
}

function getStatusLabel(status) {
  switch (status) {
    case 'pending': return 'Diproses'
    case 'completed': return 'Selesai'
    case 'cancelled': return 'Dibatalkan'
    default: return status
  }
}

function getItemImage(item) {
  if (item.product?.image) return `${STORAGE_BASE_URL}/${item.product.image}`
  if (item.package?.image) return `${STORAGE_BASE_URL}/${item.package.image}`
  return null
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 sticky top-0 z-10 px-4 py-4 shadow-sm border-b border-slate-100 dark:border-slate-700">
      <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Pesanan Saya</h1>
      <!-- Guest Phone Info -->
      <div v-if="!authStore.isAuthenticated && guestPhone && !showPhoneInput" class="mt-2 flex items-center gap-2 text-xs">
        <span class="text-slate-500 dark:text-slate-400">📱 {{ guestPhone }}</span>
        <button @click="changePhone" class="text-orange-500 underline">Ganti</button>
      </div>
    </div>

    <!-- Guest Phone Input -->
    <div v-if="!authStore.isAuthenticated && showPhoneInput" class="bg-white dark:bg-slate-800 p-4 border-b border-slate-100 dark:border-slate-700">
      <div class="max-w-md mx-auto space-y-3">
        <div class="text-center mb-4">
          <div class="w-16 h-16 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
          </div>
          <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Lihat Riwayat Pesanan</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Masukkan nomor HP yang digunakan saat memesan</p>
        </div>
        
        <input
          v-model="phoneInputValue"
          type="tel"
          placeholder="Contoh: 08123456789"
          class="w-full px-4 py-3 text-base border-2 border-orange-200 dark:border-orange-700 rounded-xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 dark:bg-slate-700 dark:text-slate-100 transition-all"
          @keyup.enter="handlePhoneSubmit"
        />
        
        <button
          @click="handlePhoneSubmit"
          :disabled="!phoneInputValue"
          class="w-full bg-orange-500 text-white font-bold py-3 rounded-xl shadow-lg shadow-orange-500/25 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-orange-600 transition-all active:scale-[0.98]"
        >
          Lihat Pesanan
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div v-if="!showPhoneInput" class="bg-white dark:bg-slate-800 px-4 pt-2 sticky top-[60px] z-10 shadow-sm overflow-x-auto no-scrollbar">
      <div class="flex gap-4 min-w-max">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          class="pb-3 text-sm font-medium transition-colors relative"
          :class="activeTab === tab.id ? 'text-orange-600' : 'text-slate-500 dark:text-slate-400'"
        >
          {{ tab.label }}
          <div 
            v-if="activeTab === tab.id" 
            class="absolute bottom-0 left-0 right-0 h-0.5 bg-orange-500 rounded-t-full"
          ></div>
        </button>
      </div>
    </div>

    <!-- Order List -->
    <div v-if="!showPhoneInput" class="p-3 space-y-3">
      <div v-if="loading" class="space-y-3">
        <div v-for="i in 5" :key="i" class="bg-white dark:bg-slate-800 p-4 rounded-2xl animate-pulse h-24"></div>
      </div>

      <div v-else-if="filteredOrders.length === 0" class="text-center py-12">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
          </svg>
        </div>
        <p class="text-slate-500 dark:text-slate-400 font-medium">Belum ada pesanan</p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Pesanan Anda akan muncul di sini</p>
      </div>

      <div 
        v-else 
        v-for="order in filteredOrders" 
        :key="order.id" 
        class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between group active:scale-[0.98] transition-all cursor-pointer"
        @click="router.push(`/customer/order/${order.id}`)"
      >
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-orange-600 shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
          </div>
          <div>
            <div class="flex items-center gap-2 mb-1">
              <p class="text-sm font-bold text-slate-800">#{{ order.invoice_number }}</p>
              <span class="px-2 py-0.5 rounded-md text-[8px] font-bold border uppercase tracking-wider" :class="getStatusColor(order.status)">
                {{ getStatusLabel(order.status) }}
              </span>
            </div>
            <p class="text-[10px] text-slate-400 font-medium">{{ formatDate(order.created_at) }} • {{ order.items.length }} Item</p>
          </div>
        </div>

        <div class="text-right">
          <p class="text-sm font-black text-slate-800">{{ formatCurrency(order.total) }}</p>
          <div class="flex items-center justify-end gap-1 mt-1 text-orange-600">
            <span class="text-[9px] font-bold uppercase tracking-widest">Detail</span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>