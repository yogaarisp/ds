// Storage URL utility for mobile app compatibility

export function getStorageUrl(path) {
  if (!path) return null
  
  // Remove leading slash if present
  const cleanPath = path.startsWith('/') ? path.substring(1) : path
  
  // Detect if running in mobile app
  const isCapacitor = window.Capacitor !== undefined
  
  if (isCapacitor) {
    // For mobile app, use full URL (corrected domain)
    return `https://ds.keetech.my.id/${cleanPath}`
  } else {
    // For web, use relative path
    return `/${cleanPath}`
  }
}

export function getProductImageUrl(imagePath) {
  if (!imagePath) return null
  
  // Handle both formats: "products/image.jpg" or "storage/products/image.jpg"
  let cleanPath = imagePath
  if (cleanPath.startsWith('storage/')) {
    cleanPath = cleanPath.substring(8) // Remove "storage/" prefix
  }
  
  return getStorageUrl(`storage/${cleanPath}`)
}