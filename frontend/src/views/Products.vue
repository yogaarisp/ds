<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { productApi } from '../api'
import { STORAGE_BASE_URL } from '../api'
import Skeleton from '../components/Skeleton.vue'

const products = ref([])
const loading = ref(false)
const showModal = ref(false)
const editingProduct = ref(null)
const searchQuery = ref('')
const showStockModal = ref(false)
const stockForm = ref({
  product_id: null,
  product_name: '',
  current_stock: 0,
  adjustment: '',
  notes: ''
})

const filteredProducts = computed(() => {
  if (!searchQuery.value) return products.value
  const query = searchQuery.value.toLowerCase()
  return products.value.filter(product => 
    (product.name && product.name.toLowerCase().includes(query)) || 
    (product.sku && product.sku.toLowerCase().includes(query))
  )
})

const form = ref({
  name: '',
  sku: '',
  base_price: '',
  stock: 0,
  min_stock: 10,
  image: null,
})
const imageFile = ref(null)
const imagePreview = ref(null)

onMounted(() => {
  loadProducts()
})

onUnmounted(() => {
  // Ensure body scroll is unlocked when component unmounts
  document.body.style.overflow = ''
})

async function loadProducts() {
  loading.value = true
  try {
    const response = await productApi.getAll()
    products.value = response.data
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

function openModal(product = null) {
  editingProduct.value = product
  if (product) {
    form.value = { ...product, image: null }
    imagePreview.value = product.image ? `${STORAGE_BASE_URL}/${product.image}` : null
  } else {
    form.value = { name: '', sku: '', base_price: '', stock: 0, min_stock: 10, image: null }
    imagePreview.value = null
  }
  imageFile.value = null
  showModal.value = true
  
  // Lock body scroll
  document.body.style.overflow = 'hidden'
}

function closeModal() {
  showModal.value = false
  // Unlock body scroll
  document.body.style.overflow = ''
}

function handleFileUpload(event) {
  const file = event.target.files[0]
  if (file) {
    imageFile.value = file
    imagePreview.value = URL.createObjectURL(file)
  }
}

async function saveProduct() {
  try {
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('sku', form.value.sku)
    formData.append('base_price', form.value.base_price)
    formData.append('stock', form.value.stock || 0)
    formData.append('min_stock', form.value.min_stock || 10)
    
    if (imageFile.value) {
      formData.append('image', imageFile.value)
    }

    if (editingProduct.value) {
      formData.append('_method', 'PUT')
      await productApi.update(editingProduct.value.id, formData)
    } else {
      await productApi.create(formData)
    }

    showModal.value = false
    // Unlock body scroll
    document.body.style.overflow = ''
    loadProducts()
  } catch (error) {
    console.error('Save error:', error.response?.data)
    alert(error.response?.data?.message || 'Gagal menyimpan')
  }
}

async function deleteProduct(product) {
  if (!confirm(`Hapus produk "${product.name}"?`)) return

  try {
    await productApi.delete(product.id)
    loadProducts()
  } catch (error) {
    alert('Gagal menghapus')
  }
}

function openStockModal(product) {
  stockForm.value = {
    product_id: product.id,
    product_name: product.name,
    current_stock: product.stock,
    adjustment: '',
    notes: ''
  }
  showStockModal.value = true
  document.body.style.overflow = 'hidden'
}

function closeStockModal() {
  showStockModal.value = false
  document.body.style.overflow = ''
}

async function saveStockAdjustment() {
  if (!stockForm.value.adjustment) return
  
  try {
    const newStock = parseInt(stockForm.value.current_stock) + parseInt(stockForm.value.adjustment)
    await productApi.updateStock(stockForm.value.product_id, {
      stock: newStock,
      notes: stockForm.value.notes || 'Penyesuaian stok manual'
    })
    
    closeStockModal()
    loadProducts()
    
    // Optional: add success feedback
  } catch (error) {
    console.error('Update stock error:', error)
    alert('Gagal memperbarui stok')
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}
</script>

<template>
  <div class="h-full overflow-y-auto bg-slate-50/50 custom-scrollbar pb-6">
    <!-- Simple & Cool Header -->
    <div class="sticky top-0 z-20 bg-slate-50/80 backdrop-blur-md px-4 md:px-6 pt-2.5 pb-1.5 border-b border-slate-100/50 shrink-0">
      <div class="flex flex-col md:flex-row justify-between md:items-center gap-3">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-base font-medium text-slate-800 leading-none">Produk</h1>
            <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wide mt-1">Management Menu & Stok</p>
          </div>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
          <div class="relative flex-1 md:w-64 group">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari produk..."
              class="w-full pl-8 pr-4 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-normal placeholder:text-[10px] placeholder:text-slate-300 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/5 transition-all outline-none shadow-sm"
            />
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 group-focus-within:text-primary-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          
          <button @click="openModal()" class="h-9 w-9 md:h-[38px] md:w-auto bg-primary-500 text-white rounded-xl flex items-center justify-center gap-0 md:gap-1.5 px-0 md:px-4 shadow-lg shadow-primary-500/20 active:scale-95 transition-all shrink-0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
            <span class="hidden md:inline text-[10px] font-medium uppercase tracking-wider">Produk Baru</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="px-2 md:px-6">

    <!-- Skeleton Loading Grid -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-3 md:gap-4">
      <div v-for="i in 6" :key="i" class="card p-4 space-y-4">
        <div class="flex items-start gap-3">
          <Skeleton width="56px" height="56px" customClass="rounded-xl shrink-0" />
          <div class="flex-1 space-y-2">
            <div class="flex justify-between gap-2">
              <Skeleton width="60%" height="16px" />
              <Skeleton width="40px" height="16px" customClass="rounded-full" />
            </div>
            <Skeleton width="40%" height="12px" />
            <Skeleton width="50%" height="20px" customClass="mt-1" />
          </div>
        </div>
        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
          <Skeleton width="80px" height="16px" />
          <div class="flex gap-2">
            <Skeleton width="32px" height="32px" customClass="rounded-lg" />
            <Skeleton width="32px" height="32px" customClass="rounded-lg" />
          </div>
        </div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-3 md:gap-4">
      <div
        v-for="product in filteredProducts"
        :key="product.id"
        class="card p-3 md:p-4 hover:shadow-md transition-shadow"
      >
        <!-- Header: Thumbnail + Info + Badge -->
        <div class="flex items-start gap-3 mb-2.5">
          <div class="w-14 h-14 flex-shrink-0 bg-gray-100 rounded-xl overflow-hidden">
            <img 
              v-if="product.image" 
              :src="`${STORAGE_BASE_URL}/${product.image}`" 
              class="w-full h-full object-cover"
              alt="Product Image"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
            </div>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <h3 class="font-medium text-gray-900 text-sm truncate">{{ product.name }}</h3>
                <p class="text-xs text-gray-400">{{ product.sku }}</p>
              </div>
              <span
                :class="[
                  'px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0',
                  product.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'
                ]"
              >
                {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </div>
            <p class="text-sm font-medium text-primary-600 mt-0.5">
              Rp {{ formatPrice(product.base_price) }}
            </p>
          </div>
        </div>

        <!-- Footer: Stock + Actions -->
        <div class="flex justify-between items-center pt-2.5 border-t border-gray-100">
          <span 
            :class="[
              'text-sm font-medium',
              product.stock <= product.min_stock ? 'text-red-600' : 'text-gray-500'
            ]"
          >
            Stok: {{ product.stock }}
          </span>
          <div class="flex gap-2">
            <button 
              @click="openStockModal(product)" 
              class="p-2 text-primary-500 hover:bg-primary-50 rounded-lg transition-colors"
              title="Tambah/Kurangi Stok"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            </button>
            <button 
              @click="openModal(product)" 
              class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
              title="Edit Produk"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button 
              @click="deleteProduct(product)" 
              class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
              title="Hapus Produk"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-[9999] md:z-50 flex items-center justify-center md:bg-black/60 md:p-3 md:backdrop-blur-sm overflow-hidden h-[100dvh] overscroll-none">
        <div class="bg-white w-full h-full md:h-auto md:max-w-md md:rounded-[2.5rem] shadow-none md:shadow-2xl overflow-hidden flex flex-col overscroll-none">
          <!-- Header -->
          <div class="px-5 py-4 flex items-center gap-3 shrink-0 border-b border-slate-100/50">
            <button 
              @click="closeModal" 
              class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-slate-600 transition-all active:scale-90"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <h2 class="text-lg font-medium text-slate-800 tracking-tight">
              {{ editingProduct ? 'Edit Menu' : 'Menu Baru' }}
            </h2>
          </div>

          <!-- Form Content -->
          <div class="flex-1 px-5 py-4 space-y-3 overflow-y-auto custom-scrollbar">
            <!-- Main Info -->
            <div class="space-y-1">
              <label class="text-[11px] font-medium text-slate-500 ml-1 uppercase tracking-wide">Nama Produk</label>
              <input 
                v-model="form.name" 
                type="text" 
                class="w-full bg-slate-50/50 border border-slate-200/50 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 focus:bg-white transition-all placeholder:text-slate-400" 
                style="font-size: 16px;"
                placeholder="Nama Menu..." 
                required 
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="text-[11px] font-medium text-slate-500 ml-1 uppercase tracking-wide">SKU</label>
                <input 
                  v-model="form.sku" 
                  type="text" 
                  class="w-full bg-slate-50/50 border border-slate-200/50 rounded-xl px-3 py-2.5 text-sm text-slate-600 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 focus:bg-white transition-all placeholder:text-slate-400" 
                  style="font-size: 16px;"
                  placeholder="Kode..." 
                  required 
                />
              </div>
              <div class="space-y-1">
                <label class="text-[11px] font-medium text-slate-500 ml-1 uppercase tracking-wide">Harga (Rp)</label>
                <input 
                  v-model="form.base_price" 
                  type="number" 
                  class="w-full bg-slate-50/50 border border-slate-200/50 rounded-xl px-3 py-2.5 text-sm font-medium text-primary-600 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 focus:bg-white transition-all placeholder:text-slate-400" 
                  style="font-size: 16px;"
                  placeholder="0" 
                  required 
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="text-[11px] font-medium text-slate-500 ml-1 uppercase tracking-wide">Stok</label>
                <input 
                  v-model="form.stock" 
                  type="number" 
                  class="w-full bg-slate-50/50 border border-slate-200/50 rounded-xl px-3 py-2.5 text-sm text-slate-700 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 focus:bg-white transition-all placeholder:text-slate-400" 
                  style="font-size: 16px;"
                />
              </div>
              <div class="space-y-1">
                <label class="text-[11px] font-medium text-slate-500 ml-1 uppercase tracking-wide">Min. Stok</label>
                <input 
                  v-model="form.min_stock" 
                  type="number" 
                  class="w-full bg-slate-50/50 border border-slate-200/50 rounded-xl px-3 py-2.5 text-sm text-slate-700 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 focus:bg-white transition-all placeholder:text-slate-400" 
                  style="font-size: 16px;"
                />
              </div>
            </div>

            <!-- Compact Photo Section -->
            <div class="flex items-center gap-3 p-3 bg-slate-50/50 rounded-xl border border-dashed border-slate-200/60">
              <div class="w-14 h-14 rounded-lg overflow-hidden bg-white shadow-sm flex-shrink-0 border border-slate-100 flex items-center justify-center text-slate-300">
                <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
              </div>
              <div class="flex-1">
                <label class="block">
                  <span class="sr-only">Choose profile photo</span>
                  <input 
                    type="file" 
                    @change="handleFileUpload" 
                    accept="image/*" 
                    class="block w-full text-xs text-slate-500
                      file:mr-3 file:py-1.5 file:px-3
                      file:rounded-full file:border-0
                      file:text-[10px] file:font-semibold
                      file:bg-primary-50 file:text-primary-700
                      hover:file:bg-primary-100
                      cursor-pointer"
                  />
                </label>
                <p class="text-[10px] text-slate-400 mt-1 font-medium ml-1">Klik tombol di atas untuk foto</p>
              </div>
            </div>
          </div>

          <div class="p-4 bg-white md:rounded-b-[2.5rem] border-t border-slate-50">
            <form @submit.prevent="saveProduct" class="flex gap-3">
              <button 
                type="button" 
                @click="closeModal" 
                class="flex-1 py-2.5 px-3 rounded-xl bg-slate-50 text-slate-600 font-medium text-xs hover:bg-slate-100 hover:text-slate-700 transition-all duration-200 active:scale-[0.98] border border-slate-200/50"
              >
                Batal
              </button>
              <button 
                type="submit" 
                class="flex-1 py-2.5 px-3 rounded-xl bg-primary-500 text-white font-medium text-xs shadow-lg shadow-primary-500/20 hover:bg-primary-600 hover:shadow-primary-500/30 transition-all duration-200 active:scale-[0.98] flex items-center justify-center gap-2"
              >
                <span>{{ editingProduct ? 'Update' : 'Simpan' }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </form>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showStockModal" class="fixed inset-0 z-[9999] md:z-50 flex items-center justify-center md:bg-black/60 md:p-3 md:backdrop-blur-sm overflow-hidden h-[100dvh] overscroll-none">
        <div class="bg-white w-full h-full md:h-auto md:max-w-md md:rounded-[2.5rem] shadow-none md:shadow-2xl overflow-hidden flex flex-col overscroll-none animate-fade-in-up">
           <!-- Header -->
           <div class="px-5 py-4 flex items-center gap-3 shrink-0 border-b border-slate-100/50">
             <button 
               @click="closeStockModal" 
               class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-slate-600 transition-all active:scale-90"
             >
               <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
               </svg>
             </button>
             <h2 class="text-lg font-medium text-slate-800 tracking-tight flex items-center gap-2">
               Update Stok
             </h2>
           </div>

           <!-- Content -->
           <div class="flex-1 p-5 space-y-5">
             <div class="text-center bg-slate-50/50 rounded-xl p-4 border border-slate-100/50">
                <p class="text-[10px] font-medium text-slate-400 uppercase tracking-widest mb-1">Stok Saat Ini</p>
                <p class="text-3xl font-medium text-slate-800 tracking-tight">
                  {{ formatPrice(stockForm.current_stock) }} 
                  <span class="text-base text-slate-400 font-medium ml-1">pcs</span>
                </p>
                <div class="mt-1 text-xs font-medium text-slate-500">{{ stockForm.product_name }}</div>
             </div>

             <div class="space-y-4">
               <div class="space-y-1.5">
                 <label class="text-[11px] font-medium text-slate-400 uppercase tracking-widest ml-1">Jumlah Penyesuaian</label>
                 <div class="relative group">
                    <input 
                     v-model="stockForm.adjustment" 
                     type="number" 
                     class="w-full bg-slate-50/50 border border-slate-200/50 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-800 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 focus:bg-white transition-all placeholder:text-slate-400" 
                     placeholder="Contoh: 10 atau -5"
                    />
                   <div class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-400">
                     pcs
                   </div>
                 </div>
                 <p class="text-[10px] text-slate-400 italic ml-1">*Gunakan angka negatif (-) untuk mengurangi stok</p>
               </div>

               <div class="space-y-1.5">
                 <label class="text-[11px] font-medium text-slate-400 uppercase tracking-widest ml-1">Catatan Penyesuaian</label>
                 <textarea 
                  v-model="stockForm.notes" 
                  class="w-full bg-slate-50/50 border border-slate-200/50 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 focus:bg-white transition-all resize-none placeholder:text-slate-400" 
                  rows="3"
                  placeholder="Contoh: Pembelian dari supplier X, retur barang, dll..."
                 ></textarea>
               </div>
             </div>
           </div>

           <div class="p-5 pt-0 bg-white flex gap-3">
              <button 
                @click="closeStockModal"
                class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-medium text-sm hover:bg-slate-200 transition-all active:scale-[0.98]"
              >
                Batal
              </button>
              <button 
                @click="saveStockAdjustment"
                :disabled="!stockForm.adjustment"
                class="flex-1 py-2.5 rounded-xl bg-primary-500 text-white font-medium text-sm shadow-lg shadow-primary-500/20 hover:bg-primary-600 disabled:opacity-50 disabled:shadow-none transition-all active:scale-[0.98] flex items-center justify-center gap-2"
              >
                Simpan Stok
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
           </div>
        </div>
      </div>
    </Teleport>
    </div>
  </div>
</template>
