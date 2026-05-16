<template>
  <div class="map-picker">
    <!-- Map Container -->
    <div class="relative">
      <div ref="mapContainer" class="w-full h-64 rounded-xl overflow-hidden border border-slate-200"></div>
      
      <!-- Loading Overlay -->
      <div v-if="loading" class="absolute inset-0 bg-white/80 flex items-center justify-center rounded-xl">
        <div class="flex items-center gap-2 text-slate-600">
          <div class="w-4 h-4 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
          <span class="text-sm font-medium">Memuat peta...</span>
        </div>
      </div>
    </div>

    <!-- Search Box -->
    <div class="mt-3 relative">
      <input
        v-model="searchQuery"
        @input="searchAddress"
        @keydown.enter.prevent="performSearch"
        type="text"
        placeholder="Cari alamat atau drag marker di peta..."
        class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm text-slate-700 focus:ring-2 focus:ring-primary-500/20 pr-10"
      />
      <button
        @click="performSearch"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary-500 transition-colors"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>
    </div>

    <!-- Search Results -->
    <div v-if="searchResults.length > 0" class="mt-2 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
      <div
        v-for="(result, index) in searchResults"
        :key="index"
        @click="selectSearchResult(result)"
        class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-b-0"
      >
        <div class="font-medium text-sm text-slate-800">{{ result.display_name }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ result.type }}</div>
      </div>
    </div>

    <!-- Current Location Info -->
    <div v-if="selectedLocation" class="mt-3 p-3 bg-primary-50 rounded-xl border border-primary-100">
      <div class="flex items-start gap-3">
        <div class="w-5 h-5 text-primary-600 mt-0.5">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div class="flex-1">
          <div class="font-medium text-sm text-slate-800">Lokasi Terpilih:</div>
          <div class="text-xs text-slate-600 mt-1">{{ selectedLocation.address }}</div>
          <div class="text-xs text-slate-500 mt-1">
            Koordinat: {{ selectedLocation.lat.toFixed(6) }}, {{ selectedLocation.lng.toFixed(6) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-4 flex gap-2">
      <button
        @click="getCurrentLocation"
        :disabled="gettingLocation"
        class="flex-1 bg-slate-100 text-slate-600 py-2.5 rounded-xl font-medium text-sm hover:bg-slate-200 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        </svg>
        {{ gettingLocation ? 'Mencari...' : 'Lokasi Saya' }}
      </button>
      
      <button
        @click="confirmLocation"
        :disabled="!selectedLocation"
        class="flex-1 bg-primary-500 text-white py-2.5 rounded-xl font-medium text-sm hover:bg-primary-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Gunakan Lokasi Ini
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Fix Leaflet default markers
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
})

const props = defineProps({
  initialLat: {
    type: Number,
    default: -6.2088 // Jakarta default
  },
  initialLng: {
    type: Number,
    default: 106.8456
  },
  initialAddress: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['location-selected'])

const mapContainer = ref(null)
const loading = ref(true)
const gettingLocation = ref(false)
const searchQuery = ref(props.initialAddress)
const searchResults = ref([])
const selectedLocation = ref(null)

let map = null
let marker = null
let searchTimeout = null

onMounted(() => {
  initMap()
})

onUnmounted(() => {
  if (map) {
    map.remove()
  }
})

function initMap() {
  loading.value = true
  
  // Initialize map
  map = L.map(mapContainer.value).setView([props.initialLat, props.initialLng], 15)
  
  // Add tile layer (OpenStreetMap)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map)
  
  // Add marker
  marker = L.marker([props.initialLat, props.initialLng], {
    draggable: true
  }).addTo(map)
  
  // Set initial location
  selectedLocation.value = {
    lat: props.initialLat,
    lng: props.initialLng,
    address: props.initialAddress || 'Lokasi terpilih'
  }
  
  // Handle marker drag
  marker.on('dragend', async function(e) {
    const position = e.target.getLatLng()
    await updateLocation(position.lat, position.lng)
  })
  
  // Handle map click
  map.on('click', async function(e) {
    const { lat, lng } = e.latlng
    marker.setLatLng([lat, lng])
    await updateLocation(lat, lng)
  })
  
  loading.value = false
}

async function updateLocation(lat, lng) {
  selectedLocation.value = {
    lat,
    lng,
    address: 'Mencari alamat...'
  }
  
  try {
    // Reverse geocoding to get address
    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`)
    const data = await response.json()
    
    if (data && data.display_name) {
      selectedLocation.value.address = data.display_name
    }
  } catch (error) {
    console.error('Reverse geocoding failed:', error)
    selectedLocation.value.address = `Koordinat: ${lat.toFixed(6)}, ${lng.toFixed(6)}`
  }
}

function searchAddress() {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(async () => {
    if (searchQuery.value.length < 3) {
      searchResults.value = []
      return
    }
    
    try {
      const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5&countrycodes=id`)
      const data = await response.json()
      
      searchResults.value = data.map(item => ({
        display_name: item.display_name,
        lat: parseFloat(item.lat),
        lng: parseFloat(item.lon),
        type: item.type || 'Lokasi'
      }))
    } catch (error) {
      console.error('Search failed:', error)
      searchResults.value = []
    }
  }, 500)
}

function performSearch() {
  if (searchResults.value.length > 0) {
    selectSearchResult(searchResults.value[0])
  }
}

function selectSearchResult(result) {
  const { lat, lng, display_name } = result
  
  // Update map and marker
  map.setView([lat, lng], 16)
  marker.setLatLng([lat, lng])
  
  // Update location
  selectedLocation.value = {
    lat,
    lng,
    address: display_name
  }
  
  // Clear search
  searchQuery.value = display_name
  searchResults.value = []
}

async function getCurrentLocation() {
  if (!navigator.geolocation) {
    alert('Geolocation tidak didukung oleh browser ini')
    return
  }
  
  gettingLocation.value = true
  
  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const { latitude, longitude } = position.coords
      
      // Update map and marker
      map.setView([latitude, longitude], 16)
      marker.setLatLng([latitude, longitude])
      
      // Update location
      await updateLocation(latitude, longitude)
      gettingLocation.value = false
    },
    (error) => {
      console.error('Geolocation error:', error)
      alert('Gagal mendapatkan lokasi. Pastikan GPS aktif dan izinkan akses lokasi.')
      gettingLocation.value = false
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 60000
    }
  )
}

function confirmLocation() {
  if (selectedLocation.value) {
    emit('location-selected', selectedLocation.value)
  }
}
</script>

<style scoped>
.map-picker {
  @apply w-full;
}

/* Leaflet popup styling */
:deep(.leaflet-popup-content-wrapper) {
  @apply rounded-lg shadow-lg;
}

:deep(.leaflet-popup-content) {
  @apply text-sm;
}
</style>