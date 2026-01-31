import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '../api'
import router from '../router'

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
    try {
      await authApi.logout()
    } catch (error) {
      // Ignore error
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      router.push('/login')
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
  }
})
