<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { usePosStore } from '../stores/pos'

const emit = defineEmits(['close', 'confirm', 'order'])

const posStore = usePosStore()

const orderDate = computed(() => {
  if (posStore.orderType === 'scheduled') {
    const date = new Date(posStore.scheduledDate)
    return date.toLocaleDateString('id-ID', { 
      day: 'numeric', 
      month: 'long', 
      year: 'numeric' 
    })
  }
  return new Date().toLocaleDateString('id-ID', { 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  })
})

const orderTime = computed(() => {
  if (posStore.orderType === 'scheduled' && posStore.scheduledTime) {
    return posStore.scheduledTime
  }
  return new Date().toLocaleTimeString('id-ID', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
})

const invoicePreview = computed(() => {
  const date = new Date()
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = String(date.getFullYear()).slice(-2)
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `DS${day}${month}${year}${hours}${minutes}`
})

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}

function getCustomerLabel(customer) {
  const name = customer?.customer_type?.name || 'Reguler'
  return name.replace(/Pelanggan\s?/i, '')
}

onMounted(() => {
  document.body.style.overflow = 'hidden'
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center z-50 sm:p-4 backdrop-blur-sm transition-all overflow-hidden">
    <div class="bg-white w-full h-full sm:h-auto sm:max-w-xl sm:max-h-[92vh] sm:rounded-3xl flex flex-col shadow-2xl overflow-hidden animate-fade-in-up">
      <!-- Header Refined -->
      <div class="px-4 py-3 border-b border-slate-50 flex items-center bg-white/80 backdrop-blur sticky top-0 z-10">
        <div class="flex items-center gap-3">
          <button 
            @click="emit('close')" 
            class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all active:scale-90"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div>
            <h2 class="text-lg font-bold text-slate-800 tracking-tight">Detail Pesanan</h2>
            <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-slate-400 font-semibold uppercase tracking-wider">
              <span>{{ invoicePreview }}</span>
              <span class="text-slate-200">|</span> 
              <span>{{ orderDate }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="flex-1 overflow-auto p-4 space-y-2 custom-scrollbar pb-10">
        <!-- Compact Status Banner -->
        <div class="bg-primary-50/50 px-3.5 py-2 rounded-2xl border border-primary-100/50 flex items-center justify-between gap-4">
          <div class="flex items-center gap-2.5">
             <div class="w-8 h-8 bg-white rounded-xl shadow-sm flex items-center justify-center text-lg">🧾</div>
             <div>
               <p class="text-[11px] font-bold text-primary-600 uppercase tracking-widest leading-none mb-1">Status</p>
               <p class="text-sm font-bold text-slate-700">{{ posStore.orderType === 'instant' ? 'Instan' : 'Pre-Order' }}</p>
             </div>
          </div>
          <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-600 border border-amber-100">
             Menunggu
          </span>
        </div>

        <!-- Info Grid Compact -->
        <div class="grid grid-cols-2 gap-1.5">
          <!-- Customer -->
          <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
              Customer
            </p>
            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-full bg-white border border-slate-100 flex items-center justify-center text-slate-400 shadow-sm text-[10px] font-bold">
                {{ posStore.selectedCustomer?.name.charAt(0).toUpperCase() || 'G' }}
              </div>
              <div class="min-w-0 flex flex-col justify-center">
                <p class="font-bold text-slate-800 text-sm truncate leading-none mb-0.5">{{ posStore.selectedCustomer?.name || 'Guest' }}</p>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">{{ getCustomerLabel(posStore.selectedCustomer) }}</p>
              </div>
            </div>
          </div>

          <!-- Order Info -->
          <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
              Metode
            </p>
            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-full bg-white border border-slate-100 flex items-center justify-center text-slate-400 shadow-sm text-xs">
                {{ posStore.orderType === 'instant' ? '⚡' : '📅' }}
              </div>
              <div class="min-w-0">
                <p class="font-bold text-slate-800 text-sm leading-none mb-0.5">{{ posStore.cartItemCount }} Item</p>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight truncate">{{ posStore.orderType === 'instant' ? 'Dine In / TA' : 'Pre-Order' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Table Info (if QR Order) -->
        <div v-if="posStore.selectedTable" class="bg-emerald-50 rounded-2xl p-3 border border-emerald-100 flex items-center gap-3">
          <div class="w-8 h-8 bg-white rounded-xl shadow-sm flex items-center justify-center text-emerald-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
            </svg>
          </div>
          <div>
            <p class="text-[11px] font-extrabold text-emerald-600 uppercase tracking-widest leading-none mb-1">Dipesan di Meja</p>
            <p class="font-bold text-slate-800 text-base leading-tight">{{ posStore.selectedTable.name }}</p>
          </div>
          <div class="ml-auto">
             <div class="bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">VIA SCAN QR</div>
          </div>
        </div>

        <!-- Order Items List Refined -->
        <div class="space-y-3">
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest px-1">Daftar Item</p>
          <div class="space-y-1">
            <div 
              v-for="(item, index) in posStore.cart" 
              :key="index"
              class="bg-white border-b border-slate-50 px-3 py-2 flex items-center justify-between hover:bg-slate-50/30 transition-all"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                  <p class="font-bold text-slate-700 text-sm truncate leading-tight">{{ item.name }}</p>
                  <span class="text-[10px] font-bold text-primary-500 bg-primary-50 px-1 py-0.5 rounded border border-primary-100/30">x{{ item.quantity }}</span>
                </div>
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">@ Rp {{ formatPrice(item.unit_price) }}</p>
              </div>
              <div class="shrink-0 text-right pl-4">
                <p class="font-bold text-slate-800 text-sm tracking-tight">Rp {{ formatPrice(item.subtotal) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Sticky Footer: Summary + Actions -->
      <div class="bg-white border-t border-slate-100 sticky bottom-0 z-20 shadow-[0_-10px_40px_rgba(0,0,0,0.04)] p-2.5 pb-3 sm:pb-2.5 space-y-1">
        <!-- Payment Summary Box -->
        <div class="bg-slate-50/50 rounded-2xl p-2.5 border border-slate-100/50 space-y-1.5">
          <div class="flex justify-between items-center">
            <span class="text-slate-400 uppercase tracking-widest font-bold text-[10px]">Subtotal</span>
            <span class="text-slate-600 font-bold text-sm">Rp {{ formatPrice(posStore.cartSubtotal) }}</span>
          </div>
          <div v-if="posStore.selectedCustomer && (posStore.cartSubtotal - posStore.cartTotal) > 0" class="flex justify-between items-center">
            <span class="text-slate-400 uppercase tracking-widest font-bold text-[10px]">Diskon</span>
            <span class="text-emerald-500 font-bold text-sm">-Rp {{ formatPrice(posStore.cartSubtotal - posStore.cartTotal) }}</span>
          </div>
          
          <div class="border-t border-slate-200/40 my-1"></div>

          <div class="flex justify-between items-center">
            <span class="font-bold text-slate-800 text-[10px] uppercase tracking-widest">Total Tagihan</span>
            <span class="text-lg font-bold text-primary-600 tracking-tight">Rp {{ formatPrice(posStore.cartTotal) }}</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-2 gap-2">
          <button
            @click="emit('order')"
            class="px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider text-slate-600 bg-slate-50 border border-slate-100 hover:bg-slate-100 transition-all active:scale-[0.97] flex items-center justify-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Pesan Dulu
          </button>
          <button
            @click="emit('confirm')"
            class="px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider text-white bg-primary-500 shadow-[0_4px_12px_rgba(235,108,34,0.2)] hover:bg-primary-600 transition-all active:scale-[0.97] flex items-center justify-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Bayar Sekarang
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
