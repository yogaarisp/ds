<script setup>
import { ref, onMounted, watch, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { transactionApi } from '../api'
import { usePosStore } from '../stores/pos'
import { useSettingsStore } from '../stores/settings'
import { usePrinter } from '../composables/usePrinter'
import CheckoutModal from '../components/CheckoutModal.vue'
import Swal from 'sweetalert2'

const router = useRouter()
const route = useRoute()
const posStore = usePosStore()
const settingsStore = useSettingsStore()
const { printerConnected, thermalPrinter } = usePrinter()

const transactions = ref([])
const allTransactions = ref([]) // Store all transactions for search
const loading = ref(false)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})
const filter = ref('today')
const customDateFilter = ref({
  from: new Date().toISOString().split('T')[0],
  to: new Date().toISOString().split('T')[0]
})
const selectedTransaction = ref(null)
const showCheckoutModal = ref(false)
const showSearch = ref(false)
const searchQuery = ref('')

const isMounted = ref(false)

watch(selectedTransaction, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onMounted(() => {
  isMounted.value = true
  if (route.query.tab) {
    filter.value = String(route.query.tab)
  }
  // Delay data loading to ensure Vue has fully mounted and stabilized
  // This prevents DOM patching errors during initial render
  setTimeout(() => {
    if (isMounted.value) loadTransactions()
  }, 50)
})

onUnmounted(() => {
  isMounted.value = false
  document.body.style.overflow = ''
})

function setFilter(newFilter) {
  filter.value = newFilter
  // If custom, don't load immediately, wait for user to select dates
  if (newFilter !== 'custom') {
    loadTransactions(1)
  }
}

let searchTimeout = null
watch(searchQuery, (newVal) => {
  // Local filtering for immediate feedback
  performSearch()
  
  // Debounced backend search for global results
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadTransactions()
  }, 500)
})

watch([() => customDateFilter.value.from, () => customDateFilter.value.to], () => {
  if (filter.value === 'custom') {
    loadTransactions(1)
  }
})

async function loadTransactions(page = 1) {
  loading.value = true
  try {
    const params = {
      page: page,
      per_page: 10
    }
    if (filter.value === 'today') params.today = true
    if (filter.value === 'upcoming') params.upcoming = true
    if (filter.value === 'custom') {
      params.from_date = customDateFilter.value.from + ' 00:00:00'
      params.to_date = customDateFilter.value.to + ' 23:59:59'
    }
    
    // Add backend search parameter
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }
    
    const response = await transactionApi.getAll(params)
    
    if (!isMounted.value) return

    const data = response.data.data || response.data
    allTransactions.value = data
    transactions.value = data
    
    // Update pagination state
    if (response.data.current_page) {
      pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total
      }
    } else {
      // Fallback if not paginated
      pagination.value = { current_page: 1, last_page: 1, total: data.length }
    }
  } catch (error) {
    console.error(error)
  } finally {
    if (isMounted.value) {
      loading.value = false
    }
  }
}

function toggleSearch() {
  showSearch.value = !showSearch.value
  if (!showSearch.value) {
    searchQuery.value = ''
    loadTransactions()
  }
}

function performSearch() {
  if (!searchQuery.value.trim()) {
    transactions.value = allTransactions.value
    return
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  transactions.value = allTransactions.value.filter(transaction => {
    // Search by invoice number
    const invoiceMatch = transaction.invoice_number?.toLowerCase().includes(query)
    
    // Search by customer name
    const customerMatch = (transaction.customer?.name?.toLowerCase().includes(query)) || 
                          (transaction.customer_name?.toLowerCase().includes(query))
    
    return invoiceMatch || customerMatch
  })
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price)
}

function selectAll(event) {
  if (event.target && typeof event.target.select === 'function') {
    event.target.select()
  }
}

function getCustomerLabel(customer) {
  const name = customer?.customer_type?.name || 'Reguler'
  return name.replace(/Pelanggan\s?/i, '')
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

function getStatusColor(status) {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

function getStatusLabel(status) {
  const labels = {
    pending: 'Pending',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
  }
  return labels[status] || status
}

function formatInvoiceNumber(transaction) {
  if (transaction.invoice_number) return transaction.invoice_number
  
  // Fallback Format: DS-ddMMyyHHmm
  const date = new Date(transaction.created_at)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = String(date.getFullYear()).slice(-2)
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  
  return `DS${day}${month}${year}${hours}${minutes}`
}

function formatDateOnly(date) {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric', 
    month: 'long', 
    year: 'numeric'
  })
}

function formatTime(date) {
  return new Date(date).toLocaleTimeString('id-ID', {
    hour: '2-digit', 
    minute: '2-digit'
  })
}

function getTotalItems(transaction) {
  if (!transaction.items) return 0
  return transaction.items.reduce((sum, item) => sum + item.quantity, 0)
}

function addToPos() {
  if (!selectedTransaction.value) return
  
  const t = selectedTransaction.value
  
  // Use loadTransaction to directly load this specific transaction
  posStore.loadTransaction(t)
  
  // Redirect to POS
  router.push('/pos')
}

function proceedToPay() {
  if (!selectedTransaction.value) return
  
  const t = selectedTransaction.value
  
  // Use loadTransaction to directly load this specific transaction
  posStore.loadTransaction(t)
  
  showCheckoutModal.value = true
}

function sendWhatsapp() {
  if (!selectedTransaction.value) return

  const t = selectedTransaction.value
  const phone = t.customer?.phone?.replace(/^0/, '62') || ''
  const customerName = t.customer?.name || t.customer_name || 'Pelanggan'
  const storeName = settingsStore.storeName || 'DIMSUM SARANG'
  
  let text = `*INVOICE #${t.invoice_number}*\n`
  text += `Halo Kak *${customerName}*, berikut rincian pesanan Kakak di *${storeName}*:\n\n`
  
  text += `Tanggal: ${formatDate(t.created_at)}\n`
  text += `------------------------------------------\n`
  
  text += `*DAFTAR ITEM:*\n`
  t.items.forEach(item => {
    const name = item.product?.name || item.package_combination?.name || item.package?.name
    text += `• ${item.quantity}x ${name} (Rp ${formatPrice(item.subtotal)})\n`
  })
  
  text += `------------------------------------------\n`
  text += `*SUBTOTAL: Rp ${formatPrice(t.subtotal)}*\n`
  if (t.discount > 0) {
    text += `Diskon: -Rp ${formatPrice(t.discount)}\n`
  }
  text += `*TOTAL AKHIR: Rp ${formatPrice(t.total)}*\n\n`
  
  if (t.paid_amount > 0) {
    text += `Metode: ${t.payment_method?.name || 'Cash'}\n`
    text += `Dibayar: Rp ${formatPrice(t.paid_amount)}\n`
    if (t.change_amount > 0) {
      text += `Kembalian: Rp ${formatPrice(t.change_amount)}\n`
    }
  }
  
  text += `\nTerima kasih sudah order di *${storeName}*!  Kami tunggu orderan berikutnya ya Kak.`

  const url = `https://wa.me/${phone}?text=${encodeURIComponent(text)}`
  window.open(url, '_blank')
}

function printReceipt() {
  if (!selectedTransaction.value) return

  const t = selectedTransaction.value

  // Try Bluetooth thermal printer first if connected
  if (printerConnected.value && thermalPrinter.connected) {
    try {
      const receiptData = {
        queueNumber: t.queue_number || '',
        transactionNumber: t.invoice_number,
        date: formatDate(t.created_at),
        cashier: t.user?.name || 'Kasir',
        customerName: t.customer?.name || t.customer_name || 'Umum',
        tableName: t.table?.name || '',
        items: t.items.map(item => ({
          name: item.product?.name || item.package_combination?.name || item.package?.name || 'Item',
          quantity: item.quantity,
          price: item.unit_price || item.subtotal / item.quantity,
          subtotal: item.subtotal
        })),
        subtotal: t.total,
        discount: t.discount || 0,
        tax: 0,
        total: t.total,
        paid: t.paid_amount || t.total,
        change: t.change_amount || 0,
        paymentMethod: t.payment_method?.name || 'Cash'
      }
      thermalPrinter.printReceipt(receiptData)
      return
    } catch (err) {
      console.warn('Thermal print failed, falling back to iframe:', err)
    }
  }

  // Fallback: iframe print
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
  const storeAddress = settingsStore.settings.store_address || ''
  const customerName = t.customer?.name || t.customer_name || 'Umum'

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
            margin: 0 auto;
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
          
          <div style="text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 5px;">NO. ANTRIAN: ${t.queue_number || '-'}</div>
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
              <span>Metode</span>
              <span>${t.payment_method?.name || 'Cash'}</span>
            </div>
          ` : ''}
        </div>
        
        <div class="footer">
          Terima Kasih<br>
          Silakan Datang Kembali
        </div>
        
        <script>
          // Auto print when loaded
        <\/script>
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

async function deleteTransaction(transaction) {
  if (!transaction) return

  const result = await Swal.fire({
    title: 'Hapus Transaksi?',
    text: `Hapus transaksi ${transaction.invoice_number}? Tindakan ini tidak dapat dibatalkan!`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
    reverseButtons: true,
    width: '320px',
    customClass: {
      popup: 'rounded-2xl text-sm',
      title: 'text-lg font-bold',
      htmlContainer: 'text-xs'
    }
  })

  if (result.isConfirmed) {
    try {
      await transactionApi.delete(transaction.id)
      Swal.fire({
        icon: 'success',
        title: 'Berhasil Dihapus',
        timer: 1500,
        showConfirmButton: false,
        width: '300px',
        customClass: {
          popup: 'rounded-2xl text-sm',
          title: 'text-lg font-bold'
        }
      })
      if (selectedTransaction.value?.id === transaction.id) {
        selectedTransaction.value = null
      }
      loadTransactions(pagination.value.current_page)
    } catch (error) {
      console.error(error)
      Swal.fire({
        icon: 'error',
        title: 'Gagal Menghapus',
        text: error.response?.data?.message || 'Terjadi kesalahan sistem',
        width: '300px',
        customClass: {
          popup: 'rounded-2xl text-sm',
          title: 'text-lg font-bold'
        }
      })
    }
  }
}
</script>

<template>
  <div class="h-full lg:h-screen flex flex-col overflow-hidden bg-slate-50/50">
    <!-- Simple & Cool Header -->
    <div class="sticky top-0 z-20 bg-slate-50/80 backdrop-blur-md px-4 md:px-6 pt-2.5 pb-1.5 border-b border-slate-100/50 shrink-0">
      <div class="w-full flex flex-col md:flex-row justify-between md:items-center gap-3">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-base font-medium text-slate-800 leading-none">Transaksi</h1>
            <p class="text-[9px] font-medium text-slate-400 uppercase tracking-wider mt-1">Riwayat & Penjualan</p>
          </div>
          
          <!-- Search Toggle -->
          <button
            @click="toggleSearch"
            class="p-2 rounded-xl transition-all duration-200 hover:bg-gray-100 active:scale-95 flex-shrink-0"
            :class="showSearch ? 'bg-primary-50 text-primary-600' : 'text-slate-400 hover:text-slate-600'"
            title="Cari Transaksi"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </button>
        </div>

        <!-- Search Form (Mobile & Desktop) -->
        <div v-if="showSearch" class="flex-1 max-w-md animate-fade-in-down">
          <div class="flex items-center gap-2 bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-1.5 w-full">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari invoice atau customer..."
                class="flex-1 outline-none text-xs text-slate-700 placeholder:text-[10px] placeholder:text-slate-300 bg-transparent w-full min-w-0 font-normal"
              @focus="selectAll"
            />
            <button
              v-if="searchQuery"
              @click="searchQuery = ''"
              class="p-1 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 items-center sm:items-center">
             <!-- Date Inputs (Mobile Optimized) -->
             <div v-show="filter === 'custom'" class="w-full sm:w-auto flex items-center justify-center gap-2 animate-fade-in-up py-1">
               <div class="relative">
                 <input 
                   v-model="customDateFilter.from" 
                   type="date" 
                   class="bg-slate-100 border-none rounded-xl text-[10px] sm:text-[11px] font-medium text-slate-600 py-2 px-3 text-center w-32 focus:ring-2 focus:ring-primary-500/20 outline-none shadow-sm"
                 >
               </div>
               <span class="text-slate-300 font-medium">–</span>
               <div class="relative">
                 <input 
                   v-model="customDateFilter.to" 
                   type="date" 
                   class="bg-slate-100 border-none rounded-xl text-[10px] sm:text-[11px] font-medium text-slate-600 py-2 px-3 text-center w-32 focus:ring-2 focus:ring-primary-500/20 outline-none shadow-sm"
                 >
               </div>
             </div>
  
            <!-- Filter Tabs -->
          <div class="bg-slate-100 p-1 rounded-2xl flex gap-1 overflow-x-auto no-scrollbar max-w-full w-full sm:w-auto">
            <button
              v-for="t in [
                { id: 'today', label: 'Hari Ini' },
                { id: 'upcoming', label: 'Pre-Order' },
                { id: 'all', label: 'Semua' },
                { id: 'custom', label: 'Custom', icon: true }
              ]"
              :key="t.id"
              @click="setFilter(t.id)"
              class="flex-1 sm:flex-none px-2 md:px-4 py-1.5 rounded-xl text-[9px] md:text-[10px] font-medium transition-all flex items-center justify-center gap-1.5 md:gap-2 whitespace-nowrap uppercase tracking-wider flex-shrink-0"
              :class="filter === t.id ? 'bg-white text-primary-600 shadow-sm' : 'text-slate-400 hover:text-slate-600'"
            >
              <svg v-if="t.icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-2.5 h-2.5 text-current"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
              {{ t.label }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Scrollable Content Area -->
    <div class="flex-1 overflow-y-auto px-2 md:px-6 custom-scrollbar pb-4">
      <!-- Search Results Info -->
      <div v-if="showSearch && searchQuery" class="mb-4 px-4 py-3 bg-blue-50 border border-blue-100 rounded-2xl flex items-center justify-between">
        <span class="text-xs font-medium text-blue-700">
          <span class="font-medium">{{ pagination.total }}</span> hasil ditemukan untuk "<span class="font-medium">{{ searchQuery }}</span>"
        </span>
        <button
          @click="searchQuery = ''"
          class="text-blue-600 hover:text-blue-800 text-[10px] font-medium uppercase tracking-widest"
        >
          Reset
        </button>
      </div>

      <div v-if="loading" key="loading" class="text-center py-12 text-slate-400 font-medium text-xs uppercase tracking-[0.2em] animate-pulse">
        Memuat data...
      </div>

      <div v-else-if="transactions.length === 0" key="empty" class="text-center py-20 text-slate-400">
        <div v-if="showSearch && searchQuery" class="space-y-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <div>
            <p class="font-medium text-slate-400 text-xs uppercase tracking-widest">Tidak ditemukan</p>
            <p class="text-[10px] text-slate-300 font-medium uppercase mt-1">Coba kata kunci lain atau reset pencarian</p>
          </div>
        </div>
        <div v-else>
          <p class="italic text-sm font-medium">Tidak ada transaksi</p>
        </div>
      </div>

      <div v-else key="list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        <div
          v-for="transaction in transactions"
          :key="transaction.id"
          class="bg-white p-4 pt-9 rounded-2xl border border-slate-100 shadow-[0_2px_8px_rgba(0,0,0,0.04)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] hover:border-primary-100/50 transition-all duration-300 cursor-pointer group relative overflow-hidden"
          @click="selectedTransaction = transaction"
        >
          <!-- Invoice Tag (Top Left) -->
          <div class="absolute top-0 left-0 z-10">
            <div class="bg-slate-50 text-slate-500 px-3 py-1.5 rounded-br-2xl border-r border-b border-slate-100 flex flex-col shadow-sm">
              <span class="text-[10px] font-bold tracking-wider leading-none mb-0.5">{{ transaction.invoice_number }}</span>
              <span v-if="transaction.queue_number" class="text-[11px] font-black text-primary-600">#{{ transaction.queue_number }}</span>
            </div>
          </div>

          <!-- Table Tag (Top Right) -->
          <div v-if="transaction.table" class="absolute top-0 right-0 z-10 flex items-start">
            <div class="bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-bl-2xl border-l border-b border-emerald-100 flex items-center gap-1.5 shadow-sm">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
              </svg>
              <span class="text-[10px] font-bold tracking-wider">{{ transaction.table.name }}</span>
            </div>
            <!-- Delete Button (Next to Table / Top Right) -->
            <button 
              @click.stop="deleteTransaction(transaction)"
              class="w-8 h-8 flex items-center justify-center text-red-400 hover:text-red-500 hover:bg-red-50 transition-all rounded-bl-xl border-l border-b border-slate-100 bg-white shadow-sm"
              title="Hapus Transaksi"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>

          <!-- Delete Button (Top Right - When NO table) -->
          <div v-if="!transaction.table" class="absolute top-0 right-0 z-10">
            <button 
              @click.stop="deleteTransaction(transaction)"
              class="w-8 h-8 flex items-center justify-center text-red-400 hover:text-red-500 hover:bg-red-50 transition-all rounded-bl-xl border-l border-b border-slate-100 bg-white shadow-sm"
              title="Hapus Transaksi"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>

          <!-- Scheduled Tag (Top Right - Adjusted if Table or Delete exists) -->
          <div v-if="transaction.order_type === 'scheduled'" class="absolute top-0 right-10" :class="{ 'mt-7': transaction.table }">
            <div class="bg-blue-50 text-blue-600 px-2 py-1 rounded-bl-xl border-l border-b border-blue-100/50 flex items-center gap-1 shadow-sm">
              <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
              <p class="text-[9px] font-medium uppercase tracking-wider">
                {{ new Date(transaction.scheduled_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                <span v-if="transaction.scheduled_time" class="opacity-75"> • {{ transaction.scheduled_time }}</span>
              </p>
            </div>
          </div>

          <div class="flex flex-col gap-3">
             <!-- Top: Customer Info -->
             <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500 font-medium shrink-0">
                   {{ (transaction.customer?.name || 'G').charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0 flex-1">
                   <div class="flex items-center gap-2 mb-1">
                      <h3 class="font-medium text-slate-800 text-sm group-hover:text-primary-600 transition-colors truncate">
                         {{ transaction.customer?.name || transaction.customer_name || 'Guest' }}
                      </h3>
                   </div>
                   <div class="flex flex-col gap-1 text-xs text-slate-500">
                      <span class="flex items-center gap-1.5 text-[10px]">
                         <span class="text-slate-500">
                            {{ formatDate(transaction.created_at) }}
                         </span>
                         <span class="text-slate-300">|</span>
                         <span class="font-medium text-slate-600">{{ transaction.order_type === 'instant' ? 'Dine In / TA' : 'Pre-Order' }}</span>
                         <!-- Table Badge -->
                         <template v-if="transaction.table">
                           <span class="text-slate-300">|</span>
                           <span class="inline-flex items-center gap-0.5 text-emerald-600 font-bold">
                             <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
                             </svg>
                             {{ transaction.table.name }}
                           </span>
                         </template>
                      </span>
                   </div>
                </div>
             </div>

             <!-- Bottom: Status and Total -->
             <div class="flex items-center justify-between pt-2 border-t border-dashed border-slate-100">
                <span :class="['px-2 py-1 rounded-full text-[9px] font-medium capitalize', getStatusColor(transaction.status)]">
                   {{ getStatusLabel(transaction.status) }}
                </span>
                <p class="font-medium text-slate-800 text-sm">
                   Rp {{ formatPrice(transaction.total) }}
                </p>
             </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination UI - Pinned at Bottom -->
    <div 
      v-if="pagination.last_page > 1" 
      class="shrink-0 bg-white border-t border-slate-100 px-4 py-1 md:px-8 md:py-2 flex flex-col md:flex-row items-center justify-between gap-1 md:gap-4 mt-2 mb-2"
    >
      <p class="text-[11px] font-medium text-slate-400">
        Tampil {{ transactions.length }} Dari {{ pagination.total }} Transaksi
      </p>
      <div class="flex p-0.5 bg-slate-50/50 rounded-xl border border-slate-100 shadow-sm">
        <button
          @click="loadTransactions(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="px-3 py-1.5 rounded-lg bg-white text-slate-500 font-medium text-xs shadow-sm disabled:opacity-30 disabled:shadow-none transition-all hover:bg-slate-50 flex items-center gap-1.5 border border-slate-100"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-2.5 h-2.5"><path d="m15 18-6-6 6-6"/></svg>
          Sblmnya
        </button>
        <div class="px-4 flex items-center justify-center">
          <span class="text-xs font-medium text-slate-700">
            {{ pagination.current_page }} <span class="text-slate-200 mx-1">/</span> {{ pagination.last_page }}
          </span>
        </div>
        <button
          @click="loadTransactions(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-3 py-1.5 rounded-lg bg-white text-slate-500 font-medium text-xs shadow-sm disabled:opacity-30 disabled:shadow-none transition-all hover:bg-slate-50 flex items-center gap-1.5 border border-slate-100"
        >
          Lanjut
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-2.5 h-2.5"><path d="m9 18 6-6-6-6"/></svg>
        </button>
      </div>
    </div>

    <!-- Transaction Detail Modal -->
    <Teleport to="body">
      <div v-if="selectedTransaction" class="fixed inset-0 bg-slate-900/60 flex items-center justify-center z-[60] sm:p-4 backdrop-blur-sm transition-all overflow-hidden">
        <div class="bg-white w-full h-full sm:h-auto sm:max-w-2xl sm:max-h-[92vh] sm:rounded-3xl flex flex-col shadow-2xl overflow-hidden transform transition-all scale-100 animate-fade-in-up">
          <!-- Header -->
          <div class="px-4 py-3 border-b border-slate-50 flex items-center gap-3 bg-white/80 backdrop-blur sticky top-0 z-10 shrink-0">
            <button 
              @click="selectedTransaction = null" 
              class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-slate-600 transition-all active:scale-90"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div>
              <h2 class="text-sm font-medium text-slate-800 tracking-tight flex items-center gap-2">
                Detail Pesanan
                <span v-if="selectedTransaction.queue_number" class="px-2 py-0.5 bg-primary-50 text-primary-600 rounded text-[10px] font-black tracking-widest">
                  #{{ selectedTransaction.queue_number }}
                </span>
              </h2>
              <div class="flex items-center gap-1.5 mt-0.5 text-[9px] text-slate-400 font-medium uppercase tracking-wider">
                <span class="font-mono">{{ formatInvoiceNumber(selectedTransaction) }}</span>
                <span class="text-slate-200">|</span> 
                <span>{{ formatDate(selectedTransaction.created_at).split(',')[0] }}</span>
              </div>
            </div>
          </div>

          <div class="flex-1 overflow-auto p-4 space-y-3 custom-scrollbar pb-8">
            <!-- Status & Type Banner -->
            <div class="bg-primary-50/50 px-3.5 py-2 rounded-2xl border border-primary-100/50 flex items-center justify-between gap-4">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-white rounded-xl shadow-sm flex items-center justify-center text-primary-600">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><rect width="16" height="20" x="4" y="2" rx="2"/><line x1="8" x2="16" y1="6" y2="6"/><line x1="8" x2="16" y1="10" y2="10"/><line x1="8" x2="12" y1="14" y2="14"/></svg>
                </div>
                <div>
                  <p class="text-[9px] font-medium text-primary-600 uppercase tracking-widest leading-none mb-1">Status</p>
                  <div class="flex items-center gap-2">
                     <span class="text-xs font-medium text-slate-700">
                      {{ getStatusLabel(selectedTransaction.status) }}
                    </span>
                    <span class="text-slate-200 text-[10px]">|</span>
                    <span class="font-medium text-slate-500 text-[11px]">
                      {{ selectedTransaction.order_type === 'instant' ? 'Dine In / TA' : 'Pre-Order' }}
                    </span>
                  </div>
                </div>
              </div>
              
              <div v-if="selectedTransaction.order_type === 'scheduled'" class="text-[10px] bg-white px-3 py-2 rounded-xl border border-primary-100 shadow-sm leading-tight">
                <span class="text-primary-600 font-medium uppercase tracking-wider block mb-0.5 flex items-center gap-1">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                  Jadwal
                </span> 
                <span class="text-slate-700 font-medium">
                  {{ new Date(selectedTransaction.scheduled_date).toLocaleDateString('id-ID') }}
                  {{ selectedTransaction.scheduled_time ? `• ${selectedTransaction.scheduled_time}` : '' }}
                </span>
              </div>
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-1.5">
              <!-- Customer -->
              <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                <h3 class="text-[10px] font-medium text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                  Customer
                </h3>
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-white border border-slate-100 flex items-center justify-center text-slate-400 shadow-sm text-[10px] font-medium">
                    {{ (selectedTransaction.customer?.name || 'G').charAt(0).toUpperCase() }}
                  </div>
                  <div class="min-w-0 flex flex-col justify-center">
                    <p class="font-medium text-slate-800 text-[12px] truncate leading-none mb-0.5">{{ selectedTransaction.customer?.name || selectedTransaction.customer_name || 'Guest' }}</p>
                    <p class="text-[8px] text-slate-400 font-medium uppercase tracking-tight">{{ getCustomerLabel(selectedTransaction.customer) }}</p>
                  </div>
                </div>
              </div>

              <!-- Cashier -->
              <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                <h3 class="text-[10px] font-medium text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                  Kasir
                </h3>
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-white border border-slate-100 flex items-center justify-center text-slate-400 shadow-sm text-[10px] font-medium">
                    {{ (selectedTransaction.user?.name || '-').charAt(0).toUpperCase() }}
                  </div>
                  <div class="min-w-0 flex flex-col justify-center">
                    <p class="font-medium text-slate-800 text-[12px] truncate leading-none">{{ selectedTransaction.user?.name || '-' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Table Info (if QR Order) -->
            <div v-if="selectedTransaction.table" class="bg-emerald-50 rounded-2xl p-3 border border-emerald-100 flex items-center gap-3">
              <div class="w-8 h-8 bg-white rounded-xl shadow-sm flex items-center justify-center text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
                </svg>
              </div>
              <div>
                <p class="text-[9px] font-extrabold text-emerald-600 uppercase tracking-widest leading-none mb-1">Dipesan di Meja</p>
                <p class="font-bold text-slate-800 text-sm leading-tight">{{ selectedTransaction.table.name }}</p>
              </div>
              <div class="ml-auto">
                 <div class="bg-emerald-500 text-white text-[8px] font-bold px-2 py-0.5 rounded-full shadow-sm">VIA SCAN QR</div>
              </div>
            </div>

            <!-- Order Items List Refined (Consistency with OrderPreview) -->
            <div class="space-y-3">
              <h3 class="text-[10px] font-medium text-slate-400 uppercase tracking-widest px-1 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                Item Pesanan ({{ getTotalItems(selectedTransaction) }})
              </h3>
              <div class="space-y-1.5">
                <div 
                  v-for="(item, index) in selectedTransaction.items" 
                  :key="item.id"
                  class="bg-white border-b border-slate-50 px-3 py-2 flex items-center justify-between hover:bg-slate-50/30 transition-all group"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="min-w-0">
                      <div class="flex items-center gap-2 mb-0.5">
                        <p class="font-medium text-slate-700 text-[12px] truncate leading-tight">
                          {{ item.product?.name || item.package_combination?.name || item.package?.name }}
                        </p>
                        <span class="text-[8px] font-medium text-primary-500 bg-primary-50 px-1 py-0.5 rounded border border-primary-100/30">x{{ item.quantity }}</span>
                      </div>
                      <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">@ Rp {{ formatPrice(item.unit_price || (item.subtotal / item.quantity)) }}</p>
                    </div>
                  </div>
                  <div class="shrink-0 text-right">
                    <p class="font-medium text-slate-800 text-[12px] tracking-tight">Rp {{ formatPrice(item.subtotal) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Sticky Footer: Payment Summary & Actions -->
          <div class="bg-white border-t border-slate-100 sticky bottom-0 z-20 shadow-[0_-10px_40px_rgba(0,0,0,0.04)] p-2.5 pb-[80px] lg:pb-2.5 space-y-1 shrink-0">
            <!-- Summary Box -->
            <div class="bg-slate-50/50 rounded-2xl p-2.5 border border-slate-100/50 space-y-1.5">
              <div class="space-y-1">
                <div class="flex justify-between items-center">
                  <span class="text-slate-400 uppercase tracking-widest font-medium text-[9px]">Subtotal</span>
                  <span class="text-slate-600 font-medium text-sm">Rp {{ formatPrice(selectedTransaction.subtotal) }}</span>
                </div>
                <div v-if="selectedTransaction.discount > 0" class="flex justify-between items-center">
                  <span class="text-slate-400 uppercase tracking-widest font-medium text-[9px]">Diskon</span>
                  <span class="text-red-500 font-medium text-sm">-Rp {{ formatPrice(selectedTransaction.discount) }}</span>
                </div>
                
                <div class="border-t border-slate-200/40 my-1"></div>

                <div class="flex justify-between items-center">
                  <span class="font-medium text-slate-800 text-[9px] uppercase tracking-widest">Total Tagihan</span>
                  <span class="text-lg font-bold text-primary-600 tracking-tight">Rp {{ formatPrice(selectedTransaction.total) }}</span>
                </div>
              </div>

              <!-- Payment Details (Split View) -->
              <div class="grid grid-cols-2 gap-4 mt-1 pt-2 border-t border-slate-200/40 border-dashed">
                 <div>
                    <p class="text-[9px] text-slate-400 uppercase tracking-widest font-medium leading-none mb-1">Dibayar</p>
                    <p class="font-bold text-slate-800 text-sm leading-none">Rp {{ formatPrice(selectedTransaction.paid_amount) }}</p>
                 </div>
                 <div v-if="selectedTransaction.change_amount > 0" class="text-right">
                    <p class="text-[9px] text-slate-400 uppercase tracking-widest font-medium leading-none mb-1">Kembalian</p>
                    <p class="font-bold text-emerald-600 text-sm leading-none">Rp {{ formatPrice(selectedTransaction.change_amount) }}</p>
                 </div>
              </div>
            </div>

             <div class="flex gap-2">
                <button 
                   v-if="selectedTransaction.status === 'pending'"
                   @click="addToPos"
                   class="flex-1 px-3 py-2.5 rounded-xl text-[10px] font-medium uppercase tracking-wider text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all active:scale-[0.97] flex items-center justify-center gap-1.5 sm:gap-2 shadow-sm"
                   title="Tambah ke POS"
                >
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                   <span class="hidden sm:inline">Tambah</span>
                </button>
                
                <button 
                   @click="printReceipt"
                   class="flex-1 px-3 py-2.5 rounded-xl text-[10px] font-medium uppercase tracking-wider text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all active:scale-[0.97] flex items-center justify-center gap-1.5 sm:gap-2 shadow-sm"
                   title="Cetak Struk"
                >
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                   <span class="hidden sm:inline">Cetak</span>
                </button>
 
                <button 
                   @click="sendWhatsapp"
                   class="flex-1 px-3 py-2.5 rounded-xl text-[10px] font-medium uppercase tracking-wider text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all active:scale-[0.97] flex items-center justify-center gap-1.5 sm:gap-2 shadow-sm"
                   title="Kirim via WhatsApp"
                >
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><line x1="12" x2="12" y1="18" y2="18"/></svg>
                   <span class="hidden sm:inline">WA</span>
                </button>

                 <button 
                    v-if="selectedTransaction.status === 'pending'"
                    @click="proceedToPay"
                    class="flex-1 px-3 py-2.5 rounded-xl text-[10px] font-medium uppercase tracking-wider text-white bg-primary-500 hover:bg-primary-600 transition-all active:scale-[0.97] flex items-center justify-center gap-1.5 sm:gap-2 shadow-sm shadow-primary-500/20"
                    title="Bayar Sekarang"
                 >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span class="hidden sm:inline">Bayar Sekarang</span>
                 </button>
             </div>
          </div>
        </div>
      </div>
    </Teleport>
    
    <Teleport to="body">
      <CheckoutModal
        v-if="showCheckoutModal"
        @close="showCheckoutModal = false; selectedTransaction = null; loadTransactions()"
      />
    </Teleport>
  </div>
</template>

<style scoped>
.search-container {
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  max-width: 0;
  opacity: 0;
  transform: translateX(0);
  white-space: nowrap;
  margin-left: 0;
}

.search-open {
  max-width: 400px;
  opacity: 1;
  transform: translateX(0);
  margin-left: 0;
}

.search-closed {
  max-width: 0;
  opacity: 0;
  transform: translateX(0);
  margin-left: 0;
}

@media (max-width: 768px) {
  .flex.items-center.flex-1 {
    flex-wrap: nowrap;
    width: 100%;
    overflow: hidden;
  }
  
  .search-open {
    max-width: calc(100% - 150px);
    width: auto;
    margin-top: 0;
    transform: translateX(0);
    flex: 1;
    min-width: 0;
  }
  
  .search-closed {
    max-width: 0;
    margin-top: 0;
    transform: translateX(0);
  }
  
  .search-container {
    width: auto;
    flex: 1;
    min-width: 0;
  }
  
  .search-input-wrapper {
    min-width: 0 !important;
    width: 100%;
    max-width: 100%;
  }
  
  .search-input-wrapper input {
    font-size: 14px;
  }
  
  .search-input-wrapper input::placeholder {
    font-size: 12px;
  }
}
input[type="date"] {
  position: relative;
}

input[type="date"]::-webkit-calendar-picker-indicator {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: auto;
  height: auto;
  color: transparent;
  background: transparent;
}

.custom-date-input::-webkit-calendar-picker-indicator {
  display: block;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}
</style>
