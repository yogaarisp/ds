<script setup>
import { ref, onMounted, computed } from 'vue'
import { reportApi, transactionApi, inventoryApi } from '../api'
import Skeleton from '../components/Skeleton.vue'

const loading = ref(true)
const todaySummary = ref({ total_transactions: 0, total_revenue: 0 })
const lowStockProducts = ref([])
const lowStockInventory = ref([])
const recentTransactions = ref([])
const upcomingTransactions = ref([])
const monthlySummary = ref({ total_revenue: 0, total_transactions: 0 })

onMounted(() => {
  // Delay to ensure DOM is ready
  setTimeout(() => {
    loadDashboardData()
  }, 500)
})

function loadDashboardData() {
  loading.value = true
  
  // Load data sequentially with proper error handling
  Promise.allSettled([
    loadTodaySummary(),
    loadMonthlySummary(),
    loadLowStock(),
    loadRecentTransactions(),
    loadUpcomingTransactions()
  ]).finally(() => {
    loading.value = false
  })
}

async function loadTodaySummary() {
  try {
    const response = await transactionApi.todaySummary()
    if (response?.data) {
      todaySummary.value = {
        total_transactions: Number(response.data.total_transactions) || 0,
        total_revenue: Number(response.data.total_revenue) || 0
      }
    }
  } catch (error) {
    console.warn('Today summary failed:', error.message)
    todaySummary.value = { total_transactions: 0, total_revenue: 0 }
  }
}

async function loadMonthlySummary() {
  try {
    const response = await reportApi.monthlySummary()
    if (response?.data) {
      monthlySummary.value = {
        total_revenue: Number(response.data.total_revenue) || 0,
        total_transactions: Number(response.data.total_transactions) || 0
      }
    }
  } catch (error) {
    console.warn('Monthly summary failed:', error.message)
    monthlySummary.value = { total_revenue: 0, total_transactions: 0 }
  }
}

async function loadLowStock() {
  try {
    const response = await inventoryApi.lowStockWarning()
    if (response?.data) {
      lowStockProducts.value = Array.isArray(response.data.products) ? response.data.products : []
      lowStockInventory.value = Array.isArray(response.data.inventory) ? response.data.inventory : []
    }
  } catch (error) {
    console.warn('Low stock failed:', error.message)
    lowStockProducts.value = []
    lowStockInventory.value = []
  }
}

async function loadRecentTransactions() {
  try {
    const response = await transactionApi.getAll({ limit: 5, status: 'completed' })
    if (response?.data) {
      const data = response.data
      recentTransactions.value = Array.isArray(data) ? data.slice(0, 5) : (Array.isArray(data.data) ? data.data.slice(0, 5) : [])
    }
  } catch (error) {
    console.warn('Recent transactions failed:', error.message)
    recentTransactions.value = []
  }
}

async function loadUpcomingTransactions() {
  try {
    const response = await transactionApi.getAll({ upcoming: true, limit: 5 })
    if (response?.data) {
      const data = response.data
      upcomingTransactions.value = Array.isArray(data) ? data.slice(0, 5) : (Array.isArray(data.data) ? data.data.slice(0, 5) : [])
    }
  } catch (error) {
    console.warn('Upcoming transactions failed:', error.message)
    upcomingTransactions.value = []
  }
}

const totalLowStock = computed(() => {
  return lowStockProducts.value.length + lowStockInventory.value.length
})

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price || 0)
}

function formatDateTime(dateStr) {
  return new Date(dateStr).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getGreeting() {
  const hour = new Date().getHours()
  if (hour < 12) return 'Selamat Pagi'
  if (hour < 15) return 'Selamat Siang'
  if (hour < 18) return 'Selamat Sore'
  return 'Selamat Malam'
}

function formatSchedule(tx) {
  if (tx.scheduled_date) {
    const date = new Date(tx.scheduled_date)
    if (tx.scheduled_time) {
      const [hours, minutes] = tx.scheduled_time.split(':')
      date.setHours(parseInt(hours), parseInt(minutes))
    }
    return date.toLocaleString('id-ID', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    })
  }
  return formatDateTime(tx.created_at)
}

function isOverdue(dateStr) {
  if (!dateStr) return false
  const date = new Date(dateStr)
  const today = new Date()
  date.setHours(0, 0, 0, 0)
  today.setHours(0, 0, 0, 0)
  return date < today
}
</script>

<template>
  <div class="pt-2 pb-4 px-3 lg:p-4 bg-gray-50/50 min-h-screen pb-20 lg:pb-6">
    <!-- Header -->
    <div class="mb-3 lg:mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">{{ getGreeting() }}! 👋</h1>
      <p class="text-gray-500 text-sm mt-0.5">Berikut ringkasan toko hari ini</p>
    </div>

    <!-- Skeleton Loading State -->
    <div v-if="loading" class="space-y-3">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        <div v-for="i in 4" :key="i" class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
          <div class="flex items-center gap-3 mb-3">
            <Skeleton width="40px" height="40px" customClass="rounded-xl" />
            <Skeleton width="60px" height="12px" />
          </div>
          <Skeleton width="80%" height="32px" customClass="mb-2" />
          <Skeleton width="50%" height="12px" />
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div v-for="i in 2" :key="i" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-4">
          <div class="flex justify-between">
            <Skeleton width="120px" height="20px" />
            <Skeleton width="80px" height="16px" />
          </div>
          <div v-for="j in 5" :key="j" class="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
            <div class="space-y-2 flex-1">
              <Skeleton width="180px" height="16px" />
              <Skeleton width="120px" height="12px" />
            </div>
            <div class="space-y-2 text-right">
              <Skeleton width="80px" height="16px" customClass="ml-auto" />
              <Skeleton width="60px" height="12px" customClass="ml-auto" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="space-y-3">
      <!-- Quick Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        <!-- Today's Transactions -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
            </div>
            <span class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Hari Ini</span>
          </div>
          <p class="text-3xl font-bold text-gray-900">{{ todaySummary.total_transactions || 0 }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Total Transaksi</p>
        </div>

        <!-- Today's Revenue -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <span class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Hari Ini</span>
          </div>
          <p class="text-xl font-bold text-gray-900">Rp {{ formatPrice(todaySummary.total_revenue || 0) }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Pendapatan</p>
        </div>

        <!-- Monthly Revenue -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
            </div>
            <span class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Bulan Ini</span>
          </div>
          <p class="text-xl font-bold text-gray-900">Rp {{ formatPrice(monthlySummary.total_revenue || 0) }}</p>
          <p class="text-xs text-gray-500 mt-0.5">{{ monthlySummary.total_transactions || 0 }} transaksi</p>
        </div>

        <!-- Low Stock Warning -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="totalLowStock > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'">
              <svg v-if="totalLowStock > 0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12" y1="17" y2="17.01"/></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <span class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Stok</span>
          </div>
          <p class="text-3xl font-bold" :class="totalLowStock > 0 ? 'text-red-600' : 'text-gray-900'">{{ totalLowStock }}</p>
          <p class="text-xs text-gray-500 mt-0.5">{{ totalLowStock > 0 ? 'Stok Rendah' : 'Stok Aman' }}</p>
        </div>
      </div>

      <!-- Two Column Layout -->
      <!-- Transactions Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <!-- Recent Transactions -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-full flex flex-col">
          <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-medium text-gray-900 text-sm">Transaksi Terbaru</h3>
            <router-link to="/transactions?tab=all" class="text-xs text-primary-600 hover:underline">
              Lihat semua →
            </router-link>
          </div>
          <div class="divide-y divide-gray-50 flex-1">
            <div v-for="(tx, index) in recentTransactions" :key="`recent-${tx.id || index}`" class="px-4 py-3 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
              <div>
                <p class="font-medium text-gray-900 text-sm">{{ tx.invoice_number || 'N/A' }} - {{ (tx.customer && tx.customer.name) || 'Guest' }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ formatDateTime(tx.created_at) }}</p>
              </div>
              <div class="text-right">
                <p class="font-semibold text-green-600 text-sm">Rp {{ formatPrice(tx.total || 0) }}</p>
                <span 
                  class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-50 text-green-600 text-[10px] font-medium"
                >
                  {{ tx.status || 'completed' }}
                </span>
              </div>
            </div>
            <div v-if="recentTransactions.length === 0" class="px-4 py-8 text-center text-gray-400 text-sm">
              Belum ada transaksi hari ini
            </div>
          </div>
        </div>

        <!-- Upcoming / PO Transactions -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-full flex flex-col">
          <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-medium text-gray-900 text-sm">Pesanan PO (Upcoming)</h3>
            <router-link to="/transactions?tab=upcoming" class="text-xs text-primary-600 hover:underline">
              Lihat semua →
            </router-link>
          </div>
          <div class="divide-y divide-gray-50 flex-1">
            <div v-for="(tx, index) in upcomingTransactions" :key="`upcoming-${tx.id || index}`" class="px-4 py-3 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
              <div>
                <p class="font-medium text-gray-900 text-sm">{{ tx.invoice_number || 'N/A' }} - {{ (tx.customer && tx.customer.name) || 'Guest' }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Jadwal: {{ formatSchedule(tx) }}</p>
              </div>
              <div class="text-right">
                <p class="font-semibold text-gray-900 text-sm">Rp {{ formatPrice(tx.total || 0) }}</p>
                <span 
                  class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                  :class="isOverdue(tx.scheduled_date) ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600'"
                >
                  {{ isOverdue(tx.scheduled_date) ? 'Overdue' : 'Scheduled' }}
                </span>
              </div>
            </div>
            <div v-if="upcomingTransactions.length === 0" class="px-4 py-8 text-center text-gray-400 text-sm">
              Tidak ada pesanan PO
            </div>
          </div>
        </div>
      </div>

      <!-- Low Stock Items Row -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-medium text-gray-900 text-sm">Stok Rendah</h3>
          <router-link to="/inventory" class="text-xs text-primary-600 hover:underline">
            Kelola stok →
          </router-link>
        </div>
        <div class="lg:max-h-72 lg:overflow-y-auto">
          <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-50">
            <!-- Left Side Products -->
            <div class="divide-y divide-gray-50">
              <div v-for="(item, index) in lowStockProducts" :key="`product-${item.id || index}`" class="px-4 py-3 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center text-orange-500">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 text-sm">{{ item.name }}</p>
                    <p class="text-[10px] text-gray-400">Produk</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-bold text-red-600 text-sm">{{ item.stock }}</p>
                  <p class="text-[10px] text-gray-400">min: {{ item.min_stock }}</p>
                </div>
              </div>
            </div>
            <!-- Right Side Inventory -->
            <div class="divide-y divide-gray-50">
              <div v-for="(item, index) in lowStockInventory" :key="`inventory-${item.id || index}`" class="px-4 py-3 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="item.type === 'raw_material' ? 'bg-yellow-50 text-yellow-600' : 'bg-blue-50 text-blue-600'">
                    <svg v-if="item.type === 'raw_material'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 21v-3.5A3.5 3.5 0 0 0 17.5 14h-7A3.5 3.5 0 0 0 7 17.5V21"/><circle cx="14" cy="7" r="4"/></svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 text-sm">{{ item.name }}</p>
                    <p class="text-[10px] text-gray-400">{{ item.type === 'raw_material' ? 'Bahan Baku' : 'Packaging' }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-bold text-red-600 text-sm">{{ formatPrice(item.stock) }} {{ item.unit }}</p>
                  <p class="text-[10px] text-gray-400">min: {{ formatPrice(item.min_stock) }}</p>
                </div>
              </div>
            </div>
          </div>
          <div v-if="totalLowStock === 0" class="px-4 py-8 text-center text-gray-400">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-emerald-500 mx-auto mb-2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            <p class="text-sm">Semua stok aman</p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <h3 class="font-medium text-gray-900 text-sm mb-3">Aksi Cepat</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
          <router-link to="/pos" class="flex flex-col items-center p-3 bg-gray-50 rounded-xl hover:bg-primary-50 transition-colors group">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 mb-1 text-gray-400 group-hover:text-primary-600 group-hover:scale-110 transition-all"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
            <span class="text-xs text-gray-600 group-hover:text-primary-600">Transaksi</span>
          </router-link>
          <router-link to="/products" class="flex flex-col items-center p-3 bg-gray-50 rounded-xl hover:bg-primary-50 transition-colors group">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 mb-1 text-gray-400 group-hover:text-primary-600 group-hover:scale-110 transition-all"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
            <span class="text-xs text-gray-600 group-hover:text-primary-600">Produk</span>
          </router-link>
          <router-link to="/inventory" class="flex flex-col items-center p-3 bg-gray-50 rounded-xl hover:bg-primary-50 transition-colors group">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 mb-1 text-gray-400 group-hover:text-primary-600 group-hover:scale-110 transition-all"><path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M17 18h1"/><path d="M12 18h1"/><path d="M7 18h1"/></svg>
            <span class="text-xs text-gray-600 group-hover:text-primary-600">Stok</span>
          </router-link>
          <router-link to="/reports" class="flex flex-col items-center p-3 bg-gray-50 rounded-xl hover:bg-primary-50 transition-colors group">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 mb-1 text-gray-400 group-hover:text-primary-600 group-hover:scale-110 transition-all"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
            <span class="text-xs text-gray-600 group-hover:text-primary-600">Laporan</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>
