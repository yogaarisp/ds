<script setup>
import { useAuthStore } from './stores/auth'
import { useSettingsStore } from './stores/settings'
import { onMounted, watch } from 'vue'

const authStore = useAuthStore()
const settingsStore = useSettingsStore()

onMounted(() => {
  authStore.checkAuth()
  // Load settings initially to get favicon
  settingsStore.loadSettings()
})

// Dynamically update favicon
watch(
  () => settingsStore.storeFavicon,
  (newFavicon) => {
    // Remove all existing favicon links
    const existingLinks = document.querySelectorAll("link[rel*='icon']");
    existingLinks.forEach(link => link.remove());
    
    if (newFavicon) {
      // Determine favicon type based on file extension
      const faviconExt = newFavicon.split('.').pop().toLowerCase();
      let faviconType = 'image/x-icon';
      
      if (faviconExt === 'png') {
        faviconType = 'image/png';
      } else if (faviconExt === 'svg') {
        faviconType = 'image/svg+xml';
      } else if (faviconExt === 'ico') {
        faviconType = 'image/x-icon';
      }
      
      // Create and add favicon link
      const link = document.createElement('link');
      link.rel = 'icon';
      link.type = faviconType;
      link.href = newFavicon;
      document.head.appendChild(link);
      
      // Also add shortcut icon for older browsers
      const shortcutLink = document.createElement('link');
      shortcutLink.rel = 'shortcut icon';
      shortcutLink.type = faviconType;
      shortcutLink.href = newFavicon;
      document.head.appendChild(shortcutLink);
    }
  },
  { immediate: true }
)
</script>

<template>
  <router-view />
</template>
