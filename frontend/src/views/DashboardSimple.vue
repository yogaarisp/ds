<template>
  <div class="p-4 lg:p-6 bg-gray-50/50 min-h-screen pb-20 lg:pb-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">{{ getGreeting() }}! 👋</h1>
      <p class="text-gray-500 text-sm mt-0.5">Berikut ringkasan toko hari ini</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
      <!-- Today's Transactions -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
            <span class="text-lg">📋</span>
          </div>
          <span class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Hari Ini</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ stats.todayTransactions }}</p>
        <p class="text-xs text-gray-500 mt-0.5">Total Transaksi</p>
      </div>

      <!-- Today's Revenue -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
            <span class="text-lg">💰</span>
          </div>
          <span class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Hari Ini</span>
        </div>
        <p class="text-xl font-bold text-gray-900">Rp {{ formatPrice(stats.todayRevenue) }}</p>
        <p class="text-xs text-gray-500 mt-0.5">Pendapatan</p>
      </div>

      <!-- Monthly Revenue -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center">
            <span class="text-lg">📊</span>
          </div>
          <span class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Bulan Ini</span>
        </div>
        <p class="text-xl font-bold text-gray-900">Rp {{ formatPrice(stats.monthlyRevenue) }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ stats.monthlyTransactions }} transaksi</p>
      </div>

      <!-- Low Stock Warning -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center">
            <span class="text-lg">✅</span>
          </div>
          <span class="text-[10px] uppercase tracking-wide text-gray-400 font-medium">Stok</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">0</p>
        <p class="text-xs text-gray-500 mt-0.5">Stok Aman</p>
      </div>
    </div>

    <!-- Transactions Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">
      <!-- Recent Transactions -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-full flex flex-col">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-medium text-gray-900 text-sm">Transaksi Terbaru</h3>
          <router-link to="/transactions?tab=all" class="text-xs text-primary-600 hover:underline">
            Lihat semua →
          </router-link>
        </div>
        <div class="divide-y divide-gray-50 flex-1">
          <div class="px-4 py-8 text-center text-gray-400 text-sm">
            Belum ada transaksi hari ini
          </div>
        </div>
      </div>

      <!-- Upcoming / PO Transactions -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-full flex flex-col">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-medium text-gray-900 text-sm">Pesanan PO (Upcoming)</h3>
          <router-link to="/transactions?tab=upcoming" class="text-xs text-primary-600 hover:underline">
            Lihat semua →
          </router-link>
        </div>
        <div class="divide-y divide-gray-50 flex-1">
          <div class="px-4 py-8 text-center text-gray-400 text-sm">
            Tidak ada pesanan PO
          </div>
        </div>
      </div>
    </div>

    <!-- Low Stock Items Row -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-medium text-gray-900 text-sm">Stok Rendah</h3>
        <router-link to="/inventory" class="text-xs text-primary-600 hover:underline">
          Kelola stok →
        </router-link>
      </div>
      <div class="px-4 py-8 text-center text-gray-400">
        <span class="text-2xl block mb-1">✅</span>
        <p class="text-sm">Semua stok aman</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
      <h3 class="font-medium text-gray-900 text-sm mb-3">Aksi Cepat</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <router-link to="/pos" class="flex flex-col items-center p-3 bg-gray-50 rounded-xl hover:bg-primary-50 transition-colors group">
          <span class="text-2xl mb-1 group-hover:scale-110 transition-transform">🛒</span>
          <span class="text-xs text-gray-600 group-hover:text-primary-600">Transaksi</span>
        </router-link>
        <router-link to="/products" class="flex flex-col items-center p-3 bg-gray-50 rounded-xl hover:bg-primary-50 transition-colors group">
          <span class="text-2xl mb-1 group-hover:scale-110 transition-transform">🥟</span>
          <span class="text-xs text-gray-600 group-hover:text-primary-600">Produk</span>
        </router-link>
        <router-link to="/inventory" class="flex flex-col items-center p-3 bg-gray-50 rounded-xl hover:bg-primary-50 transition-colors group">
          <span class="text-2xl mb-1 group-hover:scale-110 transition-transform">🏭</span>
          <span class="text-xs text-gray-600 group-hover:text-primary-600">Stok</span>
        </router-link>
        <router-link to="/reports" class="flex flex-col items-center p-3 bg-gray-50 rounded-xl hover:bg-primary-50 transition-colors group">
          <span class="text-2xl mb-1 group-hover:scale-110 transition-transform">📊</span>
          <span class="text-xs text-gray-600 group-hover:text-primary-600">Laporan</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const stats = ref({
  todayTransactions: 0,
  todayRevenue: 0,
  monthlyTransactions: 0,
  monthlyRevenue: 0
})

onMounted(() => {
  // Load stats safely
  loadStats()
})

function loadStats() {
  // Set some demo data for now
  stats.value = {
    todayTransactions: 0,
    todayRevenue: 0,
    monthlyTransactions: 0,
    monthlyRevenue: 0
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price || 0)
}

function getGreeting() {
  const hour = new Date().getHours()
  if (hour < 12) return 'Selamat Pagi'
  if (hour < 15) return 'Selamat Siang'
  if (hour < 18) return 'Selamat Sore'
  return 'Selamat Malam'
}
</script>