<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { customerAddressApi } from '@/api'
import Swal from 'sweetalert2'

const router = useRouter()
const addresses = ref([])
const loading = ref(false)
const showAddModal = ref(false)
const editingAddress = ref(null)

// Form data
const form = ref({
  label: 'Rumah',
  recipient_name: '',
  phone: '',
  address: '',
  district: '',
  city: '',
  province: '',
  postal_code: '',
  notes: '',
  is_default: false
})

const labelOptions = [
  { value: 'Rumah', label: 'Rumah', icon: '🏠' },
  { value: 'Kantor', label: 'Kantor', icon: '🏢' },
  { value: 'Lainnya', label: 'Lainnya', icon: '📍' }
]

onMounted(() => {
  fetchAddresses()
})

async function fetchAddresses() {
  loading.value = true
  try {
    const response = await customerAddressApi.getAll()
    addresses.value = response.data
  } catch (error) {
    console.error('Failed to fetch addresses:', error)
    Swal.fire('Error', 'Gagal memuat alamat', 'error')
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editingAddress.value = null
  form.value = {
    label: 'Rumah',
    recipient_name: '',
    phone: '',
    address: '',
    district: '',
    city: '',
    province: '',
    postal_code: '',
    notes: '',
    is_default: addresses.value.length === 0 // Set as default if first address
  }
  showAddModal.value = true
}

function openEditModal(address) {
  editingAddress.value = address
  form.value = { ...address }
  showAddModal.value = true
}

async function saveAddress() {
  try {
    if (editingAddress.value) {
      await customerAddressApi.update(editingAddress.value.id, form.value)
      Swal.fire('Berhasil', 'Alamat berhasil diperbarui', 'success')
    } else {
      await customerAddressApi.create(form.value)
      Swal.fire('Berhasil', 'Alamat berhasil ditambahkan', 'success')
    }
    
    showAddModal.value = false
    fetchAddresses()
  } catch (error) {
    console.error('Failed to save address:', error)
    Swal.fire('Error', 'Gagal menyimpan alamat', 'error')
  }
}

async function deleteAddress(address) {
  const result = await Swal.fire({
    title: 'Hapus Alamat?',
    text: `Apakah Anda yakin ingin menghapus alamat "${address.label}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal'
  })

  if (result.isConfirmed) {
    try {
      await customerAddressApi.delete(address.id)
      Swal.fire('Terhapus', 'Alamat berhasil dihapus', 'success')
      fetchAddresses()
    } catch (error) {
      console.error('Failed to delete address:', error)
      Swal.fire('Error', 'Gagal menghapus alamat', 'error')
    }
  }
}

async function setDefaultAddress(address) {
  try {
    await customerAddressApi.setDefault(address.id)
    Swal.fire('Berhasil', 'Alamat utama berhasil diubah', 'success')
    fetchAddresses()
  } catch (error) {
    console.error('Failed to set default address:', error)
    Swal.fire('Error', 'Gagal mengubah alamat utama', 'error')
  }
}

function getLabelIcon(label) {
  const option = labelOptions.find(opt => opt.value === label)
  return option ? option.icon : '📍'
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 bg-white z-50 px-4 py-3 shadow-sm border-b border-slate-100 flex items-center gap-3 h-14">
      <button @click="router.back()" class="p-2 -ml-2 text-slate-600 hover:bg-slate-50 rounded-full active:scale-90 transition-transform">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </button>
      <h1 class="text-lg font-bold text-slate-800">Alamat Saya</h1>
    </div>

    <!-- Content -->
    <div class="pt-16 pb-24 px-4">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="flex items-center gap-3 text-slate-400">
          <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="font-medium text-sm">Memuat alamat...</span>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="addresses.length === 0" class="text-center py-12">
        <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <h3 class="font-bold text-slate-600 mb-2">Belum Ada Alamat</h3>
        <p class="text-sm text-slate-400 mb-6">Tambahkan alamat untuk memudahkan pengiriman</p>
        <button @click="openAddModal" class="bg-orange-500 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-orange-500/25 hover:bg-orange-600 transition-all active:scale-95">
          Tambah Alamat Pertama
        </button>
      </div>

      <!-- Address List -->
      <div v-else class="space-y-3">
        <div 
          v-for="address in addresses" 
          :key="address.id"
          class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 relative"
        >
          <!-- Default Badge -->
          <div v-if="address.is_default" class="absolute top-3 right-3 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-full">
            UTAMA
          </div>

          <!-- Address Header -->
          <div class="flex items-start gap-3 mb-3">
            <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center text-lg shrink-0">
              {{ getLabelIcon(address.label) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="font-bold text-slate-800 text-sm">{{ address.label }}</h3>
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs text-slate-500">{{ address.recipient_name }}</span>
              </div>
              <p class="text-xs text-slate-400 mb-2">{{ address.phone }}</p>
              <p class="text-sm text-slate-600 leading-relaxed">{{ address.full_address || address.address }}</p>
              <p v-if="address.notes" class="text-xs text-slate-400 mt-1 italic">{{ address.notes }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 pt-3 border-t border-slate-50">
            <button 
              v-if="!address.is_default"
              @click="setDefaultAddress(address)"
              class="flex-1 bg-slate-50 text-slate-600 py-2 px-3 rounded-lg text-xs font-medium hover:bg-slate-100 transition-colors"
            >
              Jadikan Utama
            </button>
            <button 
              @click="openEditModal(address)"
              class="flex-1 bg-orange-50 text-orange-600 py-2 px-3 rounded-lg text-xs font-medium hover:bg-orange-100 transition-colors"
            >
              Edit
            </button>
            <button 
              @click="deleteAddress(address)"
              class="bg-red-50 text-red-500 py-2 px-3 rounded-lg text-xs font-medium hover:bg-red-100 transition-colors"
            >
              Hapus
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Button (Fixed) -->
    <div v-if="addresses.length > 0" class="fixed bottom-6 right-6 z-50">
      <button 
        @click="openAddModal"
        class="w-14 h-14 bg-orange-500 text-white rounded-full shadow-lg shadow-orange-500/25 flex items-center justify-center hover:bg-orange-600 transition-all active:scale-95"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-end justify-center z-[200]">
      <div class="bg-white rounded-t-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 pt-6 pb-3 flex justify-between items-center border-b border-slate-100">
          <h2 class="text-xl font-bold text-slate-800">
            {{ editingAddress ? 'Edit Alamat' : 'Tambah Alamat' }}
          </h2>
          <button @click="showAddModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Modal Content -->
        <div class="p-6 overflow-y-auto flex-1">
          <form @submit.prevent="saveAddress" class="space-y-4">
            <!-- Label -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Label Alamat</label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="option in labelOptions"
                  :key="option.value"
                  type="button"
                  @click="form.label = option.value"
                  class="p-3 border-2 rounded-xl flex flex-col items-center gap-1 transition-all"
                  :class="form.label === option.value 
                    ? 'border-orange-500 bg-orange-50 text-orange-600' 
                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                >
                  <span class="text-lg">{{ option.icon }}</span>
                  <span class="text-xs font-medium">{{ option.label }}</span>
                </button>
              </div>
            </div>

            <!-- Recipient Name -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Nama Penerima</label>
              <input
                v-model="form.recipient_name"
                type="text"
                required
                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                placeholder="Masukkan nama penerima"
              >
            </div>

            <!-- Phone -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Nomor Telepon</label>
              <input
                v-model="form.phone"
                type="tel"
                required
                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                placeholder="08xxxxxxxxxx"
              >
            </div>

            <!-- Address -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Alamat Lengkap</label>
              <textarea
                v-model="form.address"
                required
                rows="3"
                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all resize-none"
                placeholder="Jalan, nomor rumah, RT/RW, dll"
              ></textarea>
            </div>

            <!-- Location Details -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Kecamatan</label>
                <input
                  v-model="form.district"
                  type="text"
                  class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                  placeholder="Kecamatan"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Kota</label>
                <input
                  v-model="form.city"
                  type="text"
                  class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                  placeholder="Kota"
                >
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Provinsi</label>
                <input
                  v-model="form.province"
                  type="text"
                  class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                  placeholder="Provinsi"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Kode Pos</label>
                <input
                  v-model="form.postal_code"
                  type="text"
                  class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                  placeholder="12345"
                >
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Catatan (Opsional)</label>
              <textarea
                v-model="form.notes"
                rows="2"
                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all resize-none"
                placeholder="Patokan, warna rumah, dll"
              ></textarea>
            </div>

            <!-- Default Checkbox -->
            <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl">
              <input
                v-model="form.is_default"
                type="checkbox"
                id="is_default"
                class="w-4 h-4 text-orange-500 border-slate-300 rounded focus:ring-orange-500"
              >
              <label for="is_default" class="text-sm font-medium text-slate-700">
                Jadikan sebagai alamat utama
              </label>
            </div>
          </form>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 pb-6 pt-3 border-t border-slate-100">
          <div class="flex gap-3">
            <button 
              @click="showAddModal = false" 
              type="button"
              class="flex-1 py-3 rounded-xl text-slate-400 font-bold text-sm hover:bg-slate-50 transition-all"
            >
              Batal
            </button>
            <button 
              @click="saveAddress"
              class="flex-[2] bg-orange-500 py-3 rounded-xl text-white font-bold text-sm shadow-lg shadow-orange-500/20 hover:bg-orange-600 transition-all active:scale-[0.98]"
            >
              {{ editingAddress ? 'Perbarui' : 'Simpan' }} Alamat
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>