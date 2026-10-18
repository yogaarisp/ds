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

// Carousel
const currentSlide = ref(0)
const carouselSlides = ref([])
const defaultSlides = []
const slides = computed(() => carouselSlides.value.length > 0 ? carouselSlides.value : defaultSlides)
let slideInterval

// Modal states
const showVoucherModal = ref(false)
const showReferralModal = ref(false)
const showPromoModal = ref(false)
const referralCopied = ref(false)

// ── Helpers ─────────────────────────────────────────────────────────────────

function formatNumber(num) {
  return new Intl.NumberFormat('id-ID').format(num)
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR',
    minimumFractionDigits: 0, maximumFractionDigits: 0
  }).format(value)
}

// ── Carousel ─────────────────────────────────────────────────────────────────

function startCarousel() {
  if (slides.value.length < 2) return
  slideInterval = setInterval(() => {
    currentSlide.value = (currentSlide.value + 1) % slides.value.length
  }, 5000)
}

function stopCarousel() {
  clearInterval(slideInterval)
}

// ── Referral ─────────────────────────────────────────────────────────────────

const referralCode = computed(() => {
  if (!user.value?.name) return null
  // Generate kode dari nama + id customer
  const base = user.value.name.replace(/\s+/g, '').toUpperCase().slice(0, 5)
  const id = user.value.id ? String(user.value.id).padStart(3, '0') : '000'
  return `DS-${base}${id}`
})

async function copyReferral() {
  try {
    await navigator.clipboard.writeText(referralCode.value)
    referralCopied.value = true
    setTimeout(() => { referralCopied.value = false }, 2000)
  } catch {
    // fallback
    const el = document.createElement('textarea')
    el.value = referralCode.value
    document.body.appendChild(el)
    el.select()
    document.execCommand('copy')
    document.body.removeChild(el)
    referralCopied.value = true
    setTimeout(() => { referralCopied.value = false }, 2000)
  }
}

async function shareReferral() {
  const text = `Hei! Coba Dimsum Sarang yang enak ini 🥟\nGunakan kode referral aku *${referralCode.value}* buat dapat diskon!\n\nOrder di: ${window.location.origin}/customer/home`
  if (navigator.share) {
    try {
      await navigator.share({ title: 'Dimsum Sarang', text })
    } catch {}
  } else {
    await navigator.clipboard.writeText(text)
    Swal.fire({ icon: 'success', title: 'Link disalin!', timer: 1500, showConfirmButton: false, width: '280px', customClass: { popup: 'rounded-2xl' } })
  }
}

function openReferral() {
  if (!authStore.isAuthenticated) {
    authStore.promptAuthChoice()
    return
  }
  showReferralModal.value = true
}

function openVoucher() {
  if (!authStore.isAuthenticated) {
    authStore.promptAuthChoice()
    return
  }
  showVoucherModal.value = true
}

// ── Change table ─────────────────────────────────────────────────────────────

function changeTable() {
  if (cartStore.cart.length > 0) {
    Swal.fire({
      title: 'Ganti Meja?',
      html: `<p class="text-sm text-slate-600">Anda memiliki <strong>${cartStore.cart.length} item</strong> di keranjang.<br>Keranjang akan tetap tersimpan.</p>`,
      icon: 'question', showCancelButton: true,
      confirmButtonText: 'Ya, Ganti Meja', cancelButtonText: 'Batal',
      confirmButtonColor: '#F97316', cancelButtonColor: '#6b7280',
      width: '320px', customClass: { popup: 'rounded-2xl', title: 'text-base font-bold' }
    }).then(r => { if (r.isConfirmed) cartStore.clearTable() })
  } else {
    cartStore.clearTable()
    Swal.fire({ icon: 'success', title: 'Meja direset', timer: 1500, showConfirmButton: false, width: '280px', customClass: { popup: 'rounded-2xl' } })
  }
}

// ── Help modal ───────────────────────────────────────────────────────────────

function openHelpModal() {
  const phone = settingsStore.settings?.store_phone
  const instagram = settingsStore.settings?.store_instagram?.replace('@', '')
  Swal.fire({
    title: 'Butuh Bantuan?',
    html: `
      <div class="flex flex-col gap-3 mt-2">
        ${phone ? `<a href="https://wa.me/${phone.replace(/^0/, '62')}" target="_blank" class="flex items-center gap-3 bg-emerald-50 p-3 rounded-xl border border-emerald-100">
          <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
          </div>
          <div class="text-left"><span class="block text-xs font-bold text-slate-800">Hubungi WhatsApp</span><span class="block text-[10px] text-slate-500">${phone}</span></div>
        </a>` : ''}
        ${instagram ? `<a href="https://instagram.com/${instagram}" target="_blank" class="flex items-center gap-3 bg-purple-50 p-3 rounded-xl border border-purple-100">
          <div class="w-10 h-10 bg-gradient-to-tr from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
          </div>
          <div class="text-left"><span class="block text-xs font-bold text-slate-800">Kunjungi Instagram</span><span class="block text-[10px] text-slate-500">@${instagram}</span></div>
        </a>` : ''}
        ${!phone && !instagram ? '<p class="text-sm text-slate-400 italic py-4">Belum ada kontak yang ditambahkan.</p>' : ''}
      </div>`,
    showConfirmButton: false, showCloseButton: true, width: '320px',
    customClass: { popup: 'rounded-2xl p-0 overflow-hidden', title: 'text-base font-bold text-slate-800 pt-6', htmlContainer: 'px-6 pb-6 pt-0 m-0', closeButton: 'focus:outline-none' }
  })
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(async () => {
  settingsStore.loadSettings()

  // Handle QR table param
  const tableParam = route.query.table
  if (tableParam) {
    try {
      const response = await api.get(`/tables/code/${tableParam}`)
      if (response.data) {
        cartStore.setTable(response.data.code, response.data.name)
        cartStore.setTableExpiry(Date.now() + 45 * 60 * 1000)
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
    if (userData) user.value = JSON.parse(userData)
  }

  if (authStore.token) {
    authStore.fetchUser().then(u => { if (u) user.value = u })
  }

  // Carousel
  try {
    const res = await carouselApi.getActive()
    carouselSlides.value = res.data
  } catch {}
  startCarousel()

  // Popular packages
  try {
    const res = await api.get('/packages/popular')
    popularPackages.value = res.data
  } catch {}
})

onUnmounted(stopCarousel)
</script>

<template>
  <div class="bg-[#f8f7f5] min-h-screen pb-28">

    <!-- ══════════════════════════════════════════════
         HERO — Carousel
    ══════════════════════════════════════════════ -->
    <div class="relative overflow-hidden" style="border-radius: 0 0 28px 28px;">

      <!-- Slides -->
      <div class="relative h-[280px]">
        <div
          v-for="(slide, i) in slides" :key="slide.id"
          class="absolute inset-0 transition-opacity duration-700"
          :class="i === currentSlide ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        >
          <img :src="slide.image_url || slide.image" class="w-full h-full object-cover" alt="Promo" />
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
        </div>
        <!-- Empty state -->
        <div v-if="slides.length === 0" class="absolute inset-0 bg-gradient-to-br from-orange-400 to-rose-500"></div>
      </div>

      <!-- Top bar -->
      <div class="absolute top-0 left-0 right-0 flex justify-between items-start px-5 pt-5 z-20">
        <!-- Store name -->
        <div>
          <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest">Selamat Datang di</p>
          <h1 class="text-lg font-black text-white leading-tight drop-shadow-sm">
            {{ settingsStore.settings?.store_name || 'Dimsum Sarang' }}
          </h1>
        </div>
        <!-- Help btn -->
        <button @click="openHelpModal" class="bg-white/20 backdrop-blur-md p-2.5 rounded-2xl border border-white/30 active:scale-90 transition-transform">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </button>
      </div>

      <!-- Slide text overlay -->
      <div v-if="slides.length > 0" class="absolute bottom-0 left-0 right-0 px-5 pb-6 z-20">
        <transition name="fade" mode="out-in">
          <div :key="currentSlide">
            <h2 v-if="slides[currentSlide]?.title" class="text-xl font-black text-white leading-tight drop-shadow mb-1">
              {{ slides[currentSlide].title }}
            </h2>
            <div v-if="slides[currentSlide]?.period_text" class="text-[10px] font-semibold text-white/80 bg-white/20 backdrop-blur-sm px-2.5 py-1 rounded-full w-fit border border-white/20">
              {{ slides[currentSlide].period_text }}
            </div>
          </div>
        </transition>
      </div>

      <!-- Slide indicators -->
      <div v-if="slides.length > 1" class="absolute bottom-3 right-5 flex gap-1.5 z-20">
        <button
          v-for="(_, i) in slides" :key="i"
          @click="currentSlide = i"
          class="h-1.5 rounded-full transition-all duration-300 bg-white"
          :class="i === currentSlide ? 'w-5 opacity-100' : 'w-1.5 opacity-40'"
        ></button>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════
         USER INFO CARD (floating)
    ══════════════════════════════════════════════ -->
    <div class="px-4 -mt-5 relative z-20">
      <div class="bg-white rounded-2xl shadow-lg shadow-black/8 p-3.5 flex justify-between items-center border border-slate-100/80">
        <div class="flex items-center gap-3">
          <!-- Avatar -->
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-black text-base shadow-sm"
            :class="cartStore.hasTable ? 'bg-orange-500 text-white' : 'bg-orange-50 text-orange-500 border border-orange-100'">
            <template v-if="cartStore.hasTable">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10V7h18v3m-3 0v11M6 10v11m-3-11h18"/>
              </svg>
            </template>
            <template v-else>{{ user.name ? user.name.charAt(0).toUpperCase() : '👋' }}</template>
          </div>

          <div class="leading-tight">
            <template v-if="authStore.isAuthenticated && cartStore.hasTable">
              <p class="font-bold text-slate-800 text-sm">Hi, {{ user.name?.split(' ')[0] }}</p>
              <div class="flex items-center gap-1.5 mt-0.5">
                <span class="text-[9px] font-bold text-orange-500 uppercase tracking-wider">Meja:</span>
                <span class="text-[10px] font-extrabold text-orange-600">{{ cartStore.tableName }}</span>
                <button @click="changeTable" class="text-[9px] text-slate-400 underline">Ganti</button>
              </div>
            </template>
            <template v-else-if="cartStore.hasTable">
              <p class="text-[10px] font-bold text-orange-500 uppercase tracking-wider leading-none">Meja Anda</p>
              <div class="flex items-center gap-2">
                <p class="font-extrabold text-slate-800 text-base">{{ cartStore.tableName }}</p>
                <button @click="changeTable" class="text-[9px] text-orange-500 underline">Ganti</button>
              </div>
            </template>
            <template v-else-if="authStore.isAuthenticated">
              <p class="font-bold text-slate-800 text-sm">Hi, {{ user.name?.split(' ')[0] }} 👋</p>
              <p class="text-[10px] text-slate-400 font-medium">Mau makan apa hari ini?</p>
            </template>
            <template v-else>
              <p class="font-bold text-slate-800 text-sm">Selamat Datang! 👋</p>
              <p class="text-[10px] text-slate-400 font-medium">Login untuk nikmati keuntungan lebih</p>
            </template>
          </div>
        </div>

        <!-- Points & Coupons (logged in) / Login btn (guest) -->
        <div v-if="authStore.isAuthenticated" class="flex items-center gap-3">
          <div class="flex flex-col items-center">
            <div class="flex items-center gap-1">
              <div class="w-4 h-4 rounded-full bg-amber-400 flex items-center justify-center text-[8px] font-black text-white">P</div>
              <span class="font-black text-sm text-slate-800">{{ formatNumber(user.points || 0) }}</span>
            </div>
            <span class="text-[9px] text-slate-400 font-medium">Poin</span>
          </div>
          <div class="w-px h-8 bg-slate-100"></div>
          <div class="flex flex-col items-center">
            <div class="flex items-center gap-1">
              <svg class="w-4 h-4 text-orange-400" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M1.5 6.375c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v3.026a.75.75 0 01-.375.65 2.249 2.249 0 000 3.898.75.75 0 01.375.65v3.026c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 011.5 17.625v-3.026a.75.75 0 01.374-.65 2.249 2.249 0 000-3.898.75.75 0 01-.374-.65V6.375z" clip-rule="evenodd"/>
              </svg>
              <span class="font-black text-sm text-slate-800">{{ user.coupons_count || 0 }}</span>
            </div>
            <span class="text-[9px] text-slate-400 font-medium">Voucher</span>
          </div>
        </div>
        <div v-else>
          <button @click="router.push('/customer/login')" class="bg-orange-500 text-white text-[10px] font-bold px-4 py-2 rounded-xl shadow-md shadow-orange-200 active:scale-95 transition-transform">
            Login
          </button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════
         QUICK ACTIONS
    ══════════════════════════════════════════════ -->
    <div class="px-4 mt-4">
      <div class="grid grid-cols-4 gap-3">

        <!-- Order -->
        <router-link to="/customer/menu" class="flex flex-col items-center gap-2 group">
          <div class="w-full aspect-square bg-gradient-to-br from-orange-400 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-300/40 group-active:scale-90 transition-transform">
            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
              <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"/>
            </svg>
          </div>
          <span class="text-[11px] font-bold text-slate-700">Order</span>
        </router-link>

        <!-- Voucher -->
        <button @click="openVoucher" class="flex flex-col items-center gap-2 group">
          <div class="w-full aspect-square bg-gradient-to-br from-amber-400 to-yellow-500 rounded-2xl flex items-center justify-center shadow-lg shadow-yellow-300/40 group-active:scale-90 transition-transform relative">
            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
              <path fill-rule="evenodd" d="M1.5 6.375c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v3.026a.75.75 0 01-.375.65 2.249 2.249 0 000 3.898.75.75 0 01.375.65v3.026c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 011.5 17.625v-3.026a.75.75 0 01.374-.65 2.249 2.249 0 000-3.898.75.75 0 01-.374-.65V6.375z" clip-rule="evenodd"/>
            </svg>
            <span v-if="user.coupons_count > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[9px] font-black rounded-full flex items-center justify-center border-2 border-white shadow">
              {{ user.coupons_count }}
            </span>
          </div>
          <span class="text-[11px] font-bold text-slate-700">Voucher</span>
        </button>

        <!-- Referral -->
        <button @click="openReferral" class="flex flex-col items-center gap-2 group">
          <div class="w-full aspect-square bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-purple-300/40 group-active:scale-90 transition-transform">
            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
              <path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z"/>
            </svg>
          </div>
          <span class="text-[11px] font-bold text-slate-700">Referral</span>
        </button>

        <!-- Riwayat Pesanan -->
        <router-link to="/customer/order" class="flex flex-col items-center gap-2 group">
          <div class="w-full aspect-square bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-300/40 group-active:scale-90 transition-transform">
            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
              <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0118 9.375v9.375a3 3 0 003-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 00-.673-.05A3 3 0 0015 1.5h-1.5a3 3 0 00-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6zM13.5 3a1.5 1.5 0 00-1.5 1.5h3A1.5 1.5 0 0013.5 3z" clip-rule="evenodd"/>
              <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 013 20.625V9.375zm9.586 4.594a.75.75 0 00-1.172-.938l-2.476 3.096-.908-.907a.75.75 0 00-1.06 1.06l1.5 1.5a.75.75 0 001.116-.062l3-3.75z" clip-rule="evenodd"/>
            </svg>
          </div>
          <span class="text-[11px] font-bold text-slate-700">Pesanan</span>
        </router-link>

      </div>
    </div>

    <!-- ══════════════════════════════════════════════
         MENU TERLARIS
    ══════════════════════════════════════════════ -->
    <div class="px-4 mt-5">
      <div class="flex justify-between items-center mb-3">
        <div>
          <h2 class="text-base font-black text-slate-800">Menu Terlaris 🔥</h2>
          <p class="text-[10px] text-slate-400 font-medium">Paling banyak dipesan</p>
        </div>
        <router-link to="/customer/menu" class="text-[11px] font-bold text-orange-500 bg-orange-50 px-3 py-1.5 rounded-full border border-orange-100 active:scale-95 transition-transform">
          Lihat Semua →
        </router-link>
      </div>

      <div class="flex gap-3 overflow-x-auto hide-scrollbar pb-2">
        <div
          v-for="comb in popularPackages" :key="comb.id"
          @click="router.push('/customer/menu')"
          class="min-w-[148px] max-w-[148px] bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100 active:scale-95 transition-transform cursor-pointer group"
        >
          <div class="relative h-28 overflow-hidden bg-slate-50">
            <LazyImage
              v-if="comb.display_image"
              :src="comb.display_image"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              alt="Package"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-4xl">🥟</div>
            <!-- Terjual badge -->
            <div v-if="comb.total_sold > 0" class="absolute bottom-2 left-2 bg-black/60 backdrop-blur-sm text-white text-[9px] font-bold px-2 py-0.5 rounded-full">
              {{ comb.total_sold }}+ terjual
            </div>
            <!-- Hemat badge -->
            <div class="absolute top-2 right-2 bg-orange-500 text-white text-[8px] font-black px-1.5 py-0.5 rounded-full uppercase tracking-wide">
              Hemat
            </div>
          </div>
          <div class="p-2.5">
            <p class="text-[9px] font-bold text-orange-400 uppercase tracking-tighter truncate">{{ comb.parent_name }}</p>
            <p class="font-bold text-slate-800 text-[11px] truncate mt-0.5">{{ comb.display_name }}</p>
            <p class="text-orange-600 font-extrabold text-[12px] mt-1">{{ formatCurrency(comb.base_price) }}</p>
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="popularPackages.length === 0" class="flex items-center justify-center w-full py-8 text-slate-300">
          <p class="text-sm font-medium">Memuat menu...</p>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════
         PROMO BANNER
    ══════════════════════════════════════════════ -->
    <div class="px-4 mt-5">
      <div
        @click="router.push('/customer/menu')"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-orange-500 to-rose-500 p-5 cursor-pointer active:scale-[0.98] transition-transform shadow-lg shadow-orange-300/30"
      >
        <!-- Decor circles -->
        <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full"></div>
        <div class="absolute -right-2 bottom-0 w-16 h-16 bg-white/10 rounded-full"></div>
        <div class="absolute right-16 -bottom-4 w-12 h-12 bg-white/10 rounded-full"></div>

        <div class="relative z-10 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest mb-1">Penawaran Spesial</p>
            <h3 class="text-lg font-black text-white leading-tight">Coba Menu Baru<br>Sekarang! 🥟</h3>
            <div class="mt-3 bg-white/20 backdrop-blur-sm text-white text-[10px] font-bold px-3 py-1.5 rounded-full w-fit border border-white/20">
              Lihat Menu Lengkap →
            </div>
          </div>
          <div class="text-5xl opacity-90">🍱</div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════
         INFO TOKO
    ══════════════════════════════════════════════ -->
    <div class="px-4 mt-5">
      <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
        <h3 class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
          <span class="w-6 h-6 bg-orange-50 rounded-lg flex items-center justify-center text-xs">🏪</span>
          Info Toko
        </h3>
        <div class="space-y-2.5">
          <div v-if="settingsStore.settings?.store_address" class="flex items-start gap-3">
            <div class="w-7 h-7 bg-slate-50 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </div>
            <p class="text-[11px] text-slate-600 font-medium leading-relaxed">{{ settingsStore.settings.store_address }}</p>
          </div>
          <div v-if="settingsStore.settings?.store_phone" class="flex items-center gap-3">
            <div class="w-7 h-7 bg-slate-50 rounded-lg flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
              </svg>
            </div>
            <a :href="`https://wa.me/${settingsStore.settings.store_phone.replace(/^0/, '62')}`" target="_blank"
              class="text-[11px] text-orange-500 font-bold">
              {{ settingsStore.settings.store_phone }}
            </a>
          </div>
          <div v-if="settingsStore.settings?.store_hours" class="flex items-center gap-3">
            <div class="w-7 h-7 bg-slate-50 rounded-lg flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <p class="text-[11px] text-slate-600 font-medium">{{ settingsStore.settings.store_hours }}</p>
          </div>
          <!-- Default jam jika tidak ada setting -->
          <div v-if="!settingsStore.settings?.store_hours" class="flex items-center gap-3">
            <div class="w-7 h-7 bg-slate-50 rounded-lg flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <p class="text-[11px] text-slate-600 font-medium">Buka Setiap Hari · 08.00 – 21.00</p>
          </div>
        </div>
      </div>
    </div>


    <!-- ══════════════════════════════════════════════
         MODAL: VOUCHER
    ══════════════════════════════════════════════ -->
    <transition name="modal">
      <div v-if="showVoucherModal" class="fixed inset-0 z-50 flex items-end justify-center" @click.self="showVoucherModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md bg-white rounded-t-3xl z-10 overflow-hidden" style="max-height: 80vh;">
          <!-- Handle -->
          <div class="flex justify-center pt-3 pb-1">
            <div class="w-10 h-1 bg-slate-200 rounded-full"></div>
          </div>
          <div class="px-5 pt-2 pb-4 flex justify-between items-center border-b border-slate-100">
            <div class="flex items-center gap-2">
              <span class="text-xl">🎟️</span>
              <h3 class="font-black text-slate-800 text-base">Voucher Saya</h3>
            </div>
            <button @click="showVoucherModal = false" class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 active:scale-90 transition-transform">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="overflow-y-auto p-5 space-y-3" style="max-height: calc(80vh - 80px);">

            <!-- Coupon items -->
            <template v-if="user.coupons_count > 0">
              <div v-for="i in (user.coupons_count || 0)" :key="i"
                class="relative overflow-hidden bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-100 rounded-2xl p-4 flex items-center gap-4">
                <!-- Dashed divider kiri -->
                <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center shrink-0 border border-orange-100">
                  <svg class="w-6 h-6 text-orange-500" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M1.5 6.375c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v3.026a.75.75 0 01-.375.65 2.249 2.249 0 000 3.898.75.75 0 01.375.65v3.026c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 011.5 17.625v-3.026a.75.75 0 01.374-.65 2.249 2.249 0 000-3.898.75.75 0 01-.374-.65V6.375z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="font-black text-slate-800 text-sm">Voucher Diskon</p>
                  <p class="text-[10px] text-slate-500 font-medium mt-0.5">Gunakan saat checkout untuk mendapat potongan harga</p>
                </div>
                <div class="shrink-0">
                  <span class="text-[9px] font-black bg-orange-500 text-white px-2 py-1 rounded-lg uppercase tracking-wide">Aktif</span>
                </div>
                <!-- Deco -->
                <div class="absolute -right-4 -bottom-4 w-16 h-16 bg-orange-100/50 rounded-full"></div>
              </div>
            </template>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
              <div class="text-5xl mb-3">🎟️</div>
              <p class="font-bold text-slate-700 text-sm">Belum ada voucher</p>
              <p class="text-[11px] text-slate-400 mt-1 max-w-[200px]">Voucher bisa didapat dari promo spesial atau hadiah poin</p>
              <button @click="showVoucherModal = false; router.push('/customer/menu')"
                class="mt-4 bg-orange-500 text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow-md shadow-orange-200 active:scale-95 transition-transform">
                Pesan Sekarang
              </button>
            </div>

          </div>
        </div>
      </div>
    </transition>


    <!-- ══════════════════════════════════════════════
         MODAL: REFERRAL
    ══════════════════════════════════════════════ -->
    <transition name="modal">
      <div v-if="showReferralModal" class="fixed inset-0 z-50 flex items-end justify-center" @click.self="showReferralModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-md bg-white rounded-t-3xl z-10 overflow-hidden">
          <!-- Handle -->
          <div class="flex justify-center pt-3 pb-1">
            <div class="w-10 h-1 bg-slate-200 rounded-full"></div>
          </div>
          <div class="px-5 pt-2 pb-4 flex justify-between items-center border-b border-slate-100">
            <div class="flex items-center gap-2">
              <span class="text-xl">🎁</span>
              <h3 class="font-black text-slate-800 text-base">Referral Saya</h3>
            </div>
            <button @click="showReferralModal = false" class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 active:scale-90 transition-transform">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <div class="p-5 space-y-4">
            <!-- Ilustrasi -->
            <div class="text-center py-2">
              <div class="text-5xl mb-2">🥟</div>
              <h4 class="font-black text-slate-800 text-lg">Ajak Teman, Dapat Reward!</h4>
              <p class="text-[11px] text-slate-500 mt-1 leading-relaxed max-w-[260px] mx-auto">
                Bagikan kode referral kamu ke teman. Setiap teman yang mendaftar dan memesan, kamu dapat poin bonus!
              </p>
            </div>

            <!-- Kode -->
            <div class="bg-gradient-to-r from-violet-50 to-purple-50 border border-violet-100 rounded-2xl p-4">
              <p class="text-[10px] font-bold text-violet-400 uppercase tracking-widest mb-2 text-center">Kode Referral Kamu</p>
              <div class="flex items-center gap-3 bg-white rounded-xl px-4 py-3 border border-violet-100 shadow-sm">
                <span class="flex-1 font-black text-xl text-slate-800 tracking-widest text-center">{{ referralCode }}</span>
                <button @click="copyReferral"
                  class="shrink-0 flex items-center gap-1.5 bg-violet-500 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg active:scale-90 transition-all"
                  :class="referralCopied ? 'bg-emerald-500' : 'bg-violet-500'">
                  <svg v-if="!referralCopied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                  <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                  {{ referralCopied ? 'Tersalin!' : 'Salin' }}
                </button>
              </div>
            </div>

            <!-- Steps -->
            <div class="space-y-2.5">
              <div v-for="(step, i) in [
                { icon: '📤', text: 'Bagikan kode ke teman kamu' },
                { icon: '📝', text: 'Teman daftar & masukkan kode saat registrasi' },
                { icon: '🎁', text: 'Kamu dapat poin bonus setelah teman pertama kali order' }
              ]" :key="i" class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                <span class="text-lg shrink-0">{{ step.icon }}</span>
                <p class="text-[11px] font-medium text-slate-600 leading-relaxed">{{ step.text }}</p>
              </div>
            </div>

            <!-- Share button -->
            <button @click="shareReferral"
              class="w-full bg-gradient-to-r from-violet-500 to-purple-600 text-white font-bold py-3.5 rounded-2xl shadow-lg shadow-purple-300/30 active:scale-95 transition-transform flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
              </svg>
              Bagikan ke Teman
            </button>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Modal transition */
.modal-enter-active, .modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-active .relative, .modal-leave-active .relative { transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .relative { transform: translateY(100%); }
.modal-leave-to .relative { transform: translateY(100%); }

/* Slide fade */
.fade-enter-active, .fade-leave-active { transition: opacity 0.4s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
