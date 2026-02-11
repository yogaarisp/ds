<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { customerApi, customerTypeApi, transactionApi } from '../api'
import Swal from 'sweetalert2'

const STORAGE_URL = computed(() => import.meta.env.VITE_STORAGE_URL || '/storage')

const customers = ref([])
const customerTypes = ref([])
const loading = ref(false)
const showModal = ref(false)
const editingCustomer = ref(null)
const searchQuery = ref('')
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 15
})

const form = ref({
  name: '',
  phone: '',
  email: '',
  customer_type_id: '',
  discount_percentage: '',
  address: '',
  notes: '',
  password: '',
  avatar: null
})

const avatarFile = ref(null)
const avatarPreview = ref(null)

onMounted(() => {
  loadCustomers()
  loadCustomerTypes()
})

let searchTimeout = null
watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadCustomers(1)
  }, 300)
})

async function loadCustomers(page = 1) {
  loading.value = true
  try {
    const response = await customerApi.getAll({ 
      page,
      search: searchQuery.value 
    })
    customers.value = response.data.data
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      total: response.data.total,
      per_page: response.data.per_page
    }
  } catch (error) {
    console.error('Error loading customers:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Gagal memuat data customer',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    })
  } finally {
    loading.value = false
  }
}

async function loadCustomerTypes() {
  try {
    const response = await customerTypeApi.getAll()
    customerTypes.value = response.data
  } catch (error) {
    console.error('Error loading customer types:', error)
  }
}

function openModal(customer = null) {
  editingCustomer.value = customer
  avatarFile.value = null
  avatarPreview.value = null
  
  if (customer) {
    form.value = { 
      ...customer,
      customer_type_id: customer.customer_type?.id || customer.customer_type_id,
      password: '',
      avatar: null
    }
    // Set preview if customer has avatar
    if (customer.avatar) {
      avatarPreview.value = `${STORAGE_URL.value}/${customer.avatar}`
    }
  } else {
    form.value = {
      name: '',
      phone: '',
      email: '',
      customer_type_id: customerTypes.value.length > 0 ? customerTypes.value[0].id : '',
      discount_percentage: '',
      address: '',
      notes: '',
      password: 'dimsumsarang',
      avatar: null
    }
  }
  showModal.value = true
}

function handleAvatarChange(event) {
  const file = event.target.files[0]
  if (file) {
    avatarFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      avatarPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function removeAvatar() {
  avatarFile.value = null
  avatarPreview.value = null
  form.value.avatar = null
}

async function saveCustomer() {
  try {
    const formData = new FormData()
    
    // Append all form fields
    formData.append('name', form.value.name)
    formData.append('phone', form.value.phone)
    formData.append('email', form.value.email || '')
    formData.append('customer_type_id', form.value.customer_type_id)
    formData.append('discount_percentage', parseInt(form.value.discount_percentage) || 0)
    formData.append('address', form.value.address || '')
    formData.append('notes', form.value.notes || '')
    
    if (form.value.password) {
      formData.append('password', form.value.password)
    }
    
    // Append avatar if selected
    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }

    if (editingCustomer.value) {
      await customerApi.update(editingCustomer.value.id, formData)
    } else {
      await customerApi.create(formData)
    }

    showModal.value = false
    loadCustomers(pagination.value.current_page)
    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: editingCustomer.value ? 'Customer berhasil diupdate' : 'Customer berhasil ditambahkan',
      timer: 1500,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-2xl text-sm',
        title: 'text-lg font-medium',
        htmlContainer: 'text-xs'
      }
    })
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal menyimpan data',
      width: '300px',
      customClass: {
        popup: 'rounded-2xl text-sm',
        title: 'text-lg font-medium',
        htmlContainer: 'text-xs'
      }
    })
  }
}

async function deleteCustomer(customer) {
  const result = await Swal.fire({
    title: 'Hapus Customer?',
    text: `Anda yakin ingin menghapus customer "${customer.name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#3b82f6',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    width: '300px',
    customClass: {
      popup: 'rounded-2xl text-sm',
      title: 'text-lg font-medium',
      htmlContainer: 'text-xs'
    }
  })

  if (result.isConfirmed) {
    try {
      await customerApi.delete(customer.id)
      loadCustomers(pagination.value.current_page)
      Swal.fire({
        icon: 'success',
        title: 'Terhapus',
        text: 'Customer berhasil dihapus',
        timer: 1500,
        showConfirmButton: false,
        width: '300px',
        customClass: {
          popup: 'rounded-2xl text-sm',
          title: 'text-lg font-medium',
          htmlContainer: 'text-xs'
        }
      })
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: 'Gagal menghapus customer',
        width: '300px',
        customClass: {
          popup: 'rounded-2xl text-sm',
          title: 'text-lg font-medium',
          htmlContainer: 'text-xs'
        }
      })
    }
  }
}

function searchCustomers() {
  loadCustomers(1)
}

function clearSearch() {
  searchQuery.value = ''
  loadCustomers(1)
}

function formatPhone(phone) {
  if (!phone) return ''
  const cleaned = phone.replace(/\D/g, '')
  if (cleaned.startsWith('08')) {
    return cleaned.replace(/(\d{4})(\d{4})(\d+)/, '$1-$2-$3')
  }
  return phone
}

function getLevelColor(customerType) {
  const name = customerType?.name?.toLowerCase() || ''
  if (name === 'reseller') return 'bg-indigo-50 text-indigo-600 border border-indigo-100/50'
  if (name === 'keluarga') return 'bg-emerald-50 text-emerald-600 border border-emerald-100/50'
  if (name === 'reguler') return 'bg-slate-100 text-slate-500 border border-slate-200/50'
  return 'bg-amber-50 text-amber-600 border border-amber-100/50' // Default for custom types
}

function getTypeColor(type) {
  return type === 'vip' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'
}

// --- Transaction History Logic ---
const showHistoryModal = ref(false)
const selectedCustomer = ref(null)
const customerTransactions = ref([])
const transactionsLoading = ref(false)
const historyPagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})

const historyStartDate = ref('')
const historyEndDate = ref('')

watch([historyStartDate, historyEndDate], () => {
  if (showHistoryModal.value && selectedCustomer.value) {
    loadCustomerTransactions(selectedCustomer.value.id, 1)
  }
})

async function viewHistory(customer) {
  selectedCustomer.value = customer
  
  // Set default dates if empty
  if (!historyStartDate.value || !historyEndDate.value) {
    const today = new Date().toISOString().split('T')[0]
    historyStartDate.value = today
    historyEndDate.value = today
  }
  
  showHistoryModal.value = true
  customerTransactions.value = [] // Reset previous data
  await loadCustomerTransactions(customer.id)
}

async function loadCustomerTransactions(customerId, page = 1) {
  if (!customerId) return
  transactionsLoading.value = true
  try {
    const response = await transactionApi.getAll({
      customer_id: customerId,
      page: page,
      per_page: 10,
      from_date: historyStartDate.value,
      to_date: historyEndDate.value
    })
    customerTransactions.value = response.data.data // Adjust based on actual API response structure
    historyPagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      total: response.data.total
    }
  } catch (error) {
    console.error('Error loading transactions:', error)
    // Optional: show error toast
  } finally {
    transactionsLoading.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatCurrency(val) {
  return new Intl.NumberFormat('id-ID').format(val)
}

function getStatusColor(status) {
  switch (status) {
    case 'completed': return 'bg-emerald-50 text-emerald-600 border-emerald-100'
    case 'pending': return 'bg-amber-50 text-amber-600 border-amber-100'
    case 'cancelled': return 'bg-red-50 text-red-600 border-red-100'
    default: return 'bg-slate-50 text-slate-600 border-slate-100'
  }
}
</script>

<template>
  <div class="h-full overflow-y-auto bg-slate-50/50 custom-scrollbar pb-6">
    <!-- Simple & Cool Header -->
    <div class="sticky top-0 z-20 bg-slate-50/80 backdrop-blur-md px-4 md:px-6 pt-2.5 pb-1.5 border-b border-slate-100/50">
      <div class="flex flex-col md:flex-row justify-between md:items-center gap-3">
        <div class="flex items-center gap-3 mb-1 md:mb-0">
          <div>
            <h1 class="text-base font-medium text-slate-800 leading-none">Data Customers</h1>
            <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wider mt-1">Management Pelanggan & Loyalitas</p>
          </div>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
          <!-- Search Field -->
          <div class="relative flex-1 md:w-64 group">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari customer..."
              class="w-full bg-white border border-slate-200 pl-9 pr-4 py-1.5 rounded-xl text-xs font-normal text-slate-700 placeholder:text-[10px] placeholder:text-slate-300 outline-none focus:ring-4 focus:ring-primary-500/5 focus:border-primary-500 transition-all shadow-sm"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-300 group-focus-within:text-primary-500 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <button 
              v-if="searchQuery"
              @click="clearSearch"
              class="absolute inset-y-0 right-3 flex items-center text-slate-300 hover:text-red-500 transition-colors"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <button 
            @click="openModal()" 
            class="h-9 w-9 md:h-[34px] md:w-auto bg-primary-500 text-white rounded-xl flex items-center justify-center gap-0 md:gap-1.5 px-0 md:px-4 shadow-lg shadow-primary-500/20 active:scale-95 transition-all shrink-0"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
            <span class="hidden md:inline text-[10px] font-medium uppercase tracking-wider">Pelanggan</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content wrapper -->
    <div class="px-2 md:px-6">

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12 text-gray-500">
      Memuat data...
    </div>

    <!-- Customers Table -->
    <div v-else class="min-h-0">
      <!-- Mobile Cards (Lighter & Premium) -->
      <div class="lg:hidden space-y-3">
        <div v-for="customer in customers" :key="customer.id" @click="viewHistory(customer)" class="bg-white rounded-2xl p-3 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-slate-50 relative group active:scale-[0.98] transition-all cursor-pointer">
          <div class="flex justify-between items-start mb-2">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 text-sm font-medium shadow-sm">
                <img v-if="customer.avatar" :src="`${STORAGE_URL}/${customer.avatar}`" class="w-full h-full object-cover" />
                <span v-else>{{ customer.name.charAt(0).toUpperCase() }}</span>
              </div>
              <div>
                <h3 class="font-medium text-slate-800 text-sm leading-none">{{ customer.name }}</h3>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-[10px] font-medium text-slate-400">{{ formatPhone(customer.phone) }}</span>
                </div>
              </div>
            </div>
            <div class="flex gap-1">
              <button 
                @click.stop="openModal(customer)" 
                class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-50 text-blue-500 hover:bg-blue-100 transition-all active:scale-90"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button 
                @click.stop="deleteCustomer(customer)" 
                class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-100 transition-all active:scale-90"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
          
          <div class="flex flex-wrap gap-2 mb-2">
            <span :class="['px-2 py-0.5 rounded text-[9px] font-medium uppercase tracking-wider', getLevelColor(customer.customer_type)]">
              {{ customer.customer_type?.name }}
            </span>
            <span v-if="customer.discount_percentage > 0" class="px-2 py-0.5 rounded text-[9px] font-medium uppercase tracking-wider bg-emerald-50 text-emerald-600 border border-emerald-100/50">
              {{ customer.discount_percentage }}% Off
            </span>
          </div>
          
          <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-50">
            <div class="bg-slate-50/50 rounded-lg p-1.5">
               <p class="text-[7px] font-medium text-slate-400 uppercase tracking-widest mb-0.5">Orders</p>
               <p class="text-[11px] font-medium text-slate-700">{{ customer.total_orders }}x</p>
            </div>
            <div class="bg-slate-50/50 rounded-lg p-1.5 text-right">
               <p class="text-[7px] font-medium text-slate-400 uppercase tracking-widest mb-0.5">Spending</p>
               <p class="text-[11px] font-medium text-primary-600">Rp {{ new Intl.NumberFormat('id-ID').format(customer.total_spent) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Desktop Table -->
      <div class="hidden lg:block bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 overflow-hidden">
        <table class="w-full">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
              <th class="pl-8 pr-4 py-4 text-left text-xs font-medium text-slate-500">Informasi Pelanggan</th>
              <th class="px-4 py-4 text-left text-xs font-medium text-slate-500">Kontak & Alamat</th>
              <th class="px-4 py-4 text-center text-xs font-medium text-slate-500">Level</th>
              <th class="px-4 py-4 text-right text-xs font-medium text-slate-500">Statistik Order</th>
              <th class="pl-4 pr-8 py-4 text-center text-xs font-medium text-slate-500">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="customer in customers" :key="customer.id" @click="viewHistory(customer)" class="group hover:bg-slate-50/30 transition-colors border-b border-slate-50 last:border-b-0 cursor-pointer">
              <td class="pl-8 pr-4 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl overflow-hidden bg-white border border-slate-100 flex items-center justify-center text-slate-400 text-xs font-medium italic shadow-sm group-hover:bg-primary-50 group-hover:text-primary-500 group-hover:border-primary-100 transition-all">
                    <img v-if="customer.avatar" :src="`${STORAGE_URL}/${customer.avatar}`" class="w-full h-full object-cover" />
                    <span v-else>{{ customer.name.charAt(0).toUpperCase() }}</span>
                  </div>
                  <div>
                    <p class="font-medium text-slate-800 text-sm group-hover:text-primary-600 transition-colors">{{ customer.name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Member Aktif</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-5">
                <div class="space-y-1">
                  <p class="text-sm font-medium text-slate-700 flex items-center gap-1.5">
                    <span class="text-xs">📞</span> {{ formatPhone(customer.phone) }}
                  </p>
                  <p v-if="customer.address" class="text-xs text-slate-500 truncate max-w-[200px] flex items-center gap-1.5">
                    <span class="text-xs">📍</span> {{ customer.address }}
                  </p>
                </div>
              </td>
              <td class="px-4 py-4 text-center">
                <div class="flex flex-col items-center gap-1">
                  <span :class="['px-2 py-0.5 rounded text-[9px] font-medium uppercase tracking-widest', getLevelColor(customer.customer_type)]">
                    {{ customer.customer_type?.name }}
                  </span>
                  <div v-if="customer.discount_percentage > 0" class="text-[9px] font-medium text-emerald-500 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100">
                    {{ customer.discount_percentage }}% OFF
                  </div>
                </div>
              </td>
              <td class="px-4 py-4 text-right">
                <div class="space-y-0.5">
                  <p class="text-sm font-medium text-slate-700">{{ customer.total_orders }}x</p>
                  <p class="text-xs font-medium text-primary-600">Rp {{ new Intl.NumberFormat('id-ID').format(customer.total_spent) }}</p>
                </div>
              </td>
              <td class="pl-4 pr-8 py-5 text-center text-sm font-medium">
                <div class="flex justify-center gap-2">
                  <button 
                    @click.stop="openModal(customer)" 
                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-400 hover:bg-blue-50 hover:text-blue-500 transition-all active:scale-95"
                    title="Edit Customer"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button 
                    @click.stop="deleteCustomer(customer)" 
                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all active:scale-95"
                    title="Hapus Customer"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

      <div v-if="customers.length === 0" class="text-center py-20 text-slate-300">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-16 h-16 mx-auto mb-4 opacity-50"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M15.31 6.5a4.5 4.5 0 0 1 0 7"/></svg>
        <p class="font-medium text-slate-400">Belum ada customer</p>
      </div>

      <!-- Pagination (Modern) -->
      <div v-if="pagination.last_page > 1" class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4 px-4 py-3">
        <div class="text-xs font-medium text-slate-500">
          Menampilkan {{ customers.length }} dari {{ pagination.total }} pelanggan
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="loadCustomers(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-100 text-slate-400 hover:bg-slate-50 disabled:opacity-30 transition-all shadow-sm active:scale-90"
          >
            ←
          </button>
          <div class="px-4 py-2 bg-white border border-slate-100 rounded-xl text-sm font-medium text-slate-700 shadow-sm">
            {{ pagination.current_page }} <span class="text-slate-300 mx-1">/</span> {{ pagination.last_page }}
          </div>
          <button
            @click="loadCustomers(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-100 text-slate-400 hover:bg-slate-50 disabled:opacity-30 transition-all shadow-sm active:scale-90"
          >
            →
          </button>
        </div>
      </div>

    <!-- Modal -->
    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4 overflow-hidden h-[100dvh]">
        <div class="bg-white w-full max-w-2xl max-h-[90vh] rounded-[2rem] md:rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in duration-300">
          <!-- Header -->
          <div class="px-6 py-4 flex items-center justify-between shrink-0 border-b border-slate-100/50">
            <h2 class="text-lg font-bold text-slate-800 tracking-tight">
                {{ editingCustomer ? 'Edit Customer' : 'Customer Baru' }}
            </h2>
            <button 
              @click="showModal = false" 
              class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all active:scale-90"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Form Content -->
          <div class="flex-1 px-6 py-5 overflow-y-auto custom-scrollbar bg-slate-50/30">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
              
              <!-- Left Column: Primary Info -->
              <div class="space-y-4">
                <!-- Avatar Upload (Integrated) -->
                <div class="flex items-center gap-4 p-3 bg-white rounded-2xl border border-slate-100 shadow-sm mb-2">
                  <div class="relative group shrink-0">
                    <div class="w-16 h-16 rounded-2xl overflow-hidden bg-slate-50 border-2 border-slate-100 flex items-center justify-center">
                      <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                      <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </div>
                    <button v-if="avatarPreview" @click="removeAvatar" type="button" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-md">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Foto Profil</p>
                    <input type="file" @change="handleAvatarChange" accept="image/*" class="hidden" ref="avatarInput" />
                    <button @click="$refs.avatarInput.click()" type="button" class="text-xs font-bold text-primary-500 hover:text-primary-600 transition-colors">
                      {{ avatarPreview ? 'Ganti Foto' : 'Upload Foto' }}
                    </button>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold text-slate-400 ml-1 uppercase tracking-widest">Nama Lengkap</label>
                  <input v-model="form.name" type="text" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 focus:border-primary-500 transition-all outline-none" placeholder="Masukkan nama..." required />
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold text-slate-400 ml-1 uppercase tracking-widest">Nomor Telepon</label>
                  <input v-model="form.phone" type="text" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 focus:border-primary-500 transition-all outline-none" placeholder="08xxxxxxxx" required />
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-400 ml-1 uppercase tracking-widest">Tipe Customer</label>
                    <select v-model="form.customer_type_id" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 focus:border-primary-500 transition-all outline-none appearance-none cursor-pointer" required>
                      <option value="" disabled>Pilih tipe...</option>
                      <option v-for="type in customerTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                    </select>
                </div>
              </div>

              <!-- Right Column: Security & Misc -->
              <div class="space-y-4">
                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold text-slate-400 ml-1 uppercase tracking-widest">Email (Login)</label>
                  <input v-model="form.email" type="email" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 focus:border-primary-500 transition-all outline-none" placeholder="user@gmail.com" />
                </div>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-bold text-slate-400 ml-1 uppercase tracking-widest">Password</label>
                  <input v-model="form.password" type="password" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 focus:border-primary-500 transition-all outline-none" :placeholder="editingCustomer ? 'Kosongkan jika tidak diubah' : 'Min 6 karakter'" />
                </div>

                <div class="grid grid-cols-1 gap-4">
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-400 ml-1 uppercase tracking-widest">Diskon Khusus (%)</label>
                    <input v-model="form.discount_percentage" type="number" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-primary-500 focus:ring-4 focus:ring-primary-500/5 focus:border-primary-500 transition-all outline-none" placeholder="0" />
                  </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-400 ml-1 uppercase tracking-widest">Catatan Admin</label>
                    <textarea v-model="form.notes" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 focus:border-primary-500 transition-all outline-none resize-none" rows="2" placeholder="Tambahkan catatan..."></textarea>
                </div>
              </div>

              <!-- Full Width Column: Address -->
              <div class="md:col-span-2 space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 ml-1 uppercase tracking-widest">Alamat Lengkap</label>
                <textarea v-model="form.address" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:ring-4 focus:ring-primary-500/5 focus:border-primary-500 transition-all outline-none resize-none" rows="2" placeholder="Jl. Contoh No. 123..."></textarea>
              </div>

            </div>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 bg-white border-t border-slate-100 flex gap-3">
            <button 
              type="button" 
              @click="showModal = false" 
              class="flex-1 py-3 px-4 rounded-2xl bg-slate-50 text-slate-500 font-bold text-xs hover:bg-slate-100 transition-all active:scale-95"
            >
              Batal
            </button>
            <button 
              @click="saveCustomer"
              type="submit" 
              class="flex-[2] py-3 px-4 rounded-2xl bg-primary-500 text-white font-bold text-xs shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all active:scale-95 flex items-center justify-center gap-2"
            >
              <span>{{ editingCustomer ? 'Update Data' : 'Simpan Customer' }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- History Modal -->
    <Teleport to="body">
      <div v-if="showHistoryModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-3 overflow-hidden h-[100dvh]">
        <div class="bg-white w-full max-w-4xl h-[90vh] md:h-[85vh] rounded-[2rem] shadow-2xl overflow-hidden flex flex-col">
          <!-- Header -->
          <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between bg-white sticky top-0 z-10 gap-4">
            <div class="flex flex-col">
              <h2 class="text-xl font-medium text-slate-800 tracking-tight">Riwayat Transaksi</h2>
              <p v-if="selectedCustomer" class="text-sm text-slate-500 font-medium mt-0.5">{{ selectedCustomer.name }}</p>
            </div>
            
            <div class="flex items-center gap-2">
              <div class="flex items-center gap-1.5 md:gap-3 bg-white p-1 rounded-2xl border border-slate-100 shadow-sm relative group">
                <div class="relative">
                  <input 
                    v-model="historyStartDate" 
                    type="date" 
                    class="bg-slate-50 border-none rounded-xl text-[10px] md:text-xs font-medium text-slate-600 focus:ring-2 focus:ring-primary-500/20 py-2 px-3 appearance-none outline-none"
                  />
                </div>
                <span class="text-slate-300 font-medium text-xs">–</span>
                <div class="relative">
                  <input 
                    v-model="historyEndDate" 
                    type="date" 
                    class="bg-slate-50 border-none rounded-xl text-[10px] md:text-xs font-medium text-slate-600 focus:ring-2 focus:ring-primary-500/20 py-2 px-3 appearance-none outline-none"
                  />
                </div>
                
                <!-- Clear Button -->
                <button 
                  v-if="historyStartDate || historyEndDate"
                  @click="historyStartDate = ''; historyEndDate = '';"
                  class="ml-1 p-2 text-slate-300 hover:text-red-500 transition-colors"
                  title="Bersihkan Filter"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <button 
                @click="loadCustomerTransactions(selectedCustomer.id, 1)"
                class="w-10 h-10 flex items-center justify-center rounded-2xl bg-white text-slate-400 hover:text-primary-500 transition-all border border-slate-100 shadow-sm active:rotate-180 duration-500"
                title="Refresh"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>

              <button @click="showHistoryModal = false" class="w-10 h-10 flex items-center justify-center rounded-2xl bg-slate-50 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all border border-slate-100 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-auto bg-slate-50/50 p-4 md:p-6 custom-scrollbar">
            <div v-if="transactionsLoading" class="flex flex-col items-center justify-center h-60 text-slate-400">
              <div class="w-10 h-10 border-4 border-slate-200 border-t-primary-500 rounded-full animate-spin mb-4"></div>
              <p class="font-medium text-sm">Memuat riwayat...</p>
            </div>

            <div v-else-if="customerTransactions.length === 0" class="flex flex-col items-center justify-center h-60 text-slate-400">
              <svg class="w-16 h-16 opacity-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
              <p class="font-medium">Belum ada riwayat transaksi</p>
            </div>

            <div v-else class="space-y-4">
               <!-- Transactions List -->
               <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                 <table class="w-full text-sm text-left">
                   <thead class="bg-slate-50/50 text-slate-500 font-medium border-b border-slate-100 hidden md:table-header-group">
                     <tr>
                       <th class="px-6 py-4">ID Transaksi</th>
                       <th class="px-6 py-4">Waktu</th>
                       <th class="px-6 py-4">Items</th>
                       <th class="px-6 py-4 text-center">Status</th>
                       <th class="px-6 py-4 text-right">Total</th>
                     </tr>
                   </thead>
                   <tbody class="divide-y divide-slate-50">
                     <tr v-for="trx in customerTransactions" :key="trx.id" class="hover:bg-slate-50/50 transition-colors flex flex-col md:table-row p-4 border-b border-slate-50 md:border-none md:p-0">
                       <td class="md:px-6 md:py-4 pb-1">
                         <div class="flex justify-between items-start md:block">
                            <div>
                                <span class="font-medium text-slate-700 block">#{{ trx.invoice_number || trx.id }}</span>
                                <div class="text-xs text-slate-400 mt-1">{{ trx.order_type === 'instant' ? 'Instant Order' : 'Scheduled' }}</div>
                            </div>
                            <!-- Mobile only status/date -->
                            <div class="md:hidden text-right">
                                <span :class="['px-2 py-0.5 rounded text-[10px] font-medium capitalize border inline-block mb-1', getStatusColor(trx.status)]">
                                    {{ trx.status }}
                                </span>
                                <div class="text-xs text-slate-500">{{ formatDate(trx.created_at) }}</div>
                            </div>
                         </div>
                       </td>
                       <td class="px-6 py-4 text-slate-600 hidden md:table-cell">
                         {{ formatDate(trx.created_at) }}
                       </td>
                       <td class="md:px-6 md:py-4 py-2 max-w-xs">
                         <p class="truncate text-slate-600 text-sm font-medium">{{ trx.items?.map(i => i.product?.name || i.package?.name || i.package_combination?.name || i.packageCombination?.name).join(', ') }}</p>
                         <p class="text-xs text-slate-400 mt-0.5">{{ trx.items?.length }} items</p>
                       </td>
                       <td class="px-6 py-4 text-center hidden md:table-cell">
                         <span :class="['px-2.5 py-1 rounded-lg text-xs font-medium capitalize border', getStatusColor(trx.status)]">
                           {{ trx.status }}
                         </span>
                       </td>
                       <td class="md:px-6 md:py-4 pt-1 text-right flex justify-between md:table-cell items-center">
                         <span class="text-xs text-slate-400 md:hidden">Total</span>
                         <div>
                             <div class="font-medium text-slate-800 text-base md:text-sm">Rp {{ formatCurrency(trx.total) }}</div>
                             <div v-if="trx.payment_method" class="text-xs text-emerald-600 font-medium mt-0.5">
                               {{ trx.payment_method.name }}
                             </div>
                         </div>
                       </td>
                     </tr>
                   </tbody>
                 </table>
               </div>
            </div>
            
             <!-- Pagination -->
            <div v-if="historyPagination.last_page > 1" class="flex justify-center gap-2 mt-6 pb-2">
               <button 
                 @click="loadCustomerTransactions(selectedCustomer.id, historyPagination.current_page - 1)"
                 :disabled="historyPagination.current_page <= 1"
                 class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 text-sm font-medium"
               >
                 Prev
               </button>
               <span class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-sm font-medium flex items-center">
                 {{ historyPagination.current_page }} / {{ historyPagination.last_page }}
               </span>
               <button 
                 @click="loadCustomerTransactions(selectedCustomer.id, historyPagination.current_page + 1)"
                 :disabled="historyPagination.current_page >= historyPagination.last_page"
                 class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 text-sm font-medium"
               >
                 Next
               </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
    </div>
  </div>
</template>
