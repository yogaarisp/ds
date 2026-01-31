<script setup>
import { ref, onMounted, computed } from 'vue'
import { packageApi, productApi, customerTypeApi } from '../api'

const packages = ref([])
const products = ref([])
const customerTypes = ref([])
const loading = ref(false)
const showPackageModal = ref(false)
const showCombinationModal = ref(false)
const showPriceSettingsModal = ref(false)
const editingPackage = ref(null)
const editingCombination = ref(null)
const selectedPackage = ref(null)

const searchQuery = ref('')
const filteredPackages = computed(() => {
  if (!searchQuery.value) return packages.value
  const query = searchQuery.value.toLowerCase()
  return packages.value.filter(pkg => 
    (pkg.name && pkg.name.toLowerCase().includes(query)) || 
    (pkg.code && pkg.code.toLowerCase().includes(query))
  )
})

const packageForm = ref({
  name: '',
  code: '',
  type: 'bundle',
  total_quantity: 10,
  variant_count: 2,
  image: null
})
const imageFile = ref(null)
const imagePreview = ref(null)

const combinationForm = ref({
  name: '',
  base_price: '',
  items: [],
})

const priceSettingsForm = ref({
  discounts: {} // format: { customer_type_id: amount }
})

onMounted(() => {
  loadData()
})

async function loadData() {
  loading.value = true
  try {
    const [packagesRes, productsRes, customerTypesRes] = await Promise.all([
      packageApi.getAll(),
      productApi.getAll({ active: true }),
      customerTypeApi.getAll()
    ])
    packages.value = packagesRes.data
    products.value = productsRes.data
    customerTypes.value = customerTypesRes.data
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

function openPackageModal(pkg = null) {
  editingPackage.value = pkg
  if (pkg) {
    packageForm.value = { ...pkg, image: null }
    imagePreview.value = pkg.image ? `http://localhost:8000/storage/${pkg.image}` : null
  } else {
    packageForm.value = { name: '', code: '', type: 'bundle', total_quantity: 10, variant_count: 2, image: null }
    imagePreview.value = null
  }
  imageFile.value = null
  showPackageModal.value = true
}

function handleFileUpload(event) {
  const file = event.target.files[0]
  if (file) {
    imageFile.value = file
    imagePreview.value = URL.createObjectURL(file)
  }
}

async function savePackage() {
  try {
    const formData = new FormData()
    formData.append('name', packageForm.value.name)
    formData.append('code', packageForm.value.code)
    formData.append('type', packageForm.value.type)
    formData.append('total_quantity', packageForm.value.total_quantity)
    
    if (packageForm.value.variant_count) {
       formData.append('variant_count', packageForm.value.variant_count)
    }

    if (imageFile.value) {
      formData.append('image', imageFile.value)
    }

    if (editingPackage.value) {
      formData.append('_method', 'PUT')
      await packageApi.update(editingPackage.value.id, formData)
    } else {
      await packageApi.create(formData)
    }

    showPackageModal.value = false
    loadData()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menyimpan')
  }
}

function openCombinationModal(pkg, combination = null) {
  selectedPackage.value = pkg
  editingCombination.value = combination
  
  if (combination) {
    // Edit mode - populate with existing data
    combinationForm.value = {
      name: combination.name,
      base_price: combination.base_price,
      items: combination.items.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity
      }))
    }
    // Set combination image preview
    combinationImagePreview.value = combination.image ? `http://localhost:8000/storage/${combination.image}` : null
  } else {
    // Create mode
    combinationForm.value = {
      name: '',
      base_price: '',
      items: [{ product_id: '', quantity: 5 }],
    }
    combinationImagePreview.value = null
  }
  combinationImageFile.value = null
  showCombinationModal.value = true
}

function addCombinationItem() {
  combinationForm.value.items.push({ product_id: '', quantity: 5 })
}

function removeCombinationItem(index) {
  combinationForm.value.items.splice(index, 1)
}

async function saveCombination() {
  try {
    const formData = new FormData()
    formData.append('name', combinationForm.value.name)
    formData.append('base_price', parseInt(combinationForm.value.base_price))
    
    combinationForm.value.items.forEach((item, index) => {
      formData.append(`items[${index}][product_id]`, parseInt(item.product_id))
      formData.append(`items[${index}][quantity]`, parseInt(item.quantity))
    })

    if (combinationImageFile.value) {
      formData.append('image', combinationImageFile.value)
    }

    if (editingCombination.value) {
      formData.append('_method', 'PUT')
      await packageApi.updateCombination(editingCombination.value.id, formData)
    } else {
      await packageApi.createCombination(selectedPackage.value.id, formData)
    }
    
    showCombinationModal.value = false
    loadData()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menyimpan')
  }
}

async function deleteCombination(combination) {
  if (!confirm(`Hapus kombinasi "${combination.name}"?`)) return

  try {
    await packageApi.deleteCombination(combination.id)
    loadData()
  } catch (error) {
    alert('Gagal menghapus')
  }
}

async function deletePackage(pkg) {
  if (!confirm(`Hapus paket "${pkg.name}"? Semua kombinasi di dalamnya juga akan terhapus.`)) return

  try {
    await packageApi.delete(pkg.id)
    loadData()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menghapus paket')
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}

// Calculate available packs for a combination based on product stock
function getAvailablePacks(combination) {
  if (!combination.items || combination.items.length === 0) return 0
  
  let minPacks = Infinity
  
  for (const item of combination.items) {
    if (item.product && item.quantity > 0) {
      const availableFromThis = Math.floor(item.product.stock / item.quantity)
      if (availableFromThis < minPacks) {
        minPacks = availableFromThis
      }
    }
  }
  
  return minPacks === Infinity ? 0 : minPacks
}

function isLowStock(combination) {
  return getAvailablePacks(combination) <= 5
}

function openPriceSettingsModal(pkg) {
  selectedPackage.value = pkg
  
  // Initialize discounts mapping
  const discountsMap = {}
  
  // Set default 0 for all types
  customerTypes.value.forEach(type => {
    discountsMap[type.id] = 0
  })
  
  // Fill with existing discounts
  if (pkg.discounts && pkg.discounts.length > 0) {
    pkg.discounts.forEach(discount => {
      if (discountsMap.hasOwnProperty(discount.customer_type_id)) {
        discountsMap[discount.customer_type_id] = discount.discount_amount
      } else if (discount.customer_type?.id) { // Fallback if structure differs
         discountsMap[discount.customer_type.id] = discount.discount_amount
      }
    })
  }

  priceSettingsForm.value = {
    discounts: discountsMap
  }
  
  showPriceSettingsModal.value = true
}

async function savePriceSettings() {
  try {
    const discounts = Object.entries(priceSettingsForm.value.discounts).map(([typeId, amount]) => ({
      customer_type_id: parseInt(typeId),
      amount: parseInt(amount) || 0
    }))

    const data = {
      discounts: discounts
    }
    
    await packageApi.updateDiscounts(selectedPackage.value.id, data)
    showPriceSettingsModal.value = false
    loadData()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menyimpan setting harga')
  }
}
const combinationImageFile = ref(null)
const combinationImagePreview = ref(null)

function handleCombinationFileUpload(event) {
  const file = event.target.files[0]
  if (file) {
    combinationImageFile.value = file
    combinationImagePreview.value = URL.createObjectURL(file)
  }
}
</script>

<template>
  <div class="h-full overflow-y-auto bg-slate-50/50 custom-scrollbar pb-6">
    <!-- Simple & Cool Header -->
    <div class="sticky top-0 z-20 bg-slate-50/80 backdrop-blur-md px-4 md:px-6 pt-2.5 pb-1.5 border-b border-slate-100/50">
      <div class="flex flex-col md:flex-row justify-between md:items-center gap-3">
        <div class="flex items-center gap-3">
          <div>
            <h1 class="text-base font-medium text-slate-800 leading-none">Paket & Mix</h1>
            <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wider mt-1">Manajemen Produk Paket</p>
          </div>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
          <div class="relative flex-1 md:w-64 group">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari paket..."
              class="w-full pl-8 pr-4 py-1.5 rounded-xl bg-white border border-slate-200 text-[11px] font-normal placeholder:text-[10px] placeholder:text-slate-300 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/5 transition-all outline-none shadow-sm"
            />
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 group-focus-within:text-primary-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          
          <button @click="openPackageModal()" class="h-9 w-9 md:h-[38px] md:w-auto bg-primary-500 text-white rounded-xl flex items-center justify-center gap-0 md:gap-1.5 px-0 md:px-4 shadow-lg shadow-primary-500/20 active:scale-95 transition-all shrink-0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
            <span class="hidden md:inline text-[10px] font-medium uppercase tracking-wider">Paket Baru</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="px-2 md:px-6">
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 animate-pulse">
        <div class="w-12 h-12 bg-slate-200 rounded-2xl mb-4"></div>
        <div class="h-4 w-32 bg-slate-200 rounded-lg"></div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 md:gap-4">
        <div
          v-for="pkg in filteredPackages"
          :key="pkg.id"
          class="group bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 transition-all duration-300 flex flex-col overflow-hidden"
        >
          <!-- Package Info -->
          <div class="p-3 md:p-4">
            <div class="flex gap-3 md:gap-4">
              <!-- Compact Image -->
              <div class="w-14 h-14 md:w-16 md:h-16 shrink-0 bg-slate-50 rounded-xl overflow-hidden border border-slate-100 relative shadow-inner">
                <img 
                  v-if="pkg.image" 
                  :src="`http://localhost:8000/storage/${pkg.image}`" 
                  class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                  alt="Package"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-slate-200">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-7 h-7"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/5 to-transparent pointer-events-none"></div>
              </div>

              <!-- Details -->
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start mb-1">
                  <span class="text-[9px] font-medium bg-primary-50 text-primary-600 px-2 py-0.5 rounded-full uppercase tracking-tighter">
                    {{ pkg.code }}
                  </span>
                  <!-- Quick Actions -->
                  <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity translate-x-2 group-hover:translate-x-0">
                    <button @click="openPackageModal(pkg)" class="p-1.5 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-all">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-3.5 h-3.5"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                    <button @click="deletePackage(pkg)" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-3.5 h-3.5"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                  </div>
                </div>
                <h3 class="font-medium text-slate-800 text-sm leading-tight truncate mb-1">{{ pkg.name }}</h3>
                <div class="flex items-center gap-2">
                  <span class="text-[10px] font-medium text-slate-400 flex items-center gap-1">
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    {{ pkg.total_quantity }} PCS
                  </span>
                  <span class="text-[10px] font-medium text-slate-400 flex items-center gap-1">
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    {{ pkg.type === 'mix' ? `${pkg.variant_count} VARIAN` : 'BUNDLE' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2 mt-4">
              <button @click="openCombinationModal(pkg)" class="flex-1 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-xl text-[10px] font-medium uppercase tracking-wider transition-all border border-slate-100 flex items-center justify-center gap-1.5 active:scale-95">
                <span class="text-xs">➕</span> Mix Baru
              </button>
              <button @click="openPriceSettingsModal(pkg)" class="flex-1 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl text-[10px] font-medium uppercase tracking-wider transition-all border border-amber-100 flex items-center justify-center gap-1.5 active:scale-95 shadow-sm shadow-amber-900/5">
                <span class="text-xs">🏷️</span> Harga
              </button>
            </div>
          </div>

          <!-- Combinations Area -->
          <div class="flex-1 bg-slate-50/50 p-2 space-y-1.5">
            <div v-if="pkg.combinations?.length" class="space-y-1.5">
              <div
                v-for="combination in pkg.combinations"
                :key="combination.id"
                class="group/item bg-white rounded-2xl p-2.5 flex items-center gap-3 border border-slate-100 hover:border-primary-200 transition-all relative overflow-hidden"
                :class="isLowStock(combination) ? 'bg-red-50/30' : ''"
              >
                <!-- Combination Image -->
                <div class="w-10 h-10 shrink-0 bg-slate-50 rounded-xl overflow-hidden border border-slate-100 group-hover/item:border-primary-100 transition-colors shadow-sm">
                  <img v-if="combination.image" :src="`http://localhost:8000/storage/${combination.image}`" class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full flex items-center justify-center text-slate-200">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                  </div>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-0.5">
                    <p class="font-medium text-slate-700 text-[11px] truncate">{{ combination.name }}</p>
                    <span v-if="isLowStock(combination)" class="text-[8px] font-medium bg-red-500 text-white px-1.5 py-0.5 rounded-full animate-pulse">EMPTY</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <p class="text-[10px] font-medium text-primary-600">Rp {{ formatPrice(combination.base_price) }}</p>
                    <div class="flex items-center gap-1">
                      <span class="text-[9px] font-medium text-slate-400 capitalize">{{ getAvailablePacks(combination) }} packs</span>
                      <!-- Item Actions -->
                      <div class="flex items-center pl-1 ml-1 border-l border-slate-100 opacity-0 group-hover/item:opacity-100 transition-opacity">
                        <button @click="openCombinationModal(pkg, combination)" class="p-1 text-slate-400 hover:text-blue-500 transition-colors">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-3 h-3"><path d="M17 3l4 4-14 14H3v-4L17 3z"/></svg>
                        </button>
                        <button @click="deleteCombination(combination)" class="p-1 text-slate-400 hover:text-red-500 transition-colors">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-3 h-3"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else class="h-20 flex flex-col items-center justify-center text-slate-300 border-2 border-dashed border-slate-100 rounded-2xl bg-white/50">
              <span class="text-xl opacity-20 mb-1">🔍</span>
              <p class="text-[9px] font-medium uppercase tracking-widest">Belum ada mix</p>
            </div>
          </div>
        </div>
      </div>
    </div>


    <!-- Package Modal -->
    <div v-if="showPackageModal" class="fixed inset-0 bg-slate-900/60 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
      <div class="bg-white rounded-3xl w-full md:max-w-sm shadow-2xl overflow-hidden flex flex-col animate-fade-in-up">
        <!-- Header -->
        <div class="px-6 py-4 flex justify-between items-center bg-slate-50/50 border-b border-slate-100/50">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-white rounded-xl shadow-sm flex items-center justify-center text-sm">📁</div>
            <h2 class="text-lg font-medium text-slate-800 tracking-tight">
              {{ editingPackage ? 'Edit Paket' : 'Paket Baru' }}
            </h2>
          </div>
          <button @click="showPackageModal = false" class="w-8 h-8 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-red-500 shadow-sm transition-all active:scale-90">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form Content -->
        <div class="flex-1 p-5 space-y-4 overflow-y-auto">
          <div class="space-y-1.5">
            <label class="text-[10px] font-medium text-slate-400 uppercase tracking-[0.2em] ml-1">Nama Paket</label>
            <input v-model="packageForm.name" type="text" class="w-full bg-slate-50 border-2 border-slate-50 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:bg-white focus:border-primary-500/20 transition-all outline-none" placeholder="Contoh: Paket Family..." required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-medium text-slate-400 uppercase tracking-[0.2em] ml-1">Kode</label>
              <input v-model="packageForm.code" type="text" class="w-full bg-slate-50 border-2 border-slate-50 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 focus:bg-white focus:border-primary-500/20 transition-all outline-none uppercase" placeholder="PKT-001" required />
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-medium text-slate-400 uppercase tracking-[0.2em] ml-1">Tipe</label>
              <div class="relative">
                <select v-model="packageForm.type" class="w-full bg-slate-50 border-2 border-slate-50 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:bg-white focus:border-primary-500/20 transition-all outline-none appearance-none cursor-pointer">
                  <option value="bundle">Bundle</option>
                  <option value="mix">Mix Varian</option>
                </select>
                <svg class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-medium text-slate-400 uppercase tracking-[0.2em] ml-1">Total Qty</label>
              <input v-model="packageForm.total_quantity" type="number" class="w-full bg-slate-50 border-2 border-slate-50 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:bg-white focus:border-primary-500/20 transition-all outline-none" required />
            </div>
            <div class="space-y-1.5" v-if="packageForm.type === 'mix'">
              <label class="text-[10px] font-medium text-slate-400 uppercase tracking-[0.2em] ml-1">Varian</label>
              <input v-model="packageForm.variant_count" type="number" class="w-full bg-slate-50 border-2 border-slate-50 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:bg-white focus:border-primary-500/20 transition-all outline-none" />
            </div>
          </div>
          
          <div class="flex items-center gap-4 p-3 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
            <div class="w-14 h-14 rounded-xl overflow-hidden bg-white shadow-sm flex-shrink-0 border border-slate-100 flex items-center justify-center text-slate-300">
              <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-7 h-7 opacity-30"><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7"/><path d="m9 13 3 3 8-8"/><path d="M16 3h5v5"/></svg>
            </div>
            <div class="flex-1 space-y-1">
              <input type="file" @change="handleFileUpload" accept="image/*" class="block w-full text-[9px] text-slate-500 file:mr-3 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[9px] file:font-medium file:bg-primary-50 file:text-primary-700 cursor-pointer"/>
              <p class="text-[9px] text-slate-400 font-medium uppercase tracking-widest pl-1">Gambar Paket</p>
            </div>
          </div>
        </div>

        <!-- Action Footer -->
        <div class="p-5 pt-0 bg-white">
          <form @submit.prevent="savePackage" class="flex gap-3">
            <button 
              type="button" 
              @click="showPackageModal = false" 
              class="flex-1 py-2.5 px-4 rounded-xl bg-slate-100 text-slate-600 font-medium text-[10px] uppercase tracking-wider hover:bg-slate-200 transition-all"
            >
              Batal
            </button>
            <button 
              type="submit" 
              class="flex-1 py-2.5 px-4 rounded-xl bg-slate-900 text-white font-medium text-[10px] uppercase tracking-wider shadow-lg shadow-slate-900/10 hover:bg-slate-800 transition-all flex items-center justify-center gap-2"
            >
              <span>{{ editingPackage ? 'Update' : 'Simpan' }}</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="w-3 h-3"><path d="M5 13l4 4L19 7"/></svg>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Combination Modal (Mix/Kombinasi) -->
    <div v-if="showCombinationModal" class="fixed inset-0 bg-slate-900/60 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
      <div class="bg-white rounded-3xl w-full md:max-w-sm md:max-h-[90vh] shadow-2xl overflow-hidden flex flex-col animate-fade-in-up">
        <!-- Header -->
        <div class="px-6 py-4 flex justify-between items-center bg-slate-50/50 border-b border-slate-100/50 shrink-0">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-white rounded-xl shadow-sm flex items-center justify-center text-sm">🧪</div>
            <h2 class="text-lg font-medium text-slate-800 tracking-tight">
               {{ editingCombination ? 'Edit Mix' : 'Mix Baru' }}
            </h2>
          </div>
          <button @click="showCombinationModal = false" class="w-8 h-8 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-red-500 shadow-sm transition-all active:scale-90">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form Content -->
        <div class="flex-1 overflow-hidden flex flex-col p-5 space-y-4">
          <div class="space-y-4 overflow-y-auto px-1 custom-scrollbar pb-2">
            <!-- Name & Price -->
            <div class="grid grid-cols-5 gap-3">
              <div class="col-span-3 space-y-1.5">
                <label class="text-[10px] font-medium text-slate-400 uppercase tracking-[0.2em] ml-1">Nama Mix</label>
                <input v-model="combinationForm.name" type="text" class="w-full bg-slate-50 border-2 border-slate-50 rounded-xl px-4 py-2 text-sm font-medium text-slate-700 focus:bg-white focus:border-primary-500/20 transition-all outline-none" placeholder="Ayam +..." required />
              </div>
              <div class="col-span-2 space-y-1.5">
                <label class="text-[10px] font-medium text-slate-400 uppercase tracking-[0.2em] ml-1">Harga</label>
                <input v-model="combinationForm.base_price" type="number" class="w-full bg-slate-50 border-2 border-slate-50 rounded-xl px-4 py-2 text-sm font-medium text-primary-600 focus:bg-white focus:border-primary-500/20 transition-all outline-none" required />
              </div>
            </div>

            <!-- Compact Photo Upload -->
            <div class="flex items-center gap-4 p-3 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
              <div class="w-10 h-10 rounded-xl overflow-hidden bg-white shadow-sm flex-shrink-0 border border-slate-100 flex items-center justify-center">
                <img v-if="combinationImagePreview" :src="combinationImagePreview" class="w-full h-full object-cover" />
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-slate-300 opacity-50"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              </div>
              <div class="flex-1">
                <input type="file" @change="handleCombinationFileUpload" accept="image/*" class="block w-full text-[9px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[9px] file:font-medium file:bg-primary-50 file:text-primary-700 cursor-pointer"/>
              </div>
            </div>

            <!-- Products List -->
            <div class="space-y-3">
              <div class="flex justify-between items-center ml-1">
                <label class="text-[10px] font-medium text-slate-400 uppercase tracking-[0.2em]">Item Mix</label>
                <button type="button" @click="addCombinationItem" class="text-[10px] font-medium text-primary-600 uppercase tracking-tighter hover:text-primary-700">+ Tambah</button>
              </div>
              <div class="space-y-2">
                <div
                  v-for="(item, index) in combinationForm.items"
                  :key="index"
                  class="flex gap-2 p-1.5 bg-slate-50/50 rounded-xl border border-slate-100 hover:border-slate-200 transition-all"
                >
                  <div class="flex-1 relative">
                    <select v-model="item.product_id" class="w-full bg-white border border-slate-100 rounded-lg px-2 py-1.5 text-[10px] font-medium text-slate-700 appearance-none focus:border-primary-500/30 outline-none" required>
                      <option value="">Pilih...</option>
                      <option v-for="product in products" :key="product.id" :value="product.id">
                        {{ product.name }}
                      </option>
                    </select>
                  </div>
                  <div class="w-12">
                    <input
                      v-model="item.quantity"
                      type="number"
                      class="w-full bg-white border border-slate-100 rounded-lg px-1 py-1.5 text-[10px] text-center font-medium text-slate-700 focus:border-primary-500/30 outline-none"
                      required
                    />
                  </div>
                  <button
                    v-if="combinationForm.items.length > 1"
                    type="button"
                    @click="removeCombinationItem(index)"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-white text-slate-300 hover:text-red-500 shadow-sm"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="w-3 h-3"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Footer -->
        <div class="p-5 pt-0 bg-white shrink-0">
          <form @submit.prevent="saveCombination" class="flex gap-3">
            <button 
              type="button" 
              @click="showCombinationModal = false" 
              class="flex-1 py-2.5 px-4 rounded-xl bg-slate-100 text-slate-600 font-medium text-[10px] uppercase tracking-wider hover:bg-slate-200 transition-all"
            >
              Batal
            </button>
            <button 
              type="submit" 
              class="flex-1 py-2.5 px-4 rounded-xl bg-slate-900 text-white font-medium text-[10px] uppercase tracking-wider shadow-lg shadow-slate-900/10 hover:bg-slate-800 transition-all flex items-center justify-center gap-2"
            >
              <span>{{ editingCombination ? 'Update' : 'Simpan' }}</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="w-3 h-3"><path d="M5 13l4 4L19 7"/></svg>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Price Settings Modal -->
    <div v-if="showPriceSettingsModal" class="fixed inset-0 bg-slate-900/60 flex items-center justify-center z-50 md:p-4 backdrop-blur-sm">
      <div class="bg-white md:rounded-[2.5rem] w-full h-full md:h-auto md:max-w-sm shadow-2xl overflow-hidden flex flex-col animate-fade-in-up">
        <!-- Header -->
        <div class="px-6 pt-8 md:pt-6 pb-2 flex justify-between items-center bg-slate-50/50 border-b border-slate-100/50">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-white rounded-xl shadow-sm flex items-center justify-center text-sm">💰</div>
            <h2 class="text-lg font-medium text-slate-800 tracking-tight">
               Setting Harga
            </h2>
          </div>
          <button @click="showPriceSettingsModal = false" class="w-8 h-8 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-red-500 shadow-sm transition-all active:scale-90">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form Content -->
        <div class="flex-1 p-6 space-y-4 overflow-y-auto">
          <div class="bg-primary-50 p-4 rounded-2xl text-[10px] text-primary-700 font-bold leading-relaxed border border-primary-100 flex gap-3">
            <div class="w-5 h-5 bg-white rounded-lg flex items-center justify-center shrink-0 shadow-sm">💡</div>
            <span>Atur potongan harga (Rp) untuk tiap tipe customer. Harga jual akan berkurang otomatis sesuai nilai ini.</span>
          </div>

          <div class="grid grid-cols-2 gap-3 max-h-[40vh] overflow-y-auto px-1 custom-scrollbar pb-2">
            <div v-for="type in customerTypes" :key="type.id" class="space-y-1.5 p-3 bg-slate-50 rounded-2xl border border-slate-50 hover:bg-white hover:border-slate-100 transition-all">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-[0.2em] ml-0.5 truncate block">{{ type.name }}</label>
              <div class="relative group">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400 group-focus-within:text-primary-500 transition-colors">Rp</span>
                <input 
                  v-model="priceSettingsForm.discounts[type.id]" 
                  type="number" 
                  class="w-full bg-white border border-slate-100 rounded-xl pl-8 pr-3 py-2 text-xs font-medium text-slate-700 focus:border-primary-500/30 focus:ring-4 focus:ring-primary-500/5 transition-all outline-none" 
                  placeholder="0" 
                  min="0" 
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Action Footer -->
        <div class="p-6 pt-2 bg-white">
          <form @submit.prevent="savePriceSettings" class="flex gap-3">
            <button 
              type="button" 
              @click="showPriceSettingsModal = false" 
              class="flex-1 py-3 px-4 rounded-2xl bg-slate-100 text-slate-600 font-medium text-[11px] uppercase tracking-wider hover:bg-slate-200 transition-all active:scale-95"
            >
              Batal
            </button>
            <button 
              type="submit" 
              class="flex-1 py-3 px-4 rounded-2xl bg-slate-900 text-white font-medium text-[11px] uppercase tracking-wider shadow-lg shadow-slate-900/10 hover:bg-slate-800 transition-all active:scale-95 flex items-center justify-center gap-2"
            >
              <span>Simpan</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="w-3.5 h-3.5"><path d="M5 13l4 4L19 7"/></svg>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
