import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

export const useCartStore = defineStore('customerCart', () => {
  const cart = ref(JSON.parse(localStorage.getItem('customer_cart') || '[]'))

  // Table info (from QR scan)
  const tableCode = ref(localStorage.getItem('customer_table_code') || null)
  const tableName = ref(localStorage.getItem('customer_table_name') || null)

  // Persist cart to localStorage
  watch(cart, (newVal) => {
    localStorage.setItem('customer_cart', JSON.stringify(newVal))
  }, { deep: true })

  // Persist table to localStorage
  watch([tableCode, tableName], ([code, name]) => {
    if (code) {
      localStorage.setItem('customer_table_code', code)
      localStorage.setItem('customer_table_name', name || '')
    } else {
      localStorage.removeItem('customer_table_code')
      localStorage.removeItem('customer_table_name')
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
  }

  function clearTable() {
    tableCode.value = null
    tableName.value = null
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
    clearTable,
    addItem,
    updateQuantity,
    removeItem,
    clearCart
  }
})
