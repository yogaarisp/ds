<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { STORAGE_BASE_URL } from '@/api'
import LazyImage from '@/components/LazyImage.vue'

const route = useRoute()
const router = useRouter()
const order = ref(null)
const loading = ref(true)

onMounted(() => {
  fetchOrderDetail()
})

async function fetchOrderDetail() {
  loading.value = true
  try {
    const response = await api.get(`/transactions/${route.params.id}`)
    order.value = response.data
  } catch (error) {
    console.error('Failed to fetch order detail:', error)
  } finally {
    loading.value = false
  }
}

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
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
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

function goBack() {
  router.back()
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 pb-20">
    <!-- Header -->
    <div class="bg-white sticky top-0 z-10 px-4 py-4 shadow-sm border-b border-slate-100 flex items-center gap-4">
      <button @click="goBack" class="p-2 hover:bg-slate-50 rounded-full transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-slate-600">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </button>
      <h1 class="text-xl font-bold text-slate-800">Detail Pesanan</h1>
    </div>

    <div v-if="loading" class="p-4 space-y-4">
      <div class="h-32 bg-white rounded-2xl animate-pulse"></div>
      <div class="h-64 bg-white rounded-2xl animate-pulse"></div>
    </div>

    <div v-else-if="order" class="p-4 space-y-4">
      <!-- Status Card -->
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex justify-between items-start mb-4">
          <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Nomor Invoice</p>
            <p class="text-lg font-black text-slate-800">#{{ order.invoice_number }}</p>
          </div>
          <span class="px-3 py-1.5 rounded-xl text-[10px] font-bold border uppercase tracking-widest" :class="getStatusColor(order.status)">
            {{ getStatusLabel(order.status) }}
          </span>
        </div>
        
        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50">
          <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Tanggal Pesanan</p>
            <p class="text-xs font-medium text-slate-700">{{ formatDate(order.created_at) }}</p>
          </div>
          <div class="text-right">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Metode Bayar</p>
            <p class="text-xs font-medium text-slate-700">{{ order.payment_method?.name || 'Cash' }}</p>
          </div>
        </div>
      </div>

      <!-- Items Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-4 border-b border-slate-50">
          <h2 class="text-sm font-bold text-slate-800">Daftar Item</h2>
        </div>
        
        <div class="divide-y divide-slate-50">
          <div v-for="item in order.items" :key="item.id" class="p-4 flex gap-3">
            <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 flex-shrink-0 overflow-hidden">
              <LazyImage v-if="getItemImage(item)" :src="getItemImage(item)" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-[10px] font-bold text-slate-300">NO IMG</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-slate-800 line-clamp-1 mb-0.5">
                {{ item.product?.name || item.package?.name || item.package_combination?.name || 'Item' }}
              </p>
              <p class="text-xs text-slate-500 mb-2">{{ item.quantity }}x {{ formatCurrency(item.unit_price || (item.subtotal/item.quantity)) }}</p>
              <p class="text-sm font-black text-orange-600">{{ formatCurrency(item.subtotal) }}</p>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div class="bg-slate-50/50 p-4 space-y-2">
          <div class="flex justify-between items-center text-sm">
            <span class="text-slate-500">Subtotal</span>
            <span class="text-slate-700 font-medium">{{ formatCurrency(order.subtotal) }}</span>
          </div>
          <div v-if="order.discount > 0" class="flex justify-between items-center text-sm">
            <span class="text-slate-500">Diskon</span>
            <span class="text-red-500 font-medium">-{{ formatCurrency(order.discount) }}</span>
          </div>
          <div class="flex justify-between items-center pt-2 border-t border-slate-200">
            <span class="text-base font-bold text-slate-800">Total Pembayaran</span>
            <span class="text-xl font-black text-orange-600">{{ formatCurrency(order.total) }}</span>
          </div>
        </div>
      </div>

      <!-- Payment Details if any -->
      <div v-if="order.paid_amount > 0" class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 space-y-3">
        <div class="flex justify-between items-center text-sm">
          <span class="text-slate-500">Jumlah Dibayar</span>
          <span class="text-slate-700 font-bold">{{ formatCurrency(order.paid_amount) }}</span>
        </div>
        <div v-if="order.change_amount > 0" class="flex justify-between items-center text-sm">
          <span class="text-slate-500">Kembalian</span>
          <span class="text-green-600 font-bold">{{ formatCurrency(order.change_amount) }}</span>
        </div>
      </div>
    </div>

    <div v-else class="p-12 text-center">
      <p class="text-slate-500">Pesanan tidak ditemukan</p>
      <button @click="goBack" class="mt-4 text-orange-600 font-bold">Kembali</button>
    </div>
  </div>
</template>
