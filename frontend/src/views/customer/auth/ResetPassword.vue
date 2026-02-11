<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../../../api'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const verifying = ref(true)
const validToken = ref(false)
const error = ref('')
const success = ref(false)
const email = ref('')

const form = ref({
  password: '',
  password_confirmation: ''
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

onMounted(async () => {
  const token = route.query.token
  
  if (!token) {
    error.value = 'Token tidak valid'
    verifying.value = false
    return
  }
  
  // Verify token
  try {
    const response = await api.post('/password/verify-token', { token })
    validToken.value = true
    email.value = response.data.email
  } catch (err) {
    error.value = err.response?.data?.message || 'Token tidak valid atau sudah kadaluarsa'
    validToken.value = false
  } finally {
    verifying.value = false
  }
})

async function handleSubmit() {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Password tidak cocok'
    return
  }
  
  if (form.value.password.length < 6) {
    error.value = 'Password minimal 6 karakter'
    return
  }
  
  loading.value = true
  error.value = ''
  
  try {
    await api.post('/password/reset', {
      token: route.query.token,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation
    })
    
    success.value = true
    
    // Redirect to login after 2 seconds
    setTimeout(() => {
      router.push('/customer/login')
    }, 2000)
  } catch (err) {
    console.error('Reset password error:', err)
    error.value = err.response?.data?.message || 'Gagal mereset password'
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
      
      <!-- Card -->
      <div class="w-full max-w-sm mx-auto bg-white/60 backdrop-blur-xl rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/50 p-6 sm:p-8 animate-fade-in-up">
        
        <!-- Icon -->
        <div class="w-16 h-16 bg-gradient-to-tr from-orange-500 to-orange-400 rounded-2xl mx-auto flex items-center justify-center shadow-lg shadow-orange-500/30 mb-6">
          <svg v-if="!success" xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>

        <!-- Verifying State -->
        <div v-if="verifying" class="text-center py-8">
          <div class="w-8 h-8 border-3 border-orange-200 border-t-orange-500 rounded-full animate-spin mx-auto mb-4"></div>
          <p class="text-slate-500 text-sm">Memverifikasi token...</p>
        </div>

        <!-- Success State -->
        <div v-else-if="success" class="text-center">
          <h2 class="text-xl font-bold text-slate-800 mb-2">Password Berhasil Direset! 🎉</h2>
          <p class="text-slate-400 text-xs mb-6">Anda akan diarahkan ke halaman login...</p>
          <div class="w-8 h-8 border-3 border-orange-200 border-t-orange-500 rounded-full animate-spin mx-auto"></div>
        </div>

        <!-- Invalid Token State -->
        <div v-else-if="!validToken" class="text-center">
          <h2 class="text-xl font-bold text-slate-800 mb-2">Token Tidak Valid</h2>
          <p class="text-slate-400 text-xs mb-6">{{ error }}</p>
          <button 
            @click="router.push('/customer/forgot-password')"
            class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-medium py-3.5 rounded-full shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 active:scale-[0.98] transition-all duration-300"
          >
            Minta Link Baru
          </button>
        </div>

        <!-- Reset Form -->
        <div v-else>
          <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-slate-800">Buat Password Baru</h2>
            <p class="text-slate-400 text-xs mt-2">Untuk: <strong>{{ email }}</strong></p>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="mb-4 p-3 bg-red-50 text-red-500 text-xs rounded-xl flex items-center gap-2 border border-red-100/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ error }}
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-4">
            
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
                Password Baru
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

            <!-- Password Confirmation Input -->
            <div class="relative group">
              <input 
                v-model="form.password_confirmation"
                :type="showPasswordConfirmation ? 'text' : 'password'" 
                id="password_confirmation" 
                class="block px-5 pb-2.5 pt-5 w-full text-sm text-slate-900 bg-white/50 rounded-2xl border border-slate-200/60 appearance-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 peer transition-all duration-300 h-[52px] shadow-sm group-hover:bg-white/80"
                placeholder=" "
                required
              />
              <label 
                for="password_confirmation" 
                class="absolute text-sm text-slate-400 duration-300 transform -translate-y-3 scale-75 top-4 z-10 origin-[0] left-5 peer-focus:text-orange-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3"
              >
                Konfirmasi Password
              </label>
              <button 
                type="button"
                @click="showPasswordConfirmation = !showPasswordConfirmation"
                class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 hover:text-orange-500 transition-colors cursor-pointer"
              >
                <svg v-if="!showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-start gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              <p class="text-[10px] text-blue-600 leading-relaxed">Password minimal 6 karakter dan harus sama dengan konfirmasi password</p>
            </div>

            <button 
              type="submit" 
              :disabled="loading"
              class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-medium py-3.5 rounded-full shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 active:scale-[0.98] transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed mt-2 flex items-center justify-center gap-2"
            >
              <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ loading ? 'Menyimpan...' : 'Reset Password' }}
            </button>

          </form>
        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
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

.animate-fade-in-up {
  animation: fade-in-up 0.8s ease-out forwards;
}
</style>
