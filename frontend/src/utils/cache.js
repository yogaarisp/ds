/**
 * Utility functions for cache and storage management
 */

export function clearAllCache() {
  // Clear localStorage
  localStorage.clear()
  
  // Clear sessionStorage
  sessionStorage.clear()
  
  // Clear browser cache (service worker cache if available)
  if ('caches' in window) {
    caches.keys().then(names => {
      names.forEach(name => {
        caches.delete(name)
      })
    })
  }
  
  // Force reload to clear memory cache
  window.location.reload(true)
}

export function clearLocalStorage() {
  localStorage.clear()
  console.log('Local storage cleared')
}

export function clearSessionStorage() {
  sessionStorage.clear()
  console.log('Session storage cleared')
}

export function clearUserData() {
  // Clear specific user-related data
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  console.log('User data cleared')
}

export function clearCartData() {
  // Clear cart-related data from localStorage if any
  const keysToRemove = []
  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i)
    if (key && (key.includes('cart') || key.includes('pos'))) {
      keysToRemove.push(key)
    }
  }
  
  keysToRemove.forEach(key => localStorage.removeItem(key))
  console.log('Cart data cleared')
}

export function getStorageInfo() {
  const info = {
    localStorage: {
      keys: Object.keys(localStorage),
      size: JSON.stringify(localStorage).length
    },
    sessionStorage: {
      keys: Object.keys(sessionStorage),
      size: JSON.stringify(sessionStorage).length
    }
  }
  
  return info
}