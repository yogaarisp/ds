<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '@/api'
import { useSettingsStore } from '@/stores/settings'

const router = useRouter()
const settingsStore = useSettingsStore()
const loading = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)

onMounted(() => {
  settingsStore.loadSettings()
})

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  address: ''
})

async function handleRegister() {
  if (!form.value.name || !form.value.password) {
    alert('Nama dan password wajib diisi!')
    return
  }

  if (!form.value.email && !form.value.phone) {
    alert('Email atau Nomor HP wajib diisi salah satu!')
    return
  }
  
  if (form.value.password !== form.value.password_confirmation) {
    alert('Konfirmasi password tidak cocok!')
    return
  }
  
  loading.value = true
  try {
    const response = await authApi.register({
        name: form.value.name,
        email: form.value.email,
        phone: form.value.phone,
        password: form.value.password,
        password_confirmation: form.value.password_confirmation,
        address: form.value.address,
        is_customer: true
    })

    if (response.data.token) {
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user || response.data.customer))
      
      router.push('/customer/home')
    } else {
        // If auto-login not supported after register, redirect to login
        alert('Registrasi berhasil! Silakan login.')
        router.push('/customer/login')
    }
  } catch (error) {
    console.error('Registration failed:', error)
    const msg = error.response?.data?.message || 'Registrasi gagal. Silakan coba lagi.'
    alert(msg)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen w-full bg-gradient-to-br from-orange-50 via-[#FFF8F3] to-white flex flex-col relative overflow-hidden">
    
    <!-- Background Accents -->
    <div class="absolute top-[-20%] right-[-10%] w-[500px] h-[500px] bg-orange-200/20 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[400px] h-[400px] bg-orange-300/10 rounded-full blur-[80px] pointer-events-none"></div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col justify-center px-6 sm:px-8 relative z-10 py-6">
      
      <!-- Logo Section -->
      <div class="mb-6 text-center animate-fade-in-down">
        <div v-if="settingsStore.storeLogo" class="flex justify-center mb-2">
          <img 
            :src="settingsStore.storeLogo" 
            :alt="settingsStore.storeName"
            class="w-24 h-24 object-contain drop-shadow-md transform hover:scale-105 transition-transform duration-300"
          />
        </div>
        <div v-else class="w-16 h-16 bg-gradient-to-tr from-orange-500 to-orange-400 rounded-2xl mx-auto flex items-center justify-center shadow-lg shadow-orange-500/30 mb-4 transform rotate-3">
           <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
           </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">{{ settingsStore.storeName || 'Dimsum Sarang' }}</h1>
        <p class="text-slate-400 text-sm mt-1 font-medium tracking-wide">Buat akun untuk mulai memesan</p>
      </div>

      <!-- Register Card -->
      <div class="w-full max-w-sm mx-auto bg-white/60 backdrop-blur-xl rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/50 p-8 animate-fade-in-up">
        
        <div class="mb-6">
          <h2 class="text-xl font-semibold text-slate-800">Daftar Akun Baru</h2>
          <p class="text-slate-400 text-sm mt-1">Lengkapi data diri Anda</p>
        </div>

        <form @submit.prevent="handleRegister" class="space-y-4">
          
          <!-- Name Input -->
          <div class="relative group">
            <input 
              v-model="form.name"
              type="text" 
              id="name" 
              class="block px-5 pb-2.5 pt-5 w-full text-sm text-slate-900 bg-white/50 rounded-2xl border border-slate-200/60 appearance-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 peer transition-all duration-300 h-[50px] shadow-sm group-hover:bg-white/80"
              placeholder=" "
              required
            />
            <label 
              for="name" 
              class="absolute text-sm text-slate-400 duration-300 transform -translate-y-3 scale-75 top-3.5 z-10 origin-[0] left-5 peer-focus:text-orange-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3"
            >
              Nama Lengkap
            </label>
          </div>

          <!-- Email Input -->
          <div class="relative group">
            <input 
              v-model="form.email"
              type="email" 
              id="email" 
              class="block px-5 pb-2.5 pt-5 w-full text-sm text-slate-900 bg-white/50 rounded-2xl border border-slate-200/60 appearance-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 peer transition-all duration-300 h-[50px] shadow-sm group-hover:bg-white/80"
              placeholder=" "
            />
            <label 
              for="email" 
              class="absolute text-sm text-slate-400 duration-300 transform -translate-y-3 scale-75 top-3.5 z-10 origin-[0] left-5 peer-focus:text-orange-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3"
            >
              Email Address
            </label>
          </div>

          <!-- Phone Input -->
          <div class="relative group">
            <input 
              v-model="form.phone"
              type="tel" 
              id="phone" 
              class="block px-5 pb-2.5 pt-5 w-full text-sm text-slate-900 bg-white/50 rounded-2xl border border-slate-200/60 appearance-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 peer transition-all duration-300 h-[50px] shadow-sm group-hover:bg-white/80"
              placeholder=" "
            />
            <label 
              for="phone" 
              class="absolute text-sm text-slate-400 duration-300 transform -translate-y-3 scale-75 top-3.5 z-10 origin-[0] left-5 peer-focus:text-orange-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3"
            >
              Nomor HP (WhatsApp)
            </label>
          </div>

          <!-- Address Input -->
          <div class="relative group">
            <textarea 
              v-model="form.address"
              id="address" 
              class="block px-5 pb-2.5 pt-5 w-full text-sm text-slate-900 bg-white/50 rounded-2xl border border-slate-200/60 appearance-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 peer transition-all duration-300 min-h-[50px] shadow-sm group-hover:bg-white/80 resize-none"
              placeholder=" "
              rows="1"
            ></textarea>
            <label 
              for="address" 
              class="absolute text-sm text-slate-400 duration-300 transform -translate-y-3 scale-75 top-3.5 z-10 origin-[0] left-5 peer-focus:text-orange-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3"
            >
              Alamat (Opsional)
            </label>
          </div>

          <!-- Password Input -->
          <div class="relative group">
            <input 
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'" 
              id="password" 
              class="block px-5 pb-2.5 pt-5 w-full text-sm text-slate-900 bg-white/50 rounded-2xl border border-slate-200/60 appearance-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 peer transition-all duration-300 h-[50px] shadow-sm group-hover:bg-white/80"
              placeholder=" "
              required
            />
            <label 
              for="password" 
              class="absolute text-sm text-slate-400 duration-300 transform -translate-y-3 scale-75 top-3.5 z-10 origin-[0] left-5 peer-focus:text-orange-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3"
            >
              Password
            </label>
            <button 
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 hover:text-orange-500 transition-colors cursor-pointer"
            >
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>

          <!-- Confirm Password Input -->
          <div class="relative group">
            <input 
              v-model="form.password_confirmation"
              :type="showConfirmPassword ? 'text' : 'password'" 
              id="password_confirmation" 
              class="block px-5 pb-2.5 pt-5 w-full text-sm text-slate-900 bg-white/50 rounded-2xl border border-slate-200/60 appearance-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 peer transition-all duration-300 h-[50px] shadow-sm group-hover:bg-white/80"
              placeholder=" "
              required
            />
            <label 
              for="password_confirmation" 
              class="absolute text-sm text-slate-400 duration-300 transform -translate-y-3 scale-75 top-3.5 z-10 origin-[0] left-5 peer-focus:text-orange-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3"
            >
              Konfirmasi Password
            </label>
            <button 
              type="button"
              @click="showConfirmPassword = !showConfirmPassword"
              class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 hover:text-orange-500 transition-colors cursor-pointer"
            >
              <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>

          <button 
            type="submit" 
            :disabled="loading"
            class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-medium py-4 rounded-full shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 active:scale-[0.98] transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed mt-4 flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ loading ? 'Memproses...' : 'Daftar Sekarang' }}
          </button>

        </form>
      </div>

      <!-- Footer -->
      <div class="mt-6 text-center animate-fade-in-up" style="animation-delay: 100ms;">
        <p class="text-slate-500 text-sm">
          Sudah punya akun? 
          <router-link to="/customer/login" class="font-semibold text-orange-600 hover:text-orange-700 transition-colors">Masuk disini</router-link>
        </p>
      </div>

    </div>
  </div>
</template>

<style scoped>
.font-outfit {
  font-family: 'Outfit', sans-serif;
}

@keyframes fade-in-down {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-down {
  animation: fade-in-down 0.6s ease-out forwards;
}

.animate-fade-in-up {
  animation: fade-in-up 0.6s ease-out forwards;
}
</style>