import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { settingApi, STORAGE_BASE_URL } from '../api'

export const useSettingsStore = defineStore('settings', () => {
  let initialSettings = {
    store_name: 'Dimsum Sarang',
    store_logo: null,
    store_address: '',
    store_phone: '',
  }
  try {
    const cached = localStorage.getItem('cached_store_settings')
    if (cached) initialSettings = { ...initialSettings, ...JSON.parse(cached) }
  } catch (e) {}

  const settings = ref(initialSettings)
  const loading = ref(false)

  const storeName = computed(() => settings.value.store_name || 'Dimsum Sarang')
  const storeLogo = computed(() => {
    // If API returns full URL, use it directly
    if (settings.value.store_logo_url) {
      return settings.value.store_logo_url
    }
    
    // Fallback to manual construction
    if (settings.value.store_logo) {
      return `${STORAGE_BASE_URL}/${settings.value.store_logo}`
    }
    return null
  })

  const storeFavicon = computed(() => {
    // If API returns full URL, use it directly
    if (settings.value.store_favicon_url) {
      return settings.value.store_favicon_url
    }
    
    // Fallback to old method
    if (settings.value.store_favicon) {
      return `${STORAGE_BASE_URL}/${settings.value.store_favicon}`
    }
    return null
  })

  async function loadSettings() {
    loading.value = true
    try {
      const response = await settingApi.getAll()
      settings.value = { ...settings.value, ...response.data }
      localStorage.setItem('cached_store_settings', JSON.stringify(settings.value))
    } catch (error) {
      console.error('Error loading settings:', error)
    } finally {
      loading.value = false
    }
  }

  function updateSettings(newSettings) {
    settings.value = { ...settings.value, ...newSettings }
  }

  return {
    settings,
    loading,
    storeName,
    storeLogo,
    storeFavicon,
    loadSettings,
    updateSettings,
  }
})
