<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'
import Swal from 'sweetalert2'
import LazyImage from '@/components/LazyImage.vue'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()

const paymentMethods = ref([])
const selectedPaymentMethod = ref(null)
const loading = ref(false)
const processing = ref(false)
const isMounted = ref(false)

onMounted(async () => {
  isMounted.value = true
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
  const type = method.type.toLowerCase()
  const name = method.name.toLowerCase()
  
  if (type === 'cash') {
    selectedPaymentMethod.value = method.id
  } else {
    Swal.fire({
      icon: 'info',
      title: 'Coming Soon',
      text: 'Segera hadir',
      confirmButtonText: 'Oke',
      confirmButtonColor: '#F97316',
      width: '280px',
      customClass: {
        popup: 'rounded-2xl text-xs',
        title: 'text-sm font-bold',
        confirmButton: 'text-xs px-4 py-1.5'
      }
    })
  }
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
      payment_status: 'pending'
    }

    const response = await api.post('/transactions', payload)
    
    // Clear Cart
    cartStore.clearCart()
    
    await Swal.fire({
      icon: 'success',
      title: 'Pesanan Berhasil!',
      text: 'Pesanan Anda sedang diproses.',
      timer: 2000,
      showConfirmButton: false,
      customClass: {
        popup: 'rounded-2xl',
        title: 'font-bold'
      }
    })

    // Redirect to Orders or Home
    router.push('/customer/order')

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
</script>

<template>
  <div class="min-h-screen bg-slate-50 pb-32 pt-16">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 bg-white z-50 px-4 py-3 shadow-sm border-b border-slate-100 flex items-center gap-3">
      <button @click="router.back()" class="p-2 -ml-2 text-slate-600 hover:bg-slate-50 rounded-full active:scale-90 transition-transform">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </button>
      <h1 class="text-lg font-bold text-slate-800">Checkout</h1>
    </div>

    <!-- Content -->
    <div class="p-4 space-y-5">
      
      <!-- Order Items (Compact) -->
      <div v-if="cartStore.cart.length === 0" class="text-center py-12">
        <div class="text-4xl mb-3">🛒</div>
        <p class="text-slate-500 font-medium text-sm">Keranjang kosong</p>
        <button @click="router.push('/customer/menu')" class="mt-4 text-primary-600 font-bold text-sm bg-primary-50 px-4 py-2 rounded-lg">Kembali ke Menu</button>
      </div>
      
      <div v-else class="space-y-3">
        <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest px-1">Daftar Pesanan</h2>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 divide-y divide-slate-50">
          <div v-for="item in cartStore.cart" :key="item.cart_id" class="py-2 px-3 flex gap-3">
            <!-- Image (Smaller) -->
            <div class="w-12 h-12 bg-slate-100 rounded-lg shrink-0 overflow-hidden">
               <LazyImage v-if="item.image" :src="`/storage/${item.image}`" class="w-full h-full object-cover" />
               <div v-else class="w-full h-full flex items-center justify-center text-xl">📦</div>
            </div>
            <!-- Details -->
            <div class="flex-1 min-w-0 flex flex-col justify-center">
              <div class="flex justify-between items-start gap-2">
                <div>
                  <h3 class="font-bold text-slate-800 text-xs leading-tight mb-0.5 line-clamp-2">{{ item.name }}</h3>
                  <p class="text-[10px] text-slate-500">
                    {{ item.type === 'package' ? 'Paket' : 'Satuan' }} <span class="mx-1">•</span> {{ formatCurrency(item.price) }}
                  </p>
                </div>
                <!-- Compact Qty Control -->
                 <div class="flex items-center gap-2 bg-slate-50 rounded-lg p-0.5 border border-slate-100">
                    <button @click="cartStore.updateQuantity(item.cart_id, item.quantity - 1)" class="w-5 h-5 rounded-md bg-white shadow-sm flex items-center justify-center font-bold text-slate-600 text-[10px] active:scale-90 transition-transform">-</button>
                    <span class="text-xs font-bold text-slate-800 w-4 text-center">{{ item.quantity }}</span>
                    <button @click="cartStore.updateQuantity(item.cart_id, item.quantity + 1)" class="w-5 h-5 rounded-md bg-white shadow-sm flex items-center justify-center font-bold text-slate-600 text-[10px] active:scale-90 transition-transform">+</button>
                  </div>
              </div>
              
              <div class="flex justify-end mt-1">
                <p class="text-primary-600 font-bold text-xs whitespace-nowrap">{{ formatCurrency(item.price * item.quantity) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Methods (Grid) -->
      <div v-if="cartStore.cart.length > 0">
        <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest px-1 mb-2">Metode Pembayaran</h2>
        
        <div v-if="loading" class="py-8 text-center text-slate-400 text-xs">
          <div class="animate-spin w-5 h-5 border-2 border-primary-500 border-t-transparent rounded-full mx-auto mb-2"></div>
          Memuat metode pembayaran...
        </div>
        
        <div v-else class="grid grid-cols-3 gap-2">
          <button
            v-for="method in paymentMethods"
            :key="method.id"
            @click="handlePaymentSelection(method)"
            class="p-2.5 border-2 rounded-xl flex flex-col items-center justify-center gap-1.5 transition-all h-20 group relative"
            :class="selectedPaymentMethod === method.id 
              ? 'border-primary-500 bg-primary-50/50 text-primary-700 shadow-sm' 
              : 'border-slate-100 bg-white hover:border-slate-200 text-slate-400'"
          >
            <div 
              class="transition-transform duration-300 group-hover:scale-110 [&>svg]:w-5 [&>svg]:h-5" 
              :class="selectedPaymentMethod === method.id ? 'text-primary-500' : 'text-slate-300'"
              v-html="getPaymentIcon(method)"
            ></div>
            <span class="text-[8px] font-bold uppercase tracking-tight text-center leading-tight line-clamp-2">{{ method.name }}</span>
            <div v-if="selectedPaymentMethod === method.id" class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-primary-500 rounded-full"></div>
          </button>
        </div>
      </div>

      <!-- Summary & Total - REMOVED (moved to footer) -->
    </div>
    
    <!-- Footer -->
    <div v-if="cartStore.cart.length > 0" class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-100 py-2 px-4 z-[60] shadow-[0_-4px_10px_rgba(0,0,0,0.05)] flex items-center justify-between">
      <!-- Total Section -->
      <div class="flex flex-col">
        <span class="text-[10px] font-medium text-slate-500">Total Pembayaran</span>
        <span class="text-sm font-bold text-slate-800 leading-tight">{{ formatCurrency(cartStore.subtotal) }}</span>
      </div>

      <!-- Action Button -->
      <button 
        @click="handleCheckout"
        :disabled="processing"
        class="bg-primary-500 text-white font-bold px-4 py-2 rounded-lg shadow-lg shadow-primary-500/20 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2 active:scale-[0.98] transition-transform text-xs"
      >
        <svg v-if="processing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>{{ processing ? 'Memproses...' : 'Buat Pesanan' }}</span>
      </button>
    </div>
  </div>
</template>
