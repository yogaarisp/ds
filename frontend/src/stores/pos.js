import { defineStore } from 'pinia'
import { ref, computed, watch, nextTick } from 'vue'
import { productApi, packageApi, transactionApi, paymentMethodApi, inventoryApi } from '../api'

export const usePosStore = defineStore('pos', () => {
  // State
  const products = ref([])
  const packages = ref([])
  const packagingItems = ref([])
  const paymentMethods = ref([])
  const selectedCustomer = ref(null)
  const orderType = ref('instant') // instant | scheduled
  const scheduledDate = ref(null)
  const scheduledTime = ref(localStorage.getItem('pos_scheduled_time') || null)
  const cart = ref(JSON.parse(localStorage.getItem('pos_cart') || '[]'))
  const loading = ref(false)
  const error = ref(null)
  // Simple state for tracking
  const currentPendingTransactionId = ref(null)
  const pendingTransactionInfo = ref(null)
  const showPendingChoice = ref(false)

  // Load from localStorage
  const savedPendingId = localStorage.getItem('pos_current_pending_id')
  if (savedPendingId) {
    currentPendingTransactionId.value = savedPendingId
  }

  // Watch to persist
  watch(currentPendingTransactionId, (newVal) => {
    if (newVal) {
      localStorage.setItem('pos_current_pending_id', newVal)
    } else {
      localStorage.removeItem('pos_current_pending_id')
    }
  })

  // Initial load from local storage for customer
  const savedCustomer = localStorage.getItem('pos_selected_customer')
  if (savedCustomer) {
    selectedCustomer.value = JSON.parse(savedCustomer)
  }

  // Watchers to persist state
  watch(selectedCustomer, (newVal) => {
    if (newVal) {
      localStorage.setItem('pos_selected_customer', JSON.stringify(newVal))
    } else {
      localStorage.removeItem('pos_selected_customer')
    }
  }, { deep: true })

  watch(orderType, (newVal) => {
    localStorage.setItem('pos_order_type', newVal)
  })

  watch(scheduledDate, (newVal) => {
    if (newVal) localStorage.setItem('pos_scheduled_date', newVal)
    else localStorage.removeItem('pos_scheduled_date')
  })

  watch(scheduledTime, (newVal) => {
    if (newVal) localStorage.setItem('pos_scheduled_time', newVal)
    else localStorage.removeItem('pos_scheduled_time')
  })

  watch(cart, (newVal) => {
    localStorage.setItem('pos_cart', JSON.stringify(newVal))
  }, { deep: true })

  // Computed
  const cartSubtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.subtotal, 0)
  })

  const cartTotal = computed(() => cartSubtotal.value)

  const cartItemCount = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0)
  })

  // Actions
  async function loadData(force = false) {
    // If not forced and data already exists, skip loading
    if (!force && products.value.length > 0) {
      console.log('Using cached POS data')
      return
    }

    console.log('Loading POS data...')
    loading.value = true
    error.value = null
    try {
      await Promise.all([
        refreshProducts(),
        fetchPaymentMethods()
      ])
      console.log('Data loaded successfully. Products:', products.value.length)
    } catch (err) {
      error.value = 'Gagal memuat data'
      console.error('Error loading data:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchPaymentMethods() {
    const res = await paymentMethodApi.getAll({ active_only: true })
    paymentMethods.value = res.data
  }

  async function refreshProducts() {
    const params = { active: true }
    if (selectedCustomer.value) {
      params.customer_id = selectedCustomer.value.id
    }

    const [productsRes, packagesRes, inventoryRes] = await Promise.all([
      productApi.getAll(params),
      packageApi.getAll(params),
      inventoryApi.getAll({ ...params, limit: 100 }), // Get inventory items (assuming packaging)
    ])

    products.value = productsRes.data
    packages.value = packagesRes.data
    const allInventory = inventoryRes.data?.data || inventoryRes.data || []

    // Filter hanya item yang typenya 'packaging'
    packagingItems.value = allInventory.filter(item => item.type === 'packaging')

    // Delay cart price updates to prevent reactive update conflicts
    nextTick(() => {
      updateCartPrices()
    })
  }

  async function setCustomer(customer) {
    try {
      console.log('🔵 setCustomer called with:', customer?.name, customer?.id)
      selectedCustomer.value = customer
      await refreshProducts()

      // Check for pending transaction for this customer
      if (customer && customer.id) {
        try {
          console.log('🔍 Checking pending transaction for customer:', customer.id)
          const response = await transactionApi.getPendingByCustomer(customer.id)
          console.log('📦 API Response:', response.data)

          if (response.data && response.data.data) {
            const pendingTransaction = response.data.data
            console.log('✅ Found pending transaction:', pendingTransaction.id, pendingTransaction.invoice_number)

            // Store pending transaction info for user to choose
            pendingTransactionInfo.value = pendingTransaction
            showPendingChoice.value = true
          } else {
            console.log('⚪ No pending transaction found')
            currentPendingTransactionId.value = null
            pendingTransactionInfo.value = null
          }
        } catch (error) {
          console.error('❌ Error checking pending for customer:', error)
          currentPendingTransactionId.value = null
          pendingTransactionInfo.value = null
        }
      } else {
        console.log('⚪ No customer selected, clearing pending ID')
        currentPendingTransactionId.value = null
        pendingTransactionInfo.value = null
      }
    } catch (error) {
      console.error('❌ Error in setCustomer:', error)
    }
  }

  // Continue existing pending transaction
  function continuePendingTransaction() {
    if (pendingTransactionInfo.value) {
      currentPendingTransactionId.value = pendingTransactionInfo.value.id

      // Load pending transaction items into cart
      clearCart()
      pendingTransactionInfo.value.items.forEach(item => {
        const cartItem = {
          type: item.product_id ? 'product' : 'package',
          quantity: item.quantity,
          unit_price: item.unit_price || (item.subtotal / item.quantity),
          subtotal: item.subtotal
        }

        if (cartItem.type === 'product') {
          cartItem.product_id = item.product_id
          cartItem.name = item.product?.name || 'Unknown Product'
          cartItem.image = item.product?.image
        } else {
          cartItem.package_id = item.package_id
          cartItem.package_combination_id = item.package_combination_id
          cartItem.name = item.package_combination?.name || item.package?.name || 'Unknown Package'
          cartItem.image = item.package_combination?.image || item.package?.image
        }

        cart.value.push(cartItem)
      })

      // Load order info
      orderType.value = pendingTransactionInfo.value.order_type
      scheduledDate.value = pendingTransactionInfo.value.scheduled_date
      scheduledTime.value = pendingTransactionInfo.value.scheduled_time
        ? pendingTransactionInfo.value.scheduled_time.substring(0, 5)
        : null

      console.log('🛒 Loaded', cart.value.length, 'items into cart')
    }
    showPendingChoice.value = false
    pendingTransactionInfo.value = null
  }

  // Create new invoice for customer
  function createNewInvoice() {
    currentPendingTransactionId.value = null
    showPendingChoice.value = false
    pendingTransactionInfo.value = null
    console.log('➕ Will create new invoice')
  }

  // Load specific transaction directly (from Transactions page)
  function loadTransaction(transaction) {
    console.log('📥 Loading transaction directly:', transaction.id, transaction.invoice_number)

    // Set customer without triggering pending check
    if (transaction.customer) {
      selectedCustomer.value = transaction.customer
    } else {
      selectedCustomer.value = null
    }

    // Set order type & schedule
    orderType.value = transaction.order_type
    if (transaction.order_type === 'scheduled') {
      scheduledDate.value = transaction.scheduled_date
      scheduledTime.value = transaction.scheduled_time
        ? transaction.scheduled_time.substring(0, 5)
        : null
    } else {
      scheduledDate.value = null
      scheduledTime.value = null
    }

    // Set pending transaction ID
    currentPendingTransactionId.value = transaction.id

    // Clear and populate cart
    clearCart()
    transaction.items.forEach(item => {
      const cartItem = {
        type: item.product_id ? 'product' : 'package',
        quantity: item.quantity,
        unit_price: item.unit_price || (item.subtotal / item.quantity),
        subtotal: item.subtotal
      }

      if (cartItem.type === 'product') {
        cartItem.product_id = item.product_id
        cartItem.name = item.product?.name || 'Unknown Product'
        cartItem.image = item.product?.image
      } else {
        cartItem.package_id = item.package_id
        cartItem.package_combination_id = item.package_combination_id
        cartItem.name = item.package_combination?.name || item.package?.name || 'Unknown Package'
        cartItem.image = item.package_combination?.image || item.package?.image
      }

      cart.value.push(cartItem)
    })

    // Close any pending choice modal
    showPendingChoice.value = false
    pendingTransactionInfo.value = null

    console.log('✅ Loaded transaction with', cart.value.length, 'items')
  }

  function clearCustomer() {
    selectedCustomer.value = null
    currentPendingTransactionId.value = null
    refreshProducts()
  }

  async function refreshData() {
    loading.value = true
    error.value = null
    try {
      // Clear everything for fresh start
      clearCart()
      selectedCustomer.value = null
      currentPendingTransactionId.value = null

      // Reset order settings to default
      orderType.value = 'instant'
      scheduledDate.value = null
      scheduledTime.value = null

      // Refresh products and packages from server
      await refreshProducts()
    } catch (err) {
      error.value = 'Gagal memuat data'
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  function setOrderType(type) {
    orderType.value = type
    if (type === 'instant') {
      scheduledDate.value = null
      scheduledTime.value = null
    } else if (type === 'scheduled' && !scheduledDate.value) {
      // Set default to tomorrow if no date is set
      const tomorrow = new Date()
      tomorrow.setDate(tomorrow.getDate() + 1)
      scheduledDate.value = tomorrow.toISOString().split('T')[0]
    }
  }

  function addProduct(product, quantity = 1) {
    console.log('Store addProduct called with:', product, 'Qty:', quantity)
    const existingIndex = cart.value.findIndex(
      item => item.type === 'product' && item.product_id === product.id
    )

    const price = product.final_price || product.base_price
    const qty = parseInt(quantity) || 1

    if (existingIndex >= 0) {
      cart.value[existingIndex].quantity += qty
      cart.value[existingIndex].subtotal = cart.value[existingIndex].quantity * price
      console.log('Updated existing item quantity')
    } else {
      cart.value.push({
        type: 'product',
        product_id: product.id,
        name: product.name,
        image: product.image,
        unit_price: price,
        quantity: qty,
        subtotal: price * qty,
      })
      console.log('Added new item to cart')
    }
  }

  function addPackageCombination(pkg, combination) {
    const qty = combination.quantity || 1
    const price = combination.final_price || combination.base_price

    const existingIndex = cart.value.findIndex(
      item => item.type === 'package' &&
        item.package_id === pkg.id &&
        item.package_combination_id === combination.id &&
        item.is_frozen === (combination.is_frozen || false)
    )

    if (existingIndex >= 0) {
      cart.value[existingIndex].quantity += qty
      cart.value[existingIndex].subtotal = cart.value[existingIndex].quantity * price
    } else {
      cart.value.push({
        type: 'package',
        package_id: pkg.id,
        package_combination_id: combination.id,
        name: combination.name, // mix modal already formats name with (Frozen)
        image: combination.image || pkg.image,
        unit_price: price,
        quantity: qty,
        subtotal: price * qty,
        is_frozen: combination.is_frozen || false
      })
    }
  }

  function addInventoryItem(item, quantity = 1) {
    console.log('Store addInventoryItem called with:', item, 'Qty:', quantity)
    const price = item.selling_price || item.price || 0
    const qty = parseInt(quantity) || 1

    const existingIndex = cart.value.findIndex(
      cartItem => cartItem.type === 'inventory' && cartItem.inventory_id === item.id
    )

    if (existingIndex >= 0) {
      cart.value[existingIndex].quantity += qty
      cart.value[existingIndex].subtotal = cart.value[existingIndex].quantity * price
    } else {
      cart.value.push({
        type: 'inventory',
        inventory_id: item.id,
        name: item.name,
        image: item.image,
        unit_price: price,
        quantity: qty,
        subtotal: price * qty,
      })
    }
  }

  function updateCartItemQuantity(index, quantity) {
    if (quantity <= 0) {
      removeCartItem(index)
      return
    }
    cart.value[index].quantity = quantity
    cart.value[index].subtotal = cart.value[index].unit_price * quantity
  }

  function removeCartItem(index) {
    cart.value.splice(index, 1)
  }

  function clearCart() {
    cart.value = []
    // Don't clear currentPendingTransactionId - keep it for continuous adding
  }

  function updateCartPrices() {
    console.log('🔄 updateCartPrices running...')
    cart.value = cart.value.map(item => {
      let newPrice = item.unit_price

      if (item.type === 'product') {
        const product = products.value.find(p => p.id === item.product_id)
        if (product) {
          newPrice = product.final_price || product.base_price
          console.log(`Product ${item.name} new price: ${newPrice}`)
        } else {
          console.warn(`Product ${item.product_id} not found in updated list`)
        }
      } else if (item.type === 'package') {
        const pkg = packages.value.find(p => p.id === item.package_id)
        if (pkg) {
          const combination = pkg.combinations.find(c => c.id === item.package_combination_id)
          if (combination) {
            newPrice = combination.final_price || combination.base_price

            // Re-apply frozen discount if applicable
            if (item.is_frozen) {
              newPrice = newPrice - 1000
            }
            console.log(`Package ${item.name} new price: ${newPrice} (Frozen: ${item.is_frozen})`)
          } else {
            console.warn(`Combination ${item.package_combination_id} not found in package ${pkg.id}`)
          }
        } else {
          console.warn(`Package ${item.package_id} not found in updated list`)
        }
      } else if (item.type === 'inventory') {
        const inv = packagingItems.value.find(p => p.id === item.inventory_id)
        if (inv) {
          newPrice = inv.selling_price || inv.price || 0
        }
      }

      return {
        ...item,
        unit_price: newPrice,
        subtotal: newPrice * item.quantity,
      }
    })
  }

  async function checkout(paidAmount, paymentMethodId) {
    if (cart.value.length === 0) {
      throw new Error('Keranjang kosong')
    }

    // Validate scheduled order
    if (orderType.value === 'scheduled' && !scheduledDate.value) {
      throw new Error('Tanggal harus diisi untuk pre-order')
    }

    const items = cart.value.map(item => ({
      type: item.type,
      product_id: item.product_id,
      package_combination_id: item.package_combination_id,
      quantity: item.quantity,
      is_frozen: item.is_frozen || false,
      unit_price: item.unit_price, // Send calculated unit price from frontend
      subtotal: item.subtotal, // Send calculated subtotal from frontend
      // Map inventory type to product structure or keep separate based on backend requirement
      // Jika backend hanya terima product_id/package_combination_id, kita mungkin perlu penyesuaian.
      // Untuk sekarang kirim type: 'inventory' dan 'inventory_id'
      inventory_id: item.type === 'inventory' ? item.inventory_id : null
    }))

    const data = {
      customer_id: selectedCustomer.value?.id,
      order_type: orderType.value,
      items,
      subtotal: cartSubtotal.value, // Send frontend calculated subtotal
      total: cartTotal.value, // Send frontend calculated total
    }

    // Add scheduled fields only if order type is scheduled
    if (orderType.value === 'scheduled') {
      data.scheduled_date = scheduledDate.value
      if (scheduledTime.value) {
        data.scheduled_time = scheduledTime.value
      }
    }

    let transaction

    // SIMPLE LOGIC: If we have currentPendingTransactionId, UPDATE it. Otherwise CREATE new.
    if (currentPendingTransactionId.value) {
      try {
        const updateRes = await transactionApi.update(currentPendingTransactionId.value, data)
        transaction = updateRes.data
      } catch (error) {
        // If update fails, create new
        const createRes = await transactionApi.create(data)
        transaction = createRes.data
      }
    } else {
      const createRes = await transactionApi.create(data)
      transaction = createRes.data
    }

    // Complete transaction immediately
    const completeRes = await transactionApi.complete(transaction.id, paidAmount, paymentMethodId)

    // Clear everything since transaction is completed
    clearCart()
    currentPendingTransactionId.value = null
    selectedCustomer.value = null // Clear customer as requested
    refreshProducts() // Reset products to default prices

    return completeRes.data
  }

  async function createOrder() {
    if (cart.value.length === 0) {
      throw new Error('Keranjang kosong')
    }

    // Validate scheduled order
    if (orderType.value === 'scheduled' && !scheduledDate.value) {
      throw new Error('Tanggal harus diisi untuk pre-order')
    }

    const items = cart.value.map(item => ({
      type: item.type,
      product_id: item.product_id,
      package_combination_id: item.package_combination_id,
      quantity: item.quantity,
    }))

    const data = {
      customer_id: selectedCustomer.value?.id,
      order_type: orderType.value,
      items,
    }

    // Add scheduled fields only if order type is scheduled
    if (orderType.value === 'scheduled') {
      data.scheduled_date = scheduledDate.value
      if (scheduledTime.value) {
        data.scheduled_time = scheduledTime.value
      }
    }

    let result

    console.log('🚀 createOrder called')
    console.log('📝 currentPendingTransactionId:', currentPendingTransactionId.value)
    console.log('👤 Customer:', selectedCustomer.value?.name, selectedCustomer.value?.id)
    console.log('🛒 Items:', items.length)

    // SIMPLE LOGIC: If we have currentPendingTransactionId, UPDATE it. Otherwise CREATE new.
    if (currentPendingTransactionId.value) {
      console.log('🔄 UPDATING existing transaction:', currentPendingTransactionId.value)
      try {
        const updateRes = await transactionApi.update(currentPendingTransactionId.value, data)
        result = updateRes.data
        console.log('✅ Updated invoice:', result.invoice_number)
        // KEEP the same pending transaction ID - don't change it!
      } catch (error) {
        console.error('❌ Update failed:', error)
        console.log('➕ Creating new transaction instead...')
        // If update fails, create new
        const createRes = await transactionApi.create(data)
        result = createRes.data
        currentPendingTransactionId.value = result.id
        console.log('✅ Created new invoice:', result.invoice_number)
      }
    } else {
      console.log('➕ CREATING new transaction (no pending ID)')
      const createRes = await transactionApi.create(data)
      result = createRes.data
      currentPendingTransactionId.value = result.id
      console.log('✅ Created new invoice:', result.invoice_number)
    }

    // Clear cart but KEEP pending transaction ID for next order with same customer
    clearCart()

    return result
  }

  return {
    // State
    products,
    packages,
    packagingItems,
    paymentMethods,
    selectedCustomer,
    orderType,
    scheduledDate,
    scheduledTime,
    cart,
    loading,
    error,
    currentPendingTransactionId,
    pendingTransactionInfo,
    showPendingChoice,
    // Computed
    cartSubtotal,
    cartTotal,
    cartItemCount,
    // Actions
    loadData,
    refreshProducts,
    refreshData,
    fetchPaymentMethods,
    setCustomer,
    clearCustomer,
    setOrderType,
    addProduct,
    addPackageCombination,
    addInventoryItem,
    updateCartItemQuantity,
    removeCartItem,
    clearCart,
    checkout,
    createOrder,
    continuePendingTransaction,
    createNewInvoice,
    loadTransaction,
  }
})
