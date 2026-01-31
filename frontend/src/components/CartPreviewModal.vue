<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import LazyImage from './LazyImage.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const router = useRouter()
const cartStore = useCartStore()

function formatCurrency(value) {
  const val = Number(value) || 0
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(val)
}

function handleCheckout() {
  router.push('/customer/cart')
  emit('close')
}

function handleClearAll() {
  cartStore.clearCart()
  emit('close')
}
</script>

<template>
  <div class="fixed inset-0 z-[45] pointer-events-none flex flex-col justify-end">
    <!-- Backdrop -->
    <Transition name="fade">
      <div 
        v-if="show" 
        class="absolute inset-0 bg-black/40 backdrop-blur-[2px] pointer-events-auto"
        @click="emit('close')"
      ></div>
    </Transition>

    <!-- Modal Content -->
    <Transition name="slide-up">
      <div 
        v-if="show" 
        class="relative w-full bg-white rounded-t-2xl shadow-[0_-8px_30px_rgba(0,0,0,0.12)] pointer-events-auto flex flex-col max-h-[85vh] mb-0"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
          <h3 class="font-medium text-slate-800 text-[10px]">{{ cartStore.totalItems }} Item</h3>
          <button 
            @click="handleClearAll"
            class="flex items-center gap-1 text-slate-400 hover:text-red-500 transition-colors text-[10px] font-medium"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
            </svg>
            Hapus Semua
          </button>
        </div>

        <!-- Scrollable List -->
        <div class="overflow-y-auto px-4 py-2 pb-28 space-y-3 min-h-[100px] flex-1">
          <div v-for="item in cartStore.cart" :key="item.cart_id" class="flex gap-3">
             <!-- Image -->
             <div class="w-12 h-12 shrink-0 bg-slate-50 rounded-lg overflow-hidden border border-slate-100">
                <LazyImage 
                  v-if="item.image" 
                  :src="`/storage/${item.image}`" 
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-lg">📦</div>
             </div>

             <!-- Details -->
             <div class="flex-1 min-w-0 flex flex-col justify-between">
                <div>
                  <h4 class="font-bold text-slate-800 text-xs line-clamp-1">{{ item.name }}</h4>
                  <p class="text-[9px] text-slate-500 line-clamp-1">{{ item.type === 'package' ? 'Paket Hemat' : 'Satuan' }}</p>
                </div>
                
                <div class="flex items-center justify-between mt-0.5">
                   <div class="font-bold text-primary-600 text-xs">{{ formatCurrency(item.price) }}</div>
                   
                   <!-- Qty Control -->
                   <div class="flex items-center gap-2">
                      <button 
                        @click="cartStore.updateQuantity(item.cart_id, item.quantity - 1)"
                        class="w-5 h-5 rounded-full border border-slate-200 flex items-center justify-center text-slate-400 hover:text-primary-600 hover:border-primary-600 transition-all"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-2.5 h-2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                        </svg>
                      </button>
                      <span class="text-xs font-bold text-slate-800 w-4 text-center">{{ item.quantity }}</span>
                      <button 
                        @click="cartStore.updateQuantity(item.cart_id, item.quantity + 1)"
                        class="w-5 h-5 rounded-full bg-primary-500 text-white flex items-center justify-center hover:bg-primary-600 transition-all shadow-sm"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-2.5 h-2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                        </svg>
                      </button>
                   </div>
                </div>
             </div>
          </div>
          
          <div v-if="cartStore.cart.length === 0" class="flex flex-col items-center justify-center py-8 text-slate-400">
            <div class="text-3xl mb-1">🛒</div>
            <p class="text-xs">Keranjang kosong</p>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}
</style>
