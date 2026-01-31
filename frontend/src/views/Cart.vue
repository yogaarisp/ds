<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePosStore } from '../stores/pos'
import { customerApi } from '../api'
import QuickAddCustomerModal from '../components/QuickAddCustomerModal.vue'
import CheckoutModal from '../components/CheckoutModal.vue'
import OrderPreviewModal from '../components/OrderPreviewModal.vue'
import PendingChoiceModal from '../components/PendingChoiceModal.vue'
import LazyImage from '../components/LazyImage.vue'
import Swal from 'sweetalert2'

const router = useRouter()
const posStore = usePosStore()

// Customer search
const customerSearch = ref('')
const customerResults = ref([])
const showCustomerDropdown = ref(false)
const showQuickAddModal = ref(false)
const customerSearchLoading = ref(false)
const showCheckoutModal = ref(false)
const showOrderPreview = ref(false)
const customerSearchInput = ref(null)

onMounted(() => {
  // If cart is empty, redirect back to POS
  if (posStore.cart.length === 0) {
    router.push('/pos')
  }
  
  // Set customer search if already selected
  if (posStore.selectedCustomer) {
    customerSearch.value = `${posStore.selectedCustomer.name} - ${posStore.selectedCustomer.phone}`
  }

  window.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside)
  
  // Clear POS state only if moving to a non-POS/Cart page
  const destination = router.currentRoute.value.path
  if (destination !== '/pos' && destination !== '/cart') {
    posStore.refreshData()
  }
})

async function searchCustomers() {
  if (customerSearch.value.length < 2) {
    customerResults.value = []
    showCustomerDropdown.value = false
    return
  }

  customerSearchLoading.value = true
  try {
    const response = await customerApi.search(customerSearch.value)
    customerResults.value = response.data
    showCustomerDropdown.value = true
  } catch (error) {
    console.error(error)
    customerResults.value = []
    showCustomerDropdown.value = true
  } finally {
    customerSearchLoading.value = false
  }
}

function handleCustomerInputFocus() {
  // Jika customer sudah dipilih, jangan tampilkan dropdown saat focus
  if (posStore.selectedCustomer) {
    return
  }
  if (customerSearch.value.length >= 2) {
    showCustomerDropdown.value = true
  }
}

function selectCustomer(customer) {
  posStore.setCustomer(customer)
  customerSearch.value = `${customer.name} - ${customer.phone}`
  showCustomerDropdown.value = false
}

function clearCustomer() {
  posStore.clearCustomer()
  customerSearch.value = ''
  showCustomerDropdown.value = false
  // Focus input on next tick
  setTimeout(() => {
    if (customerSearchInput.value) {
      customerSearchInput.value.focus()
    }
  }, 0)
}

function openQuickAddModal() {
  showQuickAddModal.value = true
  showCustomerDropdown.value = false
}

function handleCustomerAdded(customer) {
  posStore.setCustomer(customer)
  customerSearch.value = `${customer.name} - ${customer.phone}`
  showQuickAddModal.value = false
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}

function getCustomerLevelLabel(level) {
  const labels = {
    normal: 'Reguler',
    reseller: 'Reseller', 
    keluarga: 'Keluarga'
  }
  return labels[level] || 'Reguler'
}

function handleClickOutside(event) {
  if (!event.target.closest('.customer-search-container')) {
    showCustomerDropdown.value = false
  }
}

function goBack() {
  router.push('/pos')
}

function handleOrder() {
  showOrderPreview.value = true
}

function handleConfirmOrder() {
  showOrderPreview.value = false
  showCheckoutModal.value = true
}

async function handleOrderOnly() {
  try {
    const result = await posStore.createOrder()
    showOrderPreview.value = false
    
    // Show SweetAlert2 notification
    await Swal.fire({
      icon: 'success',
      title: 'Pesanan Berhasil!',
      text: `#${result.invoice_number} - Menunggu Pembayaran`,
      timer: 2000,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
    
    // Clear semua dan refresh untuk transaksi baru
    await posStore.refreshData()
    
    // Navigate back to POS
    router.push('/pos')
  } catch (error) {
    console.error('Error creating order:', error)
    await Swal.fire({
      icon: 'error',
      title: 'Gagal Membuat Pesanan',
      text: error.message || 'Terjadi kesalahan saat membuat pesanan',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  }
}

function handleCheckoutClose() {
  showCheckoutModal.value = false
  // Jika cart kosong, berarti transaksi selesai (checkout berhasil)
  // Kembalikan ke halaman POS
  if (posStore.cart.length === 0) {
    router.push('/pos')
  }
}
</script>

<template>
  <div class="fixed inset-0 z-40 bg-[#f8fafc] flex flex-col">
    <!-- Compact Header -->
    <div class="bg-white/80 backdrop-blur-md sticky top-0 z-40 border-b border-slate-100 px-3 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button 
          @click="goBack" 
          class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-slate-600 transition-all active:scale-90"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div>
          <h1 class="text-base font-bold text-slate-800 tracking-tight">Detail Pesanan</h1>
          <p class="text-slate-400 text-[10px] font-medium uppercase tracking-widest">Konfirmasi Checkout</p>
        </div>
      </div>

      <button
        @click="posStore.refreshData()"
        class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-red-50 text-red-500 active:scale-95 transition-all shadow-sm"
        title="Reset Keranjang"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        <span class="text-[10px] font-black uppercase tracking-widest">Reset</span>
      </button>
    </div>

    <div class="flex-1 overflow-hidden flex flex-col min-h-0 animate-fade-in">
      <div class="p-2 space-y-2.5 flex-none">
      <!-- Section: Customer -->
      <section class="flex flex-col">
        <label class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest px-1 leading-none mb-1">Customer</label>
        <div class="bg-white rounded-lg shadow-sm border border-slate-100 relative customer-search-container">
          <!-- Search Input: Only show if NO customer is selected -->
          <div v-if="!posStore.selectedCustomer" class="p-1">
            <div class="relative group">
              <input
                ref="customerSearchInput"
                v-model="customerSearch"
                type="text"
                class="w-full bg-slate-50 border-transparent rounded-lg px-2.5 py-1 text-[10px] focus:bg-white focus:border-primary-500/20 focus:ring-4 focus:ring-primary-500/5 transition-all text-slate-700 font-medium placeholder:text-[9px] placeholder:text-slate-300 pr-8"
                placeholder="Cari nama / nomor HP..."
                @input="searchCustomers"
                @focus="handleCustomerInputFocus"
              />
              <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center">
                <span class="text-slate-300 text-xs">🔍</span>
              </div>
            </div>
          </div>

          <!-- Customer Dropdown List -->
          <div v-if="!posStore.selectedCustomer && showCustomerDropdown" 
               class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-100 rounded-xl shadow-xl z-50 overflow-hidden animate-fade-in-up">
            <div v-if="customerSearchLoading" class="p-4 text-center">
              <span class="w-5 h-5 border-2 border-primary-500 border-t-transparent rounded-full animate-spin inline-block"></span>
            </div>
            
            <div class="max-h-52 overflow-auto custom-scrollbar">
              <button
                v-for="customer in customerResults"
                :key="customer.id"
                @click="selectCustomer(customer)"
                class="w-full px-4 py-3 text-left hover:bg-slate-50 transition-colors flex items-center justify-between border-b border-slate-50 last:border-0"
              >
                <div>
                  <p class="font-bold text-slate-800 text-[13px]">{{ customer.name }}</p>
                  <p class="text-[11px] text-slate-400 font-medium">{{ customer.phone }}</p>
                </div>
                <span class="px-1.5 py-0.5 bg-primary-50 text-primary-600 text-[9px] font-bold rounded uppercase">
                   {{ getCustomerLevelLabel(customer.level) }}
                </span>
              </button>
              
              <div v-if="customerResults.length === 0 && !customerSearchLoading" class="p-4 text-center text-slate-400 text-xs italic">
                Customer tidak ditemukan
              </div>
            </div>
            
            <button
              v-if="customerSearch.length >= 2 && !customerSearchLoading"
              @click="openQuickAddModal"
              class="w-full p-3.5 bg-slate-50 text-primary-600 hover:bg-primary-50 transition-colors text-[11px] font-bold border-t border-slate-100 flex items-center justify-center gap-2"
            >
              <span>➕ Tambah Customer Baru</span>
            </button>
          </div>

          <!-- Selected Customer Detail -->
          <div v-else-if="posStore.selectedCustomer" class="p-2 flex items-center justify-between animate-fade-in group">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center text-primary-600 text-[13px] font-bold ring-2 ring-primary-50">
                {{ posStore.selectedCustomer.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <p class="text-[13px] font-bold text-slate-800 leading-none mb-1">{{ posStore.selectedCustomer.name }}</p>
                <div class="flex items-center gap-2">
                  <span class="px-1.5 py-0.5 bg-primary-50 text-primary-600 text-[7px] font-semibold rounded uppercase tracking-widest">
                    {{ getCustomerLevelLabel(posStore.selectedCustomer.level) }}
                  </span>
                  <span class="text-[10px] text-slate-400 font-medium tabular-nums">{{ posStore.selectedCustomer.phone }}</span>
                </div>
              </div>
            </div>
            
            <button
              @click="clearCustomer"
              class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all active:scale-90"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </section>

      <!-- Section: Order Type -->
      <section class="flex flex-col">
        <label class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest px-1 leading-none mb-1">Metode Pesanan</label>
        <div class="bg-white rounded-lg p-1 shadow-sm border border-slate-100 space-y-1">
          <div class="bg-slate-100 p-0.5 rounded-lg flex gap-0.5">
            <button
              @click="posStore.setOrderType('instant')"
              class="flex-1 py-1 rounded-md text-[10px] font-semibold transition-all flex items-center justify-center gap-1.5"
              :class="posStore.orderType === 'instant' 
                ? 'bg-white text-emerald-600 shadow-sm' 
                : 'text-slate-500'"
            >
              ⚡ Instan
            </button>
            <button
              @click="posStore.setOrderType('scheduled')"
              class="flex-1 py-1 rounded-md text-[10px] font-semibold transition-all flex items-center justify-center gap-1.5"
              :class="posStore.orderType === 'scheduled' 
                ? 'bg-white text-primary-600 shadow-sm' 
                : 'text-slate-500'"
            >
              📅 PO
            </button>
          </div>

          <div v-if="posStore.orderType === 'scheduled'" class="grid grid-cols-2 gap-2 animate-fade-in">
            <input
              v-model="posStore.scheduledDate"
              type="date"
              class="w-full bg-slate-50 border-0 rounded-lg px-2 text-[10px] font-bold text-slate-700 outline-none h-8"
              style="font-size: 10px !important;"
            />
            <input
              v-model="posStore.scheduledTime"
              type="time"
              class="w-full bg-slate-50 border-0 rounded-lg px-2 text-[10px] font-bold text-slate-700 outline-none h-8"
              style="font-size: 10px !important;"
            />
          </div>
        </div>
      </section>

      </div>

      <!-- Section: Items -->
      <div class="flex-1 overflow-y-auto custom-scrollbar p-2 pt-2 pb-32">
        <section class="flex flex-col">
        <div class="flex items-center justify-between px-1 mb-1">
          <label class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest leading-none">Item di Keranjang ({{ posStore.cartItemCount }})</label>
        </div>
        
        <div class="space-y-1.5 pb-4">
          <div
            v-for="(item, index) in posStore.cart"
            :key="index"
            class="bg-white rounded-xl p-2.5 shadow-sm border border-slate-100 flex items-center gap-3"
          >
            <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center overflow-hidden">
              <LazyImage v-if="item.image" :src="`/storage/${item.image}`" :alt="item.name" :placeholder="item.type === 'package' ? '📦' : '🥟'" />
              <span v-else class="text-xl">{{ item.type === 'package' ? '📦' : '🥟' }}</span>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex justify-between items-start">
                <h4 class="font-bold text-slate-800 text-[12px] leading-tight truncate">{{ item.name }}</h4>
                <button
                  @click="posStore.removeCartItem(index)"
                  class="text-slate-200 hover:text-red-500 transition-colors ml-1"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <div class="flex items-center justify-between mt-2">
                <div class="flex items-center bg-slate-50 rounded-lg p-0.5 gap-1">
                  <button
                    @click="posStore.updateCartItemQuantity(index, item.quantity - 1)"
                    class="w-6 h-6 flex items-center justify-center rounded bg-white shadow-sm text-slate-600 font-bold disabled:opacity-30"
                    :disabled="item.quantity <= 1"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4" />
                    </svg>
                  </button>
                  <span class="w-6 text-center text-[11px] font-bold text-slate-800">{{ item.quantity }}</span>
                  <button
                    @click="posStore.updateCartItemQuantity(index, item.quantity + 1)"
                    class="w-6 h-6 flex items-center justify-center rounded bg-white shadow-sm text-slate-600 font-bold"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                </div>
                <div class="text-right">
                  <p class="text-[9px] text-slate-400 font-bold line-through flex justify-end" v-if="item.original_unit_price > item.unit_price">
                    {{ formatPrice(item.original_unit_price * item.quantity) }}
                  </p>
                  <p class="text-[12px] font-bold text-slate-800 tracking-tight">
                    Rp {{ formatPrice(item.subtotal) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
        </section>
      </div>
    </div>

    <!-- Symmetric Compact Footer reaching bottom -->
    <div class="fixed bottom-0 left-0 right-0 lg:hidden z-30">
      <div class="bg-white/95 backdrop-blur-xl rounded-t-3xl shadow-[0_-10px_35px_rgba(0,0,0,0.06)] border-t border-slate-100 px-5 pt-3 pb-[72px] flex items-center justify-between gap-3">
        <div class="flex-1 min-w-0 flex flex-col justify-center">
          <div class="flex items-center gap-1.5 mb-0.5">
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest leading-none">Total Tagihan</p>
            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-600 text-[7px] font-bold rounded uppercase tracking-tighter">
              {{ posStore.cartItemCount }} Item
            </span>
          </div>
          <p class="text-base font-bold text-primary-600 leading-none tracking-tight truncate">Rp {{ formatPrice(posStore.cartTotal) }}</p>
        </div>

        <button
          @click="handleOrder"
          class="bg-primary-500 px-5 py-2 rounded-xl text-white font-bold text-xs shadow-[0_6px_12px_rgba(235,108,34,0.1)] hover:bg-primary-600 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2 shrink-0"
          :disabled="posStore.cart.length === 0"
        >
          <span>Proses</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Modals (Tetap) -->
    <OrderPreviewModal v-if="showOrderPreview" @close="showOrderPreview = false" @order="handleOrderOnly" @confirm="handleConfirmOrder" />
    <QuickAddCustomerModal v-if="showQuickAddModal" :searchQuery="customerSearch" @close="showQuickAddModal = false" @customerAdded="handleCustomerAdded" />
    <CheckoutModal v-if="showCheckoutModal" @close="handleCheckoutClose" />
    <PendingChoiceModal />
  </div>
</template>
