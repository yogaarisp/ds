<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
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
  // Load saved language preference
  const savedLanguage = localStorage.getItem('app_language') || 'id'
  selectedLanguage.value = savedLanguage
})

function selectLanguage(languageCode) {
  if (!languages.find(lang => lang.code === languageCode)?.isAvailable) {
    return
  }
  
  selectedLanguage.value = languageCode
  localStorage.setItem('app_language', languageCode)
  
  // Show success message
  const languageName = languages.find(lang => lang.code === languageCode)?.name
  // For now, just show alert - in real app you'd integrate with i18n
  alert(`Bahasa berhasil diubah ke ${languageName}`)
}

function showComingSoon() {
  alert('Bahasa ini akan segera tersedia!')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 bg-white z-50 px-4 py-3 shadow-sm border-b border-slate-100 flex items-center gap-3 h-14">
      <button @click="router.back()" class="p-2 -ml-2 text-slate-600 hover:bg-slate-50 rounded-full active:scale-90 transition-transform">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
      </button>
      <h1 class="text-lg font-bold text-slate-800">Pengaturan Bahasa</h1>
    </div>

    <!-- Content -->
    <div class="pt-16 pb-8 px-4">
      <!-- Current Language Info -->
      <div class="bg-orange-50 border border-orange-100 rounded-2xl p-4 mb-6">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-2xl">
            {{ languages.find(lang => lang.code === selectedLanguage)?.flag }}
          </div>
          <div>
            <h3 class="font-bold text-slate-800 text-sm">Bahasa Saat Ini</h3>
            <p class="text-orange-600 font-semibold text-sm">
              {{ languages.find(lang => lang.code === selectedLanguage)?.name }}
            </p>
          </div>
        </div>
      </div>

      <!-- Language List -->
      <div class="space-y-2">
        <h2 class="text-sm font-bold text-slate-400 uppercase tracking-widest px-1 mb-3">Pilih Bahasa</h2>
        
        <div 
          v-for="language in languages" 
          :key="language.code"
          @click="language.isAvailable ? selectLanguage(language.code) : showComingSoon()"
          class="bg-white rounded-2xl p-4 flex items-center justify-between transition-all duration-200 border border-transparent"
          :class="[
            language.isAvailable 
              ? 'cursor-pointer hover:border-orange-100 hover:shadow-sm active:scale-[0.98]' 
              : 'opacity-60 cursor-not-allowed',
            selectedLanguage === language.code && language.isAvailable 
              ? 'border-orange-200 bg-orange-50' 
              : ''
          ]"
        >
          <div class="flex items-center gap-4">
            <!-- Flag -->
            <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center text-2xl border border-slate-100">
              {{ language.flag }}
            </div>
            
            <!-- Language Info -->
            <div>
              <h3 class="font-bold text-slate-800 text-sm">{{ language.name }}</h3>
              <p class="text-slate-500 text-xs">{{ language.nativeName }}</p>
            </div>
          </div>

          <!-- Status -->
          <div class="flex items-center gap-2">
            <!-- Coming Soon Badge -->
            <div v-if="!language.isAvailable" class="bg-slate-100 text-slate-500 text-xs font-medium px-2 py-1 rounded-full">
              Segera Hadir
            </div>
            
            <!-- Selected Indicator -->
            <div v-if="selectedLanguage === language.code && language.isAvailable" class="text-orange-500">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            
            <!-- Arrow for available languages -->
            <div v-else-if="language.isAvailable" class="text-slate-300">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Info Section -->
      <div class="mt-8 bg-white rounded-2xl p-4 border border-slate-100">
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-slate-800 text-sm mb-1">Informasi</h3>
            <p class="text-slate-500 text-xs leading-relaxed">
              Perubahan bahasa akan diterapkan pada seluruh aplikasi. Beberapa bahasa masih dalam tahap pengembangan dan akan segera tersedia.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>