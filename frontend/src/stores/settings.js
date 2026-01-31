import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { settingApi } from '../api'

export const useSettingsStore = defineStore('settings', () => {
  const settings = ref({
    store_name: 'Dimsum Sarang',
    store_logo: null,
    store_address: '',
    store_phone: '',
  })
  const loading = ref(false)

  const storeName = computed(() => settings.value.store_name || 'Dimsum Sarang')
  const storeLogo = computed(() => {
    if (settings.value.store_logo) {
      // Detect if running in mobile app
      const isCapacitor = window.Capacitor !== undefined
      if (isCapacitor) {
        return `https://ds.keetech.my.id/storage/${settings.value.store_logo}`
      }
      return `/storage/${settings.value.store_logo}`
    }
    return null
  })

  const storeFavicon = computed(() => {
    if (settings.value.store_favicon) {
      // Detect if running in mobile app
      const isCapacitor = window.Capacitor !== undefined
      if (isCapacitor) {
        return `https://ds.keetech.my.id/storage/${settings.value.store_favicon}`
      }
      return `/storage/${settings.value.store_favicon}`
    }
    return null
  })

  async function loadSettings() {
    loading.value = true
    try {
      const response = await settingApi.getAll()
      settings.value = { ...settings.value, ...response.data }
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
