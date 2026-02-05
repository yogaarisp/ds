import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

export const useCartStore = defineStore('customerCart', () => {
  const cart = ref(JSON.parse(localStorage.getItem('customer_cart') || '[]'))

  // Table info (from QR scan)
  const tableCode = ref(localStorage.getItem('customer_table_code') || null)
  const tableName = ref(localStorage.getItem('customer_table_name') || null)
  const tableExpiry = ref(localStorage.getItem('customer_table_expiry') || null)

  // Persist cart to localStorage
  watch(cart, (newVal) => {
    localStorage.setItem('customer_cart', JSON.stringify(newVal))
  }, { deep: true })

  // Persist table to localStorage
  watch([tableCode, tableName, tableExpiry], ([code, name, expiry]) => {
    if (code) {
      localStorage.setItem('customer_table_code', code)
      localStorage.setItem('customer_table_name', name || '')
    } else {
      localStorage.removeItem('customer_table_code')
      localStorage.removeItem('customer_table_name')
    }

    if (expiry) {
      localStorage.setItem('customer_table_expiry', expiry)
    } else {
      localStorage.removeItem('customer_table_expiry')
    }
  })

  // Computed
  const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0)
  })

  const totalItems = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0)
  })

  const hasTable = computed(() => !!tableCode.value)

  // Actions
  function setTable(code, name) {
    tableCode.value = code
    tableName.value = name
    // Clear old expiry when setting new table manually/via QR
    setTableExpiry(null)
  }

  function setTableExpiry(timestamp) {
    tableExpiry.value = timestamp
  }

  function checkTableExpiry() {
    if (tableExpiry.value) {
      const expiry = parseInt(tableExpiry.value)

      if (isNaN(expiry)) {
        // Invalid expiry data, clear it to be safe
        setTableExpiry(null)
        return
      }

      const now = Date.now()
      if (now > expiry) {
        console.log('⏳ Table session expired. Clearing table.')
        clearTable()
      }
    }
  }

  function clearTable() {
    tableCode.value = null
    tableName.value = null
    tableExpiry.value = null
  }

  function addItem(item) {
    // Check if item exists
    // For products: match id
    // For packages: match package_id AND package_combination_id AND is_frozen

    const index = cart.value.findIndex(i => {
      if (item.type !== i.type) return false

      if (item.type === 'product') {
        return i.id === item.id
      }

      if (item.type === 'package') {
        return i.id === item.id &&
          i.package_combination_id === item.package_combination_id &&
          i.is_frozen === item.is_frozen
      }

      return false
    })

    if (index !== -1) {
      cart.value[index].quantity += item.quantity
    } else {
      cart.value.push({
        ...item,
        cart_id: Date.now() + Math.random().toString(36).substr(2, 9)
      })
    }
  }

  function updateQuantity(cartId, quantity) {
    const item = cart.value.find(i => i.cart_id === cartId)
    if (item) {
      if (quantity <= 0) {
        removeItem(cartId)
      } else {
        item.quantity = quantity
      }
    }
  }

  function removeItem(cartId) {
    const index = cart.value.findIndex(i => i.cart_id === cartId)
    if (index !== -1) {
      cart.value.splice(index, 1)
    }
  }

  function clearCart() {
    cart.value = []
  }

  return {
    cart,
    subtotal,
    totalItems,
    tableCode,
    tableName,
    hasTable,
    setTable,
    setTableExpiry,
    checkTableExpiry,
    clearTable,
    addItem,
    updateQuantity,
    removeItem,
    clearCart
  }
})
