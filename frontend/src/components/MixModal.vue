<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { STORAGE_BASE_URL } from '../api'
import LazyImage from './LazyImage.vue'

const props = defineProps({
  package: Object,
})

const emit = defineEmits(['close', 'select'])

const servingType = ref('hangat') // hangat | frozen
const quantities = ref({}) // Track quantity for each combination

function getQty(combinationId) {
  return quantities.value[combinationId] || 1
}

function setQty(combinationId, value) {
  const maxStock = props.package.combinations?.find(c => c.id === combinationId)?.stock || 99
  quantities.value[combinationId] = Math.max(1, Math.min(value, maxStock))
}

function incrementQty(combinationId) {
  setQty(combinationId, getQty(combinationId) + 1)
}

function decrementQty(combinationId) {
  setQty(combinationId, getQty(combinationId) - 1)
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}

const displayedCombinations = computed(() => {
  if (!props.package.combinations) return []
  
  return props.package.combinations.map(combo => {
    const isFrozen = servingType.value === 'frozen'
    const discount = isFrozen ? 1000 : 0
    
    let currentPrice = combo.final_price || combo.base_price
    let adjustedPrice = currentPrice - discount
    
    return {
      ...combo,
      original_name: combo.name,
      name: isFrozen ? `${combo.name} (Frozen)` : combo.name,
      final_price: adjustedPrice,
      is_frozen: isFrozen
    }
  })
})

function handleSelect(combination) {
  const qty = getQty(combination.id)
  emit('select', { ...combination, quantity: qty })
}

onMounted(() => {
  document.body.style.overflow = 'hidden'
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center z-50 sm:p-4 backdrop-blur-sm transition-all duration-300 overflow-hidden">
    <div class="bg-white w-full h-full sm:h-auto sm:max-w-md sm:max-h-[90vh] flex flex-col sm:rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up">
      
      <!-- Simplified Modern Header -->
      <div class="px-6 pt-5 pb-1 sticky top-0 bg-white border-b border-slate-50 z-10">
        <div class="flex flex-col items-center text-center relative">
          <!-- Back button on top-left -->
          <button 
            @click="emit('close')" 
            class="absolute -top-1 -left-1 w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all active:scale-90 border border-slate-100 shadow-sm"
            title="Kembali"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <!-- Centered Title Section -->
          <div class="mb-1.5">
            <h2 class="text-base font-bold text-slate-900 tracking-tight leading-none mb-1">{{ package.name }}</h2>
            <div class="flex items-center justify-center gap-1">
              <span class="w-1 h-1 rounded-full bg-primary-500 animate-pulse"></span>
              <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Pilih Varian Favorit</p>
            </div>
          </div>

          <!-- Minimal Segmented Toggle -->
          <div class="w-full flex p-1 bg-slate-50 rounded-2xl relative overflow-hidden border border-slate-100">
            <div 
              class="absolute top-1 bottom-1 w-[calc(50%-4px)] rounded-xl transition-all duration-500 cubic-bezier(0.4, 0, 0.2, 1) shadow-sm"
              :class="[
                servingType === 'hangat' 
                  ? 'left-1 bg-white' 
                  : 'left-[calc(50%+2px)] bg-white'
              ]"
            ></div>
            
            <button
              @click="servingType = 'hangat'"
              class="flex-1 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all duration-300 relative z-10 flex items-center justify-center gap-1.5"
              :class="servingType === 'hangat' ? 'text-primary-600' : 'text-slate-400 hover:text-slate-500'"
            >
              <span class="text-[10px] transition-transform duration-300" :class="{ 'scale-125 rotate-12': servingType === 'hangat' }">🔥</span> 
              Hangat
            </button>
            <button
              @click="servingType = 'frozen'"
              class="flex-1 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all duration-300 relative z-10 flex items-center justify-center gap-1.5"
              :class="servingType === 'frozen' ? 'text-blue-600' : 'text-slate-400 hover:text-slate-500'"
            >
              <span class="text-[10px] transition-transform duration-300" :class="{ 'scale-125 -rotate-12': servingType === 'frozen' }">❄️</span> 
              Frozen
              <span class="text-[7px] px-1 py-0.5 rounded-md font-bold ml-0.5 shadow-sm" :class="servingType === 'frozen' ? 'bg-blue-50 text-blue-600' : 'bg-slate-200 text-slate-500'">-1RB</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Polished Combinations List -->
      <div class="flex-1 overflow-auto px-6 py-2 space-y-1.5 custom-scrollbar bg-white">
        <div
          v-for="combination in displayedCombinations"
          :key="combination.id"
          class="w-full bg-white border border-slate-200/50 p-2 rounded-xl text-left hover:border-primary-200 hover:shadow-lg transition-all duration-300 group relative"
        >
          
          <div class="flex items-center gap-3 relative z-10">
            <!-- Compact Image -->
            <div class="w-10 h-10 flex-shrink-0 bg-slate-50 rounded-lg overflow-hidden border border-slate-100">
              <LazyImage 
                v-if="combination.image" 
                :src="`${STORAGE_BASE_URL}/${combination.image}`" 
                :alt="combination.name"
                placeholder="📦"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-base">📦</div>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <h3 class="font-bold text-slate-800 text-xs truncate group-hover:text-primary-600 transition-colors leading-tight">{{ combination.name }}</h3>
              <div class="flex flex-wrap gap-1 mt-0.5">
                <span
                  v-for="item in combination.items?.slice(0, 2)"
                  :key="item.id"
                  class="text-[10px] text-slate-500 font-medium"
                >
                  {{ item.product?.name }} x{{ item.quantity }}
                </span>
                <span v-if="combination.items?.length > 2" class="text-[10px] text-slate-400">+{{ combination.items.length - 2 }}</span>
              </div>
            </div>
            
            <!-- Price -->
            <div class="text-right flex-shrink-0">
              <p class="text-sm font-bold text-primary-600">Rp {{ formatPrice(combination.final_price) }}</p>
              <p class="text-[9px] text-slate-400 font-medium italic">Stok {{ combination.stock }}</p>
            </div>
          </div>

          <!-- Quantity Controls -->
          <div class="flex items-center justify-between mt-1.5 pt-1.5 border-t border-slate-50 relative z-10">
            <div class="flex items-center gap-2">
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Qty</span>
              <div class="flex items-center bg-slate-50 rounded-lg p-0.5 border border-slate-100">
                <button 
                  @click.stop="decrementQty(combination.id)"
                  class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-red-500 hover:shadow-sm transition-all active:scale-90"
                  :disabled="getQty(combination.id) <= 1"
                  :class="{ 'opacity-30 cursor-not-allowed': getQty(combination.id) <= 1 }"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4" /></svg>
                </button>
                <span class="w-8 text-center font-bold text-slate-700 text-xs">{{ getQty(combination.id) }}</span>
                <button 
                  @click.stop="incrementQty(combination.id)"
                  class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-primary-500 hover:shadow-sm transition-all active:scale-90"
                  :disabled="getQty(combination.id) >= combination.stock"
                  :class="{ 'opacity-30 cursor-not-allowed': getQty(combination.id) >= combination.stock }"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                </button>
              </div>
            </div>
            <button 
              @click.stop="handleSelect(combination)"
              class="px-4 py-1.5 bg-gradient-to-r from-primary-500 to-orange-500 text-white text-[10px] font-bold uppercase tracking-wider rounded-xl shadow-lg shadow-primary-500/20 hover:shadow-xl hover:shadow-primary-500/30 transition-all active:scale-95"
            >
              + TAMBAH
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!package.combinations?.length" class="text-center py-12">
          <span class="text-3xl opacity-30">🍱</span>
          <p class="text-xs font-bold text-slate-300 mt-2">Varian Kosong</p>
        </div>
      </div>
      

    </div>
  </div>
</template>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 2px;
}
</style>
