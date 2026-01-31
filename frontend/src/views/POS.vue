<script setup>
import { ref, onMounted, onUnmounted, watch, computed, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { usePosStore } from '../stores/pos'
import { customerApi } from '../api'
import MixModal from '../components/MixModal.vue'
import CheckoutModal from '../components/CheckoutModal.vue'
import QuickAddCustomerModal from '../components/QuickAddCustomerModal.vue'
import OrderPreviewModal from '../components/OrderPreviewModal.vue'
import PendingChoiceModal from '../components/PendingChoiceModal.vue'
import LazyImage from '../components/LazyImage.vue'
import Skeleton from '../components/Skeleton.vue'
import Swal from 'sweetalert2'

const router = useRouter()
const posStore = usePosStore()

const showMixModal = ref(false)
const selectedPackage = ref(null)
const showCheckoutModal = ref(false)
const showOrderPreview = ref(false)
const activeTab = ref('packages') // products | packages
const searchQuery = ref('') // Product Search Query

// Customer search
const customerSearch = ref('')
const customerResults = ref([])
const showCustomerDropdown = ref(false)
const showQuickAddModal = ref(false)
const customerSearchLoading = ref(false)
const customerSearchInput = ref(null)

function getCustomerLevelLabel(level) {
  const labels = {
    normal: 'Reguler',
    reseller: 'Reseller', 
    keluarga: 'Keluarga'
  }
  return labels[level] || 'Reguler'
}


// Watch store's selected customer to keep search input in sync
watch(() => posStore.selectedCustomer, (newCustomer) => {
  if (newCustomer) {
    customerSearch.value = `${newCustomer.name} - ${newCustomer.phone}`
  } else {
    customerSearch.value = ''
  }
})

// Reactive screen size detection
const isMobile = ref(false)
let mediaQuery = null

function updateIsMobile(e) {
  isMobile.value = !e.matches
}

onMounted(() => {
  // Setup media query for responsive detection
  mediaQuery = window.matchMedia('(min-width: 1024px)')
  isMobile.value = !mediaQuery.matches
  mediaQuery.addEventListener('change', updateIsMobile)
  
  // Sync customer search input with store
  if (posStore.selectedCustomer) {
    customerSearch.value = `${posStore.selectedCustomer.name} - ${posStore.selectedCustomer.phone}`
  }
  
  // Delay data loading to ensure Vue has fully mounted and stabilized
  // This prevents DOM patching errors during initial render
  setTimeout(() => {
    posStore.loadData()
  }, 50)
  
  // Add click outside listener
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  if (mediaQuery) {
    mediaQuery.removeEventListener('change', updateIsMobile)
  }
  
  // Clear POS state only if moving to a non-POS/Cart page
  const destination = router.currentRoute.value.path
  if (destination !== '/pos' && destination !== '/cart') {
    posStore.refreshData()
  }
})

async function searchCustomers() {
  // Jika customer sudah dipilih, jangan search
  if (posStore.selectedCustomer) {
    return
  }
  
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
    showCustomerDropdown.value = true // Still show dropdown for "Add New" option
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

function handleClickOutside(event) {
  // Close dropdown when clicking outside
  if (!event.target.closest('.customer-search-container')) {
    showCustomerDropdown.value = false
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
      if (customerSearch.value.length >= 2) {
        showCustomerDropdown.value = true
      }
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

function openMixModal(pkg) {
  selectedPackage.value = pkg
  showMixModal.value = true
}

function selectCombination(combination) {
  posStore.addPackageCombination(selectedPackage.value, combination)
  showMixModal.value = false
}

const itemQuantities = ref({}) // local state for items NOT in cart

function getItemQty(type, id) {
  // Check cart first
  const cartItem = posStore.cart.find(item => {
    if (type === 'product') return item.type === 'product' && item.product_id === id
    if (type === 'packaging') return item.type === 'inventory' && item.inventory_id === id
    return false
  })
  
  if (cartItem) return cartItem.quantity
  
  const key = `${type}_${id}`
  return itemQuantities.value[key] || 1
}

function setItemQty(type, id, val, max) {
  let qty = parseInt(val)
  if (isNaN(qty)) qty = 0
  qty = Math.max(0, Math.min(qty, max || 999))
  
  const cartIndex = posStore.cart.findIndex(item => {
    if (type === 'product') return item.type === 'product' && item.product_id === id
    if (type === 'packaging') return item.type === 'inventory' && item.inventory_id === id
    return false
  })

  if (cartIndex >= 0) {
    if (qty === 0) {
      posStore.removeCartItem(cartIndex)
    } else {
      posStore.updateCartItemQuantity(cartIndex, qty)
    }
  } else {
    const key = `${type}_${id}`
    itemQuantities.value[key] = Math.max(1, qty)
  }
}

function adjustItemQty(type, id, delta, max) {
  const current = getItemQty(type, id)
  setItemQty(type, id, current + delta, max)
}

function isItemInCart(type, id) {
  return posStore.cart.some(item => {
    if (type === 'product') return item.type === 'product' && item.product_id === id
    if (type === 'packaging') return item.type === 'inventory' && item.inventory_id === id
    return false
  })
}

function addProductToCart(product) {
  const qty = getItemQty('product', product.id)
  posStore.addProduct(product, qty)
  // Reset local qty
  itemQuantities.value[`product_${product.id}`] = 1
}

function addPackagingToCart(item) {
  const qty = getItemQty('packaging', item.id)
  posStore.addInventoryItem(item, qty)
  // Reset local qty
  itemQuantities.value[`packaging_${item.id}`] = 1
}

function goToCart() {
  router.push('/cart')
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
        popup: 'rounded-2xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
    
    // Clear semua dan refresh untuk transaksi baru
    await posStore.refreshData()
  } catch (error) {
    console.error('Error creating order:', error)
    await Swal.fire({
      icon: 'error',
      title: 'Gagal Membuat Pesanan',
      text: error.message || 'Terjadi kesalahan saat membuat pesanan',
      width: '300px',
      customClass: {
        popup: 'rounded-2xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}

// Filtered Lists
const filteredProducts = computed(() => {
  if (!searchQuery.value) return posStore.products
  const query = searchQuery.value.toLowerCase()
  return posStore.products.filter(p => p.name.toLowerCase().includes(query))
})

const filteredPackages = computed(() => {
  if (!searchQuery.value) return posStore.packages
  const query = searchQuery.value.toLowerCase()
  return posStore.packages.filter(p => p.name.toLowerCase().includes(query))
})

const filteredPackagingItems = computed(() => {
  if (!searchQuery.value) return posStore.packagingItems
  const query = searchQuery.value.toLowerCase()
  return posStore.packagingItems.filter(p => p.name.toLowerCase().includes(query))
})
</script>

<template>
  <div>
    <!-- ============================================== -->
    <!-- MOBILE VIEW (Dedicated Layout)                 -->
    <!-- ============================================== -->
    <!-- Using v-if to render only one view at a time, preventing DOM patching conflicts -->
    <div v-if="isMobile" class="bg-[#f8fafc] min-h-screen">
      <!-- Header: Tabs & Search (Now Sticky) -->
      <div class="bg-white border-b border-slate-100 shadow-sm px-2 py-3 space-y-3 sticky top-[67px] z-20">
        <!-- Search Input -->
        <div class="relative">
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Cari menu, paket, atau packaging..." 
            class="w-full pl-10 pr-4 py-2.5 bg-slate-100 border-none rounded-xl text-base font-semibold focus:ring-2 focus:ring-primary-500/20 focus:bg-white transition-all text-slate-700 placeholder:text-[10px] placeholder:text-slate-400"
          >
          <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
             <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
             </svg>
          </div>
          <button 
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>

        <div class="flex gap-1 overflow-x-auto scrollbar-hide">
            <button
              @click="activeTab = 'packages'"
              class="flex-1 min-w-0 py-2 px-2 rounded-xl text-xs font-bold transition-all duration-300 flex items-center justify-center gap-1 border whitespace-nowrap"
              :class="activeTab === 'packages' 
                ? 'bg-primary-50 border-primary-100 text-primary-600' 
                : 'bg-white border-transparent text-slate-500 hover:bg-slate-50'"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 shrink-0"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg> 
              <span class="truncate">Paket</span>
            </button>
            <button
              @click="activeTab = 'products'"
              class="flex-1 min-w-0 py-2 px-2 rounded-xl text-xs font-bold transition-all duration-300 flex items-center justify-center gap-1 border whitespace-nowrap"
              :class="activeTab === 'products' 
                ? 'bg-primary-50 border-primary-100 text-primary-600' 
                : 'bg-white border-transparent text-slate-500 hover:bg-slate-50'"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 shrink-0"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg> 
              <span class="truncate">Satuan</span>
            </button>
            <button
              @click="activeTab = 'packaging'"
              class="flex-1 min-w-0 py-2 px-2 rounded-xl text-xs font-bold transition-all duration-300 flex items-center justify-center gap-1 border whitespace-nowrap"
              :class="activeTab === 'packaging' 
                ? 'bg-primary-50 border-primary-100 text-primary-600' 
                : 'bg-white border-transparent text-slate-500 hover:bg-slate-50'"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 shrink-0"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg> 
              <span class="truncate">Pkg</span>
            </button>
        </div>
      </div>

      <!-- Content Area (Full Height Flow) -->
      <div class="px-2 pt-2 pb-8">
        <!-- Skeleton Loading State -->
        <div v-if="posStore.loading" class="animate-fade-in">
          <div v-if="activeTab === 'packages'" class="grid grid-cols-1 gap-2">
            <div v-for="i in 5" :key="i" class="bg-white rounded-2xl p-3 border border-slate-100 shadow-sm flex gap-4">
              <Skeleton width="112px" height="112px" customClass="rounded-xl shrink-0" />
              <div class="flex-1 flex flex-col py-1 min-w-0">
                <Skeleton width="80%" height="20px" customClass="mb-3" />
                <Skeleton width="70px" height="24px" customClass="rounded-full mb-auto" />
                <div class="flex items-end justify-between mt-3">
                  <div class="space-y-1">
                    <Skeleton width="60px" height="10px" />
                    <Skeleton width="40px" height="14px" />
                  </div>
                  <Skeleton width="36px" height="36px" customClass="rounded-full" />
                </div>
              </div>
            </div>
          </div>
          <div v-else class="grid grid-cols-2 gap-3">
            <div v-for="i in 6" :key="i" class="bg-white rounded-xl p-3 border border-slate-100 shadow-sm space-y-3">
              <Skeleton width="100%" height="100px" customClass="rounded-lg" />
              <Skeleton width="80%" height="14px" />
              <div class="space-y-2 mt-auto">
                <Skeleton width="60%" height="16px" />
                <Skeleton width="40%" height="10px" />
              </div>
            </div>
          </div>
        </div>

        <!-- Content Loaded -->
        <div v-else class="animate-fade-in">
          
          <!-- PRODUCTS TAB -->
          <div v-if="activeTab === 'products'" key="products-tab">
             <div v-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-300">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-16 h-16 mb-4 opacity-50"><path d="M3 3v18h18"/><path d="M12 8V4"/><path d="M16 12V4"/><path d="M8 12V4"/><path d="M12 16v-4"/><path d="M16 16v-4"/><path d="M8 16v-4"/></svg>
                <p class="text-sm font-bold text-slate-400">Produk kosong</p>
             </div>
             <div v-else class="grid grid-cols-1 gap-2">
               <div
                  v-for="product in filteredProducts"
                  :key="product.id"
                  class="bg-white rounded-2xl p-2 shadow-[0_2px_8px_rgba(0,0,0,0.02)] border border-slate-100 flex items-center gap-3 animate-fade-in"
               >
                  <div class="w-16 h-16 shrink-0 bg-slate-50 rounded-xl flex items-center justify-center overflow-hidden border border-slate-50 relative">
                    <LazyImage v-if="product.image" :src="`/storage/${product.image}`" :alt="product.name" class="w-full h-full object-cover" />
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-slate-300"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    <div v-if="product.discount > 0" class="absolute top-0 left-0 bg-red-500 text-white text-[7px] font-black px-1 py-0.5 rounded-br-lg shadow-sm uppercase">OFF</div>
                  </div>

                  <div class="flex-1 min-w-0 py-0.5">
                    <h3 class="font-bold text-slate-800 text-xs leading-tight truncate mb-1">{{ product.name }}</h3>
                    <div class="flex items-center gap-2 mb-1">
                      <p class="text-primary-600 font-bold text-xs">Rp {{ formatPrice(product.final_price || product.base_price) }}</p>
                      <span class="text-[8px] text-slate-300 font-bold uppercase tracking-wider">Stok {{ product.stock }}</span>
                    </div>
                  </div>

                  <!-- Qty Selector Mobile: Ultra Compact & Synced -->
                  <div class="flex items-center gap-1 shrink-0">
                    <div class="flex items-center bg-slate-100/50 rounded-lg p-0.5 border border-slate-200/30">
                      <button 
                        @click="adjustItemQty('product', product.id, -1, product.stock)"
                        class="w-5 h-5 flex items-center justify-center rounded shadow-sm transition-all active:scale-95 disabled:opacity-30"
                        :class="[getItemQty('product', product.id) > 0 ? 'bg-white text-primary-600 border border-primary-50' : 'bg-transparent text-slate-300']"
                        :disabled="getItemQty('product', product.id) <= 0"
                      >
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" /></svg>
                      </button>
                      
                      <input 
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        :value="getItemQty('product', product.id)"
                        @input="e => setItemQty('product', product.id, e.target.value, product.stock)"
                        class="w-12 bg-transparent border-none text-center font-bold text-slate-700 text-[10px] p-0 focus:ring-0"
                      />

                      <button 
                        @click="adjustItemQty('product', product.id, 1, product.stock)"
                        class="w-5 h-5 flex items-center justify-center rounded bg-white border border-primary-50 text-primary-600 shadow-sm transition-all active:scale-95"
                        :disabled="getItemQty('product', product.id) >= product.stock"
                      >
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" /></svg>
                      </button>
                    </div>
                    
                    <button 
                      v-if="!isItemInCart('product', product.id)"
                      @click="addProductToCart(product)"
                      class="w-6 h-6 rounded bg-gradient-to-tr from-primary-600 to-primary-500 text-white flex items-center justify-center shadow-sm active:scale-90 transition-all ml-0.5"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                    <!-- Checkmark if in cart for feedback -->
                    <div v-else class="w-6 h-6 flex items-center justify-center text-emerald-500 ml-0.5">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M20 6L9 17L4 12"/></svg>
                    </div>
                  </div>
               </div>
             </div>
          </div>

          <!-- PACKAGES TAB -->
          <div v-else-if="activeTab === 'packages'" key="packages-tab">
             <div v-if="filteredPackages.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-300">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-16 h-16 mb-4 opacity-50"><path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                <p class="text-sm font-bold text-slate-400">Paket kosong</p>
             </div>
             <div v-else class="grid grid-cols-1 gap-2">
                <button
                   v-for="pkg in filteredPackages"
                   :key="pkg.id"
                   @click="openMixModal(pkg)"
                   class="group bg-white rounded-2xl p-2 shadow-[0_2px_8px_rgba(0,0,0,0.02)] border border-slate-100 flex gap-3 text-left active:scale-[0.98] transition-all"
                >
                   <div class="w-20 h-20 shrink-0 bg-gradient-to-br from-orange-50 to-primary-50 rounded-xl flex items-center justify-center overflow-hidden relative border border-primary-50">
                      <LazyImage v-if="pkg.image" :src="`/storage/${pkg.image}`" :alt="pkg.name" :placeholder="pkg.type === 'mix' ? '🍱' : '📦'" class="w-full h-full object-cover" />
                      <span v-else class="text-2xl">{{ pkg.type === 'mix' ? '🍱' : '📦' }}</span>
                      
                      <!-- Quantity Overlay -->
                      <div class="absolute bottom-0 inset-x-0 bg-primary-600/90 backdrop-blur-[2px] text-white text-[8px] font-black text-center py-0.5">
                        {{ pkg.total_quantity }} PCS
                      </div>
                   </div>

                   <div class="flex flex-col flex-1 py-0.5 min-w-0">
                      <div class="mb-1">
                         <h3 class="font-bold text-slate-800 text-sm leading-tight truncate group-active:text-primary-600 transition-colors">{{ pkg.name }}</h3>
                      </div>
                      
                      <div class="mb-1 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 text-[8px] font-black text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full border border-orange-100 uppercase tracking-wider">
                          {{ pkg.combinations?.length || 0 }} VARIAN
                        </span>
                        <span class="text-[8px] text-slate-300 font-bold uppercase tracking-wider">Stok {{ pkg.stock }}</span>
                      </div>
                   </div>

                   <div class="shrink-0 w-8 h-8 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center border border-slate-100 group-active:bg-primary-500 group-active:text-white transition-all duration-300">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 ml-0.5"><path d="m9 18 6-6-6-6"/></svg>
                   </div>
                </button>
             </div>
          </div>

          <!-- PACKAGING TAB -->
          <div v-else-if="activeTab === 'packaging'" key="packaging-tab">
             <div v-if="filteredPackagingItems.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-400">
                <span class="text-4xl mb-3">🥡</span>
                <p class="text-sm">Packaging kosong</p>
             </div>
             <div v-else class="grid grid-cols-1 gap-2">
               <div
                  v-for="item in filteredPackagingItems"
                  :key="item.id"
                  class="bg-white rounded-2xl p-2 shadow-[0_2px_8px_rgba(0,0,0,0.02)] border border-slate-100 flex items-center gap-3 animate-fade-in"
               >
                  <div class="w-16 h-16 shrink-0 bg-slate-50 rounded-xl flex items-center justify-center overflow-hidden border border-slate-50 relative">
                    <LazyImage v-if="item.image" :src="`/storage/${item.image}`" :alt="item.name" placeholder="🥡" class="w-full h-full object-cover" />
                    <span v-else class="text-2xl">🥡</span>
                  </div>
                  
                  <div class="flex-1 min-w-0 py-0.5">
                    <h3 class="font-bold text-slate-800 text-xs leading-tight truncate mb-1">{{ item.name }}</h3>
                    <div class="flex items-center gap-2 mb-1">
                      <p class="text-primary-600 font-bold text-xs">Rp {{ formatPrice(item.selling_price || item.price || 0) }}</p>
                      <span class="text-[8px] text-slate-300 font-bold uppercase tracking-wider">Stok {{ item.stock }}</span>
                    </div>
                  </div>

                  <!-- Qty Selector Packaging Mobile: Ultra Compact & Synced -->
                  <div class="flex items-center gap-1 shrink-0">
                    <div class="flex items-center bg-slate-100/50 rounded-lg p-0.5 border border-slate-200/30">
                      <button 
                        @click="adjustItemQty('packaging', item.id, -1, item.stock)"
                        class="w-5 h-5 flex items-center justify-center rounded shadow-sm transition-all active:scale-95 disabled:opacity-30"
                        :class="[getItemQty('packaging', item.id) > 0 ? 'bg-white text-primary-600 border border-primary-50' : 'bg-transparent text-slate-300']"
                        :disabled="getItemQty('packaging', item.id) <= 0"
                      >
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" /></svg>
                      </button>
                      
                      <input 
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        :value="getItemQty('packaging', item.id)"
                        @input="e => setItemQty('packaging', item.id, e.target.value, item.stock)"
                        class="w-12 bg-transparent border-none text-center font-bold text-slate-700 text-[10px] p-0 focus:ring-0"
                      />

                      <button 
                        @click="adjustItemQty('packaging', item.id, 1, item.stock)"
                        class="w-5 h-5 flex items-center justify-center rounded bg-white border border-primary-50 text-primary-600 shadow-sm transition-all active:scale-95"
                        :disabled="getItemQty('packaging', item.id) >= item.stock"
                      >
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" /></svg>
                      </button>
                    </div>
                    
                    <button 
                      v-if="!isItemInCart('packaging', item.id)"
                      @click="addPackagingToCart(item)"
                      class="w-6 h-6 rounded bg-gradient-to-tr from-primary-600 to-primary-500 text-white flex items-center justify-center shadow-sm active:scale-90 transition-all ml-0.5"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                    <!-- Checkmark if in cart for feedback -->
                    <div v-else class="w-6 h-6 flex items-center justify-center text-emerald-500 ml-0.5">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M20 6L9 17L4 12"/></svg>
                    </div>
                  </div>
               </div>
             </div>
          </div>

        </div>
      </div>
    </div>


    <!-- ============================================== -->
    <!-- DESKTOP VIEW (Original Layout)                 -->
    <!-- ============================================== -->
    <div v-else class="flex flex-row h-screen min-h-0 bg-[#f8fafc] overflow-hidden">
      <!-- Left Side: Products & Selection -->
      <div class="flex-1 flex flex-col min-w-0 min-h-0">
        <!-- Top Navigation -->
        <div class="p-6 pb-2 shrink-0">
          <div class="flex items-center justify-between gap-4">
            
            <div class="bg-slate-200/50 p-1 rounded-xl flex gap-1 flex-1">
              <button
                @click="activeTab = 'packages'"
                class="px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-300 flex items-center gap-2"
                :class="activeTab === 'packages' 
                  ? 'bg-white text-slate-900 shadow-sm' 
                  : 'text-slate-500 hover:text-slate-900'"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg> Paket & Mix
              </button>
              <button
                @click="activeTab = 'products'"
                class="px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-300 flex items-center gap-2"
                :class="activeTab === 'products' 
                  ? 'bg-white text-slate-900 shadow-sm' 
                  : 'text-slate-500 hover:text-slate-900'"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg> Produk Satuan
              </button>
              <button
                @click="activeTab = 'packaging'"
                class="px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-300 flex items-center gap-2"
                :class="activeTab === 'packaging' 
                  ? 'bg-white text-slate-900 shadow-sm' 
                  : 'text-slate-500 hover:text-slate-900'"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg> Packaging
              </button>
            </div>

            <!-- Desktop Search Input -->
            <div class="relative w-64">
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Cari menu..." 
                class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all text-slate-700 placeholder:text-[11px] placeholder:text-slate-400"
              >
              <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                 <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                 </svg>
              </div>
              <button 
                v-if="searchQuery"
                @click="searchQuery = ''"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Desktop Content Area -->
        <div class="flex-1 overflow-y-auto px-6 pb-12 custom-scrollbar">
          <!-- Skeleton Loading State Desktop -->
          <div v-if="posStore.loading" class="animate-fade-in">
             <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 gap-3">
               <div v-for="i in 12" :key="i" class="bg-white rounded-xl p-3 border border-slate-100 shadow-sm space-y-3">
                  <Skeleton width="100%" :height="activeTab === 'packages' ? '120px' : '150px'" customClass="rounded-lg" />
                  <Skeleton width="80%" height="16px" />
                  <Skeleton width="50%" height="12px" />
                  <div class="pt-2 flex justify-between items-center border-t border-slate-50">
                    <Skeleton width="60px" height="14px" />
                    <Skeleton width="40px" height="14px" />
                  </div>
               </div>
             </div>
          </div>

          <div v-else class="animate-fade-in">
            <!-- Products Tab -->
            <div v-if="activeTab === 'products'">
              <div v-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-400">
                <span class="text-5xl mb-4">🍽️</span>
                <p class="text-lg">Produk tidak ditemukan</p>
              </div>
              <div v-else class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 gap-2.5">
                <div
                  v-for="product in filteredProducts"
                  :key="product.id"
                  class="group bg-white rounded-lg p-2.5 border border-slate-100 shadow-[0_1px_8px_rgba(0,0,0,0.02)] hover:shadow-[0_6px_20px_rgba(0,0,0,0.04)] hover:border-primary-100 transition-all duration-300 flex flex-col"
                >
                  <div class="w-full aspect-[3/2] bg-slate-50 rounded-md mb-2 flex items-center justify-center overflow-hidden relative border border-slate-50/50">
                    <LazyImage v-if="product.image" :src="`/storage/${product.image}`" :alt="product.name" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-slate-300"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    <div v-if="product.discount > 0" class="absolute top-0 right-0 bg-red-500 text-white text-[8px] font-black px-1.5 py-0.5 rounded-bl shadow-sm uppercase">OFF</div>
                    <div class="absolute bottom-1.5 left-1.5 px-1.5 py-0.5 bg-white/95 backdrop-blur-md rounded border border-slate-100 shadow-sm">
                      <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Stok {{ product.stock }}</span>
                    </div>
                  </div>

                  <div class="flex flex-col flex-1">
                    <h3 class="font-bold text-slate-800 text-[11px] leading-tight mb-0.5 group-hover:text-primary-600 transition-colors line-clamp-1 truncate w-full">{{ product.name }}</h3>
                    <p class="text-primary-600 font-black text-xs mb-2">Rp {{ formatPrice(product.final_price || product.base_price) }}</p>
                    
                    <div class="mt-auto flex items-center justify-between gap-1.5">
                       <div class="flex items-center bg-slate-100/50 rounded p-0.5 gap-0.5 border border-slate-200/30">
                          <button 
                            @click="adjustItemQty('product', product.id, -1, product.stock)"
                            class="w-6 h-6 flex items-center justify-center rounded shadow-sm transition-all active:scale-95 disabled:opacity-30"
                            :class="[getItemQty('product', product.id) > 0 ? 'bg-white text-primary-600 border border-primary-50' : 'bg-transparent text-slate-300']"
                            :disabled="getItemQty('product', product.id) <= 0"
                          >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path d="M18 12H6" /></svg>
                          </button>
                          <input 
                            type="text" inputmode="numeric" pattern="[0-9]*"
                            :value="getItemQty('product', product.id)"
                            @input="e => setItemQty('product', product.id, e.target.value, product.stock)"
                            class="w-6 bg-transparent border-none text-center font-bold text-slate-700 text-[10px] p-0 focus:ring-0"
                          />
                          <button 
                            @click="adjustItemQty('product', product.id, 1, product.stock)"
                            class="w-6 h-6 flex items-center justify-center rounded bg-white border border-primary-50 text-primary-600 shadow-sm transition-all active:scale-95"
                            :disabled="getItemQty('product', product.id) >= product.stock"
                          >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path d="M12 6v12m6-6H6" /></svg>
                          </button>
                       </div>

                       <button 
                         v-if="!isItemInCart('product', product.id)"
                         @click="addProductToCart(product)"
                         class="flex-1 h-7 rounded bg-gradient-to-tr from-primary-600 to-primary-500 text-white flex items-center justify-center shadow-sm active:scale-95 transition-all"
                         title="Tambah ke Keranjang"
                       >
                         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                       </button>
                       <div v-else class="flex-1 h-7 bg-emerald-50 text-emerald-600 rounded flex items-center justify-center border border-emerald-100" title="Sudah di Keranjang">
                         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M20 6L9 17L4 12"/></svg>
                       </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Packages Tab -->
            <div v-else-if="activeTab === 'packages'">
              <div v-if="filteredPackages.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-400">
                <span class="text-5xl mb-4">🎁</span>
                <p class="text-lg">Paket tidak ditemukan</p>
              </div>
              <div v-else class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 gap-2.5">
                <button
                   v-for="pkg in filteredPackages"
                   :key="pkg.id"
                   @click="openMixModal(pkg)"
                   class="group bg-white rounded-lg p-2.5 border border-slate-100 shadow-[0_1px_8px_rgba(0,0,0,0.02)] hover:shadow-[0_6px_20px_rgba(0,0,0,0.04)] hover:border-primary-100 transition-all duration-300 flex flex-col items-start relative overflow-hidden"
                >
                   <div class="w-full aspect-[3/2] bg-gradient-to-br from-orange-50 to-primary-50 rounded-md mb-2 flex items-center justify-center overflow-hidden border border-primary-50/50 relative">
                      <LazyImage v-if="pkg.image" :src="`/storage/${pkg.image}`" :alt="pkg.name" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                      <span v-else class="text-2xl transform group-hover:scale-110 transition-transform duration-500">{{ pkg.type === 'mix' ? '🍱' : '📦' }}</span>
                      <div class="absolute top-1.5 right-1.5 bg-primary-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded shadow-sm uppercase">
                        {{ pkg.total_quantity }} PCS
                      </div>
                   </div>
                   <h3 class="font-bold text-slate-800 text-[11px] leading-tight mb-0.5 group-hover:text-primary-600 transition-colors line-clamp-1 truncate w-full">{{ pkg.name }}</h3>
                   <div class="flex items-center gap-1">
                     <span class="inline-flex items-center gap-0.5 text-[8px] font-black text-orange-600 bg-orange-50 px-1 py-0.5 rounded border border-orange-100 uppercase whitespace-nowrap">
                       {{ pkg.combinations?.length || 0 }} Varian
                     </span>
                     <span class="text-[8px] text-slate-400 font-bold uppercase">Stok {{ pkg.stock }}</span>
                   </div>
                </button>
              </div>
            </div>

            <!-- Packaging Tab -->
            <div v-else>
              <div v-if="filteredPackagingItems.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-400">
                <span class="text-5xl mb-4">🥡</span>
                <p class="text-lg">Packaging tidak ditemukan</p>
              </div>
              <div v-else class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 gap-2.5">
                <div
                   v-for="item in filteredPackagingItems"
                   :key="item.id"
                   class="group bg-white rounded-lg p-2.5 border border-slate-100 shadow-[0_1px_8px_rgba(0,0,0,0.02)] hover:shadow-[0_6px_20px_rgba(0,0,0,0.04)] hover:border-primary-100 transition-all duration-300 flex flex-col"
                >
                   <div class="w-full aspect-[3/2] bg-slate-50 rounded-md mb-2 flex items-center justify-center overflow-hidden relative border border-slate-50/50">
                      <LazyImage v-if="item.image" :src="`/storage/${item.image}`" :alt="item.name" placeholder="🥡" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                      <span v-else class="text-2xl transform group-hover:scale-110 transition-transform duration-500">🥡</span>
                      <div class="absolute bottom-1.5 left-1.5 px-1.5 py-0.5 bg-white/95 backdrop-blur-md rounded border border-slate-100 shadow-sm">
                        <span class="text-[8px] text-slate-400 font-bold uppercase">Stok {{ item.stock }}</span>
                      </div>
                   </div>

                   <div class="flex flex-col flex-1">
                    <h3 class="font-bold text-slate-800 text-[11px] leading-tight mb-0.5 group-hover:text-primary-600 transition-colors line-clamp-1 truncate w-full">{{ item.name }}</h3>
                    <p class="text-primary-600 font-black text-xs mb-2">Rp {{ formatPrice(item.selling_price || item.price || 0) }}</p>
                    
                    <div class="mt-auto flex items-center justify-between gap-1.5">
                       <div class="flex items-center bg-slate-100/50 rounded p-0.5 gap-0.5 border border-slate-200/30">
                          <button 
                            @click="adjustItemQty('packaging', item.id, -1, item.stock)"
                            class="w-6 h-6 flex items-center justify-center rounded shadow-sm transition-all active:scale-95 disabled:opacity-30"
                            :class="[getItemQty('packaging', item.id) > 0 ? 'bg-white text-primary-600 border border-primary-50' : 'bg-transparent text-slate-300']"
                            :disabled="getItemQty('packaging', item.id) <= 0"
                          >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path d="M18 12H6" /></svg>
                          </button>
                          <input 
                            type="text" inputmode="numeric" pattern="[0-9]*"
                            :value="getItemQty('packaging', item.id)"
                            @input="e => setItemQty('packaging', item.id, e.target.value, item.stock)"
                            class="w-6 bg-transparent border-none text-center font-bold text-slate-700 text-[10px] p-0 focus:ring-0"
                          />
                          <button 
                            @click="adjustItemQty('packaging', item.id, 1, item.stock)"
                            class="w-6 h-6 flex items-center justify-center rounded bg-white border border-primary-50 text-primary-600 shadow-sm transition-all active:scale-95"
                            :disabled="getItemQty('packaging', item.id) >= item.stock"
                          >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path d="M12 6v12m6-6H6" /></svg>
                          </button>
                       </div>

                       <button 
                         v-if="!isItemInCart('packaging', item.id)"
                         @click="addPackagingToCart(item)"
                         class="flex-1 h-7 rounded bg-gradient-to-tr from-primary-600 to-primary-500 text-white flex items-center justify-center shadow-sm active:scale-95 transition-all"
                         title="Tambah ke Keranjang"
                       >
                         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                       </button>
                       <div v-else class="flex-1 h-7 bg-emerald-50 text-emerald-600 rounded flex items-center justify-center border border-emerald-100" title="Sudah di Keranjang">
                         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M20 6L9 17L4 12"/></svg>
                       </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Sidebar: Cart (Desktop) -->
      <div class="w-[360px] flex flex-col p-4 space-y-3">
        <!-- ... Copy existing cart layout ... -->
        <div class="flex-1 bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col overflow-hidden">
          <!-- Sidebar Header -->
          <div class="p-4 pb-2">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-primary-100 rounded-2xl flex items-center justify-center text-primary-600">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
                <h2 class="text-xl font-semibold text-slate-900 tracking-tight">Keranjang</h2>
              </div>
              <button
                @click="posStore.refreshData()"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-50 text-red-500 hover:bg-red-100 transition-all active:scale-95 group/reset"
                :disabled="posStore.loading"
                title="Reset POS / Hapus Semua"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 group-hover/reset:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span class="text-[10px] font-bold uppercase tracking-wider">Reset</span>
              </button>
            </div>

            <!-- Order Settings Section -->
            <div class="grid grid-cols-2 gap-2 mb-3 p-1 bg-slate-50 rounded-xl">
              <button
                @click="posStore.setOrderType('instant')"
                class="flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-semibold transition-all duration-300"
                :class="posStore.orderType === 'instant' 
                  ? 'bg-white text-green-600 shadow-sm' 
                  : 'text-slate-400 hover:text-slate-600'"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="posStore.orderType === 'instant' ? 'bg-green-500 shadow-[0_0_8px_rgba(22,163,74,0.4)]' : 'bg-slate-300'"></span>
                Instan
              </button>
              <button
                @click="posStore.setOrderType('scheduled')"
                class="flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-semibold transition-all duration-300"
                :class="posStore.orderType === 'scheduled' 
                  ? 'bg-white text-blue-600 shadow-sm' 
                  : 'text-slate-400 hover:text-slate-600'"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="posStore.orderType === 'scheduled' ? 'bg-blue-500 shadow-[0_0_8px_rgba(37,99,235,0.4)]' : 'bg-slate-300'"></span>
                Pre-Order
              </button>
            </div>

            <!-- Customer Search Logic SAME AS BEFORE ... -->
            <div class="relative customer-search-container">
              <!-- Search Input (Shown when no customer selected) -->
              <div v-if="!posStore.selectedCustomer" class="relative group">
                <input
                  ref="customerSearchInput"
                  v-model="customerSearch"
                  type="text"
                  class="w-full bg-slate-50 border-2 border-transparent rounded-xl px-4 py-2.5 text-base focus:bg-white focus:border-primary-500/20 focus:ring-4 focus:ring-primary-500/5 transition-all text-slate-700 placeholder:text-[11px] placeholder:text-slate-400 font-medium"
                  style="font-size: 16px;"
                  placeholder="Cari nama atau nomor telepon..."
                  @input="searchCustomers"
                  @focus="handleCustomerInputFocus"
                />
                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none group-focus-within:text-primary-500 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
              </div>

               <!-- Customer Detail Card (Shown when customer selected) -->
              <div 
                v-else
                class="relative bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-center group animate-fade-in"
              >
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center text-primary-600 font-semibold shadow-sm">
                    {{ posStore.selectedCustomer.name.charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="font-semibold text-slate-800 text-sm tracking-tight">{{ posStore.selectedCustomer.name }}</span>
                      <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold uppercase tracking-widest bg-slate-100 text-slate-500 border border-slate-200/50">
                        {{ getCustomerLevelLabel(posStore.selectedCustomer.customer_type?.name?.toLowerCase() || 'reguler') }}
                      </span>
                    </div>
                    <div class="text-[11px] text-slate-400 font-medium mt-0.5 tracking-wide">{{ posStore.selectedCustomer.phone }}</div>
                  </div>
                </div>
                <button 
                  @click="clearCustomer"
                  class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-lg bg-slate-50 text-slate-300 hover:bg-red-50 hover:text-red-500 transition-all active:scale-90"
                  title="Ganti Customer"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

             <!-- Pre-Order Dropdown Date/Time -->
             <div v-if="posStore.orderType === 'scheduled'" class="mt-3 grid grid-cols-2 gap-3 animate-fade-in">
                <input
                  v-model="posStore.scheduledDate"
                  type="date"
                  class="bg-blue-50/50 border-none rounded-xl px-3 py-2 text-xs font-semibold text-blue-700 focus:ring-1 focus:ring-blue-400"
                  :min="new Date().toISOString().split('T')[0]"
                />
                <input
                  v-model="posStore.scheduledTime"
                  type="time"
                  class="bg-blue-50/50 border-none rounded-xl px-3 py-2 text-xs font-semibold text-blue-700 focus:ring-1 focus:ring-blue-400"
                />
             </div>

              <!-- SEARCH RESULTS -->
              <div v-if="showCustomerDropdown" 
                   class="absolute z-20 w-full mt-2 bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] border border-slate-100 max-h-[300px] overflow-auto animate-fade-in">
                  <!-- ... search results content same as before ... -->
                <div v-if="customerSearchLoading" class="p-6 text-center text-slate-400">
                  <div class="animate-spin mb-2 mx-auto w-5 h-5 border-2 border-slate-200 border-t-primary-500 rounded-full"></div>
                  <span class="text-xs font-medium">Mencari...</span>
                </div>
                
                <div v-else>
                  <button
                    v-for="customer in customerResults"
                    :key="customer.id"
                    @click="selectCustomer(customer)"
                    class="w-full p-4 text-left hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-none group"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-xs group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                        {{ customer.name.charAt(0) }}
                      </div>
                      <div>
                        <div class="font-semibold text-slate-800 text-sm">{{ customer.name }}</div>
                        <div class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">{{ customer.phone }} • {{ customer.customer_type?.name || 'Reguler' }}</div>
                      </div>
                    </div>
                  </button>
                  
                  <button
                    v-if="customerSearch.length >= 2"
                    @click="openQuickAddModal"
                    class="w-full p-4 text-left bg-primary-50 hover:bg-primary-100 transition-colors group"
                  >
                    <div class="flex items-center gap-3 text-primary-600">
                      <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="16" x2="22" y1="11" y2="11"/></svg>
                      </div>
                      <div>
                        <div class="font-semibold text-sm">Customer Baru?</div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider opacity-80">Tambah "{{ customerSearch }}"</div>
                      </div>
                    </div>
                  </button>
                </div>
              </div>

            </div>
          </div>
          
          <div class="h-px bg-slate-50 mx-5"></div>
          
          <!-- CART LIST DESKTOP -->
          <div class="flex-1 overflow-auto px-5 py-4 space-y-2 custom-scrollbar">
            <!-- Empty State -->
             <div v-if="posStore.cart.length === 0" class="h-full flex flex-col items-center justify-center text-center py-10">
              <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center text-slate-200 mb-4 transition-transform duration-500 hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <p class="text-slate-400 font-medium tracking-tight">Belum ada item.<br><span class="text-xs text-slate-300">Pilih menu di kiri</span></p>
             </div>
             
             <!-- Items -->
            <div
              v-for="(item, index) in posStore.cart"
              :key="index"
              class="group bg-white rounded-xl p-2.5 border border-slate-100 hover:border-primary-100 hover:shadow-md hover:shadow-primary-500/5 transition-all duration-300"
            >
              <div class="flex justify-between items-start gap-3">
                <div class="flex-1 min-w-0">
                  <h4 class="font-bold text-slate-800 text-[12px] leading-tight tracking-tight mb-0.5">{{ item.name }}</h4>
                  <p class="text-[9px] text-slate-400 font-semibold uppercase tracking-wider">@ Rp {{ formatPrice(item.unit_price) }}</p>
                </div>
                <!-- Delete Button -->
                <button
                  @click="posStore.removeCartItem(index)"
                  class="w-5 h-5 flex items-center justify-center rounded-lg text-slate-200 hover:bg-red-50 hover:text-red-500 transition-all active:scale-90"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Qty & Subtotal -->
              <div class="flex items-center justify-between mt-2.5">
                <div class="flex items-center bg-slate-50 rounded-lg p-0.5 border border-slate-100/50">
                  <button
                    @click="posStore.updateCartItemQuantity(index, item.quantity - 1)"
                    class="w-5 h-5 rounded hover:bg-white hover:text-primary-600 text-slate-400 transition-all flex items-center justify-center hover:shadow-sm"
                    :disabled="item.quantity <= 1"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4" />
                    </svg>
                  </button>
                  <span class="w-6 text-center text-[11px] font-bold text-slate-700">{{ item.quantity }}</span>
                  <button
                    @click="posStore.updateCartItemQuantity(index, item.quantity + 1)"
                    class="w-5 h-5 rounded hover:bg-white hover:text-primary-600 text-slate-400 transition-all flex items-center justify-center hover:shadow-sm"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                </div>
                <span class="font-bold text-slate-800 text-[12.5px] tracking-tight">
                  Rp {{ formatPrice(item.subtotal) }}
                </span>
              </div>
            </div>
          </div>
          
          <div class="p-4 bg-white border-t border-slate-100 mt-auto">
            <!-- Totals and Action Buttons -->
            <div class="space-y-3">
              <div class="flex justify-between items-center bg-slate-50 border border-slate-100 px-3.5 py-2.5 rounded-xl transition-all">
                <div>
                  <p class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.15em] leading-none mb-1.5">Total Tagihan</p>
                  <div class="flex items-center gap-1.5 text-[8px] font-bold bg-white text-emerald-600 px-1.5 py-0.5 rounded border border-emerald-100 w-fit">
                    {{ posStore.cartItemCount }} Item
                  </div>
                </div>
                <span class="text-lg font-bold text-primary-600 tracking-tight">Rp {{ formatPrice(posStore.cartTotal) }}</span>
              </div>

                <button
                  @click="handleOrder"
                  class="group relative w-full bg-primary-500 py-3 px-6 rounded-xl text-white font-bold text-xs shadow-[0_8px_20px_rgba(235,108,34,0.1)] hover:bg-primary-600 transition-all active:scale-[0.97] disabled:opacity-50 disabled:shadow-none overflow-hidden"
                  :disabled="posStore.cart.length === 0"
                >
                  <div class="relative z-10 flex items-center justify-center gap-2">
                    Konfirmasi Order
                    <span class="group-hover:translate-x-1 transition-transform">➡️</span>
                  </div>
                  <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 -translate-x-full group-hover:translate-x-full duration-1000"></div>
                </button>
            </div>
          </div>
        </div>
      </div>
    </div><!-- End Desktop View -->

    <!-- ============================================== -->
    <!-- FLOATING CART BUTTON (Shared/Visible Mobile)   -->
    <!-- ============================================== -->
    <button
      v-if="posStore.cartItemCount > 0"
      @click="goToCart"
      class="lg:hidden fixed bottom-[98px] right-6 bg-white/90 backdrop-blur-md text-primary-600 rounded-full w-14 h-14 flex items-center justify-center shadow-[0_8px_30px_rgba(235,108,34,0.3)] border-2 border-primary-500 active:scale-90 transition-all z-50 animate-bounce-in"
    >
      <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <div class="absolute -top-2 -right-2 bg-primary-600 text-white text-[10px] font-bold min-w-[20px] h-[20px] px-1 rounded-full flex items-center justify-center shadow-md border-2 border-white">
          {{ posStore.cartItemCount }}
        </div>
      </div>
    </button>

    <!-- Modals -->
    <OrderPreviewModal v-if="showOrderPreview" @close="showOrderPreview = false" @order="handleOrderOnly" @confirm="handleConfirmOrder" />
    <QuickAddCustomerModal v-if="showQuickAddModal" :searchQuery="customerSearch" @close="showQuickAddModal = false" @customerAdded="handleCustomerAdded" />
    <MixModal v-if="showMixModal" :package="selectedPackage" @close="showMixModal = false" @select="selectCombination" />
    <CheckoutModal v-if="showCheckoutModal" @close="showCheckoutModal = false" />
    <PendingChoiceModal />
  </div>
</template>

<style scoped>
/* Custom scrollbar for desktop parts */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 20px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #94a3b8;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-bounce-in {
  animation: bounceIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes bounceIn {
  0% { transform: scale(0); opacity: 0; }
  60% { transform: scale(1.1); opacity: 1; }
  100% { transform: scale(1); opacity: 1; }
}
</style>
