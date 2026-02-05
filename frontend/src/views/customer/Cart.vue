<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import api, { STORAGE_BASE_URL } from '@/api'
import Swal from 'sweetalert2'
import LazyImage from '@/components/LazyImage.vue'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()

const paymentMethods = ref([])
const selectedPaymentMethod = ref(null)
const showPaymentMethods = ref(false)
const loading = ref(false)
const processing = ref(false)
const isMounted = ref(false)
const guestName = ref('')

onMounted(async () => {
  isMounted.value = true
  
  // Prompt auth if guest without table
  if (!authStore.isAuthenticated && !cartStore.hasTable) {
    authStore.promptAuthChoice()
  }
  
  await fetchPaymentMethods()
})

onUnmounted(() => {
  isMounted.value = false
})

async function fetchPaymentMethods() {
  if (!isMounted.value) return
  loading.value = true
  try {
    const response = await api.get('/payment-methods')
    if (isMounted.value) {
      paymentMethods.value = response.data.filter(pm => pm.is_active)
      if (paymentMethods.value.length > 0) {
        selectedPaymentMethod.value = paymentMethods.value[0].id
      }
    }
  } catch (error) {
    console.error('Failed to fetch payment methods:', error)
  } finally {
    if (isMounted.value) {
      loading.value = false
    }
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

const getPaymentIcon = (method) => {
  const name = method.name.toLowerCase()
  const type = method.type.toLowerCase()
  
  if (type === 'cash') {
    return `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>`
  } else if (name.includes('qris')) {
    return `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
      </svg>`
  } else {
    // Bank / Card
    return `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
      </svg>`
  }
}

function handlePaymentSelection(method) {
  selectedPaymentMethod.value = method.id
}

async function handleCheckout() {
  if (!selectedPaymentMethod.value) {
    Swal.fire('Error', 'Pilih metode pembayaran', 'error')
    return
  }

  if (isMounted.value) {
    processing.value = true
  }
  try {
    const items = cartStore.cart.map(item => ({
      type: item.type,
      product_id: item.type === 'product' ? item.id : undefined,
      package_combination_id: item.type === 'package' ? item.package_combination_id : undefined,
      inventory_id: item.type === 'inventory' ? item.inventory_id : undefined,
      quantity: item.quantity,
      is_frozen: item.is_frozen || false,
      unit_price: item.price,
      subtotal: item.price * item.quantity,
      note: item.note || ''
    }))

    const payload = {
      customer_id: authStore.user?.id,
      payment_method_id: selectedPaymentMethod.value,
      order_type: 'instant', // Default for now
      items: items,
      total_amount: cartStore.subtotal,
      paid_amount: 0, // Customer order usually starts unpaid or payment gateway
      payment_status: 'pending',
      table_code: cartStore.tableCode || null, // Include table if scanning QR
      customer_name: guestName.value || null
    }

    const response = await api.post('/transactions', payload)
    
    // Clear Cart (Keep Table for redirect authorization)
    cartStore.clearCart()
    
    await Swal.fire({
      icon: 'success',
      title: 'Pesanan Berhasil!',
      text: 'Pesanan Anda sedang diproses.',
      timer: 2000,
      showConfirmButton: false,
      width: '280px',
      customClass: {
        popup: 'rounded-3xl p-4',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs text-slate-500'
      }
    })

    // Redirect to Order Detail
    router.push(`/customer/order/${response.data.id}`)

  } catch (error) {
    console.error('Checkout failed:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal Checkout',
      text: error.response?.data?.message || 'Terjadi kesalahan saat memproses pesanan',
      customClass: {
        popup: 'rounded-2xl',
        title: 'font-bold'
      }
    })
  } finally {
    if (isMounted.value) {
      processing.value = false
    }
  }
}
const deliveryType = ref(cartStore.hasTable ? 'dine_in' : 'takeaway')

function handleDeliveryTypeSelection(type) {
  if (type === 'delivery') {
    Swal.fire({
      icon: 'info',
      title: 'Segera Hadir!',
      text: 'Layanan delivery akan segera tersedia.',
      confirmButtonColor: '#F97316',
      confirmButtonText: 'Oke',
      width: '260px',
      padding: '1em',
      customClass: {
        popup: 'rounded-2xl',
        title: 'font-bold text-sm mb-1',
        htmlContainer: 'text-xs text-slate-500 m-0',
        confirmButton: 'px-4 py-1.5 text-xs rounded-lg'
      }
    })
    return
  }
  deliveryType.value = type
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Header (Fixed Top) -->
    <div class="fixed top-0 left-0 right-0 bg-white z-50 px-4 py-3 shadow-sm border-b border-slate-100 flex items-center gap-3 h-14">
      <button @click="router.back()" class="p-2 -ml-2 text-slate-600 hover:bg-slate-50 rounded-full active:scale-90 transition-transform">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </button>
      <h1 class="text-lg font-bold text-slate-800">Checkout</h1>
    </div>

    <!-- Main Content (Scrollable Body) -->
    <!-- pt-16 allows header space, pb-32 allows footer space -->
    <div class="pt-16 pb-32 px-4 space-y-4">
      
      <!-- Empty Cart State -->
      <div v-if="cartStore.cart.length === 0" class="min-h-[60vh] flex flex-col items-center justify-center text-center">
        <div class="text-6xl mb-4 grayscale opacity-50">🛒</div>
        <p class="text-slate-500 font-medium text-sm mb-6">Keranjang belanja Anda kosong</p>
        <button @click="router.push('/customer/menu')" class="px-6 py-3 bg-white border border-slate-200 text-primary-600 font-bold text-sm rounded-xl shadow-sm hover:bg-slate-50 transition-all active:scale-95">
          Jelajahi Menu
        </button>
      </div>
      
      <!-- Cart Content -->
      <template v-else>

        <!-- Group Container for Delivery & Table Info -->
        <div class="space-y-2">
          <!-- Delivery Method Switcher -->
          <div class="bg-slate-200/50 p-1 rounded-xl flex relative h-10">
            <!-- Indicator -->
            <div 
               class="absolute top-1 bottom-1 w-[calc(33.33%-4px)] bg-white rounded-lg shadow-sm transition-all duration-300 ease-out z-0"
               :style="{ 
                 left: deliveryType === 'dine_in' ? '4px' : 
                       deliveryType === 'takeaway' ? 'calc(33.33% + 2px)' : 
                       'calc(66.66% + 2px)' 
               }"
            ></div>
            
            <button 
              @click="handleDeliveryTypeSelection('dine_in')"
              class="flex-1 px-2 text-[10px] font-bold rounded-lg relative z-10 transition-colors flex items-center justify-center gap-1"
              :class="deliveryType === 'dine_in' ? 'text-primary-600' : 'text-slate-500 hover:text-slate-700'"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
              </svg>
              Dine In
            </button>

            <button 
              @click="handleDeliveryTypeSelection('takeaway')"
              class="flex-1 px-2 text-[10px] font-bold rounded-lg relative z-10 transition-colors flex items-center justify-center gap-1"
              :class="deliveryType === 'takeaway' ? 'text-primary-600' : 'text-slate-500 hover:text-slate-700'"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
              </svg>
              Takeaway
            </button>
            
            <button 
              @click="handleDeliveryTypeSelection('delivery')"
              class="flex-1 px-2 text-[10px] font-bold rounded-lg relative z-10 transition-colors flex items-center justify-center gap-1"
              :class="deliveryType === 'delivery' ? 'text-primary-600' : 'text-slate-400'"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z" />
              </svg>
              Delivery
            </button>
          </div>

          <!-- Table Information (Conditional) -->
          <div v-if="cartStore.hasTable" class="bg-orange-50 border border-orange-100 rounded-xl p-2.5 space-y-3 relative overflow-hidden">
            <div class="absolute -right-3 -top-3 w-12 h-12 bg-orange-100/50 rounded-full blur-xl"></div>
            
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 bg-white rounded-lg shadow-sm flex items-center justify-center text-orange-500 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
                </svg>
              </div>
              <div>
                <p class="text-[8px] font-extrabold text-orange-500 uppercase tracking-widest leading-tight">Meja Anda</p>
                <h3 class="font-bold text-slate-800 text-sm leading-tight">{{ cartStore.tableName }}</h3>
              </div>
              <div class="ml-auto">
                <div class="bg-emerald-500 text-white text-[8px] font-bold px-2 py-0.5 rounded-full shadow-sm">AKTIF</div>
              </div>
            </div>

            <!-- Customer Name Input for Guest -->
            <div class="relative z-10">
              <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1 px-1">Nama Pemesan</p>
              <input 
                v-model="guestName"
                type="text" 
                placeholder="Masukkan nama Anda..."
                class="w-full bg-white border border-orange-200 rounded-lg px-3 py-2 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all placeholder:text-slate-300"
              />
            </div>
          </div>
        </div>

        <!-- Order Items Section -->
        <div class="space-y-2">
          <div class="flex items-center justify-between px-1">
            <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Daftar Pesanan</h2>
            <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ cartStore.itemCount }} item</span>
          </div>
          
          <!-- Items List -->
          <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden divide-y divide-slate-50">
            <div 
              v-for="(item, index) in cartStore.cart" 
              :key="item.cart_id" 
              class="py-2.5 px-3 flex gap-3"
            >
              <!-- Image -->
              <div class="w-12 h-12 bg-slate-50 rounded-lg shrink-0 overflow-hidden border border-slate-100 self-center">
                <LazyImage v-if="item.image" :src="`${STORAGE_BASE_URL}/${item.image}`" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center text-lg">📦</div>
              </div>
              
              <!-- Details -->
              <div class="flex-1 min-w-0 flex flex-col justify-center gap-1">
                <div class="flex justify-between items-center gap-2">
                  <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-slate-800 text-xs leading-tight line-clamp-1 mb-0.5">{{ item.name }}</h3>
                    <p class="text-[11px] text-slate-400 leading-tight">
                      {{ formatCurrency(item.price) }}
                    </p>
                  </div>
                  
                  <!-- Quantity Control (Swapped to Top) -->
                  <div class="shrink-0">
                    <div class="flex items-center gap-1.5 bg-slate-50 rounded-md p-0.5 border border-slate-100 h-5">
                      <button 
                        @click="cartStore.updateQuantity(item.cart_id, item.quantity - 1)" 
                        class="w-4 h-full rounded bg-white shadow-sm flex items-center justify-center text-slate-500 active:scale-90 transition-transform hover:text-red-500"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                        </svg>
                      </button>
                      <span class="text-[10px] font-bold text-slate-800 min-w-[14px] text-center">{{ item.quantity }}</span>
                      <button 
                        @click="cartStore.updateQuantity(item.cart_id, item.quantity + 1)" 
                        class="w-4 h-full rounded bg-white shadow-sm flex items-center justify-center text-slate-500 active:scale-90 transition-transform hover:text-green-600"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Subtotal (Swapped to Bottom) -->
                 <div class="flex justify-end">
                    <p class="text-primary-600 font-bold text-xs leading-tight">{{ formatCurrency(item.price * item.quantity) }}</p>
                 </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Methods Section -->
        <div class="space-y-2">
          <div class="flex items-center justify-between px-1 cursor-pointer" @click="showPaymentMethods = !showPaymentMethods">
            <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Metode Pembayaran</h2>
            <button class="text-slate-400 p-1 hover:bg-slate-100 rounded-full transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="showPaymentMethods ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
          
          <div v-show="showPaymentMethods" class="transition-all duration-300 ease-in-out">
            <div v-if="loading" class="py-6 text-center bg-white rounded-2xl border border-slate-100">
              <div class="animate-spin w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full mx-auto mb-2"></div>
              <p class="text-xs text-slate-400">Memuat metode pembayaran...</p>
            </div>
            
            <div v-else class="grid grid-cols-2 gap-2">
              <button
                v-for="method in paymentMethods"
                :key="method.id"
                @click="handlePaymentSelection(method)"
                class="p-3 border rounded-xl flex items-center gap-3 transition-all relative overflow-hidden group text-left"
                :class="selectedPaymentMethod === method.id 
                  ? 'border-primary-500 bg-primary-50/50 ring-1 ring-primary-500/20' 
                  : 'border-slate-200 bg-white hover:border-slate-300'"
              >
                <div 
                  class="w-8 h-8 rounded-lg bg-white border border-slate-100 flex items-center justify-center shrink-0 [&>svg]:w-4 [&>svg]:h-4 transition-colors"
                  :class="selectedPaymentMethod === method.id ? 'text-primary-500' : 'text-slate-400'"
                  v-html="getPaymentIcon(method)"
                ></div>
                <div class="flex-1 min-w-0">
                  <span class="block text-[11px] font-bold text-slate-700 leading-tight mb-0.5">{{ method.name }}</span>
                  <span class="block text-[9px] text-slate-400 leading-tight" v-if="method.type === 'cash'">Bayar di kasir</span>
                  <span class="block text-[9px] text-slate-400 leading-tight" v-else>Transfer otomatis</span>
                </div>
                <div v-if="selectedPaymentMethod === method.id" class="absolute top-2 right-2 text-primary-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </button>
            </div>
          </div>
          
          <!-- Selected Method Summary (When Hidden) -->
          <div v-show="!showPaymentMethods" @click="showPaymentMethods = true" class="bg-white border border-slate-200 rounded-xl p-3 flex items-center gap-3 cursor-pointer hover:border-primary-200 hover:bg-primary-50/30 transition-colors">
             <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-500 flex items-center justify-center border border-primary-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
             </div>
             <div class="flex-1">
                <p class="text-xs font-bold text-slate-700">
                  {{ paymentMethods.find(p => p.id === selectedPaymentMethod)?.name || 'Pilih Metode Pembayaran' }}
                </p>
                <p class="text-[10px] text-slate-400">Bayar di Kasir</p>
             </div>
             <div class="text-primary-500 text-xs font-bold">Ubah</div>
          </div>
        </div>

        <!-- Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 space-y-3">
          <div class="flex justify-between items-center text-xs">
            <span class="text-slate-500">Subtotal Pesanan</span>
            <span class="font-semibold text-slate-700">{{ formatCurrency(cartStore.subtotal) }}</span>
          </div>
          <div class="flex justify-between items-center text-xs">
            <span class="text-slate-500">Biaya Layanan</span>
            <span class="font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded text-[10px]">Gratis</span>
          </div>
        </div>
      </template>
    </div>
    
    <!-- Footer (Fixed Bottom) -->
    <div v-if="cartStore.cart.length > 0" class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-100 px-4 py-2.5 pb-2 z-[100] shadow-[0_-4px_20px_rgba(0,0,0,0.05)] flex items-center justify-between gap-4">
      <!-- Total Info (Footer Content) -->
      <div class="flex flex-col">
        <span class="text-xs text-slate-500 font-medium">Total Pembayaran</span>
        <span class="text-lg font-bold text-slate-800 leading-tight">{{ formatCurrency(cartStore.subtotal) }}</span>
      </div>

      <!-- Action Button -->
      <button 
        @click="handleCheckout"
        :disabled="processing || !selectedPaymentMethod"
        class="flex-1 bg-primary-500 text-white font-bold px-4 py-3 rounded-xl shadow-lg shadow-primary-500/25 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 active:scale-[0.98] transition-all hover:bg-primary-600 max-w-[200px]"
      >
        <svg v-if="processing" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm border-r border-white/20 pr-3 mr-3 hidden sm:inline" v-if="!processing">Checkout</span>
        <span class="text-sm">{{ processing ? 'Memproses...' : 'Bayar Sekarang' }}</span>
      </button>
    </div>
  </div>
</template>

