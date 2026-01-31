<script setup>
import { ref, onMounted } from 'vue'
import { reportApi, inventoryApi, productApi } from '../api'

const activeTab = ref('daily')
const loading = ref(false)
const dailyData = ref(null)
const productSales = ref([])
const packageSales = ref([])
const productStock = ref([])
const inventoryStock = ref([])

// Stock History
const historySubTab = ref('inventory') // 'inventory' or 'product'
const stockHistory = ref([])
const stockHistoryPagination = ref(null)
const inventoryItems = ref([])
const products = ref([])
const historyFilters = ref({
  from_date: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 7 days ago
  to_date: new Date().toISOString().split('T')[0],
  type: '',
  inventory_item_id: '',
  product_id: '',
})

const selectedDate = ref(new Date().toISOString().split('T')[0])

onMounted(() => {
  loadDailySales()
  loadInventoryItems()
  loadProducts()
})

async function loadInventoryItems() {
  try {
    const response = await inventoryApi.getAll()
    inventoryItems.value = response.data
  } catch (error) {
    console.error(error)
  }
}

async function loadProducts() {
  try {
    const response = await productApi.getAll()
    products.value = response.data
  } catch (error) {
    console.error(error)
  }
}

async function loadDailySales() {
  loading.value = true
  try {
    const response = await reportApi.dailySales({ date: selectedDate.value })
    dailyData.value = response.data
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

async function loadProductSales() {
  loading.value = true
  try {
    const response = await reportApi.salesByProduct()
    productSales.value = response.data.data
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

async function loadPackageSales() {
  loading.value = true
  try {
    const response = await reportApi.salesByPackage()
    packageSales.value = response.data.data
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

async function loadStockReport() {
  loading.value = true
  try {
    const [productRes, inventoryRes] = await Promise.all([
      reportApi.productStock(),
      reportApi.inventoryStock(),
    ])
    productStock.value = productRes.data
    inventoryStock.value = inventoryRes.data
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

async function loadStockHistory(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (historyFilters.value.from_date) params.from_date = historyFilters.value.from_date
    if (historyFilters.value.to_date) params.to_date = historyFilters.value.to_date
    if (historyFilters.value.type) params.type = historyFilters.value.type
    
    let response
    if (historySubTab.value === 'inventory') {
      if (historyFilters.value.inventory_item_id) params.inventory_item_id = historyFilters.value.inventory_item_id
      response = await reportApi.inventoryHistory(params)
    } else {
      if (historyFilters.value.product_id) params.product_id = historyFilters.value.product_id
      response = await reportApi.productHistory(params)
    }
    
    stockHistory.value = response.data.data
    stockHistoryPagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      total: response.data.total,
    }
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

function switchHistorySubTab(tab) {
  historySubTab.value = tab
  stockHistory.value = []
  stockHistoryPagination.value = null
  loadStockHistory()
}

function switchTab(tab) {
  activeTab.value = tab
  if (tab === 'daily') loadDailySales()
  if (tab === 'product') loadProductSales()
  if (tab === 'package') loadPackageSales()
  if (tab === 'stock') loadStockReport()
  if (tab === 'history') loadStockHistory()
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}

function formatDateTime(dateStr) {
  return new Date(dateStr).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getTypeLabel(type) {
  const labels = { in: 'Masuk', out: 'Keluar', adjustment: 'Penyesuaian' }
  return labels[type] || type
}

function getTypeColor(type) {
  const colors = {
    in: 'bg-green-100 text-green-700 border-green-200',
    out: 'bg-red-100 text-red-700 border-red-200',
    adjustment: 'bg-yellow-100 text-yellow-700 border-yellow-200',
  }
  return colors[type] || 'bg-gray-100 text-gray-700 border-gray-200'
}

function getQuantityDisplay(item) {
  if (item.type === 'in') return `+${formatPrice(item.quantity)}`
  if (item.type === 'out') return `-${formatPrice(item.quantity)}`
  return formatPrice(item.quantity)
}
</script>

<template>
  <div class="h-full overflow-y-auto bg-slate-50/50 custom-scrollbar pb-6">
    <!-- Simple & Cool Header -->
    <div class="sticky top-0 z-20 bg-slate-50/80 backdrop-blur-md px-4 md:px-6 pt-2.5 pb-1.5 border-b border-slate-100/50 shrink-0">
      <div class="flex flex-col md:flex-row justify-between md:items-center gap-3">
        <div>
          <h1 class="text-base font-medium text-slate-800 leading-none">Laporan & Analitik</h1>
          <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wide mt-1">Pantau performa bisnis & stok</p>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="px-1 md:px-6">
      <div class="space-y-6">

      <!-- Compact Tabs -->
      <div class="bg-slate-100 p-1 rounded-2xl flex gap-1 overflow-x-auto no-scrollbar shrink-0 max-w-xl">
        <button
          v-for="tab in [
            { id: 'daily', label: 'Harian', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
            { id: 'product', label: 'Produk', icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z' },
            { id: 'package', label: 'Paket', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
            { id: 'stock', label: 'Stok', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
            { id: 'history', label: 'Riwayat', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' }
          ]"
          :key="tab.id"
          @click="switchTab(tab.id)"
          class="flex-1 min-w-[70px] px-3 py-1.5 rounded-xl text-[10px] font-medium transition-all flex items-center justify-center gap-1.5 whitespace-nowrap uppercase tracking-wider"
          :class="activeTab === tab.id ? 'bg-white text-primary-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="tab.icon" />
          </svg>
          {{ tab.label }}
        </button>
      </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Memuat data...
    </div>

    <!-- Daily Report -->
    <div v-else-if="activeTab === 'daily'" class="space-y-4 animate-fade-in-up">
      <div class="flex items-center gap-3 bg-white/90 backdrop-blur px-3 py-2 rounded-2xl shadow-sm border border-slate-100 w-full md:w-fit">
        <label class="text-[9px] uppercase tracking-widest text-slate-400 font-medium">Pilih Tanggal</label>
        <input
          v-model="selectedDate"
          type="date"
          class="bg-slate-50 border-none rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 focus:ring-4 focus:ring-primary-500/5 outline-none w-full md:w-auto"
          @change="loadDailySales"
        />
      </div>

      <div v-if="dailyData" class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-white rounded-2xl p-3 md:p-4 shadow-sm border border-slate-100">
          <div class="flex items-center gap-3 mb-2.5">
            <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <span class="text-[9px] uppercase tracking-widest text-slate-400 font-medium">Total Transaksi</span>
          </div>
          <p class="text-3xl font-medium text-slate-800 tracking-tight">{{ dailyData.total_transactions }}</p>
        </div>
        
        <div class="bg-white rounded-2xl p-3 md:p-4 shadow-sm border border-slate-100">
          <div class="flex items-center gap-3 mb-2.5">
            <div class="w-9 h-9 rounded-xl bg-primary-50 flex items-center justify-center text-primary-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <span class="text-[9px] uppercase tracking-widest text-slate-400 font-medium">Pendapatan Bersih</span>
          </div>
          <p class="text-xl font-medium text-slate-800 tracking-tight">Rp {{ formatPrice(dailyData.total_revenue) }}</p>
        </div>

        <div class="bg-white rounded-2xl p-3 md:p-4 shadow-sm border border-slate-100">
          <div class="flex items-center gap-3 mb-2.5">
            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-red-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <span class="text-[9px] uppercase tracking-widest text-slate-400 font-medium">Total Potongan</span>
          </div>
          <p class="text-xl font-medium text-red-500 tracking-tight">Rp {{ formatPrice(dailyData.total_discount) }}</p>
        </div>
      </div>
    </div>

    <div v-else-if="activeTab === 'product'" class="bg-white rounded-xl md:rounded-2xl overflow-hidden shadow-sm border border-slate-100/80 mx-1 md:mx-0 animate-fade-in-up">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-slate-50/50">
              <th class="px-4 py-3 text-left text-[10px] font-medium text-slate-400 uppercase tracking-widest">Produk</th>
              <th class="px-4 py-3 text-right text-[10px] font-medium text-slate-400 uppercase tracking-widest">Qty</th>
              <th class="px-4 py-3 text-right text-[10px] font-medium text-slate-400 uppercase tracking-widest">Pendapatan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in productSales" :key="item.product_id" class="hover:bg-slate-50/50 transition-colors">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center font-medium text-[10px] shrink-0 border border-orange-100/50 uppercase tracking-tighter">
                    {{ item.product?.name.charAt(0) }}
                  </div>
                  <p class="font-medium text-slate-700 text-sm">{{ item.product?.name }}</p>
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <span class="font-medium text-slate-600 text-sm">{{ item.total_qty }}</span>
                <span class="text-[10px] text-slate-400 ml-1">pcs</span>
              </td>
              <td class="px-4 py-3 text-right">
                <p class="font-medium text-slate-800 text-sm">Rp {{ formatPrice(item.total_revenue) }}</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else-if="activeTab === 'package'" class="bg-white rounded-xl md:rounded-2xl overflow-hidden shadow-sm border border-slate-100/80 mx-1 md:mx-0 animate-fade-in-up">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-slate-50/50">
              <th class="px-4 py-3 text-left text-[10px] font-medium text-slate-400 uppercase tracking-widest">Nama Paket</th>
              <th class="px-4 py-3 text-left text-[10px] font-medium text-slate-400 uppercase tracking-widest">Kombinasi</th>
              <th class="px-4 py-3 text-right text-[10px] font-medium text-slate-400 uppercase tracking-widest">Qty</th>
              <th class="px-4 py-3 text-right text-[10px] font-medium text-slate-400 uppercase tracking-widest">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in packageSales" :key="item.package_combination_id" class="hover:bg-slate-50/50 transition-colors">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-500 flex items-center justify-center font-medium text-[10px] shrink-0 border border-primary-100/50 uppercase tracking-tighter">
                    P
                  </div>
                  <p class="font-medium text-slate-700 text-sm leading-none">{{ item.package?.name }}</p>
                </div>
              </td>
              <td class="px-4 py-3">
                <p class="text-[11px] text-slate-400 font-medium">{{ item.package_combination?.name }}</p>
              </td>
              <td class="px-4 py-3 text-right">
                <span class="font-medium text-slate-600 text-sm">{{ item.total_qty }}</span>
              </td>
              <td class="px-4 py-3 text-right">
                <p class="font-medium text-slate-800 text-sm">Rp {{ formatPrice(item.total_revenue) }}</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Stock Report -->
    <div v-else-if="activeTab === 'stock'" class="space-y-4 animate-fade-in-up">
      <div class="bg-white rounded-xl md:rounded-2xl overflow-hidden shadow-sm border border-slate-100/80 mx-1 md:mx-0 uppercase tracking-tighter">
        <div class="px-4 py-3 border-b border-slate-50 flex items-center justify-between">
          <h3 class="font-medium text-slate-800 text-sm">Stok Produk Jadi</h3>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[9px] font-medium tracking-widest">
            {{ productStock.length }} Produk
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-slate-50/50">
                <th class="px-4 py-3 text-left text-[10px] font-medium text-slate-400 uppercase tracking-widest">Nama Produk</th>
                <th class="px-4 py-3 text-right text-[10px] font-medium text-slate-400 uppercase tracking-widest">Stok</th>
                <th class="px-4 py-3 text-right text-[10px] font-medium text-slate-400 uppercase tracking-widest">Min. Stok</th>
                <th class="px-4 py-3 text-center text-[10px] font-medium text-slate-400 uppercase tracking-widest">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in productStock" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3">
                  <p class="font-medium text-slate-700 text-sm">{{ item.name }}</p>
                </td>
                <td class="px-4 py-3 text-right">
                  <span class="font-medium text-slate-600 text-sm">{{ item.stock }}</span>
                </td>
                <td class="px-4 py-3 text-right text-slate-400 text-xs">{{ item.min_stock }}</td>
                <td class="px-4 py-3 text-center">
                  <span
                    :class="[
                      'inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-medium uppercase tracking-wider',
                      item.is_low_stock ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'
                    ]"
                  >
                    {{ item.is_low_stock ? 'Rendah' : 'Aman' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white rounded-xl md:rounded-2xl overflow-hidden shadow-sm border border-slate-100/80 mx-1 md:mx-0">
        <div class="px-4 py-3 border-b border-slate-50 flex items-center justify-between">
          <h3 class="font-medium text-slate-800 text-sm">Stok Bahan Baku</h3>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-primary-50 text-primary-600 text-[9px] font-medium uppercase tracking-widest">
            {{ inventoryStock.length }} Item
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-slate-50/50">
                <th class="px-4 py-3 text-left text-[10px] font-medium text-slate-400 uppercase tracking-widest">Nama Item</th>
                <th class="px-4 py-3 text-left text-[10px] font-medium text-slate-400 uppercase tracking-widest">Kategori</th>
                <th class="px-4 py-3 text-right text-[10px] font-medium text-slate-400 uppercase tracking-widest">Stok</th>
                <th class="px-4 py-3 text-center text-[10px] font-medium text-slate-400 uppercase tracking-widest">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in inventoryStock" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3 font-medium text-slate-700 text-sm">{{ item.name }}</td>
                <td class="px-4 py-3">
                   <p class="text-[10px] text-slate-400 uppercase font-medium tracking-wide">{{ item.type === 'raw_material' ? 'Bahan Baku' : 'Packaging' }}</p>
                </td>
                <td class="px-4 py-3 text-right">
                  <span class="font-medium text-slate-600 text-sm">{{ formatPrice(item.stock) }}</span>
                  <span class="text-[10px] text-slate-400 ml-1 uppercase font-medium">{{ item.unit }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    :class="[
                      'inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-medium uppercase tracking-wider',
                      item.is_low_stock ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'
                    ]"
                  >
                    {{ item.is_low_stock ? 'Rendah' : 'Aman' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Stock History -->
    <div v-else-if="activeTab === 'history'" class="space-y-4 animate-fade-in-up">
      <!-- SubTab Selector -->
      <div class="bg-slate-100 p-1 rounded-2xl flex gap-1 w-fit mx-auto md:mx-0">
        <button
          @click="switchHistorySubTab('inventory')"
          class="px-3.5 py-1.5 rounded-xl text-[9px] font-medium transition-all flex items-center gap-2 uppercase tracking-wider"
          :class="historySubTab === 'inventory' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
        >
          <svg viewBox="0 0 24 24" fill="none" class="w-3 h-3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
          Inventory
        </button>
        <button
          @click="switchHistorySubTab('product')"
          class="px-3.5 py-1.5 rounded-xl text-[9px] font-medium transition-all flex items-center gap-2 uppercase tracking-wider"
          :class="historySubTab === 'product' ? 'bg-white text-orange-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
        >
          <svg viewBox="0 0 24 24" fill="none" class="w-3 h-3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
          Produk
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-2xl p-3 shadow-sm border border-slate-100">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
          <div class="space-y-1">
            <label class="text-[8px] font-medium text-slate-400 uppercase tracking-widest ml-1">Dari Tanggal</label>
            <input
              v-model="historyFilters.from_date"
              type="date"
              class="w-full bg-slate-50 border-none rounded-xl px-3 py-1.5 text-[10px] font-medium text-slate-600 focus:ring-4 focus:ring-primary-500/5 outline-none"
              @change="loadStockHistory()"
            />
          </div>
          <div class="space-y-1">
            <label class="text-[8px] font-medium text-slate-400 uppercase tracking-widest ml-1">Sampai</label>
            <input
              v-model="historyFilters.to_date"
              type="date"
              class="w-full bg-slate-50 border-none rounded-xl px-3 py-1.5 text-[10px] font-medium text-slate-600 focus:ring-4 focus:ring-primary-500/5 outline-none"
              @change="loadStockHistory()"
            />
          </div>
          <div class="space-y-1">
            <label class="text-[8px] font-medium text-slate-400 uppercase tracking-widest ml-1">Tipe Mutasi</label>
            <select
              v-model="historyFilters.type"
              class="w-full bg-slate-50 border-none rounded-xl px-3 py-1.5 text-[10px] font-medium text-slate-600 focus:ring-4 focus:ring-primary-500/5 outline-none"
              @change="loadStockHistory()"
            >
              <option value="">Semua Tipe</option>
              <option value="in">Masuk</option>
              <option value="out">Keluar</option>
              <option value="adjustment">Penyesuaian</option>
            </select>
          </div>
          <div v-if="historySubTab === 'inventory'" class="space-y-1">
            <label class="text-[8px] font-medium text-slate-400 uppercase tracking-widest ml-1">Pilih Item</label>
            <select
              v-model="historyFilters.inventory_item_id"
              class="w-full bg-slate-50 border-none rounded-xl px-3 py-1.5 text-[10px] font-medium text-slate-600 focus:ring-4 focus:ring-primary-500/5 outline-none"
              @change="loadStockHistory()"
            >
              <option value="">Semua Item</option>
              <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                {{ item.name }}
              </option>
            </select>
          </div>
          <div v-else class="space-y-1">
            <label class="text-[8px] font-medium text-slate-400 uppercase tracking-widest ml-1">Pilih Produk</label>
            <select
              v-model="historyFilters.product_id"
              class="w-full bg-slate-50 border-none rounded-xl px-3 py-1.5 text-[10px] font-medium text-slate-600 focus:ring-4 focus:ring-primary-500/5 outline-none"
              @change="loadStockHistory()"
            >
              <option value="">Semua Produk</option>
              <option v-for="item in products" :key="item.id" :value="item.id">
                {{ item.name }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl md:rounded-2xl overflow-hidden shadow-sm border border-slate-100/80 mx-1 md:mx-0">
        <div class="md:hidden divide-y divide-slate-100">
          <div v-for="item in stockHistory" :key="item.id" class="p-3">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-[10px] text-slate-400 font-medium">{{ formatDateTime(item.created_at) }}</p>
                <p class="text-[12px] font-medium text-slate-800 leading-snug mt-1 truncate">
                  {{ historySubTab === 'inventory' ? item.inventory_item?.name : item.product?.name }}
                </p>
                <p class="text-[9px] text-slate-400 truncate mt-0.5 italic">{{ item.notes || 'Tanpa catatan' }}</p>
              </div>
              <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-medium uppercase tracking-wider shrink-0', getTypeColor(item.type)]">
                {{ getTypeLabel(item.type) }}
              </span>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2">
              <div class="bg-slate-50 rounded-lg px-2 py-1.5">
                <p class="text-[8px] text-slate-400 uppercase tracking-widest">Mutasi</p>
                <p :class="['text-[11px] font-medium mt-0.5', item.type === 'in' ? 'text-emerald-600' : item.type === 'out' ? 'text-red-500' : 'text-amber-600']">
                  {{ getQuantityDisplay(item) }}
                </p>
              </div>
              <div class="bg-slate-50 rounded-lg px-2 py-1.5">
                <p class="text-[8px] text-slate-400 uppercase tracking-widest">Setelah</p>
                <p class="text-[11px] font-medium mt-0.5 text-slate-700">{{ formatPrice(item.stock_after) }}</p>
              </div>
            </div>
            <div class="mt-2 flex items-center justify-between gap-2 text-[9px] text-slate-400">
              <div class="truncate">
                {{ item.transaction?.customer ? item.transaction.customer.name : 'GUEST' }}
              </div>
              <div class="truncate">
                {{ item.user?.name || '-' }}
              </div>
            </div>
          </div>
          <div v-if="stockHistory.length === 0" class="px-4 py-10 text-center text-slate-300 italic text-[11px] uppercase tracking-widest">
            Tidak ada data riwayat stok
          </div>
        </div>
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-slate-50/50">
                <th class="px-4 py-3 text-left text-[10px] font-medium text-slate-400 uppercase tracking-widest">Waktu</th>
                <th class="px-4 py-3 text-left text-[10px] font-medium text-slate-400 uppercase tracking-widest">Item</th>
                <th class="px-4 py-3 text-center text-[10px] font-medium text-slate-400 uppercase tracking-widest">Tipe</th>
                <th class="px-4 py-3 text-right text-[10px] font-medium text-slate-400 uppercase tracking-widest">Mutasi</th>
                <th class="px-4 py-3 text-right text-[10px] font-medium text-slate-400 uppercase tracking-widest">Setelah</th>
                <th class="px-4 py-3 text-left text-[10px] font-medium text-slate-400 uppercase tracking-widest">Customer</th>
                <th class="px-4 py-3 text-left text-[10px] font-medium text-slate-400 uppercase tracking-widest">PIC</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in stockHistory" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3">
                  <p class="text-[10px] text-slate-500 font-medium leading-tight whitespace-nowrap">{{ formatDateTime(item.created_at) }}</p>
                </td>
                <td class="px-4 py-3">
                   <p class="font-medium text-slate-700 text-sm leading-none">{{ historySubTab === 'inventory' ? item.inventory_item?.name : item.product?.name }}</p>
                   <p class="text-[10px] text-slate-400 truncate max-w-[120px] mt-1 italic">{{ item.notes || 'Tanpa catatan' }}</p>
                </td>
                <td class="px-4 py-3 text-center">
                  <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-medium uppercase tracking-wider', getTypeColor(item.type)]">
                    {{ getTypeLabel(item.type) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <p :class="['font-medium text-sm', item.type === 'in' ? 'text-emerald-600' : item.type === 'out' ? 'text-red-500' : 'text-amber-600']">
                    {{ getQuantityDisplay(item) }}
                  </p>
                  <p class="text-[10px] text-slate-400 font-medium">Awal: {{ formatPrice(item.stock_before) }}</p>
                </td>
                <td class="px-4 py-3 text-right">
                  <p class="font-medium text-slate-800 text-sm tracking-tight">{{ formatPrice(item.stock_after) }}</p>
                </td>
                <td class="px-4 py-3">
                  <div v-if="item.transaction?.customer" class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-blue-50 flex items-center justify-center text-[9px] font-medium text-blue-600 border border-blue-100/50 uppercase">
                      {{ item.transaction.customer.name.charAt(0) }}
                    </div>
                    <div>
                      <p class="text-[11px] font-medium text-slate-700 leading-none">{{ item.transaction.customer.name.split(' ')[0] }}</p>
                      <p class="text-[9px] text-slate-400 font-medium mt-0.5 uppercase tracking-tighter">{{ item.transaction.customer.customerType?.name.replace('Pelanggan ', '') || 'Reguler' }}</p>
                    </div>
                  </div>
                  <div v-else class="text-left">
                    <span class="text-[10px] text-slate-300 font-medium italic">GUEST</span>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center text-[9px] font-medium text-slate-500 border border-slate-100 uppercase">
                      {{ item.user?.name.charAt(0) }}
                    </div>
                    <div>
                      <p class="text-[11px] font-medium text-slate-700 leading-none">{{ item.user?.name.split(' ')[0] }}</p>
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-if="stockHistory.length === 0">
                <td colspan="7" class="px-4 py-16 text-center text-slate-300 italic text-[11px] uppercase tracking-widest">
                  Tidak ada data riwayat stok
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="stockHistoryPagination && stockHistoryPagination.last_page > 1" class="flex flex-col md:flex-row items-center justify-between gap-4 pb-10">
        <p class="text-[11px] font-medium text-slate-400">
          Tampil {{ stockHistory.length }} Dari {{ stockHistoryPagination.total }} Data
        </p>
        <div class="flex p-0.5 bg-slate-100 rounded-xl border border-slate-200/50">
          <button
            @click="loadStockHistory(stockHistoryPagination.current_page - 1)"
            :disabled="stockHistoryPagination.current_page === 1"
            class="px-3 py-1.5 rounded-lg bg-white text-slate-500 font-medium text-[11px] shadow-sm disabled:opacity-30 disabled:shadow-none transition-all hover:bg-slate-50 flex items-center gap-1.5 border border-slate-100"
          >
            <svg viewBox="0 0 24 24" fill="none" class="w-3 h-3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Sblmnya
          </button>
          <div class="px-4 flex items-center justify-center min-w-[60px]">
            <span class="text-[11px] font-medium text-slate-600">
              {{ stockHistoryPagination.current_page }} <span class="text-slate-300 mx-1">/</span> {{ stockHistoryPagination.last_page }}
            </span>
          </div>
          <button
            @click="loadStockHistory(stockHistoryPagination.current_page + 1)"
            :disabled="stockHistoryPagination.current_page === stockHistoryPagination.last_page"
            class="px-3 py-1.5 rounded-lg bg-white text-slate-500 font-medium text-[11px] shadow-sm disabled:opacity-30 disabled:shadow-none transition-all hover:bg-slate-50 flex items-center gap-1.5 border border-slate-100"
          >
            Lanjut
            <svg viewBox="0 0 24 24" fill="none" class="w-3 h-3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
          </button>
        </div>
      </div>
    </div>
    </div>
  </div>
</div>
</template>
