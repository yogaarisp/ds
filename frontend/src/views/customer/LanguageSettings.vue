<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from '@/i18n'
import Swal from 'sweetalert2'

const router = useRouter()
const { t, setLanguage, locale } = useI18n()
const selectedLanguage = ref('id')

const languages = [
  {
    code: 'id',
    name: 'Bahasa Indonesia',
    nativeName: 'Bahasa Indonesia',
    flag: '🇮🇩',
    isAvailable: true
  },
  {
    code: 'en',
    name: 'English',
    nativeName: 'English',
    flag: '🇺🇸',
    isAvailable: true
  },
  {
    code: 'zh',
    name: 'Chinese',
    nativeName: '中文',
    flag: '🇨🇳',
    isAvailable: false
  },
  {
    code: 'ja',
    name: 'Japanese',
    nativeName: '日本語',
    flag: '🇯🇵',
    isAvailable: false
  },
  {
    code: 'ko',
    name: 'Korean',
    nativeName: '한국어',
    flag: '🇰🇷',
    isAvailable: false
  }
]

onMounted(() => {
  selectedLanguage.value = locale.value
})

function selectLanguage(languageCode) {
  const target = languages.find(lang => lang.code === languageCode)
  if (!target?.isAvailable) {
    return
  }
  
  selectedLanguage.value = languageCode
  setLanguage(languageCode)
  
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: `${t('languageChanged')} ${target.name}`,
    showConfirmButton: false,
    timer: 1500
  })
}

function showComingSoon() {
  Swal.fire({
    title: t('comingSoon'),
    text: 'Bahasa ini sedang dalam tahap penerjemahan.',
    icon: 'info',
    confirmButtonColor: '#F97316',
    confirmButtonText: 'Mengerti',
    width: '280px',
    customClass: {
      popup: 'rounded-2xl',
      title: 'text-sm font-bold',
      confirmButton: 'px-4 py-2 text-xs rounded-xl font-bold'
    }
  })
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 pb-28 text-slate-800 dark:text-slate-100 transition-colors">
    <div class="max-w-md mx-auto">
      
      <!-- Top Sticky App Bar -->
      <header class="sticky top-0 z-30 bg-slate-50/95 dark:bg-slate-900/95 backdrop-blur-md px-4 py-3 border-b border-slate-200/50 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button 
            @click="router.back()" 
            aria-label="Kembali"
            class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-orange-500 active:scale-90 transition-all"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
          </button>
          <h1 class="text-sm font-bold text-slate-800 dark:text-slate-100">
            {{ t('languageSettings') }}
          </h1>
        </div>
      </header>

      <!-- Content -->
      <div class="p-4 space-y-4">
        
        <!-- Current Language Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-3.5 border border-slate-200/70 dark:border-slate-700/80 shadow-xs flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-2xl">{{ languages.find(l => l.code === selectedLanguage)?.flag }}</span>
            <div>
              <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wide">{{ t('currentLanguage') }}</span>
              <p class="text-xs font-bold text-slate-800 dark:text-gray-100">
                {{ languages.find(l => l.code === selectedLanguage)?.name }}
              </p>
            </div>
          </div>
          <span class="text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-semibold px-2 py-0.5 rounded-full">
            Aktif
          </span>
        </div>

        <!-- Language List -->
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-1 mb-2 block">
            {{ t('selectLanguage') }}
          </span>

          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/70 dark:border-slate-700/80 divide-y divide-slate-100 dark:divide-slate-700/60 shadow-xs overflow-hidden">
            <div 
              v-for="lang in languages" 
              :key="lang.code"
              @click="lang.isAvailable ? selectLanguage(lang.code) : showComingSoon()"
              class="p-3.5 flex items-center justify-between transition-colors"
              :class="[
                lang.isAvailable ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50' : 'opacity-50 cursor-not-allowed'
              ]"
            >
              <div class="flex items-center gap-3">
                <span class="text-xl">{{ lang.flag }}</span>
                <div>
                  <p class="text-xs font-semibold text-slate-800 dark:text-gray-100">{{ lang.name }}</p>
                  <p class="text-[10px] text-slate-400">{{ lang.nativeName }}</p>
                </div>
              </div>

              <div>
                <span v-if="!lang.isAvailable" class="text-[9px] bg-slate-100 dark:bg-slate-700 text-slate-400 px-2 py-0.5 rounded-full font-medium">
                  Segera
                </span>
                <span v-else-if="selectedLanguage === lang.code" class="text-orange-500 font-bold text-xs">
                  ✓
                </span>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>