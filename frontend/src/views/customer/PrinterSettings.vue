<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { usePrinter } from '@/composables/usePrinter'
import Swal from 'sweetalert2'

const router = useRouter()
const { 
  printerConnected, 
  printerWidth, 
  autoPrint,
  setPrinterWidth,
  setAutoPrint,
  connectPrinter,
  disconnectPrinter,
  thermalPrinter
} = usePrinter()

const connecting = ref(false)

async function handleConnect() {
  connecting.value = true
  try {
    await connectPrinter()
    Swal.fire({
      icon: 'success',
      title: 'Terhubung!',
      text: 'Printer berhasil terhubung',
      confirmButtonColor: '#F97316',
      timer: 2000,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-base font-bold'
      }
    })
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal Terhubung',
      text: error.message || 'Tidak dapat terhubung ke printer',
      confirmButtonColor: '#F97316',
      width: '300px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-base font-bold'
      }
    })
  } finally {
    connecting.value = false
  }
}

async function handleDisconnect() {
  try {
    await disconnectPrinter()
    Swal.fire({
      icon: 'info',
      title: 'Terputus',
      text: 'Printer telah diputus',
      confirmButtonColor: '#F97316',
      timer: 2000,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-base font-bold'
      }
    })
  } catch (error) {
    console.error('Disconnect error:', error)
  }
}

async function handleTestPrint() {
  if (!printerConnected.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Printer Belum Terhubung',
      text: 'Hubungkan printer terlebih dahulu',
      confirmButtonColor: '#F97316',
      width: '300px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-base font-bold'
      }
    })
    return
  }

  try {
    await thermalPrinter.testPrint()
    Swal.fire({
      icon: 'success',
      title: 'Test Print Berhasil',
      text: 'Cek printer Anda',
      confirmButtonColor: '#F97316',
      timer: 2000,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-base font-bold'
      }
    })
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Test Print Gagal',
      text: error.message,
      confirmButtonColor: '#F97316',
      width: '300px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-base font-bold'
      }
    })
  }
}

function handleWidthChange(width) {
  setPrinterWidth(width)
  thermalPrinter.setPrinterWidth(parseInt(width))
}

function handleAutoPrintChange(enabled) {
  setAutoPrint(enabled)
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-gray-900 pb-24">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 px-4 py-3 shadow-sm border-b border-slate-100 dark:border-gray-700 flex items-center gap-3">
      <button @click="router.back()" class="p-2 -ml-2 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-gray-700 rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </button>
      <h1 class="text-lg font-bold text-slate-800 dark:text-gray-100">Pengaturan Printer</h1>
    </div>

    <div class="p-4 space-y-4">
      <!-- Connection Status -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-slate-100 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-orange-500">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
              </svg>
            </div>
            <div>
              <h3 class="font-bold text-slate-800 dark:text-gray-100">Status Printer</h3>
              <p class="text-sm" :class="printerConnected ? 'text-green-600 dark:text-green-400' : 'text-slate-400'">
                {{ printerConnected ? 'Terhubung' : 'Tidak Terhubung' }}
              </p>
            </div>
          </div>
          <div v-if="printerConnected" class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
        </div>

        <div class="flex gap-2">
          <button 
            v-if="!printerConnected"
            @click="handleConnect"
            :disabled="connecting"
            class="flex-1 bg-orange-500 text-white font-bold py-3 rounded-xl hover:bg-orange-600 transition-colors disabled:opacity-50"
          >
            {{ connecting ? 'Menghubungkan...' : 'Hubungkan Printer' }}
          </button>
          <button 
            v-else
            @click="handleDisconnect"
            class="flex-1 bg-red-500 text-white font-bold py-3 rounded-xl hover:bg-red-600 transition-colors"
          >
            Putuskan Koneksi
          </button>
          <button 
            @click="handleTestPrint"
            :disabled="!printerConnected"
            class="px-6 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-200 font-bold py-3 rounded-xl hover:bg-slate-200 dark:hover:bg-gray-600 transition-colors disabled:opacity-50"
          >
            Test Print
          </button>
        </div>
      </div>

      <!-- Printer Width -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-slate-100 dark:border-gray-700">
        <h3 class="font-bold text-slate-800 dark:text-gray-100 mb-3">Ukuran Kertas</h3>
        <div class="grid grid-cols-2 gap-3">
          <button
            @click="handleWidthChange('58')"
            class="p-4 rounded-xl border-2 transition-all"
            :class="printerWidth === '58' 
              ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30' 
              : 'border-slate-200 dark:border-gray-600 hover:border-orange-300'"
          >
            <div class="text-center">
              <div class="text-2xl font-bold text-slate-800 dark:text-gray-100">58mm</div>
              <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">RPN02N</div>
            </div>
          </button>
          <button
            @click="handleWidthChange('80')"
            class="p-4 rounded-xl border-2 transition-all"
            :class="printerWidth === '80' 
              ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30' 
              : 'border-slate-200 dark:border-gray-600 hover:border-orange-300'"
          >
            <div class="text-center">
              <div class="text-2xl font-bold text-slate-800 dark:text-gray-100">80mm</div>
              <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">RPN02</div>
            </div>
          </button>
        </div>
      </div>

      <!-- Auto Print -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-slate-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-bold text-slate-800 dark:text-gray-100">Auto Print</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Cetak otomatis setelah checkout</p>
          </div>
          <button
            @click="handleAutoPrintChange(!autoPrint)"
            class="relative w-14 h-8 rounded-full transition-colors"
            :class="autoPrint ? 'bg-orange-500' : 'bg-slate-300 dark:bg-gray-600'"
          >
            <div
              class="absolute top-1 w-6 h-6 bg-white rounded-full shadow-md transition-transform"
              :class="autoPrint ? 'translate-x-7' : 'translate-x-1'"
            ></div>
          </button>
        </div>
      </div>

      <!-- Info -->
      <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 rounded-2xl p-4">
        <div class="flex gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
          </svg>
          <div class="text-sm text-blue-700 dark:text-blue-300">
            <p class="font-semibold mb-1">Catatan:</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
              <li>Pastikan printer Bluetooth sudah dinyalakan</li>
              <li>Printer harus support ESC/POS commands</li>
              <li>Untuk browser, gunakan Chrome atau Edge</li>
              <li>Untuk APK, pastikan permission Bluetooth aktif</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
