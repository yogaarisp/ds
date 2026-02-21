<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api, { STORAGE_BASE_URL } from '@/api'
import LazyImage from '@/components/LazyImage.vue'
import CombinationDetailModal from '@/components/CombinationDetailModal.vue'
import CartPreviewModal from '@/components/CartPreviewModal.vue'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const cartStore = useCartStore()
const authStore = useAuthStore()

const categories = [
  { id: 'mix1', name: 'Mix 1 Varian', type: 'package', query: 'Mix 1' },
  { id: 'mix2', name: 'Mix 2 Varian', type: 'package', query: 'Mix 2' },
  { id: 'mix3', name: 'Mix 3 Varian', type: 'package', query: 'Mix 3' },
  { id: 'mix5', name: 'Mix 5 Varian', type: 'package', query: 'Mix 5' },
  { id: 'ciraso', name: 'Ciraso', type: 'package', query: 'Ciraso' },
  { id: 'paket5', name: 'Paket 5 Pcs', type: 'package', query: 'Paket 5' },
  { id: 'satuan', name: 'Paket Satuan', type: 'products', query: '' },
]

const activeCategory = ref(categories[0])
const packages = ref([])
const products = ref([])
const loading = ref(false)
const user = ref(null)

// Modal State
const showDetailModal = ref(false)
const showCartPreview = ref(false)
const selectedCombination = ref(null)

const isMounted = ref(false)

onMounted(async () => {
  isMounted.value = true
  
  // Get user from store or local storage
  if (authStore.user) {
    user.value = authStore.user
  } else {
    const userData = localStorage.getItem('user')
    if (userData) {
      user.value = JSON.parse(userData)
    }
  }

  await fetchData()
  if (isMounted.value) {
    window.addEventListener('scroll', handleScroll)
  }
})

onUnmounted(() => {
  isMounted.value = false
  window.removeEventListener('scroll', handleScroll)
})

async function fetchData() {
  loading.value = true
  try {
    const params = { 
      active: true,
      ...(user.value ? { customer_id: user.value.id } : {})
    }
    const [pkgRes, prodRes] = await Promise.all([
      api.get('/packages', { params }),
      api.get('/products', { params })
    ])
    if (isMounted.value) {
      packages.value = pkgRes.data
      products.value = prodRes.data
    }
  } catch (error) {
    console.error('Failed to fetch data:', error)
  } finally {
    if (isMounted.value) {
      loading.value = false
    }
  }
}

const allCategoriesContent = computed(() => {
  return categories.map(cat => {
    if (cat.type === 'package') {
       const pkg = packages.value.find(p => p.name && p.name.toLowerCase().includes(cat.query.toLowerCase()))
       if (pkg) {
          const combinations = (pkg.combinations || []).map(combo => ({
            ...combo,
            _unique_key: `combo-${pkg.id}-${combo.id}`,
            original_name: combo.name,
            final_price: combo.final_price || combo.base_price,
            package_id: pkg.id // Needed for context
          }))
          return { ...cat, viewType: 'combinations', items: combinations, packageData: pkg }
       }
       return { ...cat, viewType: 'combinations', items: [] }
    } 
    
    // products or search
    let items = []
    if (cat.type === 'products') {
       items = products.value.map(p => ({ ...p, _unique_key: `prod-${cat.id}-${p.id}` }))
    } else if (cat.type === 'search') {
       const pkgs = packages.value.filter(p => p.name && p.name.toLowerCase().includes(cat.query.toLowerCase()))
       const prods = products.value.filter(p => p.name && p.name.toLowerCase().includes(cat.query.toLowerCase()))
       items = [
          ...pkgs.map(p => ({ ...p, _unique_key: `search-pkg-${p.id}` })), 
          ...prods.map(p => ({ ...p, _unique_key: `search-prod-${p.id}` }))
       ]
    }
    
    return { ...cat, viewType: 'list', items }
  })
})

function scrollToCategory(cat) {
  activeCategory.value = cat
  const el = document.getElementById(`category-${cat.id}`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
  }
}

// Scroll Spy
function handleScroll() {
  const offset = 150 // Increased offset for better trigger point
  
  // Find visible section
  for (const cat of categories) {
    const el = document.getElementById(`category-${cat.id}`)
    if (el) {
       const rect = el.getBoundingClientRect()
       
       // Check if element is in viewport (with offset)
       // rect.top <= offset means the top of the element is above the offset line
       // rect.bottom > offset means the bottom of the element is below the offset line
       if (rect.top <= offset && rect.bottom > offset) {
          if (activeCategory.value.id !== cat.id) {
            activeCategory.value = cat
            // Manual scroll for sidebar to avoid window jumping
            const sidebarContainer = document.getElementById('sidebar-container')
            const sidebarBtn = document.getElementById(`sidebar-btn-${cat.id}`)
            
            if (sidebarContainer && sidebarBtn) {
                const btnRect = sidebarBtn.getBoundingClientRect()
                const containerRect = sidebarContainer.getBoundingClientRect()
                
                // Calculate target scroll to center the button
                const relativeTop = btnRect.top - containerRect.top
                const targetScroll = sidebarContainer.scrollTop + relativeTop - (containerRect.height / 2) + (btnRect.height / 2)
                
                sidebarContainer.scrollTo({
                    top: targetScroll,
                    behavior: 'smooth'
                })
            }
          }
          break
       }
    }
  }
}

// Mix Logic Helpers
function openDetail(combination, pkg) {
  selectedCombination.value = combination
  // We need to ensure we have the package ID.
  // combination.package_id was added in computed
  showDetailModal.value = true
}

function handleAddToCartFromModal(item) {
   // Find the package context from selectedCombination or similar
   // We stored package_id in the item/combination
   // But wait, handleAddToCartFromModal receives 'item' which is the emitted object from modal.
   // The modal emits { ...combination, quantity, ... }
   
   // We need to find the package ID. 
   // In the new logic, 'selectedCombination' has 'package_id'.
   
   console.log('🛒 Add to cart from modal - Auth & Table check:', {
     isAuthenticated: authStore.isAuthenticated,
     hasTable: cartStore.hasTable,
     tableCode: cartStore.tableCode,
     tableName: cartStore.tableName
   })
   
   // REMOVED AUTH CHECK - Let user add to cart first, check auth at checkout
   // This is more user-friendly for QR scan flow
   
   console.log('✅ Adding package to cart from modal')
   cartStore.addItem({
      type: 'package',
      id: selectedCombination.value.package_id, // Use stored package_id
      package_combination_id: item.id,
      name: item.name, 
      price: item.price,
      image: item.image,
      quantity: item.quantity,
      is_frozen: item.is_frozen
   })
   showDetailModal.value = false
   selectedCombination.value = null
}

function addToCart(item, type = 'product') {
  console.log('🛒 Add to cart - Auth & Table check:', {
    isAuthenticated: authStore.isAuthenticated,
    hasTable: cartStore.hasTable,
    tableCode: cartStore.tableCode,
    tableName: cartStore.tableName
  })
  
  // REMOVED AUTH CHECK - Let user add to cart first, check auth at checkout
  // This is more user-friendly for QR scan flow

  console.log('✅ Adding product to cart')
  if (type === 'product') {
    const price = item.final_price ?? item.base_price
    cartStore.addItem({
      type: 'product',
      id: item.id,
      name: item.name,
      price: price,
      image: item.image,
      quantity: 1
    })
    // alert('Produk ditambahkan ke keranjang')
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

function goToCheckout() {
  console.log('🛒 Go to checkout - Auth & Table check:', {
    isAuthenticated: authStore.isAuthenticated,
    hasTable: cartStore.hasTable,
    tableCode: cartStore.tableCode,
    tableName: cartStore.tableName,
    cartItems: cartStore.cart.length
  })
  
  // REMOVED AUTH CHECK - Let user go to cart, check auth there
  // Cart page will handle auth prompt if needed
  
  console.log('✅ Navigating to cart')
  showCartPreview.value = false
  router.push('/customer/cart')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Sidebar -->
    <div id="sidebar-container" class="fixed left-0 top-0 bottom-0 w-[72px] sm:w-[80px] overflow-y-auto bg-slate-50 dark:bg-slate-900 z-30 pb-24 scrollbar-hide border-r border-slate-100 dark:border-slate-800">
      <div class="flex flex-col gap-2 py-2">
        <button
          v-for="cat in categories"
          :key="cat.id"
          :id="`sidebar-btn-${cat.id}`"
          @click="scrollToCategory(cat)"
          class="flex flex-col items-center justify-center py-2 px-1 transition-all relative rounded-r-xl mx-0"
          :class="activeCategory.id === cat.id ? 'bg-white dark:bg-slate-800 text-primary-600 font-bold shadow-sm z-10' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-400'"
        >
          <!-- Active Indicator (Hidden or stylized differently) -->
          <div v-if="activeCategory.id === cat.id" class="absolute left-0 top-1/2 -translate-y-1/2 h-8 w-1 bg-primary-500 rounded-r-full"></div>
          
          <span class="text-[10px] text-center font-medium leading-tight line-clamp-2 w-full px-1 py-2" :class="activeCategory.id === cat.id ? 'text-primary-700' : 'text-slate-500 dark:text-slate-400'">{{ cat.name }}</span>
        </button>
      </div>
    </div>

    <!-- Content Area -->
    <div class="bg-white dark:bg-slate-900 relative scroll-smooth ml-[72px] sm:ml-[80px] min-h-screen" id="menu-content">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center min-h-screen">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="pb-24">
        
        <div 
           v-for="section in allCategoriesContent" 
           :key="section.id"
           :id="`category-${section.id}`"
           class="scroll-mt-4"
        >
            <!-- Header -->
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2 px-3 pt-3 sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-20 py-3 shadow-sm">
              {{ section.name }}
              <!-- Table Badge -->
              <span v-if="cartStore.hasTable && section.id === allCategoriesContent[0]?.id" class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
                </svg>
                {{ cartStore.tableName }}
              </span>
            </h2>

            <!-- MIX / PACKAGE VIEW -->
            <div v-if="section.viewType === 'combinations'" class="space-y-6">
              
              <!-- Variants List -->
              <div class="space-y-0">
                <div 
                  v-for="combo in section.items" 
                  :key="combo._unique_key || combo.id" 
                  @click="openDetail(combo, section.packageData)"
                  class="flex gap-3 py-2 px-2 rounded-xl border border-transparent bg-white dark:bg-slate-800 transition-all cursor-pointer active:scale-[0.98]"
                >
                   <!-- Image -->
                   <div class="w-20 h-20 shrink-0 bg-slate-50 dark:bg-slate-700 rounded-lg overflow-hidden border border-transparent">
                      <LazyImage 
                        v-if="combo.image" 
                        :src="`${STORAGE_BASE_URL}/${combo.image}`" 
                        class="w-full h-full object-cover"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center text-2xl">📦</div>
                      <!-- Sold Out Overlay -->
                      <div v-if="combo.stock <= 0" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] flex items-center justify-center z-10">
                        <span class="text-[10px] font-black text-slate-400 border border-slate-300 px-2 py-1 rounded-md rotate-[-12deg] tracking-widest uppercase bg-white/80 shadow-sm text-center">SOLD OUT</span>
                      </div>
                   </div>

                   <!-- Details -->
                   <div class="flex-1 min-w-0 flex flex-col justify-between">
                     <div>
                       <h3 class="font-bold text-slate-800 dark:text-slate-100 text-xs mb-1">{{ combo.name }}</h3>
                       <div class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2">
                         <span v-for="(item, idx) in combo.items" :key="idx">
                           {{ item.product?.name }} ({{ item.quantity }}){{ idx < combo.items.length - 1 ? ', ' : '' }}
                         </span>
                       </div>
                     </div>
                     
                     <div class="flex items-end justify-between mt-2">
                       <div class="font-bold text-primary-600 text-xs">{{ formatCurrency(combo.final_price) }}</div>
                       
                       <!-- Add Button -->
                       <button 
                          @click.stop="combo.stock > 0 ? openDetail(combo, section.packageData) : null"
                          class="h-8 w-8 flex items-center justify-center bg-primary-50 text-primary-600 text-lg font-bold rounded-lg border border-primary-100 active:scale-95 disabled:opacity-50 disabled:grayscale disabled:pointer-events-none"
                          :disabled="combo.stock <= 0"
                        >
                          +
                        </button>
                     </div>
                   </div>
                </div>
                
                <div v-if="!section.items.length" class="text-center py-10 text-slate-400 dark:text-slate-500">
                   <div class="text-4xl mb-2">🤔</div>
                   <p>Belum ada varian tersedia</p>
                </div>
              </div>
            </div>

            <!-- PRODUCTS VIEW (Paket Satuan) -->
            <div v-else-if="section.viewType === 'list'" class="grid grid-cols-1 gap-0">
               <div v-for="product in section.items" :key="product._unique_key || product.id" class="flex gap-3 py-2 px-2 rounded-xl border border-transparent bg-white dark:bg-slate-800 transition-all">
                   <!-- Image -->
                   <div class="w-20 h-20 shrink-0 bg-slate-50 dark:bg-slate-700 rounded-lg overflow-hidden border border-transparent relative">
                      <LazyImage 
                        v-if="product.image" 
                        :src="`${STORAGE_BASE_URL}/${product.image}`" 
                        class="w-full h-full object-cover"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center text-2xl">🥟</div>
                      <!-- Sold Out Overlay -->
                      <div v-if="product.stock <= 0" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] flex items-center justify-center z-10">
                        <span class="text-[10px] font-black text-slate-400 border border-slate-300 px-2 py-1 rounded-md rotate-[-12deg] tracking-widest uppercase bg-white/80 shadow-sm text-center">SOLD OUT</span>
                      </div>
                   </div>

                   <!-- Details -->
                   <div class="flex-1 min-w-0 flex flex-col justify-between">
                     <div>
                       <h3 class="font-bold text-slate-800 dark:text-slate-100 text-xs mb-1">{{ product.name }}</h3>
                       <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-2">{{ product.description || 'Enak dan bergizi' }}</p>
                     </div>
                     
                     <div class="flex items-end justify-between mt-2">
                       <div class="font-bold text-primary-600 text-xs">{{ formatCurrency(product.final_price ?? product.base_price) }}</div>
                       <button 
                          @click="product.stock > 0 ? addToCart(product, 'product') : null"
                          class="h-8 w-8 flex items-center justify-center bg-primary-50 text-primary-600 text-lg font-bold rounded-lg border border-primary-100 active:scale-95 disabled:opacity-50 disabled:grayscale disabled:pointer-events-none"
                          :disabled="product.stock <= 0"
                        >
                          +
                        </button>
                     </div>
                   </div>
               </div>
               
               <div v-if="!section.items.length" class="text-center py-10 text-slate-400 dark:text-slate-500">
                 <p>Tidak ada produk ditemukan</p>
               </div>
            </div>

        </div>

      </div>
    </div>

    <!-- Detail Modal -->
    <CombinationDetailModal
      v-if="showDetailModal && selectedCombination"
      :show="showDetailModal"
      :combination="selectedCombination"
      @close="showDetailModal = false; selectedCombination = null"
      @add-to-cart="handleAddToCartFromModal"
    />

    <!-- Cart Preview Modal -->
    <CartPreviewModal
      :show="showCartPreview"
      @close="showCartPreview = false"
    />

    <!-- Floating Cart Bar -->
    <Transition name="slide-up">
      <div 
        v-if="cartStore.totalItems > 0 && !showDetailModal" 
        class="fixed bottom-[52px] lg:bottom-0 left-0 right-0 bg-white dark:bg-slate-800 shadow-[0_-4px_10px_rgba(0,0,0,0.05)] dark:shadow-[0_-4px_10px_rgba(0,0,0,0.2)] border-t border-slate-100 dark:border-slate-700 py-2 px-4 flex items-center justify-between z-[60] cursor-pointer transition-transform"
        @click="showCartPreview = !showCartPreview"
      >
        <div class="flex items-center gap-3">
          <!-- Icon Badge -->
          <div class="relative">
             <div class="w-8 h-8 bg-orange-50 dark:bg-orange-900/30 rounded-lg flex items-center justify-center text-orange-500 dark:text-orange-400">
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                 <path fill-rule="evenodd" d="M7.5 6v.75H5.513c-.96 0-1.764.724-1.865 1.679l-1.263 12A1.875 1.875 0 004.25 22.5h15.5a1.875 1.875 0 001.865-2.071l-1.263-12a1.875 1.875 0 00-1.865-1.679H16.5V6a4.5 4.5 0 10-9 0zM12 3a3 3 0 00-3 3v.75h6V6a3 3 0 00-3-3zm-3 8.25a3 3 0 106 0v-.75a.75.75 0 011.5 0v.75a4.5 4.5 0 11-9 0v-.75a.75.75 0 011.5 0v.75z" clip-rule="evenodd" />
               </svg>
             </div>
             <div class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold h-4 w-4 flex items-center justify-center rounded-full border-2 border-white dark:border-slate-800">
               {{ cartStore.totalItems }}
             </div>
          </div>
          
          <!-- Price -->
          <div class="flex flex-col">
            <span class="text-sm font-bold text-slate-800 dark:text-slate-100 leading-tight">{{ formatCurrency(cartStore.subtotal) }}</span>
          </div>
        </div>

        <!-- Checkout Button -->
        <button 
          @click.stop="goToCheckout"
          class="bg-primary-500 text-white px-3 py-1.5 rounded-lg font-bold text-xs shadow-lg shadow-primary-500/20 active:scale-95 transition-transform"
        >
          Checkout
        </button>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Slide Up Animation */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}
</style>
