<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { customerApi, customerTypeApi } from '../api'

const props = defineProps({
  searchQuery: String,
})

const emit = defineEmits(['close', 'customerAdded'])

const customerTypes = ref([])
const loading = ref(false)
const form = ref({
  name: props.searchQuery || '',
  phone: '',
  email: '',
  customer_type_id: '',
  type: 'regular',
})

onMounted(async () => {
  document.body.style.overflow = 'hidden'
  try {
    const response = await customerTypeApi.getAll()
    customerTypes.value = response.data
    if (customerTypes.value.length > 0) {
      // Set default to ID 1 (Reguler) if available, otherwise first item
      const reguler = customerTypes.value.find(t => t.name.toLowerCase() === 'reguler')
      form.value.customer_type_id = reguler ? reguler.id : customerTypes.value[0].id
    }
  } catch (error) {
    console.error('Failed to load customer types:', error)
  }
})

onUnmounted(() => {
  document.body.style.overflow = ''
})

// Watch for searchQuery changes to update form name
watch(() => props.searchQuery, (newQuery) => {
  if (newQuery && !form.value.name) {
    form.value.name = newQuery
  }
})

async function saveCustomer() {
  if (!form.value.name || !form.value.phone) {
    alert('Nama dan nomor telepon wajib diisi')
    return
  }

  loading.value = true
  try {
    const response = await customerApi.create(form.value)
    emit('customerAdded', response.data)
    emit('close')
  } catch (error) {
    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat()
      alert(errors.join('\n'))
    } else {
      alert(error.response?.data?.message || 'Gagal menyimpan customer')
    }
  } finally {
    loading.value = false
  }
}

function formatPhoneInput(e) {
  let value = e.target.value.replace(/\D/g, '')
  
  // Auto add 08 if user starts typing numbers
  if (value.length > 0 && !value.startsWith('08')) {
    if (value.startsWith('8')) {
      value = '0' + value
    } else if (!value.startsWith('0')) {
      value = '08' + value
    }
  }
  
  form.value.phone = value
}
</script>

<template>
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[110] flex items-center justify-center sm:p-4">
    <div class="bg-white w-full h-full sm:h-auto sm:max-w-lg sm:rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.2)] flex flex-col overflow-hidden animate-fade-in-up">
      <!-- Header -->
      <div class="px-6 py-5 border-b border-slate-50 flex items-center gap-3 bg-white sticky top-0 z-10 font-outfit">
        <button 
          @click="emit('close')" 
          class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-500 hover:text-slate-700 transition-all active:scale-95"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div class="flex-1">
          <h2 class="text-sm font-medium text-slate-800 tracking-tight">Tambah Customer Baru</h2>
          <p class="text-slate-400 text-[9px] mt-0.5 font-medium uppercase tracking-widest">Lengkapi data pembeli</p>
        </div>
      </div>

      <!-- Form -->
      <form id="quick-add-form" @submit.prevent="saveCustomer" class="p-5 space-y-4 overflow-auto flex-1 custom-scrollbar">
        <!-- Field: Name -->
        <div class="space-y-1.5">
          <label class="block text-[9px] font-medium text-slate-400 uppercase tracking-widest px-1">Nama Lengkap *</label>
          <div class="relative group">
            <input 
              v-model="form.name" 
              type="text" 
              class="w-full bg-slate-50 border-2 border-transparent rounded-2xl px-5 py-3 text-sm focus:bg-white focus:border-primary-500/30 focus:ring-4 focus:ring-primary-500/5 transition-all text-slate-700 font-medium placeholder:text-slate-400" 
              placeholder="Contoh: Budi Santoso"
              required 
              autofocus
            />
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19a4 4 0 00-8 0m4-9a4 4 0 110-8 4 4 0 010 8z" />
              </svg>
            </span>
          </div>
        </div>

        <!-- Field: Phone -->
        <div class="space-y-1.5">
          <label class="block text-[9px] font-medium text-slate-400 uppercase tracking-widest px-1">Nomor Telepon *</label>
          <div class="relative group">
            <input 
              :value="form.phone"
              @input="formatPhoneInput"
              type="tel" 
              class="w-full bg-slate-50 border-2 border-transparent rounded-2xl px-5 py-3 text-sm focus:bg-white focus:border-primary-500/30 focus:ring-4 focus:ring-primary-500/5 transition-all text-slate-700 font-medium placeholder:text-slate-400" 
              placeholder="08xxxxxxxxxx"
              required 
            />
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.25 4.5A2.25 2.25 0 014.5 2.25h2.25A2.25 2.25 0 019 4.5V6a2.25 2.25 0 01-2.25 2.25H6a11.25 11.25 0 009.75 9.75v-0.75A2.25 2.25 0 0118 15h1.5A2.25 2.25 0 0121.75 17.25V19.5A2.25 2.25 0 0119.5 21.75H18A15.75 15.75 0 012.25 6V4.5z" />
              </svg>
            </span>
          </div>
        </div>

        <!-- Field: Email -->
        <div class="space-y-1.5">
          <label class="block text-[9px] font-medium text-slate-400 uppercase tracking-widest px-1">Email <span class="text-[8px] opacity-50">(Opsional)</span></label>
          <div class="relative group">
            <input 
              v-model="form.email" 
              type="email" 
              class="w-full bg-slate-50 border-2 border-transparent rounded-2xl px-5 py-3 text-sm focus:bg-white focus:border-primary-500/30 focus:ring-4 focus:ring-primary-500/5 transition-all text-slate-700 font-medium placeholder:text-slate-400" 
              placeholder="budi@email.com"
            />
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v10.5A2.25 2.25 0 0118.75 19.5H5.25A2.25 2.25 0 013 17.25V6.75z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7.5l9 6 9-6" />
              </svg>
            </span>
          </div>
        </div>

        <!-- Field: Type -->
        <div class="space-y-1.5">
          <label class="block text-[9px] font-medium text-slate-400 uppercase tracking-widest px-1">Tipe Customer</label>
          <div class="relative">
            <select 
              v-model="form.customer_type_id" 
              class="w-full bg-slate-50 border-2 border-transparent rounded-2xl px-5 py-3 text-sm focus:bg-white focus:border-primary-500/30 focus:ring-4 focus:ring-primary-500/5 transition-all text-slate-700 font-medium appearance-none cursor-pointer"
            >
              <option v-for="type in customerTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
            <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Info Banner -->
        <div class="bg-primary-50 border border-primary-100/50 p-4 rounded-2xl flex gap-3">
          <span class="text-primary-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3a6 6 0 00-3.464 10.91c.287.2.464.528.464.878V17a2 2 0 002 2h2a2 2 0 002-2v-2.212c0-.35.177-.678.464-.878A6 6 0 0012 3z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 21h6" />
            </svg>
          </span>
          <p class="text-[9px] text-primary-700 font-medium leading-relaxed">
            Customer akan langsung terpilih setelah disimpan. Harga belanjaan akan menyesuaikan secara otomatis dengan level yang dipilih.
          </p>
        </div>

      </form>

      <!-- Footer Buttons -->
      <div class="px-5 pt-3 pb-20 sm:pb-5 bg-white border-t border-slate-50">
        <button 
          type="submit" 
          form="quick-add-form"
          class="w-full bg-primary-500 py-3.5 rounded-2xl text-white font-medium text-sm shadow-md hover:bg-primary-600 transition-all active:scale-[0.97] disabled:opacity-50 disabled:shadow-none flex items-center justify-center gap-2"
          :disabled="loading"
        >
          <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ loading ? 'Menyimpan...' : 'Simpan & Pilih' }}
        </button>
      </div>
    </div>
  </div>
</template>
