<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { usePosStore } from '../stores/pos'

const posStore = usePosStore()

const pendingInfo = computed(() => posStore.pendingTransactionInfo)

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getTotalItems() {
  if (!pendingInfo.value?.items) return 0
  return pendingInfo.value.items.reduce((sum, item) => sum + item.quantity, 0)
}

onMounted(() => {
  document.body.style.overflow = 'hidden'
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<template>
  <div v-if="posStore.showPendingChoice" class="fixed inset-0 bg-slate-900/60 flex items-center justify-center z-50 sm:p-4 backdrop-blur-sm transition-all overflow-hidden">
    <div class="bg-white w-full h-full sm:h-auto sm:max-w-md sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-fade-in-up">
      <!-- Header -->
      <div class="bg-amber-50/80 border-b border-amber-100/50 px-4 py-3 relative backdrop-blur">
        <button 
          @click="posStore.showPendingChoice = false" 
          class="absolute top-3 left-3 w-7 h-7 flex items-center justify-center rounded-lg bg-white/50 text-amber-900/40 hover:text-amber-900 transition-all active:scale-95"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div class="flex items-center gap-3 pl-8">
          <div class="w-9 h-9 bg-white rounded-xl flex items-center justify-center text-lg shrink-0 shadow-sm">
            ⚠️
          </div>
          <div>
            <h2 class="text-sm font-semibold text-amber-900">Pesanan Pending</h2>
            <p class="text-[10px] text-amber-700/60 font-medium mt-0.5">{{ posStore.selectedCustomer?.name }}</p>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-auto px-4 py-3 space-y-3">
        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
          <div class="flex justify-between items-start mb-3">
            <div>
              <p class="font-mono font-semibold text-slate-800 text-sm">{{ pendingInfo?.invoice_number }}</p>
              <p class="text-[10px] text-slate-400 font-medium mt-0.5">{{ formatDate(pendingInfo?.created_at) }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-md text-[9px] font-semibold uppercase bg-amber-100 text-amber-700">
              Pending
            </span>
          </div>
          
          <div class="bg-white rounded-lg border border-slate-100 p-2.5 mb-2">
            <div class="flex justify-between items-center mb-1">
              <span class="text-[10px] font-medium text-slate-400 uppercase">Total Item</span>
              <span class="text-sm font-semibold text-slate-700">{{ getTotalItems() }} Pcs</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-[10px] font-medium text-slate-400 uppercase">Tagihan</span>
              <span class="text-lg font-bold text-primary-600">Rp {{ formatPrice(pendingInfo?.total) }}</span>
            </div>
          </div>

          <!-- Items preview -->
          <div class="space-y-1.5">
            <p class="text-[10px] font-medium text-slate-400 uppercase px-1">Ringkasan Menu:</p>
            <div class="max-h-28 overflow-auto custom-scrollbar space-y-1 pr-1">
              <div 
                v-for="item in pendingInfo?.items" 
                :key="item.id" 
                class="flex justify-between items-center bg-white/50 px-2.5 py-1.5 rounded-lg border border-slate-50"
              >
                <div class="min-w-0">
                  <div class="flex items-center gap-1.5 mb-0.5">
                    <p class="text-[11px] font-medium text-slate-700 truncate mr-1">
                      {{ item.product?.name || item.package_combination?.name || item.package?.name }}
                    </p>
                    <span class="text-[9px] font-medium text-primary-500 bg-primary-50 px-1 py-0.5 rounded">x{{ item.quantity }}</span>
                  </div>
                  <p class="text-[10px] text-slate-400">Rp {{ formatPrice(item.unit_price) }}</p>
                </div>
                <span class="text-[11px] font-medium text-slate-800 shrink-0">Rp {{ formatPrice(item.subtotal) }}</span>
              </div>
            </div>
          </div>
        </div>

        <p class="text-[10px] text-slate-500 text-center leading-relaxed">
          Sistem mendeteksi pesanan yang belum selesai.<br>
          <span class="font-medium text-slate-700">Lanjutkan untuk memproses pembayaran sekarang.</span>
        </p>
      </div>

      <!-- Action Footer -->
      <div class="px-4 pt-3 pb-20 sm:pb-3 bg-white border-t border-slate-100 mt-auto">
        <div class="flex gap-2">
          <button
            @click="posStore.createNewInvoice()"
            class="flex-1 py-3 px-3 rounded-xl bg-slate-100 text-slate-600 font-medium text-sm hover:bg-slate-200 transition-all active:scale-[0.98] flex items-center justify-center gap-1.5"
          >
            <span>➕</span> Buat Baru
          </button>
          <button
            @click="posStore.continuePendingTransaction()"
            class="flex-1 py-3 px-3 rounded-xl bg-primary-500 text-white font-medium text-sm hover:bg-primary-600 transition-all active:scale-[0.98] flex items-center justify-center gap-1.5"
          >
            <span>📝</span> Lanjutkan
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
