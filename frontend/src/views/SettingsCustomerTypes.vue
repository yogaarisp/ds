<script setup>
import { ref, onMounted } from 'vue'
import { customerTypeApi } from '../api' // Ensure this exists in api/index.js
import Swal from 'sweetalert2'

const customerTypes = ref([])
const loading = ref(false)
const showModal = ref(false)
const modalMode = ref('create') // create, edit
const form = ref({
  id: null,
  name: '',
  description: ''
})
const saving = ref(false)

onMounted(() => {
  loadCustomerTypes()
})

async function loadCustomerTypes() {
  loading.value = true
  try {
    const response = await customerTypeApi.getAll()
    customerTypes.value = response.data
  } catch (error) {
    console.error('Error loading customer types:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal memuat tipe customer'
    })
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  modalMode.value = 'create'
  form.value = { id: null, name: '', description: '' }
  showModal.value = true
}

function openEditModal(type) {
  modalMode.value = 'edit'
  form.value = { ...type }
  showModal.value = true
}

async function handleSave() {
  if (!form.value.name) return

  saving.value = true
  try {
    if (modalMode.value === 'create') {
      await customerTypeApi.create(form.value)
      Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Tipe customer berhasil ditambahkan',
        timer: 1500,
        showConfirmButton: false,
        width: '300px',
        customClass: {
          popup: 'rounded-xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })
    } else {
      await customerTypeApi.update(form.value.id, form.value)
      Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Tipe customer berhasil diperbarui',
        timer: 1500,
        showConfirmButton: false,
        width: '300px',
        customClass: {
          popup: 'rounded-xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })
    }
    showModal.value = false
    loadCustomerTypes()
    } catch (error) {
    console.error('Error saving customer type:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal menyimpan data',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } finally {
    saving.value = false
  }
}

async function handleDelete(type) {
  const result = await Swal.fire({
    title: 'Hapus Tipe Customer?',
    text: `Anda yakin ingin menghapus "${type.name}"? Data yang sudah terhubung mungkin akan error.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#3b82f6',
    width: '300px',
    customClass: {
      popup: 'rounded-xl text-sm',
      title: 'text-lg font-bold',
      htmlContainer: 'text-xs'
    }
  })

  if (result.isConfirmed) {
    try {
      await customerTypeApi.delete(type.id)
      Swal.fire({
        icon: 'success',
        title: 'Terhapus!',
        text: 'Tipe customer berhasil dihapus.',
        timer: 1500,
        showConfirmButton: false,
        width: '300px',
        customClass: {
          popup: 'rounded-xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })
      loadCustomerTypes()
    } catch (error) {
      console.error('Error deleting customer type:', error)
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error.response?.data?.message || 'Gagal menghapus data',
        width: '300px',
        customClass: {
          popup: 'rounded-xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })
    }
  }
}
</script>

<template>
  <div class="h-full space-y-4 animate-fade-in-up">
    <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
      <h2 class="text-lg font-bold text-slate-800 ml-2">Tipe Customer</h2>
      <button @click="openCreateModal" class="px-4 py-2 rounded-xl bg-primary-500 text-white font-bold text-xs hover:bg-primary-600 transition-all shadow-md shadow-primary-500/20 flex items-center justify-center gap-2">
        <span>+ Tambah Tipe</span>
      </button>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl overflow-hidden shadow-xl shadow-slate-200/40 border border-slate-100">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-slate-50/50">
              <th class="px-6 py-5 text-left text-[9px] font-bold text-slate-400 uppercase tracking-widest">Tipe</th>
              <th class="px-6 py-5 text-left text-[9px] font-bold text-slate-400 uppercase tracking-widest hidden md:table-cell">Deskripsi</th>
              <th class="px-6 py-5 text-right text-[9px] font-bold text-slate-400 uppercase tracking-widest">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="3" class="px-6 py-8 text-center text-slate-400 font-bold text-xs uppercase tracking-widest">Memuat data...</td>
            </tr>
            <tr v-else-if="customerTypes.length === 0">
              <td colspan="3" class="px-6 py-8 text-center text-slate-300 italic text-sm">Belum ada tipe customer</td>
            </tr>
            <tr v-for="type in customerTypes" :key="type.id" class="hover:bg-slate-50/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center font-bold text-sm shrink-0 border border-primary-100/50 shadow-sm">
                    {{ type.name.charAt(0).toUpperCase() }}
                  </div>
                  <p class="font-bold text-slate-700 text-sm tracking-tight leading-tight">{{ type.name }}</p>
                </div>
              </td>
              <td class="px-6 py-4 hidden md:table-cell">
                <p class="text-[10px] font-semibold text-slate-400 line-clamp-1 italic">{{ type.description || '-' }}</p>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button @click="openEditModal(type)" class="w-8 h-8 rounded-xl text-slate-400 hover:text-primary-500 hover:bg-primary-50 transition-all flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="handleDelete(type)" class="w-8 h-8 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all flex items-center justify-center">
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

    <!-- Modal Form -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-black/50 backdrop-blur-sm">
      <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col animate-fade-in-up">
        <div class="px-6 pt-6 pb-2 flex justify-between items-center text-slate-800">
          <h2 class="text-xl font-bold tracking-tight">
            {{ modalMode === 'create' ? 'Tipe Baru' : 'Edit Tipe' }}
          </h2>
          <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <form @submit.prevent="handleSave" class="p-6 pt-2 space-y-4">
          <div class="space-y-1.5">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Nama Tipe</label>
            <input 
              v-model="form.name" 
              type="text" 
              class="w-full bg-slate-50 border-none rounded-xl px-4 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" 
              placeholder="Contoh: VIP, Member..." 
              required
            />
          </div>
          
          <div class="space-y-1.5">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Deskripsi</label>
            <textarea 
              v-model="form.description" 
              class="w-full bg-slate-50 border-none rounded-xl px-4 py-3.5 text-sm text-slate-600 min-h-[100px] focus:ring-2 focus:ring-primary-500/10" 
              placeholder="Deskripsi singkat..."
            ></textarea>
          </div>

          <div class="pt-2 flex gap-3 text-slate-800">
            <button 
              type="button" 
              @click="showModal = false" 
              class="flex-1 py-4 rounded-xl text-slate-400 font-bold text-xs uppercase tracking-widest hover:bg-slate-50 transition-all"
            >
              Batal
            </button>
            <button 
              type="submit" 
              class="flex-[2] bg-primary-500 py-4 rounded-xl text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all disabled:opacity-50"
              :disabled="saving"
            >
              {{ saving ? 'Proses...' : 'Simpan Tipe' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
