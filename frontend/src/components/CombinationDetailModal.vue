<script setup>
import { ref, computed } from 'vue'
import { STORAGE_BASE_URL } from '../api'
import LazyImage from './LazyImage.vue'

const props = defineProps({
  combination: {
    type: Object,
    required: true
  },
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'add-to-cart'])

const servingType = ref('hangat')
const quantity = ref(1)

// Reset state when modal opens/changes
const init = () => {
  servingType.value = 'hangat'
  quantity.value = 1
}

// Compute dynamic details based on serving type
const currentDetails = computed(() => {
  const isFrozen = servingType.value === 'frozen'
  const discount = isFrozen ? 1000 : 0
  const basePrice = props.combination.final_price || props.combination.base_price
  const price = basePrice - discount
  
  return {
    name: isFrozen ? `${props.combination.name} (Frozen)` : props.combination.name,
    price: price,
    is_frozen: isFrozen
  }
})

function incrementQty() {
  if (quantity.value < (props.combination.stock || 99)) {
    quantity.value++
  }
}

function decrementQty() {
  if (quantity.value > 1) {
    quantity.value--
  }
}

function handleAdd() {
  emit('add-to-cart', {
    ...props.combination,
    ...currentDetails.value,
    quantity: quantity.value
  })
  emit('close')
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}
</script>

<template>
  <div class="fixed inset-0 z-[60] flex items-end justify-center sm:items-center pointer-events-none">
    <!-- Backdrop -->
    <Transition name="fade" mode="out-in">
      <div v-if="show" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity pointer-events-auto" @click="emit('close')"></div>
    </Transition>
    
    <!-- Modal Content -->
    <Transition name="slide-up" mode="out-in">
      <div v-if="show" class="relative w-full sm:max-w-sm bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden pointer-events-auto max-h-[90vh] flex flex-col">
        <!-- Close Button -->
        <button 
          @click="emit('close')"
          class="absolute top-3 right-3 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-black/20 text-white hover:bg-black/30 backdrop-blur-md transition-all"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>

        <!-- Scrollable Content -->
        <div class="overflow-y-auto">
          <!-- Image -->
          <div class="h-56 w-full bg-slate-100 relative">
            <LazyImage 
              v-if="combination.image" 
              :src="`${STORAGE_BASE_URL}/${combination.image}`" 
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-4xl">📦</div>
            <!-- Gradient Overlay for text readability if needed -->
            <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-black/50 to-transparent sm:hidden"></div>
          </div>

          <!-- Content -->
          <div class="p-4">
            <h3 class="text-lg font-bold text-slate-800 mb-1">{{ combination.name }}</h3>
            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
               <span v-for="(item, idx) in combination.items" :key="idx">
                 {{ item.product?.name }} ({{ item.quantity }}){{ idx < combination.items.length - 1 ? ', ' : '' }}
               </span>
            </p>
          </div>
        </div>

        <!-- Sticky Footer: Price & Add -->
        <div class="p-4 bg-white border-t border-slate-100 mt-auto space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Total Harga</p>
              <p class="text-xl font-bold text-primary-600">{{ formatCurrency(currentDetails.price * quantity) }}</p>
            </div>

             <!-- Qty -->
             <div class="flex items-center bg-slate-50 rounded-xl border border-slate-200 h-9 px-1">
                <button 
                  @click="decrementQty"
                  class="w-8 h-full flex items-center justify-center text-slate-400 hover:text-red-500 transition-colors text-base font-bold"
                  :disabled="quantity <= 1"
                >
                  -
                </button>
                <span class="w-8 text-center font-bold text-slate-700 text-sm">{{ quantity }}</span>
                <button 
                  @click="incrementQty"
                  class="w-8 h-full flex items-center justify-center text-slate-400 hover:text-primary-500 transition-colors text-base font-bold"
                  :disabled="quantity >= (combination.stock || 99)"
                >
                  +
                </button>
             </div>
          </div>

           <!-- Add Button -->
           <button 
             @click="handleAdd"
             class="w-full h-11 bg-primary-500 text-white font-bold text-sm rounded-xl shadow-lg shadow-primary-500/30 active:scale-95 transition-all flex items-center justify-center gap-2"
           >
             <span>Masuk Keranjang</span>
           </button>
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
