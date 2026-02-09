<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth' // Use auth store for user data
import { useCartStore } from '@/stores/cart'
import api, { carouselApi } from '@/api'
import { useRouter, useRoute } from 'vue-router'
import Swal from 'sweetalert2'
import LazyImage from '@/components/LazyImage.vue'

const router = useRouter()
const route = useRoute()
const settingsStore = useSettingsStore()
const authStore = useAuthStore() // Access auth store
const cartStore = useCartStore()
const user = ref({})
const popularPackages = ref([])

// Carousel State
const currentSlide = ref(0)
const carouselSlides = ref([])
const defaultSlides = [
  { id: 1, image_url: 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', title: 'Selamat Datang', period_text: 'Nikmati menu terbaik kami' },
  { id: 2, image_url: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', title: 'Menu Spesial', period_text: 'Tersedia berbagai pilihan paket' },
  { id: 3, image_url: 'https://images.unsplash.com/photo-1496116218417-7a17478ee177?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', title: 'Promo Menarik', period_text: 'Cek promo bulanan kami' },
]

// Use API slides if available, otherwise fallback to default
const slides = computed(() => {
  return carouselSlides.value.length > 0 ? carouselSlides.value : defaultSlides
})

let slideInterval

function showComingSoon(e) {
  if (e) e.preventDefault()
  Swal.fire({
    title: 'Coming Soon!',
    text: 'Fitur ini akan segera hadir.',
    icon: 'info',
    confirmButtonColor: '#f97316',
    confirmButtonText: 'Oke',
    width: '280px',
    customClass: {
      popup: 'rounded-xl',
      title: 'text-lg font-bold',
      htmlContainer: 'text-sm',
      confirmButton: 'px-4 py-1.5 text-sm rounded-lg'
    }
  })
}

onMounted(async () => {
  settingsStore.loadSettings()
  
  // Handle table parameter from QR code
  const tableParam = route.query.table
  if (tableParam) {
    try {
      // Fetch table info from API
      const response = await api.get(`/tables/code/${tableParam}`)
      if (response.data) {
        cartStore.setTable(response.data.code, response.data.name)
        console.log('Table set from QR:', response.data.name)
      }
    } catch (error) {
      console.error('Failed to load table from QR:', error)
    }
  }
  
  // Load user from auth store or local storage
  if (authStore.user) {
    user.value = authStore.user
  } else {
    const userData = localStorage.getItem('user')
    if (userData) {
      user.value = JSON.parse(userData)
    }
  }
  
  // Fetch latest user data (points & coupons)
  if (authStore.token) {
    authStore.fetchUser().then(updatedUser => {
      if (updatedUser) {
        user.value = updatedUser
      }
    })
  }

  // Fetch carousel slides from API
  try {
    const res = await carouselApi.getActive()
    carouselSlides.value = res.data
  } catch (err) {
    console.log('Using default carousel slides')
  }

  // Fetch popular packages
  api.get('/packages/popular').then(res => {
    popularPackages.value = res.data
  })

  // Start Carousel
  startCarousel()
})

onUnmounted(() => {
  stopCarousel()
})

function startCarousel() {
  slideInterval = setInterval(() => {
    currentSlide.value = (currentSlide.value + 1) % slides.value.length
  }, 5000)
}

function stopCarousel() {
  clearInterval(slideInterval)
}

function formatNumber(num) {
  return new Intl.NumberFormat('id-ID').format(num)
}


function addToCart(product) {
  // Check auth or table session
  if (!authStore.isAuthenticated && !cartStore.hasTable) {
    authStore.promptAuthChoice()
    return
  }

  cartStore.addItem({
    type: 'product',
    id: product.id,
    name: product.name,
    price: product.final_price || product.base_price,
    image: product.image,
    quantity: 1
  })

  Swal.fire({
    icon: 'success',
    title: 'Ditambahkan!',
    text: `${product.name} masuk ke keranjang`,
    showConfirmButton: false,
    timer: 1500,
    width: '280px',
    customClass: {
      popup: 'rounded-xl',
      title: 'text-sm font-bold',
      htmlContainer: 'text-xs'
    }
  })
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

function changeTable() {
  // Check if there are items in cart
  if (cartStore.cart.length > 0) {
    Swal.fire({
      title: 'Ganti Meja?',
      html: `
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Anda memiliki <strong>${cartStore.cart.length} item</strong> di keranjang.</p>
        <p class="text-sm text-slate-600 dark:text-slate-400">Keranjang akan tetap tersimpan.</p>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, Ganti Meja',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#F97316',
      cancelButtonColor: '#6b7280',
      width: '320px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-base font-bold',
        htmlContainer: 'text-sm',
        confirmButton: 'px-4 py-2 text-sm rounded-lg',
        cancelButton: 'px-4 py-2 text-sm rounded-lg'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        cartStore.clearTable()
        Swal.fire({
          icon: 'success',
          title: 'Meja Direset',
          text: 'Silakan scan QR atau pilih meja baru saat checkout',
          timer: 2000,
          showConfirmButton: false,
          width: '300px',
          customClass: {
            popup: 'rounded-2xl',
            title: 'text-base font-bold',
            htmlContainer: 'text-sm'
          }
        })
      }
    })
  } else {
    // No items in cart, just clear
    Swal.fire({
      title: 'Ganti Meja?',
      text: 'Anda akan melepas meja saat ini',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, Ganti',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#F97316',
      cancelButtonColor: '#6b7280',
      width: '300px',
      customClass: {
        popup: 'rounded-2xl',
        title: 'text-base font-bold',
        htmlContainer: 'text-sm',
        confirmButton: 'px-4 py-2 text-sm rounded-lg',
        cancelButton: 'px-4 py-2 text-sm rounded-lg'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        cartStore.clearTable()
        Swal.fire({
          icon: 'success',
          title: 'Meja Direset',
          text: 'Silakan scan QR atau pilih meja baru',
          timer: 2000,
          showConfirmButton: false,
          width: '300px',
          customClass: {
            popup: 'rounded-2xl',
            title: 'text-base font-bold',
            htmlContainer: 'text-sm'
          }
        })
      }
    })
  }
}
</script>

<template>
  <div class="bg-slate-50 min-h-screen pb-24 dark:bg-gray-900">
    
    <!-- 1. Top Section with Carousel -->
    <div class="relative rounded-b-xl overflow-hidden shadow-lg">
      <!-- QR Button (Top Right) -->
      <div class="absolute top-4 right-4 z-30">
        <button @click="showComingSoon" class="bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-full flex items-center gap-2 shadow-sm active:scale-95 transition-transform">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 14.5V12m0 0V8.825m0 0a5.5 5.5 0 11-11 0c0 3.037 2.463 5.5 5.5 5.5m0 0h6.25m0-9.75a2.768 2.768 0 00-2.756 3.456m0-3.344A2.756 2.756 0 005.5 8.5m10.25 1.25a1.25 1.25 0 112.5 0 1.25 1.25 0 01-2.5 0z" />
          </svg>
          <span class="text-sm font-semibold text-slate-800">QR</span>
        </button>
      </div>

      <!-- Carousel Content -->
      <div class="relative h-[260px] w-full">
        <div 
          v-for="(slide, index) in slides" 
          :key="slide.id"
          class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
          :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
        >
          <!-- Image with gradient overlay for better text readability -->
          <img :src="slide.image_url || slide.image" class="w-full h-full object-cover" alt="Promo">
          <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/30 to-transparent"></div>
          <!-- Text Overlay -->
          <div class="absolute inset-0 flex flex-col justify-center px-6 pt-8 text-white">
            <h2 class="text-2xl font-bold mb-2 drop-shadow-md">{{ slide.title }}</h2>
            <div v-if="slide.period_text" class="bg-white/20 backdrop-blur-md px-2.5 py-0.5 rounded-lg w-fit text-[10px] font-medium border border-white/30">
              {{ slide.period_text }}
            </div>
            
            <!-- Vouchers (Dynamic from API) -->
            <div v-if="slide.badge_label || slide.badge_label_2" class="mt-4 flex gap-3 overflow-x-auto hide-scrollbar pb-2">
               <div v-if="slide.badge_label && slide.badge_value" class="bg-white text-orange-600 px-3 py-2 rounded-xl shadow-lg min-w-[120px]">
                 <div class="text-[9px] font-semibold uppercase opacity-70">{{ slide.badge_label }}</div>
                 <div class="font-bold text-base leading-tight">{{ slide.badge_value }}</div>
               </div>
               <div v-if="slide.badge_label_2 && slide.badge_value_2" class="bg-white text-orange-600 px-3 py-2 rounded-xl shadow-lg min-w-[120px]">
                 <div class="text-[9px] font-semibold uppercase opacity-70">{{ slide.badge_label_2 }}</div>
                 <div class="font-bold text-base leading-tight">{{ slide.badge_value_2 }}</div>
               </div>
            </div>
          </div>
        </div>

        <!-- Indicators -->
        <div class="absolute bottom-10 right-6 flex gap-1.5 z-20">
          <button 
            v-for="(slide, index) in slides" 
            :key="index"
            @click="currentSlide = index"
            class="h-1.5 rounded-full transition-all duration-300 bg-white"
            :class="index === currentSlide ? 'w-6 opacity-100' : 'w-1.5 opacity-50'"
          ></button>
        </div>
      </div>
    </div>

    <!-- 2. User Info Bar (Floating) -->
    <div class="px-4 -mt-6 relative z-20">
      <div class="bg-white rounded-xl shadow-lg shadow-slate-200/50 p-3 flex justify-between items-center border border-slate-100 dark:bg-gray-800 dark:border-gray-700 dark:shadow-gray-900/50">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 bg-white rounded-lg shadow-sm flex items-center justify-center text-orange-500 shrink-0">
            <template v-if="cartStore.hasTable">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18" />
                </svg>
             </template>
             <template v-else>
                {{ user.name ? user.name.charAt(0).toUpperCase() : '👋' }}
             </template>
          </div>
          <div class="flex flex-col">
            <!-- Show both customer name and table when both are available -->
            <template v-if="authStore.isAuthenticated && cartStore.hasTable">
              <span class="font-semibold text-slate-800 text-sm leading-tight dark:text-gray-100">Hi, {{ user.name }}</span>
              <div class="flex items-center gap-1">
                <span class="text-[9px] text-orange-500 font-bold uppercase tracking-wider">Meja:</span>
                <span class="font-bold text-orange-600 text-xs">{{ cartStore.tableName }}</span>
                <button @click="changeTable" class="ml-1 text-[9px] text-orange-500 underline hover:text-orange-600 transition-colors">
                  Ganti
                </button>
              </div>
            </template>
            <!-- Show only table when not authenticated but has table -->
            <template v-else-if="cartStore.hasTable">
              <div class="flex items-center gap-2">
                <div>
                  <span class="text-[10px] text-orange-500 font-bold uppercase tracking-wider leading-none mb-0.5">Meja Anda</span>
                  <span class="font-bold text-slate-800 text-base leading-none dark:text-gray-100 block">{{ cartStore.tableName }}</span>
                </div>
                <button @click="changeTable" class="text-[10px] text-orange-500 underline hover:text-orange-600 transition-colors whitespace-nowrap">
                  Ganti
                </button>
              </div>
            </template>
            <!-- Show only customer name when authenticated but no table -->
            <template v-else>
              <span class="font-semibold text-slate-800 text-base dark:text-gray-100">{{ user.name ? 'Hi, ' + user.name : 'Selamat Datang' }}</span>
            </template>
          </div>
        </div>
        <div v-if="authStore.isAuthenticated" class="flex items-center gap-3">
           <!-- Coupons -->
           <div class="flex items-center gap-1 text-slate-600">
             <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-orange-400">
                <path fill-rule="evenodd" d="M1.5 6.375c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v3.026a.75.75 0 01-.375.65 2.249 2.249 0 000 3.898.75.75 0 01.375.65v3.026c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 011.5 17.625v-3.026a.75.75 0 01.374-.65 2.249 2.249 0 000-3.898.75.75 0 01-.374-.65V6.375zm15-1.125a.75.75 0 01.75.75v.75a.75.75 0 01-1.5 0V6a.75.75 0 01.75-.75zm.75 4.5a.75.75 0 00-1.5 0v.75a.75.75 0 001.5 0v-.75zm-.75 3a.75.75 0 01.75.75v.75a.75.75 0 01-1.5 0v-.75a.75.75 0 01.75-.75z" clip-rule="evenodd" />
             </svg>
             <span class="font-semibold text-sm">{{ user.coupons_count || 0 }}</span>
           </div>
           <!-- Points -->
           <div class="flex items-center gap-1 text-slate-600">
             <div class="w-4 h-4 rounded-full bg-yellow-400 flex items-center justify-center text-[9px] text-white font-bold border border-yellow-200 shadow-sm">P</div>
             <span class="font-semibold text-sm">{{ user.points || 0 }} <span class="text-[10px] font-normal text-slate-400">pts</span></span>
           </div>
        </div>
      </div>
    </div>

    <!-- 3. Popular Menu Section -->
    <div class="px-4 mt-3 text-left">
      <div class="bg-white rounded-[24px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/60 p-5 relative overflow-hidden">
        <div class="flex justify-between items-center mb-5">
          <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2 dark:text-gray-100">
            Menu Terlaris 🔥
          </h2>
          <router-link to="/customer/menu" class="text-[11px] font-bold text-orange-500 bg-orange-50 px-2.5 py-1 rounded-full active:scale-95 transition-all">Lihat Semua</router-link>
        </div>
        
        <!-- Horizontal Scroll Cards -->
        <div class="flex gap-4 overflow-x-auto hide-scrollbar -mx-1 pb-1">
          <div 
            v-for="comb in popularPackages" 
            :key="comb.id"
            @click="router.push('/customer/menu')"
            class="min-w-[150px] max-w-[150px] bg-slate-50 rounded-2xl border border-slate-100/50 overflow-hidden group active:scale-95 transition-all duration-300 cursor-pointer"
          >
            <div class="relative h-24 overflow-hidden">
              <LazyImage 
                v-if="comb.display_image"
                :src="comb.display_image" 
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                alt="Package" 
              />
              <div v-else class="w-full h-full flex items-center justify-center text-3xl bg-slate-100 dark:bg-slate-800">
                📦
              </div>
               <div class="absolute top-2 right-2 bg-orange-500/90 backdrop-blur-sm px-1.5 py-0.5 rounded-full shadow-sm">
                  <span class="text-[8px] font-bold text-white uppercase tracking-wider">Hemat</span>
               </div>
            </div>
            <div class="p-2.5">
               <div class="text-[7px] font-bold text-orange-400 uppercase tracking-tighter mb-0.5 truncate">{{ comb.parent_name }}</div>
               <h3 class="font-bold text-slate-800 text-[11px] truncate mb-0.5">{{ comb.display_name }}</h3>
               <div class="flex items-center justify-between mt-1">
                  <span class="text-orange-600 font-extrabold text-[11px]">{{ formatCurrency(comb.base_price) }}</span>
                  <span v-if="comb.total_sold > 0" class="text-[8px] text-slate-400 font-bold bg-slate-100 px-1 rounded">{{ comb.total_sold }}+ terjual</span>
               </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. Main Actions Grid -->
    <div class="px-4 mt-3">
      <div class="bg-white rounded-[24px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/60 p-5 relative overflow-hidden">
        <!-- Decorative Background Blob -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-orange-50/50 to-transparent rounded-bl-full -z-0 opacity-50"></div>

        <!-- Big Buttons -->
        <div class="grid grid-cols-2 gap-4 mb-6 relative z-10">
          <router-link to="/customer/menu" class="group relative overflow-hidden bg-gradient-to-br from-[#FFF8F1] to-white p-4 rounded-2xl border border-orange-100/60 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-white rounded-2xl shadow-[0_2px_10px_rgba(249,115,22,0.15)] flex items-center justify-center text-orange-500 group-hover:scale-110 group-active:scale-95 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                  <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z" />
                </svg>
              </div>
              <div class="flex flex-col">
                <span class="font-bold text-slate-800 text-base leading-tight">Order</span>
                <span class="text-[10px] font-medium text-slate-400">Pesan Sekarang</span>
              </div>
            </div>
            <!-- Decor -->
            <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-orange-100/50 rounded-full blur-xl group-hover:bg-orange-200/50 transition-colors"></div>
          </router-link>
          
          <a href="#" @click="showComingSoon" class="group relative overflow-hidden bg-gradient-to-br from-[#FEFCE8] to-white p-4 rounded-2xl border border-yellow-100/60 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-white rounded-2xl shadow-[0_2px_10px_rgba(234,179,8,0.15)] flex items-center justify-center text-yellow-500 group-hover:scale-110 group-active:scale-95 transition-all duration-300">
                 <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                   <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0111.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 01-1.085.67L12 18.089l-7.165 3.583A.75.75 0 013.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93z" clip-rule="evenodd" />
                 </svg>
              </div>
              <div class="flex flex-col">
                <span class="font-bold text-slate-800 text-base leading-tight">Voucher+</span>
                <span class="text-[10px] font-medium text-slate-400">Hemat Lebih</span>
              </div>
            </div>
             <!-- Decor -->
             <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-yellow-100/50 rounded-full blur-xl group-hover:bg-yellow-200/50 transition-colors"></div>
          </a>
        </div>

        <!-- Small Actions -->
        <div class="grid grid-cols-4 gap-4 relative z-10">
          <div @click="showComingSoon" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-[52px] h-[52px] bg-slate-50 rounded-[18px] flex items-center justify-center shadow-sm border border-slate-100 group-active:scale-90 transition-all duration-300 group-hover:bg-red-50 group-hover:border-red-100 group-hover:shadow-red-100/50">
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-red-500 group-hover:scale-110 transition-transform">
                 <path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177 7.547 7.547 0 01-1.705-1.715.75.75 0 00-1.152-.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" clip-rule="evenodd" />
               </svg>
            </div>
            <span class="text-[11px] font-semibold text-slate-600 text-center leading-tight group-hover:text-red-600 transition-colors">Monthly<br>Promo</span>
          </div>

          <div @click="showComingSoon" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-[52px] h-[52px] bg-slate-50 rounded-[18px] flex items-center justify-center shadow-sm border border-slate-100 group-active:scale-90 transition-all duration-300 group-hover:bg-blue-50 group-hover:border-blue-100 group-hover:shadow-blue-100/50">
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-blue-500 group-hover:scale-110 transition-transform">
                 <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
               </svg>
            </div>
            <span class="text-[11px] font-semibold text-slate-600 text-center leading-tight group-hover:text-blue-600 transition-colors">Referral</span>
          </div>

          <div @click="showComingSoon" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-[52px] h-[52px] bg-slate-50 rounded-[18px] flex items-center justify-center shadow-sm border border-slate-100 group-active:scale-90 transition-all duration-300 group-hover:bg-purple-50 group-hover:border-purple-100 group-hover:shadow-purple-100/50">
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-purple-500 group-hover:scale-110 transition-transform">
                 <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />
               </svg>
            </div>
            <span class="text-[11px] font-semibold text-slate-600 text-center leading-tight group-hover:text-purple-600 transition-colors">Spin &<br>Win</span>
          </div>

          <div @click="showComingSoon" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-[52px] h-[52px] bg-slate-50 rounded-[18px] flex items-center justify-center shadow-sm border border-slate-100 group-active:scale-90 transition-all duration-300 group-hover:bg-green-50 group-hover:border-green-100 group-hover:shadow-green-100/50">
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-green-500 group-hover:scale-110 transition-transform">
                 <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0118 9.375v9.375a3 3 0 003-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 00-.673-.05A3 3 0 0015 1.5h-1.5a3 3 0 00-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6zM13.5 1.5h-3c.22.501.598.916 1.08 1.168v.57c0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75v-.57a2.25 2.25 0 001.08-1.168z" clip-rule="evenodd" />
                 <path fill-rule="evenodd" d="M3.75 15h16.5c.414 0 .75.336.75.75v5.25a3 3 0 01-3 3H6a3 3 0 01-3-3v-5.25c0-.414.336-.75.75-.75z" clip-rule="evenodd" />
               </svg>
            </div>
            <span class="text-[11px] font-semibold text-slate-600 text-center leading-tight group-hover:text-green-600 transition-colors">Mission</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. Mission Section -->
    <div class="px-4 mt-6">
      <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-3 border-b border-slate-50 pb-2">
          <h2 class="text-base font-bold text-slate-800">Misi Spesial</h2>
          <span class="text-[10px] text-slate-500 bg-slate-50 px-2 py-0.5 rounded-full border border-slate-100">Berakhir 31 Jan</span>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-orange-50 flex items-center justify-center shrink-0 border border-orange-100">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-orange-500">
              <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-slate-800 text-sm leading-tight mb-1">Diskon 25% Spesial Imlek</h3>
            <p class="text-[10px] text-slate-500">Selesaikan misi untuk dapatkan voucher</p>
          </div>
          <button class="bg-orange-500 text-white text-[10px] font-semibold px-3 py-1.5 rounded-lg shadow-md shadow-orange-500/20 hover:bg-orange-600 transition-colors">
            Mulai
          </button>
        </div>
      </div>
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
