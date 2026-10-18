<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import api, { carouselApi } from '@/api'
import { useRouter, useRoute } from 'vue-router'
import Swal from 'sweetalert2'
import LazyImage from '@/components/LazyImage.vue'

const router = useRouter()
const route = useRoute()
const settingsStore = useSettingsStore()
const authStore = useAuthStore()
const cartStore = useCartStore()

const user = ref({})
const popularPackages = ref([])
const loadingPopular = ref(true)

// Carousel State
const currentSlide = ref(0)
const carouselSlides = ref([])

// Fallback slides
const defaultSlides = [
  {
    id: 'default-1',
    title: 'Kelezatan Dimsum Hangat Pilihan',
    period_text: 'Promo Hari Ini',
    badge_label: 'Pilihan',
    badge_value: 'Best Seller',
    image: 'https://images.unsplash.com/photo-1541696432-82c6da8ce7bf?auto=format&fit=crop&w=900&q=80'
  }
]

const slides = computed(() => {
  return carouselSlides.value.length > 0 ? carouselSlides.value : defaultSlides
})

let slideInterval

// Friendly first name for greeting
const userFirstName = computed(() => {
  if (user.value?.name) {
    return user.value.name.trim().split(' ')[0]
  }
  if (authStore.user?.name) {
    return authStore.user.name.trim().split(' ')[0]
  }
  return ''
})

// Help Modal (WhatsApp & Instagram) - Clean & Minimalist
function openHelpModal() {
  const phone = settingsStore.settings?.store_phone
  const instagram = settingsStore.settings?.store_instagram?.replace('@', '')

  Swal.fire({
    title: 'Bantuan & Kontak',
    html: `
      <div class="flex flex-col gap-2.5 mt-3 text-left">
        ${phone ? `
          <a href="https://wa.me/${phone.replace(/^0/, '62')}" target="_blank" class="flex items-center gap-3 bg-slate-50 hover:bg-slate-100 p-3 rounded-xl border border-slate-200/80 transition-colors">
            <div class="w-9 h-9 bg-slate-900 text-white rounded-lg flex items-center justify-center shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
            </div>
            <div>
              <span class="block text-xs font-bold text-slate-800">WhatsApp Kasir</span>
              <span class="block text-[11px] text-slate-500">${phone}</span>
            </div>
          </a>
        ` : ''}

        ${instagram ? `
          <a href="https://instagram.com/${instagram}" target="_blank" class="flex items-center gap-3 bg-slate-50 hover:bg-slate-100 p-3 rounded-xl border border-slate-200/80 transition-colors">
            <div class="w-9 h-9 bg-slate-900 text-white rounded-lg flex items-center justify-center shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </div>
            <div>
               <span class="block text-xs font-bold text-slate-800">Instagram Resmi</span>
               <span class="block text-[11px] text-slate-500">@${instagram}</span>
            </div>
          </a>
        ` : ''}

        ${!phone && !instagram ? '<p class="text-xs text-slate-400 italic py-3 text-center">Kontak outlet belum tersedia.</p>' : ''}
      </div>
    `,
    showConfirmButton: false,
    showCloseButton: true,
    width: '320px',
    customClass: {
      popup: 'rounded-2xl p-0 overflow-hidden',
      title: 'text-sm font-bold text-slate-800 pt-5',
      htmlContainer: 'px-5 pb-5 pt-0 m-0',
      closeButton: 'focus:outline-none'
    }
  })
}

// Voucher Modal - Clean & Focused
function openVoucherModal() {
  if (authStore.isAuthenticated) {
    const count = user.value?.coupons_count || 0
    Swal.fire({
      title: 'Voucher Promo',
      html: `
        <div class="text-center py-2">
          <p class="text-xs text-slate-600 mb-3">Tersedia <strong class="text-orange-600 font-bold">${count} voucher</strong> yang dapat digunakan pada pesananmu.</p>
          <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 text-xs text-slate-600 text-left leading-relaxed">
            💡 <strong>Info Penggunaan:</strong> Pilih menu favorit, lalu pilih voucher saat ringkasan pembayaran di keranjang.
          </div>
        </div>
      `,
      confirmButtonText: 'Buka Daftar Menu',
      confirmButtonColor: '#F97316',
      width: '310px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-sm font-bold',
        confirmButton: 'w-full py-2.5 rounded-xl text-xs font-bold'
      }
    }).then(res => {
      if (res.isConfirmed) {
        router.push('/customer/menu')
      }
    })
  } else {
    Swal.fire({
      title: 'Voucher & Diskon',
      html: `
        <p class="text-xs text-slate-600 mb-2 leading-relaxed">
          Masuk atau daftar akun untuk klaim voucher potongan harga dan simpan poin dari setiap transaksi.
        </p>
      `,
      showCancelButton: true,
      confirmButtonText: 'Masuk / Daftar',
      cancelButtonText: 'Tutup',
      confirmButtonColor: '#F97316',
      cancelButtonColor: '#64748b',
      width: '310px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-sm font-bold',
        confirmButton: 'px-4 py-2 rounded-xl text-xs font-bold',
        cancelButton: 'px-4 py-2 rounded-xl text-xs font-medium'
      }
    }).then(res => {
      if (res.isConfirmed) {
        authStore.promptAuthChoice()
      }
    })
  }
}

// Quick Add to Cart with clean toast
function addPackageToCart(comb, event) {
  if (event) event.stopPropagation()

  cartStore.addItem({
    type: 'package',
    id: comb.package_id,
    package_combination_id: comb.id,
    name: comb.display_name || comb.name,
    price: comb.final_price || comb.base_price,
    image: comb.display_image,
    quantity: 1,
    is_frozen: 0
  })

  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: `${comb.display_name || comb.name} ditambahkan`,
    showConfirmButton: false,
    timer: 1400,
    timerProgressBar: false
  })
}

// Table Management
function changeTable() {
  const hasItems = cartStore.cart.length > 0
  Swal.fire({
    title: 'Ganti Meja?',
    text: hasItems 
      ? `Ada ${cartStore.cart.length} item di keranjang. Keranjang tetap tersimpan.` 
      : 'Sesi meja saat ini akan dilepas.',
    showCancelButton: true,
    confirmButtonText: 'Ya, Ganti',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#F97316',
    cancelButtonColor: '#64748b',
    width: '300px',
    customClass: {
      popup: 'rounded-2xl',
      title: 'text-sm font-bold',
      confirmButton: 'px-4 py-2 text-xs rounded-xl font-bold',
      cancelButton: 'px-4 py-2 text-xs rounded-xl'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      cartStore.clearTable()
      Swal.fire({
        icon: 'success',
        title: 'Meja Direset',
        text: 'Silakan scan QR meja baru',
        timer: 1600,
        showConfirmButton: false,
        width: '280px',
        customClass: { popup: 'rounded-2xl', title: 'text-sm font-bold' }
      })
    }
  })
}

// Table info for guest
function promptManualTable() {
  Swal.fire({
    title: 'Makan di Tempat',
    html: `
      <div class="text-left text-xs text-slate-600 space-y-2">
        <p>Silakan <strong>scan kode QR</strong> di meja untuk menghubungkan pesanan langsung.</p>
        <p class="text-slate-400">Atau kamu bisa memilih menu terlebih dahulu sekarang.</p>
      </div>
    `,
    confirmButtonText: 'Pilih Menu',
    confirmButtonColor: '#F97316',
    width: '300px',
    customClass: {
      popup: 'rounded-2xl',
      title: 'text-sm font-bold',
      confirmButton: 'w-full py-2.5 text-xs rounded-xl font-bold'
    }
  }).then(res => {
    if (res.isConfirmed) {
      router.push('/customer/menu')
    }
  })
}

function startCarousel() {
  if (slideInterval) clearInterval(slideInterval)
  slideInterval = setInterval(() => {
    if (slides.value.length > 1) {
      currentSlide.value = (currentSlide.value + 1) % slides.value.length
    }
  }, 5000)
}

function stopCarousel() {
  if (slideInterval) clearInterval(slideInterval)
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value || 0)
}

onMounted(async () => {
  settingsStore.loadSettings()

  // Handle table parameter from QR code
  const tableParam = route.query.table
  if (tableParam) {
    try {
      const response = await api.get(`/tables/code/${tableParam}`)
      if (response.data) {
        cartStore.setTable(response.data.code, response.data.name)
        const expiryTime = Date.now() + (45 * 60 * 1000)
        cartStore.setTableExpiry(expiryTime)
      }
    } catch (error) {
      console.error('Failed to load table from QR:', error)
    }
  }

  // Load user
  if (authStore.user) {
    user.value = authStore.user
  } else {
    const userData = localStorage.getItem('user')
    if (userData) {
      user.value = JSON.parse(userData)
    }
  }

  if (authStore.token) {
    authStore.fetchUser().then(updatedUser => {
      if (updatedUser) {
        user.value = updatedUser
      }
    })
  }

  // Fetch carousel slides
  try {
    const res = await carouselApi.getActive()
    if (res.data && res.data.length > 0) {
      carouselSlides.value = res.data
    }
  } catch (err) {
    console.log('Using default carousel slides')
  }

  // Fetch popular packages
  try {
    loadingPopular.value = true
    const res = await api.get('/packages/popular')
    popularPackages.value = res.data || []
  } catch (err) {
    console.error('Failed to fetch popular packages:', err)
  } finally {
    loadingPopular.value = false
  }

  startCarousel()
})

onUnmounted(() => {
  stopCarousel()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 pb-28 text-slate-800 dark:text-slate-100 transition-colors">
    <div class="max-w-md mx-auto">

      <!-- ══════════════════════════════════════════
           1. TOP APP BAR (Clean, Monochrome + Single Orange Accent)
      ══════════════════════════════════════════ -->
      <header class="sticky top-0 z-30 bg-slate-50/95 dark:bg-slate-900/95 backdrop-blur-md px-4 pt-3 pb-2.5 border-b border-slate-200/50 dark:border-slate-800">
        <div class="flex items-center justify-between gap-3">
          
          <!-- Brand & Context -->
          <div class="flex items-center gap-2.5 min-w-0">
            <div v-if="settingsStore.storeLogo" class="w-8 h-8 rounded-lg overflow-hidden bg-white dark:bg-slate-800 p-0.5 border border-slate-200 dark:border-slate-700 shrink-0">
              <img :src="settingsStore.storeLogo" class="w-full h-full object-contain" alt="Logo" />
            </div>
            <div v-else class="w-8 h-8 rounded-lg bg-slate-900 text-white font-bold flex items-center justify-center shrink-0 text-xs">
              {{ settingsStore.storeName ? settingsStore.storeName.charAt(0) : 'D' }}
            </div>

            <div class="flex flex-col min-w-0">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 truncate">
                {{ settingsStore.storeName }}
              </span>
              <span class="text-sm font-bold text-slate-800 dark:text-gray-100 truncate leading-tight">
                <template v-if="authStore.isAuthenticated">
                  Hai, {{ userFirstName || 'Pelanggan' }} 👋
                </template>
                <template v-else-if="cartStore.hasTable">
                  {{ cartStore.tableName }}
                </template>
                <template v-else>
                  Selamat Datang
                </template>
              </span>
            </div>
          </div>

          <!-- Actions: Table Pill or Login + Help -->
          <div class="flex items-center gap-2 shrink-0">
            
            <!-- Table Pill (Clean & Subtle) -->
            <button 
              v-if="cartStore.hasTable" 
              @click="changeTable"
              class="flex items-center gap-1.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700 px-2.5 py-1 rounded-full text-xs font-semibold hover:border-orange-300 transition-colors active:scale-95"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <span>{{ cartStore.tableName }}</span>
              <span class="text-[10px] text-slate-400 hover:text-orange-500">Ubah</span>
            </button>

            <!-- Login Link if Guest -->
            <router-link 
              v-else-if="!authStore.isAuthenticated" 
              to="/customer/login" 
              class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-xs active:scale-95 transition-all"
            >
              Masuk
            </router-link>

            <!-- WhatsApp / Help Trigger -->
            <button 
              @click="openHelpModal" 
              aria-label="Kontak dan Bantuan"
              class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-orange-500 active:scale-90 transition-all"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>

          </div>
        </div>
      </header>


      <!-- ══════════════════════════════════════════
           2. CONTEXT BAR (Clean, Calm & Unified)
      ══════════════════════════════════════════ -->
      <section class="px-4 mt-2.5">
        
        <!-- CASE A: LOGGED IN USER -->
        <div 
          v-if="authStore.isAuthenticated"
          class="bg-white dark:bg-slate-800 rounded-2xl p-3 border border-slate-200/70 dark:border-slate-700/80 flex items-center justify-between gap-3 shadow-xs"
        >
          <!-- User info -->
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs flex items-center justify-center shrink-0 border border-slate-200/60 dark:border-slate-600">
              {{ userFirstName ? userFirstName.charAt(0).toUpperCase() : 'M' }}
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="text-xs font-bold text-slate-800 dark:text-gray-100 truncate">
                  {{ user.name || 'Member Setia' }}
                </span>
              </div>
              <p class="text-[10px] text-slate-400 truncate">
                <template v-if="cartStore.hasTable">
                  Dine-in di {{ cartStore.tableName }}
                </template>
                <template v-else>
                  Member Aktif
                </template>
              </p>
            </div>
          </div>

          <!-- Neutral Loyalty Chips -->
          <div class="flex items-center gap-1.5 shrink-0">
            <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-700/50 border border-slate-200/60 dark:border-slate-600 px-2 py-1 rounded-xl text-slate-700 dark:text-slate-200">
              <span class="text-[10px] text-orange-500 font-bold">★</span>
              <span class="text-xs font-bold">{{ user.points || 0 }}</span>
              <span class="text-[9px] text-slate-400">pts</span>
            </div>

            <button 
              @click="openVoucherModal" 
              class="flex items-center gap-1 bg-slate-50 dark:bg-slate-700/50 border border-slate-200/60 dark:border-slate-600 px-2 py-1 rounded-xl text-slate-700 dark:text-slate-200 active:scale-95 transition-all"
            >
              <span class="text-[10px] text-slate-500">🎟️</span>
              <span class="text-xs font-bold">{{ user.coupons_count || 0 }}</span>
            </button>
          </div>
        </div>

        <!-- CASE B: GUEST WITH TABLE -->
        <div 
          v-else-if="cartStore.hasTable"
          class="bg-white dark:bg-slate-800 rounded-2xl p-3 border border-slate-200/70 dark:border-slate-700/80 shadow-xs flex items-center justify-between"
        >
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
              </svg>
            </div>
            <div>
              <div class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Makan di Tempat</span>
              </div>
              <h3 class="text-xs font-bold text-slate-800 dark:text-gray-100 leading-tight">
                {{ cartStore.tableName }}
              </h3>
            </div>
          </div>
          
          <button 
            @click="changeTable"
            class="text-[11px] font-medium text-slate-500 hover:text-slate-800 dark:text-slate-300 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 active:scale-95 transition-all"
          >
            Ganti Meja
          </button>
        </div>

        <!-- CASE C: GUEST WITHOUT TABLE -->
        <div 
          v-else
          class="bg-white dark:bg-slate-800 rounded-2xl p-3 border border-slate-200/70 dark:border-slate-700/80 shadow-xs flex items-center justify-between gap-2"
        >
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div>
              <h4 class="text-xs font-bold text-slate-800 dark:text-gray-100 leading-tight">
                Sedang di Resto?
              </h4>
              <p class="text-[10px] text-slate-400">
                Scan QR meja atau pesan takeaway
              </p>
            </div>
          </div>

          <button 
            @click="promptManualTable"
            class="text-[11px] font-semibold text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 px-3 py-1.5 rounded-xl active:scale-95 transition-all"
          >
            Pilih Meja
          </button>
        </div>

      </section>


      <!-- ══════════════════════════════════════════
           3. INSET PROMO CAROUSEL (Subtle & Breathable)
      ══════════════════════════════════════════ -->
      <section class="px-4 mt-3">
        <div class="relative h-[185px] w-full rounded-2xl overflow-hidden shadow-xs border border-slate-200/70 dark:border-slate-800 bg-slate-900">
          
          <!-- Slides -->
          <div 
            v-for="(slide, index) in slides" 
            :key="slide.id || index"
            class="absolute inset-0 transition-opacity duration-500 ease-in-out"
            :class="index === currentSlide ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'"
          >
            <img 
              :src="slide.image_url || slide.image" 
              class="w-full h-full object-cover" 
              alt="Promo Slide"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

            <div class="absolute inset-0 flex flex-col justify-end p-4 text-white z-20">
              <div v-if="slide.period_text" class="mb-1">
                <span class="bg-white/20 backdrop-blur-md px-2 py-0.5 rounded text-[9px] font-semibold text-slate-200">
                  {{ slide.period_text }}
                </span>
              </div>

              <h2 class="text-base sm:text-lg font-bold leading-tight drop-shadow-sm mb-1 line-clamp-2">
                {{ slide.title }}
              </h2>

              <p v-if="slide.subtitle" class="text-[11px] text-slate-300 line-clamp-1 opacity-90">
                {{ slide.subtitle }}
              </p>
            </div>
          </div>

          <!-- Subtle Dots -->
          <div v-if="slides.length > 1" class="absolute bottom-3 right-4 flex items-center gap-1.5 z-20">
            <button 
              v-for="(slide, index) in slides" 
              :key="'dot-' + index"
              @click="currentSlide = index"
              class="h-1 rounded-full transition-all duration-200"
              :class="index === currentSlide ? 'w-4 bg-white' : 'w-1 bg-white/40'"
            ></button>
          </div>

        </div>
      </section>


      <!-- ══════════════════════════════════════════
           4. QUICK SHORTCUTS (Unified Neutral Palette)
      ══════════════════════════════════════════ -->
      <section class="px-4 mt-3.5">
        <div class="grid grid-cols-4 gap-2">
          
          <!-- Shortcut 1: Menu -->
          <router-link 
            to="/customer/menu" 
            class="flex flex-col items-center gap-1.5 py-2.5 px-1 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 hover:border-orange-300 transition-all active:scale-95"
          >
            <div class="w-9 h-9 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                <path d="M11 2a1 1 0 011 1v1h7a1 1 0 011 1v14a1 1 0 01-1 1h-7v1a1 1 0 11-2 0v-1H3a1 1 0 01-1-1V5a1 1 0 011-1h7V3a1 1 0 011-1zm-1 4H4v12h6V6zm2 12h6V6h-6v12z"/>
              </svg>
            </div>
            <span class="text-[10px] font-semibold text-slate-700 dark:text-slate-300">
              Menu
            </span>
          </router-link>

          <!-- Shortcut 2: Paket Hemat -->
          <router-link 
            to="/customer/menu" 
            class="flex flex-col items-center gap-1.5 py-2.5 px-1 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 hover:border-orange-300 transition-all active:scale-95"
          >
            <div class="w-9 h-9 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                <path d="M9.375 3a1.875 1.875 0 000 3.75h1.875v4.5H3.75A1.875 1.875 0 011.875 9.375v-1.5A1.875 1.875 0 013.75 6h5.625V3z" />
                <path d="M12.75 6.75h1.875a1.875 1.875 0 011.875 1.875v1.5a1.875 1.875 0 01-1.875 1.875H12.75V6.75z" />
                <path fill-rule="evenodd" d="M1.5 13.5A1.5 1.5 0 013 12h18a1.5 1.5 0 011.5 1.5v6A1.5 1.5 0 0121 21H3a1.5 1.5 0 01-1.5-1.5v-6z" clip-rule="evenodd" />
              </svg>
            </div>
            <span class="text-[10px] font-semibold text-slate-700 dark:text-slate-300">
              Paket
            </span>
          </router-link>

          <!-- Shortcut 3: Voucher -->
          <button 
            @click="openVoucherModal" 
            class="flex flex-col items-center gap-1.5 py-2.5 px-1 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 hover:border-orange-300 transition-all active:scale-95"
          >
            <div class="w-9 h-9 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                <path fill-rule="evenodd" d="M1.5 6.375c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v3.026a.75.75 0 01-.375.65 2.249 2.249 0 000 3.898.75.75 0 01.375.65v3.026c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 011.5 17.625v-3.026a.75.75 0 01.374-.65 2.249 2.249 0 000-3.898.75.75 0 01-.374-.65V6.375z" clip-rule="evenodd" />
              </svg>
            </div>
            <span class="text-[10px] font-semibold text-slate-700 dark:text-slate-300">
              Voucher
            </span>
          </button>

          <!-- Shortcut 4: Bantuan -->
          <button 
            @click="openHelpModal" 
            class="flex flex-col items-center gap-1.5 py-2.5 px-1 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 hover:border-orange-300 transition-all active:scale-95"
          >
            <div class="w-9 h-9 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                <path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0112 2.25c2.43 0 4.817.177 7.152.52 1.28.187 2.26 1.264 2.33 2.553a48.56 48.56 0 010 13.354c-.07 1.29-1.05 2.366-2.33 2.553a49.18 49.18 0 01-4.717.472A8.995 8.995 0 0012 19.5c-.868 0-1.705.123-2.435.352a49.198 49.198 0 01-4.717-.472c-1.28-.187-2.26-1.264-2.33-2.553a48.56 48.56 0 010-13.354c.07-1.29 1.05-2.366 2.33-2.553z" clip-rule="evenodd" />
              </svg>
            </div>
            <span class="text-[10px] font-semibold text-slate-700 dark:text-slate-300">
              Bantuan
            </span>
          </button>

        </div>
      </section>


      <!-- ══════════════════════════════════════════
           5. MENU TERLARIS (Clean Cards)
      ══════════════════════════════════════════ -->
      <section class="mt-5 px-4">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-2.5">
          <div>
            <h2 class="text-sm font-bold text-slate-800 dark:text-gray-100 leading-none">
              Menu Terlaris
            </h2>
            <p class="text-[10px] text-slate-400 mt-0.5">
              Pilihan favorit pelanggan
            </p>
          </div>

          <router-link 
            to="/customer/menu" 
            class="text-xs font-semibold text-orange-600 hover:text-orange-700 dark:text-orange-400 active:scale-95 transition-all"
          >
            Lihat Semua →
          </router-link>
        </div>

        <!-- Cards Scroll -->
        <div class="flex gap-3 overflow-x-auto hide-scrollbar -mx-4 px-4 pb-1">
          
          <template v-if="loadingPopular">
            <div v-for="i in 3" :key="'skel-' + i" class="min-w-[145px] max-w-[145px] bg-white dark:bg-slate-800 rounded-2xl p-2 border border-slate-200/60 dark:border-slate-700/60 animate-pulse">
              <div class="h-24 bg-slate-200 dark:bg-slate-700 rounded-xl mb-2"></div>
              <div class="h-2.5 bg-slate-200 dark:bg-slate-700 rounded w-10 mb-1"></div>
              <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-24 mb-2"></div>
              <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-16"></div>
            </div>
          </template>

          <template v-else-if="popularPackages.length > 0">
            <div 
              v-for="comb in popularPackages" 
              :key="comb.id"
              @click="router.push('/customer/menu')"
              class="min-w-[145px] max-w-[145px] bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/70 dark:border-slate-700/70 p-2 flex flex-col justify-between group cursor-pointer active:scale-98 transition-all"
            >
              <!-- Image Container -->
              <div class="h-24 rounded-xl overflow-hidden relative bg-slate-100 dark:bg-slate-700">
                <LazyImage 
                  v-if="comb.display_image"
                  :src="comb.display_image" 
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" 
                  alt="Menu" 
                />
                <div v-else class="w-full h-full flex items-center justify-center text-2xl">
                  🥟
                </div>

                <div v-if="comb.total_sold > 0" class="absolute bottom-1 right-1 bg-black/60 backdrop-blur-xs text-white px-1.5 py-0.5 rounded text-[8px] font-medium">
                  {{ comb.total_sold }}+ terjual
                </div>
              </div>

              <!-- Product Info -->
              <div class="pt-2 px-0.5">
                <span class="text-[8px] font-semibold text-slate-400 uppercase tracking-wider block truncate">
                  {{ comb.parent_name || 'Paket' }}
                </span>
                <h3 class="font-bold text-slate-800 dark:text-gray-100 text-xs truncate">
                  {{ comb.display_name }}
                </h3>

                <!-- Price & Add Button -->
                <div class="flex items-center justify-between mt-2 pt-1 border-t border-slate-100 dark:border-slate-700/60">
                  <span class="text-xs font-bold text-slate-900 dark:text-gray-100">
                    {{ formatCurrency(comb.final_price || comb.base_price) }}
                  </span>

                  <button 
                    @click="addPackageToCart(comb, $event)"
                    title="Tambah ke keranjang"
                    class="w-6 h-6 rounded-full bg-slate-900 hover:bg-orange-500 text-white flex items-center justify-center active:scale-90 transition-all shrink-0"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                </div>
              </div>

            </div>
          </template>

          <div v-else class="w-full text-center py-5 text-slate-400 text-xs">
            Belum ada menu terlaris.
          </div>

        </div>

      </section>


      <!-- ══════════════════════════════════════════
           6. BOTTOM CARD (Clean & Non-Loud)
      ══════════════════════════════════════════ -->
      <section class="px-4 mt-4">
        
        <!-- CASE 1: FOR LOGGED IN USERS -->
        <div 
          v-if="authStore.isAuthenticated"
          class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/70 rounded-2xl p-4 shadow-xs"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-800 dark:text-gray-100">Member Loyalty</span>
            <span class="text-[10px] text-slate-400">ID #{{ user.id || '001' }}</span>
          </div>

          <div class="flex items-baseline gap-1 mb-1">
            <span class="text-2xl font-black text-slate-900 dark:text-gray-100">{{ user.points || 0 }}</span>
            <span class="text-xs font-medium text-slate-500">Poin Aktif</span>
          </div>
          <p class="text-[11px] text-slate-400 leading-tight mb-3">
            Poin otomatis bertambah di setiap transaksi dan dapat digunakan untuk potongan belanja.
          </p>

          <div class="pt-2.5 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
            <router-link to="/customer/order" class="text-xs font-semibold text-orange-600 hover:text-orange-700 dark:text-orange-400">
              Riwayat Pesanan →
            </router-link>
            <router-link to="/customer/settings" class="text-xs text-slate-400 hover:text-slate-600">
              Pengaturan Akun
            </router-link>
          </div>
        </div>

        <!-- CASE 2: FOR GUESTS -->
        <div 
          v-else
          class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/70 rounded-2xl p-4 shadow-xs"
        >
          <h3 class="text-xs font-bold text-slate-800 dark:text-gray-100 mb-1">
            Gabung Member Outlet
          </h3>
          <p class="text-[11px] text-slate-500 leading-relaxed mb-3">
            Kumpulkan poin di setiap transaksi dan nikmati voucher potongan belanja khusus pengguna terdaftar.
          </p>

          <div class="flex items-center gap-2">
            <router-link 
              to="/customer/register" 
              class="flex-1 text-center bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs py-2 rounded-xl active:scale-95 transition-all"
            >
              Daftar Akun
            </router-link>

            <router-link 
              to="/customer/login" 
              class="text-center bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 font-semibold text-xs py-2 px-4 rounded-xl active:scale-95 transition-all"
            >
              Masuk
            </router-link>
          </div>
        </div>

      </section>

    </div>
  </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
