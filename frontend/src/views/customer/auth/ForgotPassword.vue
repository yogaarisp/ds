<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../../api'

const router = useRouter()
const loading = ref(false)
const success = ref(false)
const error = ref('')
const email = ref('')

async function handleSubmit() {
  if (!email.value) return
  
  loading.value = true
  error.value = ''
  success.value = false
  
  try {
    await api.post('/password/forgot', {
      email: email.value
    })
    
    success.value = true
  } catch (err) {
    console.error('Forgot password error:', err)
    error.value = err.response?.data?.message || 'Gagal mengirim email reset password'
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
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
          </svg>
        </div>

        <div class="mb-6 text-center">
          <h2 class="text-xl font-bold text-slate-800">Lupa Password?</h2>
          <p class="text-slate-400 text-xs mt-2">Masukkan email Anda dan kami akan mengirimkan link untuk reset password</p>
        </div>

        <!-- Success Message -->
        <div v-if="success" class="mb-4 p-4 bg-emerald-50 text-emerald-600 text-xs rounded-xl flex items-start gap-3 border border-emerald-100/50">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <div>
            <p class="font-bold mb-1">Email Terkirim! ✉️</p>
            <p class="text-[11px] leading-relaxed">Link reset password telah dikirim ke <strong>{{ email }}</strong>. Silakan cek inbox atau folder spam Anda.</p>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mb-4 p-3 bg-red-50 text-red-500 text-xs rounded-xl flex items-center gap-2 border border-red-100/50">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          {{ error }}
        </div>

        <form v-if="!success" @submit.prevent="handleSubmit" class="space-y-4">
          
          <!-- Email Input -->
          <div class="relative group">
            <input 
              v-model="email"
              type="email" 
              id="email" 
              class="block px-5 pb-2.5 pt-5 w-full text-sm text-slate-900 bg-white/50 rounded-2xl border border-slate-200/60 appearance-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 peer transition-all duration-300 h-[52px] shadow-sm group-hover:bg-white/80"
              placeholder=" "
              required
            />
            <label 
              for="email" 
              class="absolute text-sm text-slate-400 duration-300 transform -translate-y-3 scale-75 top-4 z-10 origin-[0] left-5 peer-focus:text-orange-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3"
            >
              Email
            </label>
            <div class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
          </div>

          <button 
            type="submit" 
            :disabled="loading"
            class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-medium py-3.5 rounded-full shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 active:scale-[0.98] transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed mt-2 flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ loading ? 'Mengirim...' : 'Kirim Link Reset' }}
          </button>

          <!-- Back to Login -->
          <button 
            type="button"
            @click="router.push('/customer/login')"
            class="w-full text-slate-500 font-medium py-3 rounded-full hover:bg-slate-50 transition-all text-sm mt-2 flex items-center justify-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Login
          </button>

        </form>

        <!-- Success Actions -->
        <div v-else class="space-y-3">
          <button 
            @click="router.push('/customer/login')"
            class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-medium py-3.5 rounded-full shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 active:scale-[0.98] transition-all duration-300"
          >
            Kembali ke Login
          </button>
          <button 
            @click="success = false"
            class="w-full text-slate-500 font-medium py-3 rounded-full hover:bg-slate-50 transition-all text-sm"
          >
            Kirim Ulang Email
          </button>
        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
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
