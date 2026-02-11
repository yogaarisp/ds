<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import Swal from 'sweetalert2'

const tables = ref([])
const loading = ref(false)
const showModal = ref(false)
const editingTable = ref(null)
const activeFilter = ref('all')

const form = ref({
  name: '',
  code: '',
  capacity: 4,
  status: 'available'
})

const statusConfig = {
  available: { 
    label: 'Tersedia', 
    color: 'bg-emerald-500', 
    bgCard: 'border-emerald-200 bg-gradient-to-br from-emerald-50/80 to-white',
    badge: 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
    dot: 'bg-emerald-500',
    icon: '🟢'
  },
  occupied: { 
    label: 'Terisi', 
    color: 'bg-red-500', 
    bgCard: 'border-red-200 bg-gradient-to-br from-red-50/80 to-white',
    badge: 'bg-red-100 text-red-700 ring-1 ring-red-200',
    dot: 'bg-red-500',
    icon: '🔴'
  },
  finishing: { 
    label: 'Selesai Bayar', 
    color: 'bg-amber-500', 
    bgCard: 'border-amber-200 bg-gradient-to-br from-amber-50/80 to-white',
    badge: 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
    dot: 'bg-amber-500',
    icon: '🟡'
  },
  reserved: { 
    label: 'Dipesan', 
    color: 'bg-blue-500', 
    bgCard: 'border-blue-200 bg-gradient-to-br from-blue-50/80 to-white',
    badge: 'bg-blue-100 text-blue-700 ring-1 ring-blue-200',
    dot: 'bg-blue-500',
    icon: '🔵'
  }
}

const statusOptions = [
  { value: 'available', label: 'Tersedia' },
  { value: 'occupied', label: 'Terisi' },
  { value: 'finishing', label: 'Selesai Bayar' },
  { value: 'reserved', label: 'Dipesan' }
]

const filterTabs = [
  { value: 'all', label: 'Semua' },
  { value: 'available', label: '🟢 Tersedia' },
  { value: 'occupied', label: '🔴 Terisi' },
  { value: 'finishing', label: '🟡 Selesai Bayar' },
]

// Summary stats
const stats = computed(() => ({
  total: tables.value.length,
  available: tables.value.filter(t => t.status === 'available').length,
  occupied: tables.value.filter(t => t.status === 'occupied').length,
  finishing: tables.value.filter(t => t.status === 'finishing').length,
}))

// Filtered tables
const filteredTables = computed(() => {
  if (activeFilter.value === 'all') return tables.value
  return tables.value.filter(t => t.status === activeFilter.value)
})

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

async function clearTable(table) {
  const result = await Swal.fire({
    title: 'Bersihkan Meja?',
    text: `Set ${table.name} kembali ke Tersedia?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10B981',
    cancelButtonColor: '#6B7280',
    confirmButtonText: '✓ Bersihkan',
    cancelButtonText: 'Batal',
    width: '300px',
    customClass: { popup: 'rounded-2xl', title: 'text-sm font-medium', confirmButton: 'text-xs px-3 py-1.5', cancelButton: 'text-xs px-3 py-1.5' }
  })

  if (result.isConfirmed) {
    try {
      await api.post(`/tables/${table.id}/clear`)
      Swal.fire({
        icon: 'success',
        title: 'Meja tersedia kembali ✓',
        timer: 1500,
        showConfirmButton: false,
        width: '280px',
        customClass: { popup: 'rounded-2xl', title: 'text-sm font-medium' }
      })
      fetchTables()
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal membersihkan meja',
        width: '280px',
        customClass: { popup: 'rounded-2xl', title: 'text-sm font-medium' }
      })
    }
  }
}

function downloadQR(table) {
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(table.qr_url)}`
  
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

function getTimeSince(dateStr) {
  if (!dateStr) return ''
  const created = new Date(dateStr)
  const now = new Date()
  const diffMs = now - created
  const diffMins = Math.floor(diffMs / 60000)
  if (diffMins < 1) return 'Baru saja'
  if (diffMins < 60) return `${diffMins} menit`
  const diffHours = Math.floor(diffMins / 60)
  return `${diffHours} jam ${diffMins % 60} mnt`
}

function getConfig(status) {
  return statusConfig[status] || statusConfig.available
}
</script>

<template>
  <div class="p-4 md:p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Kelola Meja</h1>
        <p class="text-slate-500 text-sm">Atur meja resto dan pantau status secara real-time</p>
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

    <!-- Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
      <div class="bg-white rounded-xl p-3 border border-slate-100 shadow-sm">
        <div class="text-xs text-slate-400 font-medium mb-1">Total Meja</div>
        <div class="text-2xl font-bold text-slate-800">{{ stats.total }}</div>
      </div>
      <div class="bg-white rounded-xl p-3 border border-emerald-100 shadow-sm">
        <div class="flex items-center gap-1.5 text-xs text-emerald-600 font-medium mb-1">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>Tersedia
        </div>
        <div class="text-2xl font-bold text-emerald-700">{{ stats.available }}</div>
      </div>
      <div class="bg-white rounded-xl p-3 border border-red-100 shadow-sm">
        <div class="flex items-center gap-1.5 text-xs text-red-600 font-medium mb-1">
          <span class="w-2 h-2 rounded-full bg-red-500"></span>Terisi
        </div>
        <div class="text-2xl font-bold text-red-700">{{ stats.occupied }}</div>
      </div>
      <div class="bg-white rounded-xl p-3 border border-amber-100 shadow-sm">
        <div class="flex items-center gap-1.5 text-xs text-amber-600 font-medium mb-1">
          <span class="w-2 h-2 rounded-full bg-amber-500"></span>Selesai Bayar
        </div>
        <div class="text-2xl font-bold text-amber-700">{{ stats.finishing }}</div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2 mb-5 overflow-x-auto pb-1">
      <button
        v-for="tab in filterTabs"
        :key="tab.value"
        @click="activeFilter = tab.value"
        class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all duration-200"
        :class="activeFilter === tab.value 
          ? 'bg-slate-800 text-white shadow-sm' 
          : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
    </div>

    <!-- Tables Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <div
        v-for="table in filteredTables"
        :key="table.id"
        class="rounded-2xl p-4 shadow-sm border-2 hover:shadow-md transition-all duration-300 relative overflow-hidden"
        :class="getConfig(table.status).bgCard"
      >
        <!-- Pulsing dot indicator -->
        <div class="absolute top-3 right-3">
          <span class="relative flex h-3 w-3">
            <span 
              v-if="table.status === 'occupied'"
              class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
              :class="getConfig(table.status).dot"
            ></span>
            <span 
              class="relative inline-flex rounded-full h-3 w-3"
              :class="getConfig(table.status).dot"
            ></span>
          </span>
        </div>

        <!-- Table Header -->
        <div class="flex items-center gap-3 mb-3">
          <div 
            class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm border text-lg"
            :class="{
              'bg-emerald-50 border-emerald-200 text-emerald-600': table.status === 'available',
              'bg-red-50 border-red-200 text-red-600': table.status === 'occupied',
              'bg-amber-50 border-amber-200 text-amber-600': table.status === 'finishing',
              'bg-blue-50 border-blue-200 text-blue-600': table.status === 'reserved',
            }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-bold text-slate-800 truncate">{{ table.name }}</h3>
            <div class="flex items-center gap-2">
              <span class="text-[11px] text-slate-400">Kapasitas {{ table.capacity }}</span>
              <span class="text-slate-300">•</span>
              <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold', getConfig(table.status).badge]">
                {{ getConfig(table.status).label }}
              </span>
            </div>
          </div>
        </div>

        <!-- Active Transaction Info (Occupied/Finishing) -->
        <div 
          v-if="table.active_transaction && (table.status === 'occupied' || table.status === 'finishing')"
          class="rounded-xl p-3 mb-3 border"
          :class="{
            'bg-red-50/50 border-red-100': table.status === 'occupied',
            'bg-amber-50/50 border-amber-100': table.status === 'finishing',
          }"
        >
          <div class="flex items-center gap-2 mb-1.5">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="text-xs font-semibold text-slate-700">{{ table.active_transaction.customer_name }}</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ getTimeSince(table.active_transaction.created_at) }}
            </div>
            <span class="text-[11px] font-bold text-slate-700">
              Rp {{ Number(table.active_transaction.total).toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <!-- Available: QR URL Preview -->
        <div v-else class="bg-slate-50 rounded-lg p-2 mb-3">
          <p class="text-[10px] text-slate-400 font-mono truncate">{{ table.qr_url }}</p>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <!-- Clear Table Button (only for finishing) -->
          <button
            v-if="table.status === 'finishing'"
            @click="clearTable(table)"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-200 hover:shadow-lg hover:shadow-emerald-300 transition-all duration-200"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Bersihkan
          </button>
          
          <!-- QR Download -->
          <button
            v-if="table.status !== 'finishing'"
            @click="downloadQR(table)"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm font-medium hover:bg-emerald-100 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
            </svg>
            QR
          </button>

          <!-- Edit -->
          <button
            @click="openEditModal(table)"
            class="px-3 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-200 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>

          <!-- Delete -->
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
      <div v-if="filteredTables.length === 0" class="col-span-full text-center py-12">
        <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 mx-auto mb-4 border border-slate-100">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-700 mb-2">
          {{ activeFilter === 'all' ? 'Belum Ada Meja' : 'Tidak Ada Meja' }}
        </h3>
        <p class="text-slate-500 text-sm mb-4">
          {{ activeFilter === 'all' 
            ? 'Tambahkan meja pertama untuk mulai menggunakan fitur QR Order' 
            : 'Tidak ada meja dengan status ini' }}
        </p>
        <button
          v-if="activeFilter === 'all'"
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
