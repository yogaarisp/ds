<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePosStore } from '../stores/pos'
import { useSettingsStore } from '../stores/settings'
import Swal from 'sweetalert2'

const emit = defineEmits(['close'])

const posStore = usePosStore()
const settingsStore = useSettingsStore()
const paidAmount = ref('')
const loading = ref(false)
const error = ref('')
const completedTransaction = ref(null)
const selectedPaymentMethod = ref(null)

// DP (Down Payment) State
const paymentType = ref('full') // 'full' or 'dp'
const dpAmount = ref('')

// Initialize with Cash if available
const initPaymentMethod = () => {
  const cash = posStore.paymentMethods.find(m => m.type === 'cash')
  if (cash) selectPaymentMethod(cash)
}

// Call on mount if payment methods are loaded
onMounted(async () => {
  document.body.style.overflow = 'hidden'
  if (posStore.paymentMethods.length === 0) {
    await posStore.fetchPaymentMethods()
  }
  initPaymentMethod()
})

// Also watch for changes if initialized empty
const unwatch = posStore.$subscribe((mutation, state) => {
  if (state.paymentMethods.length > 0 && !selectedPaymentMethod.value) {
    initPaymentMethod()
  }
})

onUnmounted(() => {
  unwatch()
  document.body.style.overflow = ''
})

function selectPaymentMethod(method) {
  selectedPaymentMethod.value = method
  if (method.type !== 'cash') {
    paidAmount.value = '' // Clear manual input
    dpAmount.value = ''
  }
}

function selectPaymentType(type) {
  paymentType.value = type
  // Reset amounts when switching
  paidAmount.value = ''
  dpAmount.value = ''
}

// DP Amount as number
const dpAmountNumber = computed(() => {
  return parseInt(dpAmount.value.replace(/\D/g, '')) || 0
})

// Remaining after DP
const remainingAfterDp = computed(() => {
  return Math.max(0, posStore.cartTotal - dpAmountNumber.value)
})

const paidAmountNumber = computed(() => {
  if (selectedPaymentMethod.value?.type !== 'cash') {
    return paymentType.value === 'dp' ? dpAmountNumber.value : posStore.cartTotal
  }
  return parseInt(paidAmount.value.replace(/\D/g, '')) || 0
})

const changeAmount = computed(() => {
  if (paymentType.value === 'dp') {
    return 0 // No change for DP
  }
  return Math.max(0, paidAmountNumber.value - posStore.cartTotal)
})

const isValid = computed(() => {
  if (!selectedPaymentMethod.value) return false
  
  // DP validation
  if (paymentType.value === 'dp') {
    if (dpAmountNumber.value <= 0) return false
    if (dpAmountNumber.value >= posStore.cartTotal) return false // DP must be less than total
    return true
  }
  
  // Full payment validation
  if (selectedPaymentMethod.value.type !== 'cash') return true
  return paidAmountNumber.value >= posStore.cartTotal
})

const getPaymentIcon = (method) => {
  const name = method.name.toLowerCase()
  const type = method.type.toLowerCase()
  
  if (type === 'cash') {
    return `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>`
  } else if (name.includes('qris')) {
    return `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
      </svg>`
  } else {
    // Bank / Card
    return `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
      </svg>`
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function printReceipt(t) {
  if (!t || !t.items) return

  // Create hidden iframe
  const iframe = document.createElement('iframe')
  iframe.style.display = 'none'
  document.body.appendChild(iframe)
  
  const width = 300 // 58mm printer approx width in pixels
  const doc = iframe.contentWindow.document

  const itemsHtml = t.items.map(item => {
    const name = item.product?.name || item.package_combination?.name || item.package?.name || 'Item'
    return `
      <div style="margin-bottom: 5px;">
        <div style="display: flex; justify-content: space-between;">
          <span>${item.quantity}x ${name}</span>
          <span>${formatPrice(item.subtotal)}</span>
        </div>
      </div>
    `
  }).join('')

  // Get store details
  const logoUrl = settingsStore.storeLogo
  const storeName = settingsStore.storeName || 'DIMSUM SARANG'
  const storeAddress = settingsStore.settings.store_address || 'Jl. Raya No. 1'
  const customerName = t.customer?.name || 'Umum'

  doc.open()
  doc.write(`
    <html>
      <head>
        <title>Print Receipt</title>
        <style>
          body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: ${width}px;
            margin: 0 auto; /* Centering */
            padding: 10px;
          }
          .header { text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
          .footer { text-align: center; margin-top: 10px; border-top: 1px dashed #000; padding-top: 10px; }
          .total { margin-top: 10px; border-top: 1px dashed #000; padding-top: 5px; }
          .row { display: flex; justify-content: space-between; }
          .bold { font-weight: bold; }
          .logo { max-width: 50px; height: auto; margin-bottom: 5px; }
        </style>
      </head>
      <body>
        <div class="header">
          ${logoUrl ? `<img src="${logoUrl}" class="logo" alt="Logo">` : ''}
          <div class="bold">${storeName}</div>
          <div style="font-size: 10px; margin-bottom: 5px;">${storeAddress}</div>
          <div style="border-bottom: 1px dashed #000; margin-bottom: 5px;"></div>
          
          <div style="text-align: left;">
            <div>Inv: ${t.invoice_number}</div>
            <div>Tgl: ${formatDate(t.created_at)}</div>
            <div>Pelanggan : ${customerName}</div>
          </div>
        </div>
        
        <div class="items">
          ${itemsHtml}
        </div>
        
        <div class="total">
          <div class="row">
            <span>Total</span>
            <span class="bold">Rp ${formatPrice(t.total)}</span>
          </div>
          ${t.paid_amount > 0 ? `
            <div class="row">
              <span>Bayar</span>
              <span>Rp ${formatPrice(t.paid_amount)}</span>
            </div>
            <div class="row">
              <span>Kembali</span>
              <span>Rp ${formatPrice(t.change_amount)}</span>
            </div>
            <div class="row" style="margin-top: 5px; font-style: italic;">
              <span>Metode Pembayaran</span>
              <span>${t.payment_method?.name || 'Cash'}</span>
            </div>
          ` : ''}
        </div>
        
        <div class="footer">
          Terima Kasih<br>
          Silakan Datang Kembali
        </div>
      </body>
    </html>
  `)
  doc.close()

  // Use onload to ensure image is loaded before printing
  iframe.onload = function() {
    setTimeout(() => {
      iframe.contentWindow.focus()
      iframe.contentWindow.print()
      // Remove iframe after printing
      setTimeout(() => {
        document.body.removeChild(iframe)
      }, 1000)
    }, 500)
  }
}

function formatInput(e) {
  const value = e.target.value.replace(/\D/g, '')
  paidAmount.value = value ? formatPrice(parseInt(value)) : ''
}

function formatDpInput(e) {
  const value = e.target.value.replace(/\D/g, '')
  dpAmount.value = value ? formatPrice(parseInt(value)) : ''
}

function setQuickAmount(amount) {
  paidAmount.value = formatPrice(amount)
}

function setQuickDpAmount(amount) {
  dpAmount.value = formatPrice(amount)
}

// Quick DP amounts (percentages of total)
const quickDpAmounts = computed(() => {
  const total = posStore.cartTotal
  return [
    { label: '25%', amount: Math.round(total * 0.25) },
    { label: '50%', amount: Math.round(total * 0.5) },
    { label: '75%', amount: Math.round(total * 0.75) },
  ]
})

async function handleCheckout() {
  if (!isValid.value) return

  loading.value = true
  error.value = ''

  try {
    let result
    
    if (paymentType.value === 'dp') {
      // DP Payment
      result = await posStore.checkoutWithDp(
        dpAmountNumber.value,
        selectedPaymentMethod.value.id
      )
      
      // Show DP success notification
      await Swal.fire({
        icon: 'success',
        title: 'DP Berhasil!',
        html: `
          <div class="text-left text-sm">
            <p class="mb-2">Order <strong>${result.invoice_number}</strong></p>
            <p class="text-slate-600">DP: <strong>Rp ${formatPrice(result.dp_amount)}</strong></p>
            <p class="text-orange-600">Sisa: <strong>Rp ${formatPrice(result.remaining_amount)}</strong></p>
          </div>
        `,
        timer: 2500,
        showConfirmButton: false,
        width: '320px',
        customClass: {
          popup: 'rounded-2xl text-sm',
          title: 'text-lg font-bold text-emerald-600',
        }
      })
    } else {
      // Full Payment (existing flow)
      result = await posStore.checkout(paidAmountNumber.value, selectedPaymentMethod.value.id)
      
      // Show SweetAlert2 notification
      await Swal.fire({
        icon: 'success',
        title: 'Pembayaran Berhasil!',
        text: `Order ${result.invoice_number} lunas`,
        timer: 1500,
        showConfirmButton: false,
        width: '300px',
        customClass: {
          popup: 'rounded-2xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })

      // Auto Print Receipt
      printReceipt(result)
    }

    completedTransaction.value = result
    emit('close')
  } catch (err) {
    error.value = err.response?.data?.message || err.message || 'Checkout gagal'
  } finally {
    loading.value = false
  }
}

async function handleCreateOrder() {
  loading.value = true
  error.value = ''

  try {
    const result = await posStore.createOrder()
    completedTransaction.value = result
    
    // Show SweetAlert2 notification
    await Swal.fire({
      icon: 'success',
      title: 'Pesanan Diproses',
      text: `Order ${result.invoice_number} berhasil dibuat!`,
      timer: 2000,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-2xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })

    emit('close')
  } catch (err) {
    error.value = err.response?.data?.message || err.message || 'Gagal membuat pesanan'
  } finally {
    loading.value = false
  }
}

function handleClose() {
  emit('close')
}

// Quick amounts
const quickAmounts = computed(() => {
  const total = posStore.cartTotal
  const amounts = [total]
  
  // Round up to nearest 5000, 10000, 20000, 50000
  const roundUps = [5000, 10000, 20000, 50000, 100000]
  roundUps.forEach(r => {
    const rounded = Math.ceil(total / r) * r
    if (rounded > total && !amounts.includes(rounded)) {
      amounts.push(rounded)
    }
  })
  
  return amounts.slice(0, 4).sort((a, b) => a - b)
})
</script>

<template>
  <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center z-[70] sm:p-4 backdrop-blur-sm">
    <!-- Mobile: Fixed full screen, Desktop: Centered modal -->
    <div class="bg-white w-full h-full sm:h-auto sm:max-w-md sm:max-h-[92vh] sm:rounded-3xl flex flex-col shadow-2xl overflow-hidden animate-fade-in-up">
      <!-- Checkout Form -->
      <div class="flex flex-col h-full sm:max-h-[92vh]">
        <!-- Header - Fixed on mobile -->
        <div class="px-5 py-4 border-b border-slate-50 flex items-center bg-white z-10 shrink-0">
          <div class="flex items-center gap-3">
            <button @click="handleClose" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-slate-600 transition-all active:scale-90">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div>
              <h2 class="text-base font-bold text-slate-800 tracking-tight">Pembayaran</h2>
              <p class="text-slate-400 text-[10px] font-semibold uppercase tracking-wider">Pilih metode & konfirmasi</p>
            </div>
          </div>
        </div>

        <div class="flex-1 overflow-auto p-4 space-y-3 custom-scrollbar">
          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-xl text-xs font-semibold border border-red-100 animate-shake">
            ⚠️ {{ error }}
          </div>

          <!-- Total Info compact but prominent -->
          <div class="bg-primary-50 rounded-2xl p-4 border border-primary-100/50 text-center relative overflow-hidden">
            <p class="text-[9px] font-semibold text-primary-600 uppercase tracking-widest mb-1">Total Tagihan</p>
            <p class="text-2xl font-bold text-primary-600 tracking-tighter">
              Rp {{ formatPrice(posStore.cartTotal) }}
            </p>
          </div>

          <!-- Payment Type Selector (Full / DP) -->
          <div class="space-y-1.5">
            <label class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest px-1">Tipe Pembayaran</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                @click="selectPaymentType('full')"
                class="p-3 border-2 rounded-xl flex items-center justify-center gap-2 transition-all"
                :class="paymentType === 'full' 
                  ? 'border-emerald-500 bg-emerald-50/50 text-emerald-700' 
                  : 'border-slate-100 bg-slate-50/50 hover:bg-white text-slate-400'"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-xs font-bold">Bayar Lunas</span>
              </button>
              <button
                @click="selectPaymentType('dp')"
                class="p-3 border-2 rounded-xl flex items-center justify-center gap-2 transition-all"
                :class="paymentType === 'dp' 
                  ? 'border-orange-500 bg-orange-50/50 text-orange-700' 
                  : 'border-slate-100 bg-slate-50/50 hover:bg-white text-slate-400'"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-xs font-bold">DP / Uang Muka</span>
              </button>
            </div>
          </div>

          <!-- DP Input Section -->
          <div v-if="paymentType === 'dp'" class="space-y-3 animate-fade-in-up">
            <!-- Quick DP Amounts -->
            <div class="space-y-1.5">
              <p class="text-[9px] font-semibold text-orange-500 uppercase tracking-widest px-1">Pilih Jumlah DP</p>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="dp in quickDpAmounts"
                  :key="dp.label"
                  @click="setQuickDpAmount(dp.amount)"
                  class="px-3 py-2.5 bg-white border border-orange-100 hover:border-orange-300 hover:text-orange-600 rounded-lg text-xs font-semibold transition-all active:scale-95"
                >
                  <span class="block text-orange-500">{{ dp.label }}</span>
                  <span class="block text-[10px] text-slate-500">{{ formatPrice(dp.amount / 1000) }}k</span>
                </button>
              </div>
            </div>

            <!-- DP Amount Input -->
            <div class="space-y-1.5">
              <label class="text-[9px] font-semibold text-orange-500 uppercase tracking-widest px-1">Jumlah DP</label>
              <div class="relative group">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-orange-300 font-semibold text-xs transition-colors group-focus-within:text-orange-500">Rp</div>
                <input
                  :value="dpAmount"
                  @input="formatDpInput"
                  type="text"
                  class="w-full bg-orange-50/50 border-2 border-transparent rounded-xl pl-10 pr-4 py-3 text-xl font-bold text-slate-800 focus:bg-white focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 transition-all placeholder:text-orange-200"
                  placeholder="0"
                  inputmode="numeric"
                />
              </div>
            </div>

            <!-- Remaining Amount Display -->
            <div v-if="dpAmountNumber > 0" class="bg-slate-50 rounded-xl px-3 py-2.5 border border-slate-100">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-[8px] font-semibold text-slate-400 uppercase tracking-widest leading-none mb-0.5">Sisa Tagihan</p>
                  <p class="text-sm font-bold text-orange-600 tracking-tight">
                    Rp {{ formatPrice(remainingAfterDp) }}
                  </p>
                </div>
                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center text-orange-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <p class="text-[9px] text-slate-400 mt-1">* Akan dibayar saat pengambilan</p>
            </div>

            <!-- DP Validation Warning -->
            <div v-if="dpAmountNumber > 0 && dpAmountNumber >= posStore.cartTotal" class="bg-red-50 text-red-600 p-3 rounded-xl text-xs font-semibold border border-red-100">
              ⚠️ Jumlah DP harus kurang dari total. Gunakan "Bayar Lunas" jika ingin bayar penuh.
            </div>
          </div>

          <!-- Payment Methods Compact -->
          <div class="space-y-1.5">
            <label class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest px-1">Metode Pembayaran</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="method in posStore.paymentMethods"
                :key="method.id"
                @click="selectPaymentMethod(method)"
                class="p-2.5 border-2 rounded-xl flex flex-col items-center justify-center gap-1.5 transition-all h-20 group relative"
                :class="selectedPaymentMethod?.id === method.id 
                  ? 'border-primary-500 bg-primary-50/50 text-primary-700 shadow-sm' 
                  : 'border-slate-100 bg-slate-50/50 hover:bg-white hover:border-slate-200 text-slate-400'"
              >
                <div 
                  class="transition-transform duration-300 group-hover:scale-110 [&>svg]:w-5 [&>svg]:h-5" 
                  :class="selectedPaymentMethod?.id === method.id ? 'text-primary-500' : 'text-slate-300'"
                  v-html="getPaymentIcon(method)"
                ></div>
                <span class="text-[8px] font-semibold uppercase tracking-tight text-center leading-tight">{{ method.name }}</span>
                <div v-if="selectedPaymentMethod?.id === method.id" class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-primary-500 rounded-full"></div>
              </button>
            </div>
          </div>

          <!-- Cash Payment Input Compact (only for Full Payment mode) -->
          <div v-if="selectedPaymentMethod?.type === 'cash' && paymentType === 'full'" class="space-y-3 animate-fade-in-up">
            <!-- Quick Amounts -->
            <div class="space-y-1.5">
              <p class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest px-1">Uang Pas / Cepat</p>
              <div class="grid grid-cols-4 gap-1.5">
                <button
                  v-for="amount in quickAmounts"
                  :key="amount"
                  @click="setQuickAmount(amount)"
                  class="px-2 py-2 bg-white border border-slate-100 hover:border-primary-200 hover:text-primary-600 rounded-lg text-[11px] font-semibold transition-all active:scale-95"
                >
                  {{ formatPrice(amount / 1000) }}k
                </button>
              </div>
            </div>

            <!-- Paid Amount Input Compact -->
            <div class="space-y-1.5">
              <label class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest px-1">Jumlah Dibayar</label>
              <div class="relative group">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 font-semibold text-xs transition-colors group-focus-within:text-primary-500">Rp</div>
                <input
                  :value="paidAmount"
                  @input="formatInput"
                  type="text"
                  class="w-full bg-slate-50 border-2 border-transparent rounded-xl pl-10 pr-4 py-3 text-xl font-bold text-slate-800 focus:bg-white focus:border-primary-500/30 focus:ring-4 focus:ring-primary-500/5 transition-all placeholder:text-slate-200"
                  placeholder="0"
                  inputmode="numeric"
                  ref="paidAmountInput"
                />
              </div>
            </div>

            <!-- Change Compact & Modern -->
            <div v-if="changeAmount > 0" class="bg-emerald-50/80 rounded-xl px-3 py-2 border border-emerald-100 flex items-center justify-between animate-bounce-in">
              <div>
                <p class="text-[8px] font-semibold text-emerald-600 uppercase tracking-widest leading-none mb-0.5">Kembalian</p>
                <p class="text-sm font-bold text-emerald-700 tracking-tight">
                  Rp {{ formatPrice(changeAmount) }}
                </p>
              </div>
              <div class="w-7 h-7 bg-white rounded-lg shadow-sm flex items-center justify-center text-sm">💸</div>
            </div>
          </div>

          <!-- Non-Cash Info Compact -->
          <div v-else-if="selectedPaymentMethod" class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100 animate-fade-in-up">
            <p class="text-[10px] text-slate-400 font-semibold mb-2 uppercase tracking-wider">Detail Pembayaran</p>
            <p class="font-bold text-base text-slate-800 leading-tight">{{ selectedPaymentMethod.name }}</p>
            
            <div v-if="selectedPaymentMethod.account_number" class="mt-3 pt-3 border-t border-slate-200/50">
              <p class="text-[9px] text-slate-400 font-semibold uppercase tracking-widest mb-1">No. Rekening / VA</p>
              <p class="font-mono text-lg font-bold text-primary-600 tracking-wider">
                {{ selectedPaymentMethod.account_number }}
              </p>
              <p v-if="selectedPaymentMethod.account_name" class="text-[11px] text-slate-500 font-semibold mt-0.5">
                a.n. {{ selectedPaymentMethod.account_name }}
              </p>
            </div>
            
            <div class="mt-3 px-3 py-1.5 bg-amber-50 rounded-lg border border-amber-100">
               <p class="text-[8px] text-amber-600 font-semibold uppercase tracking-widest">⚠️ Pastikan saldo masuk sebelum klik bayar</p>
            </div>
          </div>
        </div>

        <!-- Fixed Footer Action Compact -->
        <div class="p-3 pb-[80px] lg:pb-4 bg-white border-t border-slate-50 z-10 shadow-[0_-10px_30px_rgba(0,0,0,0.03)] shrink-0">
          <button
            @click="handleCheckout"
            class="w-full py-3 rounded-xl text-white font-bold text-sm shadow-lg hover:opacity-90 transition-all active:scale-[0.98] disabled:opacity-50 disabled:shadow-none flex items-center justify-center gap-2"
            :class="paymentType === 'dp' 
              ? 'bg-orange-500 shadow-orange-500/25' 
              : 'bg-primary-500 shadow-[0_8px_20px_rgba(235,108,34,0.25)]'"
            :disabled="!isValid || loading"
          >
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            <template v-if="!loading">
              <template v-if="paymentType === 'dp'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Bayar DP Rp {{ formatPrice(dpAmountNumber) }}</span>
              </template>
              <template v-else>
                <span>Konfirmasi & Bayar Lunas</span>
              </template>
            </template>
            <span v-else>Memproses...</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
