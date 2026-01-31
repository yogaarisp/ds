<script setup>
import { computed } from 'vue'
import { usePosStore } from '../stores/pos'
import Swal from 'sweetalert2'

const posStore = usePosStore()

const emit = defineEmits(['close'])

const pendingTransaction = computed(() => posStore.pendingTransaction)

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', { 
    weekday: 'short', 
    day: 'numeric', 
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function loadPendingOrder() {
  try {
    await posStore.loadPendingTransaction()
    emit('close')
    
    // Show success notification
    Swal.fire({
      icon: 'success',
      title: 'Pesanan Dimuat!',
      text: `Pesanan #${pendingTransaction.value.invoice_number} berhasil dimuat ke keranjang`,
      timer: 2000,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-2xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } catch (error) {
    console.error('Error loading pending order:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal Memuat',
      text: 'Terjadi kesalahan saat memuat pesanan',
      timer: 2000,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-2xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  }
}

async function cancelPendingOrder() {
  try {
    const result = await Swal.fire({
      title: 'Batalkan Pesanan?',
      text: `Pesanan #${pendingTransaction.value.invoice_number} akan dibatalkan`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Batalkan',
      cancelButtonText: 'Tidak',
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#6b7280',
      width: '350px',
      customClass: {
        popup: 'rounded-2xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })

    if (result.isConfirmed) {
      await posStore.cancelPendingTransaction()
      emit('close')
      
      Swal.fire({
        icon: 'success',
        title: 'Pesanan Dibatalkan',
        text: 'Pesanan berhasil dibatalkan',
        timer: 2000,
        showConfirmButton: false,
        width: '300px',
        customClass: {
          popup: 'rounded-2xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })
    }
  } catch (error) {
    console.error('Error canceling pending order:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal Membatalkan',
      text: 'Terjadi kesalahan saat membatalkan pesanan',
      timer: 2000,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-2xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  }
}

function dismiss() {
  emit('close')
}
</script>

<template>
  <div v-if="pendingTransaction" class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-4">
    <div class="flex items-start gap-3">
      <div class="text-orange-600 text-xl flex-shrink-0">⚠️</div>
      
      <div class="flex-1 min-w-0">
        <h4 class="font-semibold text-orange-800 text-sm mb-1">
          Pesanan Pending Ditemukan
        </h4>
        <div class="text-xs text-orange-700 space-y-1">
          <p><span class="font-medium">No:</span> #{{ pendingTransaction.invoice_number }}</p>
          <p><span class="font-medium">Total:</span> Rp {{ formatPrice(pendingTransaction.total) }}</p>
          <p><span class="font-medium">Dibuat:</span> {{ formatDate(pendingTransaction.created_at) }}</p>
          <p><span class="font-medium">Item:</span> {{ pendingTransaction.items?.length || 0 }} item</p>
        </div>
        
        <div class="bg-orange-100 rounded p-2 mt-2 text-xs text-orange-800">
          <p class="font-medium">💡 Pesanan baru akan menggantikan pesanan ini</p>
        </div>
        
        <div class="flex gap-2 mt-3">
          <button
            @click="loadPendingOrder"
            class="px-3 py-1.5 bg-orange-600 text-white text-xs font-medium rounded-lg hover:bg-orange-700 transition-colors"
          >
            Muat Item Lama
          </button>
          <button
            @click="cancelPendingOrder"
            class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors"
          >
            Batalkan Pesanan
          </button>
          <button
            @click="dismiss"
            class="px-3 py-1.5 bg-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-400 transition-colors"
          >
            Sembunyikan
          </button>
        </div>
      </div>
      
      <button
        @click="dismiss"
        class="text-orange-600 hover:text-orange-800 text-lg flex-shrink-0"
      >
        ✕
      </button>
    </div>
  </div>
</template>