import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '../api'
import router from '../router'
import Swal from 'sweetalert2'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isOwner = computed(() => user.value?.role === 'owner')
  const isKasir = computed(() => user.value?.role === 'kasir')
  const isGudang = computed(() => user.value?.role === 'gudang')

  function checkAuth() {
    const savedToken = localStorage.getItem('token')
    const savedUser = localStorage.getItem('user')

    if (savedToken && savedUser) {
      token.value = savedToken
      user.value = JSON.parse(savedUser)
    }
  }

  async function promptAuthChoice() {
    const result = await Swal.fire({
      title: 'Sudah punya akun belum?',
      text: 'Silakan login jika sudah punya akun, atau daftar jika belum.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sudah, Mau Login',
      cancelButtonText: 'Belum, Mau Daftar',
      confirmButtonColor: '#f97316', // primary orange
      cancelButtonColor: '#64748b', // slate
      reverseButtons: true,
      width: '320px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-lg font-bold',
        htmlContainer: 'text-sm',
        confirmButton: 'px-4 py-2 rounded-xl text-sm font-bold',
        cancelButton: 'px-4 py-2 rounded-xl text-sm font-bold'
      }
    })

    if (result.isConfirmed) {
      router.push('/customer/login')
    } else if (result.dismiss === Swal.DismissReason.cancel) {
      router.push('/customer/register')
    }
  }

  async function login(email, password) {
    loading.value = true
    try {
      console.log('🔐 Login attempt:', {
        email,
        password: '***',
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent
      })

      // Test basic connectivity first
      console.log('🔍 Testing basic connectivity...')
      const testResponse = await fetch('https://ds.keetech.my.id/api/settings')
      console.log('🔍 Connectivity test:', {
        status: testResponse.status,
        statusText: testResponse.statusText
      })

      const response = await authApi.login({ email, password })
      console.log('✅ Login success:', response.data)

      token.value = response.data.token
      user.value = response.data.user

      localStorage.setItem('token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))

      router.push('/')
      return { success: true }
    } catch (error) {
      console.error('❌ Login error details:', {
        message: error.message,
        status: error.response?.status,
        statusText: error.response?.statusText,
        data: error.response?.data,
        headers: error.response?.headers,
        config: {
          url: error.config?.url,
          method: error.config?.method,
          baseURL: error.config?.baseURL,
          headers: error.config?.headers
        }
      })

      let errorMessage = 'Login gagal. Periksa koneksi internet.'

      if (error.response?.status === 422) {
        errorMessage = 'Email atau password salah.'
      } else if (error.response?.status === 401) {
        errorMessage = 'Kredensial tidak valid.'
      } else if (error.response?.status === 500) {
        errorMessage = 'Server error. Coba lagi nanti.'
      } else if (error.code === 'NETWORK_ERROR' || !error.response) {
        errorMessage = 'Tidak dapat terhubung ke server. Periksa koneksi internet.'
      }

      return {
        success: false,
        message: errorMessage
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchUser() {
    try {
      const response = await authApi.me()
      user.value = response.data
      localStorage.setItem('user', JSON.stringify(user.value))
      return user.value
    } catch (error) {
      console.error('Failed to fetch user:', error)
      return null
    }
  }

  async function logout() {
    // Remember if user is staff/admin before clearing
    const isStaff = user.value && ['owner', 'kasir', 'gudang'].includes(user.value.role)
    
    try {
      await authApi.logout()
    } catch (error) {
      // Ignore error
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      
      // Redirect based on user type
      // Staff/Admin -> login page
      // Customer -> customer home
      if (isStaff) {
        router.push('/login')
      } else {
        router.push('/customer/home')
      }
    }
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    isOwner,
    isKasir,
    isGudang,
    checkAuth,
    login,
    logout,
    fetchUser,
    promptAuthChoice,
  }
})
