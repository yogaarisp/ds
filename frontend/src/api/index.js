import axios from 'axios'

// Detect if running in mobile app
const isCapacitor = window.Capacitor !== undefined

// API Base URL configuration
const API_BASE_URL = isCapacitor
  ? 'https://ds.keetech.my.id/api'
  : '/api'

const STORAGE_BASE_URL = isCapacitor
  ? 'https://ds.keetech.my.id/storage'
  : '/storage'

console.log('🔧 API Configuration:', {
  isCapacitor,
  API_BASE_URL,
  STORAGE_BASE_URL,
  userAgent: navigator.userAgent,
  platform: window.Capacitor?.getPlatform?.() || 'web'
})

const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 15000, // Increased timeout for mobile
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Test connectivity on app start
if (isCapacitor) {
  console.log('🔍 Testing API connectivity...')

  // Block Cloudflare analytics for mobile app
  const originalFetch = window.fetch
  window.fetch = function (url, options) {
    if (typeof url === 'string' && url.includes('cloudflareinsights.com')) {
      console.log('🚫 Blocked Cloudflare script:', url)
      return Promise.reject(new Error('Blocked by mobile app'))
    }
    return originalFetch.apply(this, arguments)
  }

  fetch(API_BASE_URL.replace('/api', '/api/settings'))
    .then(response => {
      console.log('✅ API Test Response:', {
        status: response.status,
        statusText: response.statusText,
        headers: Object.fromEntries(response.headers.entries())
      })
      return response.json()
    })
    .then(data => {
      console.log('✅ API Test Data:', data)
    })
    .catch(error => {
      console.error('❌ API Test Failed:', error)
    })
}

// Request interceptor untuk menambahkan token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  // Add mobile app identifier
  if (isCapacitor) {
    config.headers['X-Mobile-App'] = 'ds-pos'
    config.headers['X-App-Version'] = '1.0.5'
  }

  console.log('API Request:', {
    url: config.url,
    method: config.method,
    baseURL: config.baseURL,
    headers: config.headers
  })

  return config
})

// Response interceptor untuk handle error
api.interceptors.response.use(
  (response) => {
    console.log('API Response:', {
      url: response.config.url,
      status: response.status,
      data: response.data
    })
    return response
  },
  (error) => {
    console.error('API Error:', {
      url: error.config?.url,
      status: error.response?.status,
      data: error.response?.data,
      message: error.message
    })

    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      if (isCapacitor) {
        // For mobile app, redirect to login page
        window.location.href = '#/login'
      } else {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export { STORAGE_BASE_URL }
export default api

// Auth
export const authApi = {
  login: (data) => api.post('/login', data),
  register: (data) => api.post('/register', data),
  logout: () => api.post('/logout'),
  me: () => api.get('/me'),
}

// Products
export const productApi = {
  getAll: (params) => api.get('/products', { params }),
  get: (id) => api.get(`/products/${id}`),
  create: (data) => {
    if (data instanceof FormData) {
      return api.post('/products', data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }
    return api.post('/products', data)
  },
  update: (id, data) => {
    if (data instanceof FormData) {
      return api.post(`/products/${id}`, data, {
        headers: {
          'Content-Type': 'multipart/form-data',
          'X-HTTP-Method-Override': 'PUT' // PHP/Laravel workaround for PUT with FormData
        },
        params: { _method: 'PUT' } // Alternative Laravel workaround
      })
    }
    return api.put(`/products/${id}`, data)
  },
  delete: (id) => api.delete(`/products/${id}`),
  updateStock: (id, stock) => api.patch(`/products/${id}/stock`, { stock }),
}

// Packages
export const packageApi = {
  getAll: (params) => api.get('/packages', { params }),
  get: (id) => api.get(`/packages/${id}`),
  create: (data) => api.post('/packages', data),
  update: (id, data) => {
    if (data instanceof FormData) {
      return api.post(`/packages/${id}`, data, {
        headers: { 'Content-Type': 'multipart/form-data' },
        params: { _method: 'PUT' }
      })
    }
    return api.put(`/packages/${id}`, data)
  },
  delete: (id) => api.delete(`/packages/${id}`),
  createCombination: (packageId, data) => {
    if (data instanceof FormData) {
      return api.post(`/packages/${packageId}/combinations`, data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }
    return api.post(`/packages/${packageId}/combinations`, data)
  },
  updateCombination: (id, data) => {
    if (data instanceof FormData) {
      return api.post(`/combinations/${id}`, data, {
        headers: { 'Content-Type': 'multipart/form-data' },
        params: { _method: 'PUT' }
      })
    }
    return api.put(`/combinations/${id}`, data)
  },
  deleteCombination: (id) => api.delete(`/combinations/${id}`),
  updateDiscounts: (id, data) => api.put(`/packages/${id}/discounts`, data),
}

// Customers
export const customerApi = {
  getAll: (params) => api.get('/customers', { params }),
  get: (id) => api.get(`/customers/${id}`),
  create: (data) => api.post('/customers', data),
  update: (id, data) => api.put(`/customers/${id}`, data),
  delete: (id) => api.delete(`/customers/${id}`),
  search: (query) => api.get('/customers-search', { params: { q: query } }),
  stats: (id) => api.get(`/customers/${id}/stats`),
}

// Customer Types
export const customerTypeApi = {
  getAll: () => api.get('/customer-types'),
  create: (data) => api.post('/customer-types', data),
  update: (id, data) => api.put(`/customer-types/${id}`, data),
  delete: (id) => api.delete(`/customer-types/${id}`),
}

// Transactions
export const transactionApi = {
  getAll: (params) => api.get('/transactions', { params }),
  get: (id) => api.get(`/transactions/${id}`),
  create: (data) => api.post('/transactions', data),
  update: (id, data) => api.put(`/transactions/${id}`, data),
  // Complete transaction - supports both full payment and DP
  complete: (id, paidAmount, paymentMethodId, paymentType = 'full', dpAmount = 0) => api.post(`/transactions/${id}/complete`, {
    paid_amount: paidAmount,
    payment_method_id: paymentMethodId,
    payment_type: paymentType, // 'full' or 'dp'
    dp_amount: dpAmount
  }),
  // Pay remaining balance after DP
  payRemaining: (id, paidAmount, paymentMethodId) => api.post(`/transactions/${id}/pay-remaining`, {
    paid_amount: paidAmount,
    payment_method_id: paymentMethodId
  }),
  cancel: (id) => api.post(`/transactions/${id}/cancel`),
  todaySummary: () => api.get('/transactions-summary/today'),
  upcomingOrders: () => api.get('/transactions-summary/upcoming'),
  getPendingByCustomer: (customerId) => api.get('/transactions/pending-by-customer', {
    params: customerId ? { customer_id: customerId } : {}
  }),
  delete: (id) => api.delete(`/transactions/${id}`),
}

// Payment Methods
export const paymentMethodApi = {
  getAll: (params) => api.get('/payment-methods', { params }),
  create: (data) => api.post('/payment-methods', data),
  update: (id, data) => api.put(`/payment-methods/${id}`, data),
  delete: (id) => api.delete(`/payment-methods/${id}`),
}

// Inventory
export const inventoryApi = {
  getAll: (params) => api.get('/inventory', { params }),
  get: (id) => api.get(`/inventory/${id}`),
  create: (data) => api.post('/inventory', data),
  update: (id, data) => api.put(`/inventory/${id}`, data),
  delete: (id) => api.delete(`/inventory/${id}`),
  addStock: (id, data) => api.post(`/inventory/${id}/add-stock`, data),
  transactions: (id, params) => api.get(`/inventory/${id}/transactions`, { params }),
  lowStockWarning: () => api.get('/inventory-warning/low-stock'),
}

// Reports
export const reportApi = {
  dailySales: (params) => api.get('/reports/daily-sales', { params }),
  salesByProduct: (params) => api.get('/reports/sales-by-product', { params }),
  salesByPackage: (params) => api.get('/reports/sales-by-package', { params }),
  productStock: () => api.get('/reports/product-stock'),
  inventoryStock: (params) => api.get('/reports/inventory-stock', { params }),
  inventoryHistory: (params) => api.get('/reports/inventory-history', { params }),
  productHistory: (params) => api.get('/reports/product-history', { params }),
  monthlySummary: (params) => api.get('/reports/monthly-summary', { params }),
}


// Settings
export const settingApi = {
  getAll: () => api.get('/settings'),
  update: (data) => api.put('/settings', data),
  uploadLogo: (file) => {
    const formData = new FormData()
    formData.append('logo', file)
    return api.post('/settings/logo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  uploadFavicon: (file) => {
    const formData = new FormData()
    formData.append('favicon', file)
    return api.post('/settings/favicon', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  deleteLogo: () => api.delete('/settings/logo'),
}

// Users
export const userApi = {
  getAll: (params) => api.get('/users', { params }),
  get: (id) => api.get(`/users/${id}`),
  create: (data) => api.post('/users', data),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: (id) => api.delete(`/users/${id}`),
}

// Carousel Slides
export const carouselApi = {
  // Public - for customer
  getActive: () => api.get('/carousel-slides/active'),

  // Admin CRUD
  getAll: () => api.get('/carousel-slides'),
  get: (id) => api.get(`/carousel-slides/${id}`),
  create: (data) => {
    if (data instanceof FormData) {
      return api.post('/carousel-slides', data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }
    return api.post('/carousel-slides', data)
  },
  update: (id, data) => {
    if (data instanceof FormData) {
      return api.post(`/carousel-slides/${id}`, data, {
        headers: { 'Content-Type': 'multipart/form-data' },
        params: { _method: 'PUT' }
      })
    }
    return api.put(`/carousel-slides/${id}`, data)
  },
  delete: (id) => api.delete(`/carousel-slides/${id}`),
  reorder: (slides) => api.post('/carousel-slides/reorder', { slides }),
  toggleActive: (id) => api.post(`/carousel-slides/${id}/toggle-active`),
}

// Database Management
export const databaseApi = {
  info: () => api.get('/database/info'),
  export: () => {
    return api.get('/database/export', {
      responseType: 'blob',
      timeout: 300000 // 5 minutes timeout for large databases
    })
  },
  import: (file) => {
    const formData = new FormData()
    formData.append('sql_file', file)
    return api.post('/database/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 300000 // 5 minutes timeout for large imports
    })
  }
}

// Customer Addresses
export const customerAddressApi = {
  getAll: () => api.get('/customer-addresses'),
  get: (id) => api.get(`/customer-addresses/${id}`),
  create: (data) => api.post('/customer-addresses', data),
  update: (id, data) => api.put(`/customer-addresses/${id}`, data),
  delete: (id) => api.delete(`/customer-addresses/${id}`),
  setDefault: (id) => api.post(`/customer-addresses/${id}/set-default`),
}

// Tables
export const tableApi = {
  getAll: () => api.get('/tables'),
  get: (id) => api.get(`/tables/${id}`),
  create: (data) => api.post('/tables', data),
  update: (id, data) => api.put(`/tables/${id}`, data),
  delete: (id) => api.delete(`/tables/${id}`),
  findByCode: (code) => api.get(`/tables/code/${code}`),
  generateQr: (id) => api.get(`/tables/${id}/qr`),
}
