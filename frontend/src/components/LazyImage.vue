<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { getStorageUrl } from '../utils/storage'

const props = defineProps({
  src: {
    type: String,
    required: true
  },
  alt: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: '🥟'
  }
})

const imageRef = ref(null)
const isLoaded = ref(false)
const isVisible = ref(false)
const hasError = ref(false)

// Compute proper storage URL for mobile compatibility
const computedSrc = computed(() => {
  if (!props.src) return null
  
  // If src already starts with http/https, use as is
  if (props.src.startsWith('http')) {
    return props.src
  }
  
  // If src starts with /storage/, use storage utility
  if (props.src.startsWith('/storage/')) {
    return getStorageUrl(props.src.substring(1)) // Remove leading slash
  }
  
  // Otherwise use as is
  return props.src
})

const isMounted = ref(false)
let observer = null

onMounted(() => {
  isMounted.value = true
  // Use IntersectionObserver for lazy loading
  if ('IntersectionObserver' in window) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && isMounted.value) {
            isVisible.value = true
            observer.unobserve(entry.target)
          }
        })
      },
      {
        rootMargin: '50px', // Start loading 50px before entering viewport
        threshold: 0.01
      }
    )

    if (imageRef.value) {
      observer.observe(imageRef.value)
    }
  } else {
    // Fallback for older browsers
    isVisible.value = true
  }
})

onUnmounted(() => {
  isMounted.value = false
  if (observer) {
    observer.disconnect()
    observer = null
  }
})

function handleLoad() {
  isLoaded.value = true
}

function handleError() {
  hasError.value = true
  isLoaded.value = true
}
</script>

<template>
  <div ref="imageRef" class="lazy-image-container w-full h-full relative overflow-hidden">
    <!-- Skeleton Placeholder -->
    <div 
      v-if="!isLoaded"
      class="absolute inset-0 bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 animate-shimmer flex items-center justify-center"
    >
      <span class="text-2xl opacity-30">{{ placeholder }}</span>
    </div>

    <!-- Actual Image -->
    <img
      v-if="isVisible && !hasError"
      :src="computedSrc"
      :alt="alt"
      class="w-full h-full object-cover transition-opacity duration-300"
      :class="isLoaded ? 'opacity-100' : 'opacity-0'"
      @load="handleLoad"
      @error="handleError"
      loading="lazy"
    />

    <!-- Error State / Fallback -->
    <div 
      v-if="hasError"
      class="absolute inset-0 bg-slate-50 flex items-center justify-center"
    >
      <span class="text-3xl">{{ placeholder }}</span>
    </div>
  </div>
</template>

<style scoped>
@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.animate-shimmer {
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite linear;
}
</style>
