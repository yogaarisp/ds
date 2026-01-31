<script setup>
import { ref, onMounted, computed } from 'vue'
import { inventoryApi, productApi } from '../api'

const items = ref([])
const products = ref([])
const loading = ref(false)
const activeTab = ref('products') // products | raw_material | packaging
const showModal = ref(false)
const showAddStockModal = ref(false)
const showProductStockModal = ref(false)
const editingItem = ref(null)
const selectedItem = ref(null)
const selectedProduct = ref(null)
const searchQuery = ref('')

const form = ref({
  name: '',
  code: '',
  type: 'raw_material',
  unit: '',
  stock: 0,
  min_stock: 0,
  price_per_unit: 0,
})

const addStockForm = ref({
  quantity: '',
  notes: '',
})

const filteredItems = computed(() => {
  return items.value.filter(item => {
    const matchesTab = item.type === activeTab.value
    const matchesSearch = !searchQuery.value || 
                         item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         item.code.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchesTab && matchesSearch
  })
})

const filteredProducts = computed(() => {
  return products.value.filter(product => {
    return !searchQuery.value || 
           product.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
           product.sku.toLowerCase().includes(searchQuery.value.toLowerCase())
  })
})

const productStockForm = ref({
  adjustment: '',
  notes: '',
})

// Add Product State
const showProductModal = ref(false)
const productForm = ref({
  name: '',
  sku: '',
  base_price: '',
  stock: 0,
  min_stock: 0,
})
const productImageFile = ref(null)
const productImagePreview = ref(null)

onMounted(() => {
  loadItems()
})

async function loadItems() {
  loading.value = true
  try {
    const [inventoryRes, productsRes] = await Promise.all([
      inventoryApi.getAll(),
      productApi.getAll()
    ])
    items.value = inventoryRes.data
    products.value = productsRes.data
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

function openModal(item = null) {
  editingItem.value = item
  if (item) {
    form.value = { ...item }
  } else {
    form.value = {
      name: '',
      code: '',
      type: activeTab.value,
      unit: '',
      stock: 0,
      min_stock: 0,
      price_per_unit: 0,
    }
  }
  showModal.value = true
}

async function saveItem() {
  try {
    const data = {
      ...form.value,
      stock: parseFloat(form.value.stock),
      min_stock: parseFloat(form.value.min_stock),
      price_per_unit: parseInt(form.value.price_per_unit),
    }

    if (editingItem.value) {
      await inventoryApi.update(editingItem.value.id, data)
    } else {
      await inventoryApi.create(data)
    }

    showModal.value = false
    loadItems()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menyimpan')
  }
}

function openAddStockModal(item) {
  selectedItem.value = item
  addStockForm.value = { quantity: '', notes: '' }
  showAddStockModal.value = true
}

async function addStock() {
  try {
    await inventoryApi.addStock(selectedItem.value.id, {
      quantity: parseFloat(addStockForm.value.quantity),
      notes: addStockForm.value.notes,
    })

    showAddStockModal.value = false
    loadItems()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menambah stok')
  }
}

function formatNumber(num) {
  return new Intl.NumberFormat('id-ID').format(num)
}

function openProductStockModal(product) {
  selectedProduct.value = product
  productStockForm.value = { adjustment: '', notes: '' }
  showProductStockModal.value = true
}

async function adjustProductStock() {
  try {
    const newStock = parseInt(selectedProduct.value.stock) + parseInt(productStockForm.value.adjustment)
    await productApi.updateStock(selectedProduct.value.id, newStock)
    showProductStockModal.value = false
    loadItems()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal update stok')
  }
}

function openAddProductModal() {
  productForm.value = {
    name: '',
    sku: '',
    base_price: '',
    stock: 0,
    min_stock: 0,
  }
  productImageFile.value = null
  productImagePreview.value = null
  showProductModal.value = true
}

function handleProductFileUpload(event) {
  const file = event.target.files[0]
  if (file) {
    productImageFile.value = file
    productImagePreview.value = URL.createObjectURL(file)
  }
}

async function saveProduct() {
  try {
    const formData = new FormData()
    formData.append('name', productForm.value.name)
    formData.append('sku', productForm.value.sku)
    formData.append('base_price', productForm.value.base_price)
    formData.append('stock', productForm.value.stock)
    formData.append('min_stock', productForm.value.min_stock)
    
    if (productImageFile.value) {
      formData.append('image', productImageFile.value)
    }

    await productApi.create(formData)
    showProductModal.value = false
    loadItems()
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menambah produk')
  }
}
</script>

<template>
  <div class="h-full overflow-y-auto bg-slate-50/50 custom-scrollbar pb-6">
    <!-- Simple & Cool Header -->
    <div class="sticky top-0 z-20 bg-slate-50/80 backdrop-blur-md px-4 md:px-6 pt-2.5 pb-1.5 border-b border-slate-100/50 shrink-0">
      <div class="flex flex-col md:flex-row justify-between md:items-center gap-3">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-base font-medium text-slate-800 leading-none">Inventory</h1>
            <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wide mt-1">Management Stok & Bahan Baku</p>
          </div>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
          <!-- Search Field -->
          <div class="relative flex-1 md:w-64 group">
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Cari inventory..." 
              class="w-full bg-white border border-slate-200 pl-9 pr-4 py-1.5 rounded-xl text-xs font-normal text-slate-700 placeholder:text-[10px] placeholder:text-slate-300 focus:outline-none focus:ring-4 focus:ring-primary-500/5 focus:border-primary-500 transition-all shadow-sm"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary-500 transition-colors">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>

          <button v-if="activeTab === 'products'" @click="openAddProductModal()" class="h-9 w-9 md:h-[34px] md:w-auto bg-primary-500 text-white rounded-xl flex items-center justify-center gap-0 md:gap-1.5 px-0 md:px-4 shadow-lg shadow-primary-500/20 active:scale-95 transition-all shrink-0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
            <span class="hidden md:inline text-[10px] font-medium uppercase tracking-wider">Produk</span>
          </button>
          <button v-else @click="openModal()" class="h-9 w-9 md:h-[34px] md:w-auto bg-primary-500 text-white rounded-xl flex items-center justify-center gap-0 md:gap-1.5 px-0 md:px-4 shadow-lg shadow-primary-500/20 active:scale-95 transition-all shrink-0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
            <span class="hidden md:inline text-[10px] font-medium uppercase tracking-wider">Item</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="px-2 md:px-6">
      <!-- Standardized Tabs -->
      <div class="bg-slate-100 p-1 rounded-2xl flex gap-1 overflow-x-auto no-scrollbar shrink-0 max-w-xl mb-3">
        <button
          v-for="t in [
            { id: 'products', label: 'Produk' },
            { id: 'raw_material', label: 'Bahan Baku' },
            { id: 'packaging', label: 'Packaging' }
          ]"
          :key="t.id"
          @click="activeTab = t.id"
          class="flex-1 min-w-[90px] px-4 py-2 rounded-xl text-[10px] font-medium transition-all flex items-center justify-center gap-2 whitespace-nowrap uppercase tracking-wider"
          :class="activeTab === t.id ? 'bg-white text-primary-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
        >
          {{ t.label }}
        </button>
      </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Memuat data...
    </div>

    <!-- Products Grid -->
    <div v-if="activeTab === 'products'" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-3">
      <div
        v-for="product in filteredProducts"
        :key="product.id"
        class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 hover:shadow-md transition-all group"
      >
        <div class="flex items-start gap-3 mb-3">
          <div class="w-10 h-10 flex-shrink-0 bg-slate-50 rounded-xl overflow-hidden border border-slate-100 group-hover:border-primary-100 transition-colors">
            <img 
              v-if="product.image" 
              :src="`http://localhost:8000/storage/${product.image}`" 
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-slate-200">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
            </div>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-medium text-slate-800 text-sm truncate uppercase tracking-tight">{{ product.name }}</h3>
            <p class="text-[10px] font-medium text-slate-400 mt-0.5 tracking-wider">{{ product.sku }}</p>
          </div>
          <span
            :class="[
              'px-2 py-0.5 rounded-full text-[9px] font-medium flex-shrink-0 uppercase tracking-wider',
              product.stock <= product.min_stock ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'
            ]"
          >
            {{ product.stock <= product.min_stock ? 'Low' : 'OK' }}
          </span>
        </div>

        <div class="text-2xl font-medium text-primary-600 mb-1 tracking-tight">
          {{ formatNumber(product.stock) }} <span class="text-xs text-slate-300 ml-1 uppercase">pcs</span>
        </div>

        <p class="text-[10px] font-medium text-slate-400 mb-4 uppercase tracking-widest">
          Min. Stok: <span class="text-slate-600">{{ formatNumber(product.min_stock) }}</span>
        </p>

        <div class="flex gap-2 pt-3 border-t border-slate-50">
          <button @click="openProductStockModal(product)" class="flex-1 py-2 rounded-xl bg-slate-50 text-slate-600 text-[10px] font-medium uppercase tracking-wider hover:bg-primary-50 hover:text-primary-600 transition-all active:scale-95 border border-slate-100">
            +/- Update Stok
          </button>
        </div>
      </div>
    </div>

    <!-- Inventory Items Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-3">
      <div
        v-for="item in filteredItems"
        :key="item.id"
        class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 transition-all hover:shadow-md"
      >
        <div class="flex justify-between items-start mb-3">
          <div class="min-w-0">
            <h3 class="font-medium text-slate-800 text-sm truncate uppercase tracking-tight">{{ item.name }}</h3>
            <p class="text-[10px] font-medium text-slate-400 mt-0.5 tracking-wider uppercase">{{ item.code }}</p>
          </div>
          <span
            :class="[
              'px-2 py-0.5 rounded-full text-[9px] font-medium uppercase tracking-wider',
              parseFloat(item.stock) <= parseFloat(item.min_stock) ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'
            ]"
          >
            {{ parseFloat(item.stock) <= parseFloat(item.min_stock) ? 'Low' : 'OK' }}
          </span>
        </div>

        <div class="text-2xl font-medium text-primary-600 mb-1 tracking-tight">
          {{ formatNumber(item.stock) }} <span class="text-xs text-slate-300 ml-1 uppercase font-medium">{{ item.unit }}</span>
        </div>

        <p class="text-[10px] font-medium text-slate-400 mb-4 uppercase tracking-widest">
          Min. Stok: <span class="text-slate-600">{{ formatNumber(item.min_stock) }} {{ item.unit }}</span>
        </p>

        <div class="flex gap-2 pt-3 border-t border-slate-50">
          <button @click="openAddStockModal(item)" class="flex-1 py-2 rounded-xl bg-slate-50 text-slate-600 text-[10px] font-medium uppercase tracking-wider hover:bg-emerald-50 hover:text-emerald-600 transition-all active:scale-95 border border-slate-100 flex items-center justify-center gap-1.5">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-3.5 h-3.5"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Stok
          </button>
          <button @click="openModal(item)" class="p-2 rounded-xl text-slate-400 hover:text-blue-500 transition-all active:scale-90 border border-slate-50">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4"><path d="m17 3 4 4-14 14H3v-4z"/></svg>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Item Modal -->
  <Teleport to="body">
    <div v-if="showModal" class="fixed inset-0 z-[9999] md:z-50 flex items-center justify-center md:bg-black/60 md:p-3 md:backdrop-blur-sm h-[100dvh] h-[100svh] overflow-hidden">
      <div class="bg-white w-full h-full md:h-auto md:max-w-md md:rounded-[2.5rem] shadow-none md:shadow-2xl overflow-hidden flex flex-col overscroll-none animate-fade-in-up">
        <!-- Header -->
        <div class="px-6 py-5 flex justify-between items-center bg-white shrink-0 border-b border-slate-100">
          <h2 class="text-lg font-medium text-slate-800 tracking-tight">
            {{ editingItem ? 'Edit Item' : 'Item Baru' }}
          </h2>
          <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveItem" class="flex-1 p-6 space-y-4 overflow-y-auto custom-scrollbar pb-10 md:pb-6">
          <div class="space-y-1">
            <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Nama Item</label>
            <input v-model="form.name" type="text" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all" required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Kode / SKU</label>
              <input v-model="form.code" type="text" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-600 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all" required />
            </div>
            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Tipe</label>
              <select v-model="form.type" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all">
                <option value="raw_material">Bahan Baku</option>
                <option value="packaging">Packaging</option>
              </select>
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Satuan</label>
            <input v-model="form.unit" type="text" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all" placeholder="gram, pcs, lembar" required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Stok Awal</label>
              <input v-model="form.stock" type="number" step="0.01" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all" />
            </div>
            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Min. Stok</label>
              <input v-model="form.min_stock" type="number" step="0.01" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all" />
            </div>
          </div>

          <div class="flex gap-3 pt-4">
            <button type="button" @click="showModal = false" class="flex-1 py-3.5 rounded-2xl bg-slate-50 text-slate-500 text-xs font-medium uppercase tracking-wider hover:bg-slate-100 transition-all border border-slate-100">
              Batal
            </button>
            <button type="submit" class="flex-1 py-3.5 rounded-2xl bg-primary-500 text-white text-xs font-medium uppercase tracking-wider shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all active:scale-[0.97]">
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Add Inventory Stock Modal -->
  <Teleport to="body">
    <div v-if="showAddStockModal" class="fixed inset-0 z-[9999] md:z-50 flex items-center justify-center md:bg-black/60 md:p-3 md:backdrop-blur-sm h-[100dvh] overflow-hidden">
      <div class="bg-white w-full h-full md:h-auto md:max-w-md md:rounded-[2.5rem] shadow-none md:shadow-2xl overflow-hidden flex flex-col overscroll-none animate-fade-in-up">
        <!-- Header -->
        <div class="px-6 py-5 flex justify-between items-center bg-white shrink-0 border-b border-slate-100">
          <h2 class="text-lg font-medium text-slate-800 tracking-tight">
            Tambah Stok: {{ selectedItem?.name }}
          </h2>
          <button @click="showAddStockModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="addStock" class="flex-1 p-6 space-y-6 overflow-y-auto custom-scrollbar">
          <div class="bg-slate-50 rounded-2xl p-4 text-center border border-slate-100/50">
            <p class="text-[9px] font-medium text-slate-400 uppercase tracking-widest mb-1">Stok Saat Ini</p>
            <p class="text-2xl font-medium text-primary-600 tracking-tight">
              {{ formatNumber(selectedItem?.stock) }} <span class="text-xs text-slate-300 font-medium ml-1">{{ selectedItem?.unit }}</span>
            </p>
          </div>

          <div class="space-y-4">
            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Jumlah Ditambahkan</label>
              <div class="relative">
                <input
                  v-model="addStockForm.quantity"
                  type="number"
                  step="0.01"
                  class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all"
                  :placeholder="`Dalam ${selectedItem?.unit}`"
                  required
                />
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400 uppercase">{{ selectedItem?.unit }}</span>
              </div>
            </div>

            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Catatan</label>
              <textarea v-model="addStockForm.notes" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all resize-none" rows="2" placeholder="Contoh: Pembelian dari supplier X"></textarea>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showAddStockModal = false" class="flex-1 py-3.5 rounded-2xl bg-slate-50 text-slate-500 text-xs font-medium uppercase tracking-wider hover:bg-slate-100 transition-all border border-slate-100">
              Batal
            </button>
            <button type="submit" class="flex-1 py-3.5 rounded-2xl bg-emerald-500 text-white text-xs font-medium uppercase tracking-wider shadow-lg shadow-emerald-500/20 hover:bg-emerald-600 transition-all active:scale-[0.97]">
              Simpan Stok
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Adjust Product Stock Modal -->
  <Teleport to="body">
    <div v-if="showProductStockModal" class="fixed inset-0 z-[9999] md:z-50 flex items-center justify-center md:bg-black/60 md:p-3 md:backdrop-blur-sm h-[100dvh] overflow-hidden">
      <div class="bg-white w-full h-full md:h-auto md:max-w-md md:rounded-[2.5rem] shadow-none md:shadow-2xl overflow-hidden flex flex-col overscroll-none animate-fade-in-up">
        <!-- Header -->
        <div class="px-6 py-5 flex justify-between items-center bg-white shrink-0 border-b border-slate-100">
          <h2 class="text-lg font-medium text-slate-800 tracking-tight">
            Adjust Stok: {{ selectedProduct?.name }}
          </h2>
          <button @click="showProductStockModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="adjustProductStock" class="flex-1 p-6 space-y-6 overflow-y-auto custom-scrollbar">
          <div class="bg-slate-50 rounded-2xl p-4 text-center border border-slate-100/50">
            <p class="text-[9px] font-medium text-slate-400 uppercase tracking-widest mb-1">Stok Saat Ini</p>
            <p class="text-2xl font-medium text-primary-600 tracking-tight">
              {{ formatNumber(selectedProduct?.stock) }} <span class="text-xs text-slate-300 font-medium ml-1">pcs</span>
            </p>
          </div>

          <div class="space-y-4">
            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Adjustment (+ tambah, - kurang)</label>
              <div class="relative">
                <input
                  v-model="productStockForm.adjustment"
                  type="number"
                  class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all"
                  placeholder="Contoh: 10 atau -5"
                  required
                />
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400 uppercase">pcs</span>
              </div>
            </div>

            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Catatan</label>
              <textarea v-model="productStockForm.notes" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all resize-none" rows="2" placeholder="Keterangan penyesuian..."></textarea>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showProductStockModal = false" class="flex-1 py-3.5 rounded-2xl bg-slate-50 text-slate-500 text-xs font-medium uppercase tracking-wider hover:bg-slate-100 transition-all border border-slate-100">
              Batal
            </button>
            <button type="submit" class="flex-1 py-3.5 rounded-2xl bg-primary-500 text-white text-xs font-medium uppercase tracking-wider shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all active:scale-[0.97]">
              Update Stok
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- New Product Modal -->
  <Teleport to="body">
    <div v-if="showProductModal" class="fixed inset-0 z-[9999] md:z-50 flex items-center justify-center md:bg-black/60 md:p-3 md:backdrop-blur-sm h-[100dvh] overflow-hidden">
      <div class="bg-white w-full h-full md:h-auto md:max-w-md md:rounded-[2.5rem] shadow-none md:shadow-2xl overflow-hidden flex flex-col overscroll-none animate-fade-in-up">
        <!-- Header -->
        <div class="px-6 py-5 flex justify-between items-center bg-white shrink-0 border-b border-slate-100">
          <h2 class="text-lg font-medium text-slate-800 tracking-tight">Menu Baru</h2>
          <button @click="showProductModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveProduct" class="flex-1 p-6 pt-2 space-y-4 overflow-y-auto custom-scrollbar pb-10 md:pb-6">
          <div class="space-y-1">
            <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Nama Produk</label>
            <input v-model="productForm.name" type="text" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all placeholder-slate-300" placeholder="Nama Menu..." required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">SKU</label>
              <input v-model="productForm.sku" type="text" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-600 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all placeholder-slate-300" placeholder="Kode..." required />
            </div>
            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Harga (Rp)</label>
              <input v-model="productForm.base_price" type="number" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-primary-600 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all placeholder-slate-300" placeholder="0" required />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Stok</label>
              <input v-model="productForm.stock" type="number" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all" />
            </div>
            <div class="space-y-1">
              <label class="text-[9px] font-medium text-slate-400 uppercase tracking-widest ml-1">Min. Stok</label>
              <input v-model="productForm.min_stock" type="number" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 outline-none transition-all" />
            </div>
          </div>

          <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-100">
            <div class="w-14 h-14 rounded-xl overflow-hidden bg-white shadow-sm flex-shrink-0 border border-slate-100 flex items-center justify-center text-slate-200">
              <img v-if="productImagePreview" :src="productImagePreview" class="w-full h-full object-cover" />
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
            </div>
            <div class="flex-1">
              <input type="file" @change="handleProductFileUpload" accept="image/*" class="text-[10px] file:bg-primary-50 file:text-primary-600 file:border-0 file:rounded-lg file:px-3 file:py-1.5 file:font-medium hover:file:bg-primary-100 cursor-pointer w-full uppercase tracking-wider"/>
              <p class="text-[9px] text-slate-400 mt-1 font-medium italic">Resolusi terbaik: 512x512</p>
            </div>
          </div>

          <div class="flex gap-3 pt-3">
            <button type="button" @click="showProductModal = false" class="flex-1 py-3.5 rounded-2xl bg-slate-50 text-slate-400 font-medium text-xs hover:bg-slate-100 transition-all uppercase tracking-widest">
              Batal
            </button>
            <button type="submit" class="flex-[2] bg-primary-500 py-3.5 rounded-2xl text-white font-medium text-xs shadow-lg shadow-primary-500/25 hover:bg-primary-600 transition-all active:scale-[0.97] uppercase tracking-widest">
              Simpan Produk
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
  </div>
</template>
