<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import Swal from 'sweetalert2'

const tables = ref([])
const loading = ref(false)
const showModal = ref(false)
const editingTable = ref(null)

const form = ref({
  name: '',
  code: '',
  capacity: 4,
  status: 'available'
})

const statusOptions = [
  { value: 'available', label: 'Tersedia', color: 'bg-emerald-100 text-emerald-700' },
  { value: 'occupied', label: 'Terisi', color: 'bg-amber-100 text-amber-700' },
  { value: 'reserved', label: 'Dipesan', color: 'bg-blue-100 text-blue-700' }
]

onMounted(() => {
  fetchTables()
})

async function fetchTables() {
  loading.value = true
  try {
    const response = await api.get('/tables')
    tables.value = response.data
  } catch (error) {
    console.error('Failed to fetch tables:', error)
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = {
    name: '',
    code: '',
    capacity: 4,
    status: 'available'
  }
  editingTable.value = null
}

function openAddModal() {
  resetForm()
  showModal.value = true
}

function openEditModal(table) {
  editingTable.value = table
  form.value = {
    name: table.name,
    code: table.code,
    capacity: table.capacity,
    status: table.status
  }
  showModal.value = true
}

async function saveTable() {
  try {
    if (editingTable.value) {
      await api.put(`/tables/${editingTable.value.id}`, form.value)
      Swal.fire({
        icon: 'success',
        title: 'Meja diupdate',
        timer: 1500,
        showConfirmButton: false,
        width: '280px',
        customClass: { popup: 'rounded-2xl', title: 'text-sm font-medium' }
      })
    } else {
      await api.post('/tables', form.value)
      Swal.fire({
        icon: 'success',
        title: 'Meja ditambahkan',
        timer: 1500,
        showConfirmButton: false,
        width: '280px',
        customClass: { popup: 'rounded-2xl', title: 'text-sm font-medium' }
      })
    }
    showModal.value = false
    resetForm()
    fetchTables()
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal menyimpan meja',
      width: '280px',
      customClass: { popup: 'rounded-2xl', title: 'text-sm font-medium' }
    })
  }
}

async function deleteTable(table) {
  const result = await Swal.fire({
    title: 'Hapus Meja?',
    text: `Hapus ${table.name}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF4444',
    cancelButtonColor: '#6B7280',
    confirmButtonText: 'Hapus',
    cancelButtonText: 'Batal',
    width: '280px',
    customClass: { popup: 'rounded-2xl', title: 'text-sm font-medium', confirmButton: 'text-xs px-3 py-1.5', cancelButton: 'text-xs px-3 py-1.5' }
  })

  if (result.isConfirmed) {
    try {
      await api.delete(`/tables/${table.id}`)
      Swal.fire({
        icon: 'success',
        title: 'Meja dihapus',
        timer: 1500,
        showConfirmButton: false,
        width: '280px',
        customClass: { popup: 'rounded-2xl', title: 'text-sm font-medium' }
      })
      fetchTables()
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal menghapus',
        width: '280px',
        customClass: { popup: 'rounded-2xl', title: 'text-sm font-medium' }
      })
    }
  }
}

function downloadQR(table) {
  // Generate QR Code using a simple library or external service
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(table.qr_url)}`
  
  // Create a temporary link to download
  const link = document.createElement('a')
  link.href = qrUrl
  link.download = `QR-${table.name}.png`
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  
  Swal.fire({
    icon: 'success',
    title: 'QR diunduh',
    timer: 1500,
    showConfirmButton: false,
    width: '280px',
    customClass: { popup: 'rounded-2xl', title: 'text-sm font-medium' }
  })
}

function getStatusBadgeClass(status) {
  const option = statusOptions.find(s => s.value === status)
  return option ? option.color : 'bg-slate-100 text-slate-700'
}

function getStatusLabel(status) {
  const option = statusOptions.find(s => s.value === status)
  return option ? option.label : status
}
</script>

<template>
  <div class="p-4 md:p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Kelola Meja</h1>
        <p class="text-slate-500 text-sm">Atur meja resto dan generate QR Code untuk pemesanan</p>
      </div>
      <button
        @click="openAddModal"
        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-medium shadow-lg shadow-primary-500/25 hover:shadow-xl hover:shadow-primary-500/30 transition-all duration-300"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Meja</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
    </div>

    <!-- Tables Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <div
        v-for="table in tables"
        :key="table.id"
        class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 hover:shadow-md transition-shadow"
      >
        <!-- Table Icon & Name -->
        <div class="flex items-center gap-3 mb-3">
          <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-100 to-slate-50 flex items-center justify-center text-orange-500 shadow-sm border border-orange-100/30">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
            </svg>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-slate-800">{{ table.name }}</h3>
            <p class="text-xs text-slate-400">Kapasitas: {{ table.capacity }} orang</p>
          </div>
        </div>

        <!-- Status Badge -->
        <div class="mb-3">
          <span :class="['px-2.5 py-1 rounded-full text-xs font-medium', getStatusBadgeClass(table.status)]">
            {{ getStatusLabel(table.status) }}
          </span>
        </div>

        <!-- QR URL Preview -->
        <div class="bg-slate-50 rounded-lg p-2 mb-3">
          <p class="text-[10px] text-slate-400 font-mono truncate">{{ table.qr_url }}</p>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <button
            @click="downloadQR(table)"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm font-medium hover:bg-emerald-100 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
            </svg>
            QR
          </button>
          <button
            @click="openEditModal(table)"
            class="px-3 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-200 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>
          <button
            @click="deleteTable(table)"
            class="px-3 py-2 bg-red-50 text-red-600 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="tables.length === 0" class="col-span-full text-center py-12">
        <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 mx-auto mb-4 border border-slate-100">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-700 mb-2">Belum Ada Meja</h3>
        <p class="text-slate-500 text-sm mb-4">Tambahkan meja pertama untuk mulai menggunakan fitur QR Order</p>
        <button
          @click="openAddModal"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg font-medium hover:bg-primary-600 transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Tambah Meja
        </button>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
          <h2 class="text-xl font-bold text-slate-800 mb-4">
            {{ editingTable ? 'Edit Meja' : 'Tambah Meja Baru' }}
          </h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Nama Meja</label>
              <input
                v-model="form.name"
                type="text"
                placeholder="Contoh: Meja 1"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Kode (untuk URL)</label>
              <input
                v-model="form.code"
                type="text"
                placeholder="Contoh: table-1 (kosongkan untuk auto)"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all"
              />
              <p class="text-xs text-slate-400 mt-1">Akan digunakan di URL QR Code</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Kapasitas</label>
              <input
                v-model.number="form.capacity"
                type="number"
                min="1"
                max="20"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
              <select
                v-model="form.status"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all"
              >
                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button
              @click="showModal = false"
              class="flex-1 px-4 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-medium hover:bg-slate-200 transition-colors"
            >
              Batal
            </button>
            <button
              @click="saveTable"
              class="flex-1 px-4 py-2.5 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-medium shadow-lg shadow-primary-500/25 hover:shadow-xl transition-all"
            >
              {{ editingTable ? 'Update' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
