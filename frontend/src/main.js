import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { useAuthStore } from './stores/auth'
import './style.css'

// Mobile app specific configurations
if (window.Capacitor) {
  console.log('🔧 Configuring mobile app...')
  
  // Block problematic third-party scripts
  const blockedDomains = [
    'cloudflareinsights.com',
    'googletagmanager.com',
    'google-analytics.com'
  ]
  
  // Override document.createElement to block script loading
  const originalCreateElement = document.createElement
  document.createElement = function(tagName) {
    const element = originalCreateElement.call(this, tagName)
    
    if (tagName.toLowerCase() === 'script') {
      const originalSetAttribute = element.setAttribute
      element.setAttribute = function(name, value) {
        if (name === 'src' && blockedDomains.some(domain => value.includes(domain))) {
          console.log('🚫 Blocked script:', value)
          return
        }
        return originalSetAttribute.call(this, name, value)
      }
    }
    
    return element
  }
  
  console.log('✅ Mobile app configuration complete')
}

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Initialize auth check
const authStore = useAuthStore()
authStore.checkAuth()

app.mount('#app')
