import { ref, watch, onMounted } from 'vue'

const theme = ref('light')
const systemTheme = ref('light')

// Detect system theme
function detectSystemTheme() {
  if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    systemTheme.value = 'dark'
  } else {
    systemTheme.value = 'light'
  }
}

// Apply theme to document
function applyTheme(themeValue) {
  const root = document.documentElement
  
  if (themeValue === 'auto') {
    // Use system theme
    if (systemTheme.value === 'dark') {
      root.classList.add('dark')
      root.classList.remove('light')
    } else {
      root.classList.add('light')
      root.classList.remove('dark')
    }
  } else {
    // Use selected theme
    root.classList.add(themeValue)
    root.classList.remove(themeValue === 'dark' ? 'light' : 'dark')
  }
}

// Save theme to localStorage
function saveTheme(themeValue) {
  localStorage.setItem('app_theme', themeValue)
}

// Load theme from localStorage
function loadTheme() {
  const savedTheme = localStorage.getItem('app_theme')
  if (savedTheme && ['light', 'dark', 'auto'].includes(savedTheme)) {
    theme.value = savedTheme
  } else {
    // Default to light theme if no saved preference
    theme.value = 'light'
    localStorage.setItem('app_theme', 'light')
  }
}

export function useTheme() {
  onMounted(() => {
    // Detect system theme
    detectSystemTheme()
    
    // Load saved theme
    loadTheme()
    
    // Apply initial theme
    applyTheme(theme.value)
    
    // Listen for system theme changes
    if (window.matchMedia) {
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
      mediaQuery.addEventListener('change', (e) => {
        systemTheme.value = e.matches ? 'dark' : 'light'
        if (theme.value === 'auto') {
          applyTheme('auto')
        }
      })
    }
  })
  
  // Watch for theme changes
  watch(theme, (newTheme) => {
    applyTheme(newTheme)
    saveTheme(newTheme)
  })
  
  const setTheme = (newTheme) => {
    if (['light', 'dark', 'auto'].includes(newTheme)) {
      theme.value = newTheme
    }
  }
  
  const getCurrentTheme = () => {
    if (theme.value === 'auto') {
      return systemTheme.value
    }
    return theme.value
  }
  
  return {
    theme,
    systemTheme,
    setTheme,
    getCurrentTheme
  }
}