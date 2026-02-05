<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '@/api'
import { useSettingsStore } from '@/stores/settings'

const router = useRouter()
const settingsStore = useSettingsStore()
const loading = ref(false)
const showPassword = ref(false)

onMounted(() => {
  settingsStore.loadSettings()
})

const form = ref({
  email_or_phone: '',
  password: '',
  remember: false
})

async function handleLogin() {
  if (!form.value.email_or_phone || !form.value.password) return
  
  loading.value = true
  try {
    const response = await authApi.login({
        email: form.value.email_or_phone,
        password: form.value.password,
        is_customer: true
    })

    if (response.data.token) {
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user || response.data.customer))
      
      router.push('/customer/home')
    }
  } catch (error) {
    console.error('Login failed:', error)
    alert('Login gagal. Periksa email/nomor HP dan password Anda.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="h-screen h-[100dvh] w-full bg-gradient-to-br from-orange-50 via-[#FFF8F3] to-white flex flex-col relative overflow-hidden">
    
    <!-- Background Accents -->
    <div class="absolute top-[-20%] right-[-10%] w-[500px] h-[500px] bg-orange-200/20 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[400px] h-[400px] bg-orange-300/10 rounded-full blur-[80px] pointer-events-none"></div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col justify-center px-6 sm:px-8 relative z-10 py-4 max-h-screen">
      
      <!-- Logo Section -->
      <div class="mb-4 text-center animate-fade-in-down">
        <div v-if="settingsStore.storeLogo" class="flex justify-center mb-2">
          <img 
            :src="settingsStore.storeLogo" 
            :alt="settingsStore.storeName"
            class="w-20 h-20 object-contain drop-shadow-md transform hover:scale-105 transition-transform duration-300"
          />
        </div>
        <div v-else class="w-14 h-14 bg-gradient-to-tr from-orange-500 to-orange-400 rounded-2xl mx-auto flex items-center justify-center shadow-lg shadow-orange-500/30 mb-2 transform rotate-3">
           <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
           </svg>
        </div>
        <h1 class="text-xl font-bold text-slate-800 tracking-tight">{{ settingsStore.storeName || 'Dimsum Sarang' }}</h1>
        <p class="text-slate-400 text-[11px] mt-0.5 font-medium tracking-wide">Nikmati Dimsum Hangat Setiap Hari</p>
      </div>

      <!-- Login Card -->
      <div class="w-full max-w-sm mx-auto bg-white/60 backdrop-blur-xl rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/50 p-6 sm:p-8 animate-fade-in-up">
        
        <div class="mb-6">
          <h2 class="text-lg font-semibold text-slate-800">Selamat Datang</h2>
          <p class="text-slate-400 text-xs mt-0.5">Masuk untuk mulai memesan</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-4">
          
          <!-- Email/Phone Input -->
          <div class="relative group">
            <input 
              v-model="form.email_or_phone"
              type="text" 
              id="identity" 
              class="block px-5 pb-2.5 pt-5 w-full text-sm text-slate-900 bg-white/50 rounded-2xl border border-slate-200/60 appearance-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 peer transition-all duration-300 h-[52px] shadow-sm group-hover:bg-white/80"
              placeholder=" "
              required
            />
            <label 
              for="identity" 
              class="absolute text-sm text-slate-400 duration-300 transform -translate-y-3 scale-75 top-4 z-10 origin-[0] left-5 peer-focus:text-orange-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3"
            >
              Email atau Nomor HP
            </label>
            <div class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>

          <!-- Password Input -->
          <div class="relative group">
            <input 
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'" 
              id="password" 
              class="block px-5 pb-2.5 pt-5 w-full text-sm text-slate-900 bg-white/50 rounded-2xl border border-slate-200/60 appearance-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 peer transition-all duration-300 h-[52px] shadow-sm group-hover:bg-white/80"
              placeholder=" "
              required
            />
            <label 
              for="password" 
              class="absolute text-sm text-slate-400 duration-300 transform -translate-y-3 scale-75 top-4 z-10 origin-[0] left-5 peer-focus:text-orange-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3"
            >
              Password
            </label>
            <button 
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 hover:text-orange-500 transition-colors cursor-pointer"
            >
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>

          <div class="flex items-center justify-between pt-0.5">
            <div class="flex items-center">
              <input 
                v-model="form.remember"
                id="remember-me" 
                type="checkbox" 
                class="w-3.5 h-3.5 text-orange-500 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 focus:ring-2 transition-all"
              >
              <label for="remember-me" class="ml-1.5 text-[10px] font-medium text-slate-500 cursor-pointer">Ingat Saya</label>
            </div>
            <a href="#" class="text-[10px] font-medium text-orange-500 hover:text-orange-600 transition-colors">Lupa Password?</a>
          </div>

          <button 
            type="submit" 
            :disabled="loading"
            class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-medium py-3.5 rounded-full shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 active:scale-[0.98] transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed mt-2 flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ loading ? 'Memproses...' : 'Masuk' }}
          </button>

        </form>
      </div>

      <!-- Footer -->
      <div class="mt-4 text-center animate-fade-in-up" style="animation-delay: 100ms;">
        <p class="text-slate-500 text-xs text-[11px]">
          Belum punya akun? 
          <router-link to="/customer/register" class="font-bold text-orange-600 hover:text-orange-700 transition-colors">Daftar Sekarang</router-link>
        </p>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* @import removed and moved to index.html */

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
  animation: fade-in-down 0.8s ease-out forwards;
}

.animate-fade-in-up {
  animation: fade-in-up 0.8s ease-out forwards;
}
</style>
