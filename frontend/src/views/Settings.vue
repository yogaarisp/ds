<script setup>
import { ref, onMounted } from 'vue'
import { settingApi, userApi, paymentMethodApi, carouselApi, databaseApi, STORAGE_BASE_URL } from '../api'
import { useSettingsStore } from '../stores/settings'
import { useAuthStore } from '../stores/auth'
import { usePrinter } from '@/composables/usePrinter'
import SettingsCustomerTypes from './SettingsCustomerTypes.vue'
import MapPicker from '../components/MapPicker.vue'
import Swal from 'sweetalert2'

const settingsStore = useSettingsStore()
const authStore = useAuthStore()
const { 
  printerConnected, 
  printerWidth, 
  autoPrint,
  setPrinterWidth,
  setAutoPrint,
  connectPrinter,
  disconnectPrinter,
  thermalPrinter
} = usePrinter()

const activeTab = ref('store') // store | users | payments | email | printer | database
const loading = ref(false)
const saving = ref(false)
const connecting = ref(false)

const logoInput = ref(null)
const faviconInput = ref(null)
const carouselImageInput = ref(null)
const userAvatarInput = ref(null)

// Store settings
const settings = ref({
  store_name: '',
  store_logo: null,
  store_favicon: null,
  store_address: '',
  store_phone: '',
  store_instagram: '',
  store_latitude: -6.2088, // Default Jakarta
  store_longitude: 106.8456,
  mail_host: 'smtp.gmail.com',
  mail_port: '587',
  mail_username: '',
  mail_password: '',
  mail_encryption: 'tls',
  mail_from_address: '',
  mail_from_name: '',
  // Automated Payment Settings
  midtrans_enabled: false,
  midtrans_client_key: '',
  midtrans_server_key: '',
  midtrans_mode: 'sandbox',
  xendit_enabled: false,
  xendit_secret_key: '',
  xendit_callback_token: '',
  // n8n Notification Settings
  n8n_notification_enabled: false,
  n8n_webhook_url: '',
})
const logoPreview = ref(null)
const logoFile = ref(null)

const faviconPreview = ref(null)
const faviconFile = ref(null)

// Map picker
const showMapPicker = ref(false)

// Email test
const testEmail = ref('')
const testingEmail = ref(false)
const testEmailResult = ref(null)

// Users
const users = ref([])
const showUserModal = ref(false)
const editingUser = ref(null)
const userForm = ref({
  name: '',
  email: '',
  password: '',
  role: 'kasir',
  is_active: true,
})
const userAvatarFile = ref(null)
const userAvatarPreview = ref(null)

// Payment Methods
const paymentMethods = ref([])
const showPaymentModal = ref(false)
const editingPayment = ref(null)
const paymentForm = ref({
  name: '',
  type: 'cash',
  account_name: '',
  account_number: '',
  qr_code_image: null,
  is_active: true,
})
const qrCodeFile = ref(null)
const qrCodePreview = ref(null)

// Carousel Slides
const carouselSlides = ref([])
const showCarouselModal = ref(false)
const editingCarousel = ref(null)
const carouselForm = ref({
  title: '',
  subtitle: '',
  period_text: '',
  image_url: '',
  badge_label: '',
  badge_value: '',
  badge_label_2: '',
  badge_value_2: '',
  link_url: '',
  start_date: '',
  end_date: '',
  is_active: true,
})
const carouselImageFile = ref(null)
const carouselImagePreview = ref(null)

// Database Management
const databaseInfo = ref(null)
const loadingDatabase = ref(false)
const exportingDatabase = ref(false)
const importingDatabase = ref(false)
const importFile = ref(null)
const importFileInput = ref(null)

// Native Printing State
const discoveredPrinters = ref([])
const showNativeScanResult = ref(false)

onMounted(() => {
  loadSettings()
  loadUsers()
  loadPaymentMethods()
  loadCarouselSlides()
  
  // Only load database info if user is owner
  if (authStore.user?.role === 'owner') {
    loadDatabaseInfo()
  }
})

async function loadSettings() {
  loading.value = true
  try {
    const response = await settingApi.getAll()
    settings.value = { ...settings.value, ...response.data }
    if (settings.value.store_logo) {
      logoPreview.value = `${STORAGE_BASE_URL}/${settings.value.store_logo}`
    }
    if (settings.value.store_favicon) {
      faviconPreview.value = `${STORAGE_BASE_URL}/${settings.value.store_favicon}`
    }
    
    // Cast enabled flags to boolean
    settings.value.midtrans_enabled = !!parseInt(settings.value.midtrans_enabled)
    settings.value.xendit_enabled = !!parseInt(settings.value.xendit_enabled)
    settings.value.n8n_notification_enabled = !!parseInt(settings.value.n8n_notification_enabled)
  } catch (error) {
    console.error('Error loading settings:', error)
  } finally {
    loading.value = false
  }
}

async function loadUsers() {
  try {
    const response = await userApi.getAll()
    users.value = response.data.data || response.data
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

async function loadPaymentMethods() {
  try {
    const response = await paymentMethodApi.getAll()
    paymentMethods.value = response.data
  } catch (error) {
    console.error('Error loading payment methods:', error)
  }
}

async function saveSettings() {
  saving.value = true
  try {
    if (logoFile.value) {
      await settingApi.uploadLogo(logoFile.value)
    }

    if (faviconFile.value) {
      await settingApi.uploadFavicon(faviconFile.value)
    }

    await settingApi.update({
      store_name: settings.value.store_name,
      store_address: settings.value.store_address,
      store_phone: settings.value.store_phone,
      store_instagram: settings.value.store_instagram,
      store_latitude: settings.value.store_latitude,
      store_longitude: settings.value.store_longitude,
      mail_host: settings.value.mail_host,
      mail_port: settings.value.mail_port,
      mail_username: settings.value.mail_username,
      mail_password: settings.value.mail_password,
      mail_encryption: settings.value.mail_encryption,
      mail_from_address: settings.value.mail_from_address,
      mail_from_name: settings.value.mail_from_name,
      midtrans_enabled: settings.value.midtrans_enabled,
      midtrans_client_key: settings.value.midtrans_client_key,
      midtrans_server_key: settings.value.midtrans_server_key,
      midtrans_mode: settings.value.midtrans_mode,
      xendit_enabled: settings.value.xendit_enabled,
      xendit_secret_key: settings.value.xendit_secret_key,
      xendit_callback_token: settings.value.xendit_callback_token,
      n8n_notification_enabled: settings.value.n8n_notification_enabled,
      n8n_webhook_url: settings.value.n8n_webhook_url,
    })

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Pengaturan berhasil disimpan',
      timer: 1500,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })

    logoFile.value = null
    faviconFile.value = null
    await loadSettings()
    await settingsStore.loadSettings()
  } catch (error) {
    console.error('Error saving settings:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Gagal menyimpan pengaturan',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } finally {
    saving.value = false
  }
}


function handleLogoChange(event) {
  const file = event.target.files[0]
  if (file) {
    logoFile.value = file
    logoPreview.value = URL.createObjectURL(file)
  }
}

function handleFaviconChange(event) {
  const file = event.target.files[0]
  if (file) {
    faviconFile.value = file
    faviconPreview.value = URL.createObjectURL(file)
  }
}

// Map picker functions
function openMapPicker() {
  showMapPicker.value = true
}

function handleLocationSelected(location) {
  settings.value.store_latitude = location.lat
  settings.value.store_longitude = location.lng
  settings.value.store_address = location.address
  showMapPicker.value = false
  
  Swal.fire({
    icon: 'success',
    title: 'Lokasi Dipilih!',
    text: 'Lokasi toko berhasil diperbarui',
    timer: 1500,
    showConfirmButton: false,
    width: '300px',
    customClass: {
      popup: 'rounded-xl text-sm',
      title: 'text-lg font-bold',
      htmlContainer: 'text-xs'
    }
  })
}

async function deleteLogo() {
  const result = await Swal.fire({
    title: 'Hapus Logo?',
    text: 'Logo akan dihapus',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    width: '300px',
    customClass: {
      popup: 'rounded-xl text-sm',
      title: 'text-lg font-bold',
      htmlContainer: 'text-xs'
    }
  })

  if (result.isConfirmed) {
    try {
      await settingApi.deleteLogo()
      logoPreview.value = null
      settings.value.store_logo = null
      await settingsStore.loadSettings()
    } catch (error) {
      console.error('Error deleting logo:', error)
    }
  }
}

// User management
function openAddUser() {
  editingUser.value = null
  userAvatarFile.value = null
  userAvatarPreview.value = null
  userForm.value = {
    name: '',
    email: '',
    password: '',
    role: 'kasir',
    is_active: true,
  }
  showUserModal.value = true
}

function openEditUser(user) {
  editingUser.value = user
  userAvatarFile.value = null
  userAvatarPreview.value = null
  userForm.value = {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role,
    is_active: user.is_active,
  }
  // Set preview if user has avatar
  if (user.avatar) {
    userAvatarPreview.value = `${STORAGE_BASE_URL}/${user.avatar}`
  }
  showUserModal.value = true
}

function handleUserAvatarChange(event) {
  const file = event.target.files[0]
  if (file) {
    userAvatarFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      userAvatarPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function removeUserAvatar() {
  userAvatarFile.value = null
  userAvatarPreview.value = null
}

async function saveUser() {
  try {
    const formData = new FormData()
    formData.append('name', userForm.value.name)
    formData.append('email', userForm.value.email)
    formData.append('role', userForm.value.role)
    formData.append('is_active', userForm.value.is_active ? '1' : '0')
    
    if (userForm.value.password) {
      formData.append('password', userForm.value.password)
    }
    
    // Append avatar if selected
    if (userAvatarFile.value) {
      formData.append('avatar', userAvatarFile.value)
    }

    if (editingUser.value) {
      await userApi.update(editingUser.value.id, formData)
    } else {
      await userApi.create(formData)
    }

    showUserModal.value = false
    await loadUsers()

    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: editingUser.value ? 'User berhasil diupdate' : 'User berhasil ditambahkan',
      timer: 1500,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } catch (error) {
    console.error('Error saving user:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal menyimpan user',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  }
}

async function deleteUser(user) {
  const result = await Swal.fire({
    title: 'Hapus User?',
    text: `User "${user.name}" akan dihapus`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    width: '300px',
    customClass: {
      popup: 'rounded-xl text-sm',
      title: 'text-lg font-bold',
      htmlContainer: 'text-xs'
    }
  })

  if (result.isConfirmed) {
    try {
      await userApi.delete(user.id)
      await loadUsers()
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'User berhasil dihapus',
        timer: 1500,
        showConfirmButton: false,
        width: '300px',
        customClass: {
          popup: 'rounded-xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })
    } catch (error) {
      console.error('Error deleting user:', error)
    }
  }
}

// Payment Method Management
function openAddPayment() {
  editingPayment.value = null
  paymentForm.value = {
    name: '',
    type: 'cash',
    account_name: '',
    account_number: '',
    qr_code_image: null,
    is_active: true,
  }
  qrCodeFile.value = null
  qrCodePreview.value = null
  showPaymentModal.value = true
}

function openEditPayment(payment) {
  editingPayment.value = payment
  paymentForm.value = {
    name: payment.name,
    type: payment.type,
    account_name: payment.account_name,
    account_number: payment.account_number,
    qr_code_image: payment.qr_code_image,
    is_active: payment.is_active,
  }
  qrCodeFile.value = null
  // Set preview if QR code exists
  if (payment.qr_code_image) {
    qrCodePreview.value = `${STORAGE_BASE_URL}/${payment.qr_code_image}`
  } else {
    qrCodePreview.value = null
  }
  showPaymentModal.value = true
}

function handleQRCodeUpload(event) {
  const file = event.target.files[0]
  if (file) {
    qrCodeFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      qrCodePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

async function savePayment() {
  try {
    const formData = new FormData()
    formData.append('name', paymentForm.value.name)
    formData.append('type', paymentForm.value.type)
    formData.append('account_name', paymentForm.value.account_name || '')
    formData.append('account_number', paymentForm.value.account_number || '')
    formData.append('is_active', paymentForm.value.is_active ? '1' : '0')
    
    // Add QR code image if uploaded
    if (qrCodeFile.value) {
      formData.append('qr_code_image', qrCodeFile.value)
    }

    if (editingPayment.value) {
      await paymentMethodApi.update(editingPayment.value.id, formData)
    } else {
      await paymentMethodApi.create(formData)
    }

    showPaymentModal.value = false
    await loadPaymentMethods()
    
    // Reset form
    qrCodeFile.value = null
    qrCodePreview.value = null

    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: editingPayment.value ? 'Metode pembayaran berhasil diupdate' : 'Metode pembayaran berhasil ditambahkan',
      timer: 1500,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } catch (error) {
    console.error('Error saving payment method:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal menyimpan metode pembayaran',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  }
}

async function deletePayment(payment) {
  const result = await Swal.fire({
    title: 'Hapus Metode?',
    text: `Metode "${payment.name}" akan dihapus`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    width: '300px',
    customClass: {
      popup: 'rounded-xl text-sm',
      title: 'text-lg font-bold',
      htmlContainer: 'text-xs'
    }
  })

  if (result.isConfirmed) {
    try {
      await paymentMethodApi.delete(payment.id)
      await loadPaymentMethods()
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Metode pembayaran berhasil dihapus',
        timer: 1500,
        showConfirmButton: false,
        width: '300px',
        customClass: {
          popup: 'rounded-xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })
    } catch (error) {
      console.error('Error deleting payment method:', error)
    }
  }
}

async function togglePaymentActive(payment) {
  try {
    await paymentMethodApi.update(payment.id, {
      ...payment,
      is_active: !payment.is_active
    })
    await loadPaymentMethods()
  } catch (error) {
    console.error('Error toggling payment method:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Gagal mengubah status metode pembayaran',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  }
}

function getRoleLabel(role) {
  const labels = { owner: 'Owner', kasir: 'Kasir', gudang: 'Gudang' }
  return labels[role] || role
}

function getPaymentTypeLabel(type) {
  const labels = { cash: 'Cash', transfer: 'Transfer', qris: 'QRIS', ewallet: 'E-Wallet', other: 'Lainnya' }
  return labels[type] || type
}

// Carousel Slide Management
async function loadCarouselSlides() {
  try {
    const response = await carouselApi.getAll()
    carouselSlides.value = response.data
  } catch (error) {
    console.error('Error loading carousel slides:', error)
  }
}

function openAddCarousel() {
  editingCarousel.value = null
  carouselForm.value = {
    title: '',
    subtitle: '',
    period_text: '',
    image_url: '',
    badge_label: '',
    badge_value: '',
    badge_label_2: '',
    badge_value_2: '',
    link_url: '',
    start_date: '',
    end_date: '',
    is_active: true,
  }
  carouselImageFile.value = null
  carouselImagePreview.value = null
  showCarouselModal.value = true
}

function openEditCarousel(slide) {
  editingCarousel.value = slide
  carouselForm.value = {
    title: slide.title,
    subtitle: slide.subtitle || '',
    period_text: slide.period_text || '',
    image_url: slide.image || '',
    badge_label: slide.badge_label || '',
    badge_value: slide.badge_value || '',
    badge_label_2: slide.badge_label_2 || '',
    badge_value_2: slide.badge_value_2 || '',
    link_url: slide.link_url || '',
    start_date: slide.start_date ? slide.start_date.split('T')[0] : '',
    end_date: slide.end_date ? slide.end_date.split('T')[0] : '',
    is_active: slide.is_active,
  }
  carouselImageFile.value = null
  carouselImagePreview.value = slide.image_url
  showCarouselModal.value = true
}

function handleCarouselImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    carouselImageFile.value = file
    carouselImagePreview.value = URL.createObjectURL(file)
  }
}

async function saveCarousel() {
  try {
    const formData = new FormData()
    formData.append('title', carouselForm.value.title)
    formData.append('subtitle', carouselForm.value.subtitle || '')
    formData.append('period_text', carouselForm.value.period_text || '')
    formData.append('badge_label', carouselForm.value.badge_label || '')
    formData.append('badge_value', carouselForm.value.badge_value || '')
    formData.append('badge_label_2', carouselForm.value.badge_label_2 || '')
    formData.append('badge_value_2', carouselForm.value.badge_value_2 || '')
    formData.append('link_url', carouselForm.value.link_url || '')
    formData.append('start_date', carouselForm.value.start_date || '')
    formData.append('end_date', carouselForm.value.end_date || '')
    formData.append('is_active', carouselForm.value.is_active ? '1' : '0')

    if (carouselImageFile.value) {
      formData.append('image', carouselImageFile.value)
    } else if (carouselForm.value.image_url && carouselForm.value.image_url.startsWith('http')) {
      formData.append('image_url', carouselForm.value.image_url)
    }

    if (editingCarousel.value) {
      await carouselApi.update(editingCarousel.value.id, formData)
    } else {
      await carouselApi.create(formData)
    }

    showCarouselModal.value = false
    await loadCarouselSlides()

    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: editingCarousel.value ? 'Slide berhasil diupdate' : 'Slide berhasil ditambahkan',
      timer: 1500,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } catch (error) {
    console.error('Error saving carousel:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal menyimpan slide',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  }
}

async function deleteCarousel(slide) {
  const result = await Swal.fire({
    title: 'Hapus Slide?',
    text: `Slide "${slide.title}" akan dihapus`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    width: '300px',
    customClass: {
      popup: 'rounded-xl text-sm',
      title: 'text-lg font-bold',
      htmlContainer: 'text-xs'
    }
  })

  if (result.isConfirmed) {
    try {
      await carouselApi.delete(slide.id)
      await loadCarouselSlides()
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Slide berhasil dihapus',
        timer: 1500,
        showConfirmButton: false,
        width: '300px',
        customClass: {
          popup: 'rounded-xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })
    } catch (error) {
      console.error('Error deleting carousel:', error)
    }
  }
}

async function toggleCarouselActive(slide) {
  try {
    await carouselApi.toggleActive(slide.id)
    await loadCarouselSlides()
  } catch (error) {
    console.error('Error toggling slide:', error)
  }
}

function getPaymentTypeIcon(type) {
  const icons = {
    cash: '💵',
    transfer: '🏦',
    qris: '📱',
    ewallet: '💰',
    other: '💳'
  }
  return icons[type] || '💳'
}

// Database Management Functions
async function loadDatabaseInfo() {
  loadingDatabase.value = true
  try {
    const response = await databaseApi.info()
    databaseInfo.value = response.data.data
  } catch (error) {
    console.error('Error loading database info:', error)
    // If access is denied (403) or other errors, don't show database section
    if (error.response?.status === 403) {
      console.log('Database management access denied - user is not owner')
    }
    databaseInfo.value = null
  } finally {
    loadingDatabase.value = false
  }
}

async function exportDatabase() {
  exportingDatabase.value = true
  try {
    const response = await databaseApi.export()
    
    // Create download link with proper SQL content type
    const blob = new Blob([response.data], { type: 'application/sql' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    
    // Get filename from response headers or create default with timestamp
    const contentDisposition = response.headers['content-disposition']
    let filename = `database_backup_${new Date().toISOString().replace(/[:.]/g, '-').split('T')[0]}_${new Date().toTimeString().split(' ')[0].replace(/:/g, '')}.sql`
    
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
      if (filenameMatch) {
        filename = filenameMatch[1].replace(/['"]/g, '').trim();
      }
    }

    // Ensure .sql extension
    if (!filename.toLowerCase().endsWith('.sql')) {
      filename = filename.replace(/\.[^/.]+$/, '') + '.sql';
    }
    
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    await Swal.fire({
      icon: 'success',
      title: 'Export Berhasil! 🎉',
      html: `
        <div class="text-left space-y-2">
          <p class="text-sm text-gray-700">File berhasil didownload:</p>
          <p class="text-xs font-mono bg-gray-100 p-2 rounded border">${filename}</p>
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-3">
            <p class="text-xs text-blue-700 font-semibold">📁 Lokasi File:</p>
            <p class="text-xs text-blue-600">Cek folder <strong>Downloads</strong> browser Anda</p>
            <p class="text-[10px] text-blue-500 mt-1">💡 Tekan Ctrl+J (Chrome) atau Cmd+Shift+J (Mac) untuk membuka Downloads</p>
          </div>
        </div>
      `,
      timer: 4000,
      showConfirmButton: true,
      confirmButtonText: 'OK',
      width: '450px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })

    // Refresh database info to show latest backup
    await loadDatabaseInfo()

  } catch (error) {
    console.error('Error exporting database:', error)
    
    let errorMessage = 'Gagal mengexport database'
    if (error.response?.data) {
      // Try to parse error from blob response
      try {
        const text = await error.response.data.text()
        const errorData = JSON.parse(text)
        errorMessage = errorData.message || errorMessage
      } catch (parseError) {
        errorMessage = error.response.data.message || error.message || errorMessage
      }
    }
    
    Swal.fire({
      icon: 'error',
      title: 'Gagal Export',
      text: errorMessage,
      width: '350px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } finally {
    exportingDatabase.value = false
  }
}

async function importDatabase() {
  if (!importFile.value) {
    Swal.fire({
      icon: 'warning',
      title: 'File Diperlukan',
      text: 'Pilih file SQL untuk diimport',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
    return
  }

  const result = await Swal.fire({
    title: 'Import Database?',
    html: `
      <div class="text-left space-y-2">
        <p class="text-sm text-gray-600">File: <strong>${importFile.value.name}</strong></p>
        <p class="text-sm text-gray-600">Size: <strong>${(importFile.value.size / 1024 / 1024).toFixed(2)} MB</strong></p>
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mt-3">
          <p class="text-xs text-red-600 font-semibold">⚠️ PERINGATAN:</p>
          <p class="text-xs text-red-600">Import akan menimpa semua data yang ada. Pastikan Anda sudah backup database saat ini.</p>
        </div>
      </div>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    confirmButtonText: 'Ya, Import',
    cancelButtonText: 'Batal',
    width: '400px',
    customClass: {
      popup: 'rounded-xl text-sm',
      title: 'text-lg font-bold',
      htmlContainer: 'text-xs'
    }
  })

  if (!result.isConfirmed) return

  importingDatabase.value = true
  try {
    await databaseApi.import(importFile.value)

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Database berhasil diimport',
      timer: 2000,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })

    // Clear file input and refresh info
    importFile.value = null
    if (importFileInput.value) {
      importFileInput.value.value = ''
    }
    await loadDatabaseInfo()

  } catch (error) {
    console.error('Error importing database:', error)
    Swal.fire({
      icon: 'error',
      title: 'Gagal Import',
      text: error.response?.data?.message || 'Gagal mengimport database',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } finally {
    importingDatabase.value = false
  }
}

function handleImportFileChange(event) {
  const file = event.target.files[0]
  if (file) {
    // Validate file type
    const validTypes = ['application/sql', 'text/plain', 'application/octet-stream']
    const validExtensions = ['.sql', '.txt']
    const hasValidExtension = validExtensions.some(ext => file.name.toLowerCase().endsWith(ext))
    
    if (!validTypes.includes(file.type) && !hasValidExtension) {
      Swal.fire({
        icon: 'error',
        title: 'File Tidak Valid',
        text: 'Hanya file SQL (.sql) yang diperbolehkan',
        width: '300px',
        customClass: {
          popup: 'rounded-xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })
      event.target.value = ''
      return
    }

    // Validate file size (max 50MB)
    if (file.size > 50 * 1024 * 1024) {
      Swal.fire({
        icon: 'error',
        title: 'File Terlalu Besar',
        text: 'Ukuran file maksimal 50MB',
        width: '300px',
        customClass: {
          popup: 'rounded-xl text-sm',
          title: 'text-lg font-bold',
          htmlContainer: 'text-xs'
        }
      })
      event.target.value = ''
      return
    }

    importFile.value = file
  }
}

// Test Email Function
async function sendTestEmail() {
  if (!testEmail.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Email Diperlukan',
      text: 'Masukkan email untuk test',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
    return
  }

  testingEmail.value = true
  testEmailResult.value = null

  try {
    const response = await settingApi.testEmail(testEmail.value)
    
    testEmailResult.value = {
      success: true,
      message: response.data.message
    }

    Swal.fire({
      icon: 'success',
      title: 'Email Terkirim! ✉️',
      text: response.data.message,
      width: '350px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } catch (error) {
    console.error('Test email error:', error)
    
    const errorMsg = error.response?.data?.error || error.response?.data?.message || 'Gagal mengirim email test'
    
    testEmailResult.value = {
      success: false,
      message: errorMsg
    }

    Swal.fire({
      icon: 'error',
      title: 'Gagal Kirim Email',
      html: `<div class="text-left"><p class="text-xs mb-2">${errorMsg}</p><p class="text-[10px] text-gray-500">Periksa konfigurasi SMTP Anda</p></div>`,
      width: '350px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } finally {
    testingEmail.value = false
  }
}

// n8n Notification Test
const testingNotification = ref(false)

async function sendTestNotification() {
  if (!settings.value.n8n_webhook_url) {
    Swal.fire({
      icon: 'warning',
      title: 'URL Diperlukan',
      text: 'Masukkan URL webhook n8n untuk mengetes',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
    return
  }

  testingNotification.value = true
  try {
    const response = await settingApi.testNotification(settings.value.n8n_webhook_url)
    
    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: response.data.message,
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal mengirim test notifikasi',
      width: '300px',
      customClass: {
        popup: 'rounded-xl text-sm',
        title: 'text-lg font-bold',
        htmlContainer: 'text-xs'
      }
    })
  } finally {
    testingNotification.value = false
  }
}

// Printer Functions
async function handleConnect() {
  connecting.value = true
  try {
    const printers = await thermalPrinter.scan()
    discoveredPrinters.value = printers
    
    if (printers && printers.length > 0) {
      showNativeScanResult.value = true
    } else if (!thermalPrinter.isAndroidApp()) {
      // Browser: No permitted devices yet, launch browser picker
      await connectToNativePrinter(null)
    } else {
      throw new Error('Tidak ada printer Bluetooth/USB ditemukan. Pastikan printer sudah dipairing di pengaturan Android.')
    }
  } catch (error) {
    if (error.name !== 'NotFoundError' && error.name !== 'AbortError') {
      Swal.fire({
        icon: 'error',
        title: 'Gagal Konek',
        text: error.message,
        width: '300px',
        customClass: { popup: 'rounded-xl text-sm' }
      })
    }
  } finally {
    connecting.value = false
  }
}

async function connectToNativePrinter(printer) {
  loading.value = true
  try {
    await thermalPrinter.connect(printer)
    printerConnected.value = true
    showNativeScanResult.value = false
    Swal.fire({
      icon: 'success',
      title: 'Terhubung!',
      text: printer ? `Tersambung ke ${printer.name}` : 'Printer berhasil disambungkan',
      timer: 1500,
      showConfirmButton: false,
      width: '300px',
      customClass: { popup: 'rounded-xl text-sm' }
    })
  } catch (error) {
    if (error.name !== 'NotFoundError' && error.name !== 'AbortError') {
      Swal.fire({
        icon: 'error',
        title: 'Gagal Konek',
        text: error.message,
        width: '300px',
        customClass: { popup: 'rounded-xl text-sm' }
      })
    }
  } finally {
    loading.value = false
  }
}

async function handleDisconnect() {
  if (thermalPrinter.isAndroidApp()) {
    try {
      await thermalPrinter.disconnect()
      printerConnected.value = false
      Swal.fire({
        icon: 'success',
        title: 'Terputus!',
        text: 'Printer berhasil diputuskan',
        timer: 1500,
        showConfirmButton: false,
        width: '300px',
        customClass: { popup: 'rounded-xl text-sm' }
      })
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error.message || 'Gagal memutuskan printer',
        width: '300px',
        customClass: { popup: 'rounded-xl text-sm' }
      })
    }
    return
  }
  
  await disconnectPrinter()
  Swal.fire({
    icon: 'success',
    title: 'Terputus!',
    text: 'Printer berhasil diputuskan',
    timer: 1500,
    showConfirmButton: false,
    width: '300px',
    customClass: {
      popup: 'rounded-xl text-sm',
      title: 'text-lg font-bold',
      htmlContainer: 'text-xs'
    }
  })
}

async function handleTestPrint() {
  if (!printerConnected.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Printer Belum Terhubung',
      text: 'Hubungkan printer terlebih dahulu',
      confirmButtonColor: '#F97316',
      width: '300px',
      customClass: {
        popup: 'rounded-2xl shadow-xl',
        title: 'text-base font-bold'
      }
    })
    return
  }

  try {
    await thermalPrinter.testPrint()
    Swal.fire({
      icon: 'success',
      title: 'Test Print Berhasil',
      text: 'Cek printer Anda',
      confirmButtonColor: '#F97316',
      timer: 2000,
      showConfirmButton: false,
      width: '300px',
      customClass: {
        popup: 'rounded-2xl shadow-xl',
        title: 'text-base font-bold'
      }
    })
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Test Print Gagal',
      text: error.message,
      confirmButtonColor: '#F97316',
      width: '300px',
      customClass: {
        popup: 'rounded-2xl shadow-xl',
        title: 'text-base font-bold'
      }
    })
  }
}

function handleWidthChange(width) {
  setPrinterWidth(width)
  thermalPrinter.setPrinterWidth(parseInt(width))
}

function handleAutoPrintChange(enabled) {
  setAutoPrint(enabled)
}
</script>

<template>
  <div class="h-full overflow-y-auto p-4 md:p-6 custom-scrollbar">
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Pengaturan</h1>
          <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">Konfigurasi Toko & Sistem</p>
        </div>
      </div>

      <!-- Modern Tabs -->
      <div class="bg-slate-100/80 p-1 rounded-2xl flex gap-1 shadow-inner overflow-x-auto no-scrollbar shrink-0">
        <button
          v-for="tab in [
            { id: 'store', label: 'Toko', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
            { id: 'carousel', label: 'Banner', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
            { id: 'users', label: 'User', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
            { id: 'customer_types', label: 'Tipe', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
            { id: 'payments', label: 'Bayar', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
            { id: 'automated_payment', label: 'Gateways', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
            { id: 'printer', label: 'Printer', icon: 'M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z' },
            { id: 'email', label: 'Email', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
            { id: 'notification', label: 'Notifikasi', icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' },
            ...(authStore.user?.role === 'owner' ? [{ id: 'database', label: 'Database', icon: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4' }] : [])
          ]"
          :key="tab.id"
          @click="activeTab = tab.id"
          class="flex-1 min-w-[90px] px-4 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all flex items-center justify-center gap-2 whitespace-nowrap"
          :class="activeTab === tab.id ? 'bg-white text-primary-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="tab.icon" />
          </svg>
          {{ tab.label }}
        </button>
      </div>

    <!-- Store Settings -->
    <div v-if="activeTab === 'store'" class="w-full animate-fade-in-up">
      <div class="bg-white rounded-xl p-6 shadow-xl shadow-slate-200/50 border border-slate-100">
        <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-3">
           <div class="w-9 h-9 rounded-xl bg-primary-100 flex items-center justify-center text-primary-600 shadow-sm shadow-primary-500/10">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
           </div>
           Profil Toko
        </h2>

        <!-- Logo & Favicon Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Logo Uploader -->
          <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Logo Toko</label>
            <div class="group relative border-2 border-dashed border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center text-center hover:border-primary-500 hover:bg-primary-50 transition-all cursor-pointer h-48" @click="logoInput.click()">
              <div v-if="logoPreview" class="relative w-28 h-28 mb-2">
                 <img :src="logoPreview" alt="Logo" class="w-full h-full object-contain drop-shadow-sm" />
                 <div class="absolute inset-0 bg-black/40 rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                     <span class="text-white font-medium text-sm">Ganti Logo</span>
                 </div>
              </div>
              <div v-else class="flex flex-col items-center justify-center text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2 text-gray-300 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-sm font-medium text-gray-600">Klik untuk upload logo</span>
                <span class="text-xs text-gray-400 mt-1">PNG, JPG (Max 2MB)</span>
              </div>
              <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="handleLogoChange" />
            
              <!-- Delete Button -->
              <button v-if="logoPreview" @click.stop="deleteLogo" class="absolute top-2 right-2 p-1.5 bg-white text-red-500 rounded-full shadow-sm hover:bg-red-50 border border-gray-200 transition-colors z-10" title="Hapus Logo">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                 </svg>
              </button>
            </div>
          </div>

          <!-- Favicon Uploader -->
          <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Favicon Website</label>
             <div class="group relative border-2 border-dashed border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center text-center hover:border-primary-500 hover:bg-primary-50 transition-all cursor-pointer h-48" @click="faviconInput.click()">
              <div v-if="faviconPreview" class="relative w-20 h-20 mb-2">
                 <img :src="faviconPreview" alt="Favicon" class="w-full h-full object-contain" />
                  <div class="absolute inset-0 bg-black/40 rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                     <span class="text-white font-medium text-sm">Ganti Icon</span>
                 </div>
              </div>
              <div v-else class="flex flex-col items-center justify-center text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2 text-gray-300 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
                <span class="text-sm font-medium text-gray-600">Klik untuk upload favicon</span>
                <span class="text-xs text-gray-400 mt-1">ICO, PNG (Max 1MB)</span>
              </div>
              <input ref="faviconInput" type="file" accept="image/*" class="hidden" @change="handleFaviconChange" />
            </div>
          </div>
        </div>

        <div class="space-y-6">
           <!-- Store Name -->
          <div class="space-y-1.5">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Nama Toko</label>
            <input v-model="settings.store_name" type="text" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="Nama Toko..." />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Address -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Alamat Toko</label>
              <div class="space-y-3">
                <textarea v-model="settings.store_address" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm text-slate-700 min-h-[100px] focus:ring-2 focus:ring-primary-500/10" placeholder="Alamat lengkap..."></textarea>
                
                <!-- Map Picker Button -->
                <button
                  @click="openMapPicker"
                  type="button"
                  class="w-full bg-primary-50 text-primary-600 py-2.5 rounded-xl font-medium text-sm hover:bg-primary-100 transition-colors flex items-center justify-center gap-2 border border-primary-200"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Pilih di Peta
                </button>
                
                <!-- Coordinates Display -->
                <div v-if="settings.store_latitude && settings.store_longitude" class="text-xs text-slate-500 bg-slate-50 p-2 rounded-lg">
                  📍 Koordinat: {{ settings.store_latitude.toFixed(6) }}, {{ settings.store_longitude.toFixed(6) }}
                </div>
              </div>
            </div>

            <div class="space-y-6">
              <!-- Phone -->
              <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">No. WhatsApp</label>
                <div class="relative">
                  <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400">📱</span>
                  <input v-model="settings.store_phone" type="text" class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="08xxxxxxxxxx" />
                </div>
              </div>

              <!-- Instagram / Social Media -->
              <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Instagram</label>
                <div class="relative">
                  <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400">📸</span>
                  <input v-model="settings.store_instagram" type="text" class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="@username" />
                </div>
              </div>
              
              <!-- Save Button -->
              <button @click="saveSettings" :disabled="saving" class="w-full bg-primary-500 py-3.5 rounded-xl text-white font-bold text-[11px] tracking-widest uppercase shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all active:scale-[0.98]">
                {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Carousel/Banner Management -->
    <div v-if="activeTab === 'carousel'" class="animate-fade-in-up space-y-4">
      <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
        <div>
          <h2 class="text-lg font-bold text-slate-800 ml-2">Kelola Banner</h2>
          <p class="text-[10px] text-slate-400 font-medium ml-2 mt-0.5">Banner carousel di halaman customer</p>
        </div>
        <button @click="openAddCarousel" class="px-4 py-2 rounded-xl bg-primary-500 text-white font-bold text-xs hover:bg-primary-600 transition-all shadow-md shadow-primary-500/20 flex items-center justify-center gap-2">
           <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
           </svg>
           <span class="hidden md:inline">Tambah Slide</span>
        </button>
      </div>

      <!-- Empty State -->
      <div v-if="carouselSlides.length === 0" class="bg-white rounded-2xl p-12 shadow-sm border border-slate-100 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <h3 class="font-bold text-slate-600 mb-1">Belum Ada Banner</h3>
        <p class="text-xs text-slate-400">Tambahkan slide pertama untuk carousel customer</p>
      </div>

      <!-- Carousel Slides Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pb-20">
        <div v-for="slide in carouselSlides" :key="slide.id" class="group bg-white rounded-2xl overflow-hidden shadow-lg shadow-slate-200/40 border border-slate-100 hover:border-primary-200 transition-all">
          <!-- Image Preview -->
          <div class="relative h-36 bg-slate-100 overflow-hidden">
            <img v-if="slide.image_url" :src="slide.image_url" class="w-full h-full object-cover" alt="Slide">
            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <!-- Subtle bottom gradient for text readability -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
            <div class="absolute inset-0 p-4 flex flex-col justify-end">
              <div class="text-white">
                <h3 class="font-bold text-lg drop-shadow-md leading-tight">{{ slide.title }}</h3>
                <p v-if="slide.period_text" class="text-[10px] bg-white/20 backdrop-blur-sm px-2 py-0.5 rounded-md w-fit mt-1 border border-white/30">{{ slide.period_text }}</p>
              </div>
            </div>
            <!-- Status Badge -->
            <div class="absolute top-3 right-3">
              <button @click="toggleCarouselActive(slide)" :class="['px-2.5 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider transition-all', slide.is_active ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-200 text-slate-500']">
                {{ slide.is_active ? 'Aktif' : 'Nonaktif' }}
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="p-4">
            <!-- Badges Preview -->
            <div v-if="slide.badge_label || slide.badge_label_2" class="flex flex-wrap gap-2 mb-3">
              <div v-if="slide.badge_label" class="bg-orange-50 text-orange-600 px-2 py-1 rounded-lg">
                <span class="text-[8px] font-semibold uppercase block opacity-70">{{ slide.badge_label }}</span>
                <span class="text-[10px] font-bold">{{ slide.badge_value }}</span>
              </div>
              <div v-if="slide.badge_label_2" class="bg-orange-50 text-orange-600 px-2 py-1 rounded-lg">
                <span class="text-[8px] font-semibold uppercase block opacity-70">{{ slide.badge_label_2 }}</span>
                <span class="text-[10px] font-bold">{{ slide.badge_value_2 }}</span>
              </div>
            </div>

            <!-- Date Range -->
            <div v-if="slide.start_date || slide.end_date" class="flex items-center gap-1.5 text-[10px] text-slate-400 mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>{{ slide.start_date || 'Sekarang' }} - {{ slide.end_date || 'Selamanya' }}</span>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
              <button @click="openEditCarousel(slide)" class="flex-1 py-2 bg-slate-50 rounded-xl text-slate-500 hover:text-primary-600 hover:bg-primary-50 font-bold text-[10px] uppercase tracking-widest transition-all flex items-center justify-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
              </button>
              <button @click="deleteCarousel(slide)" class="py-2 px-3 bg-red-50/50 rounded-xl text-red-400 hover:text-red-500 hover:bg-red-50 transition-all flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Carousel Modal -->
    <div v-if="showCarouselModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-3">
      <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 pt-6 pb-3 flex justify-between items-center text-slate-800 border-b border-slate-100">
          <h2 class="text-xl font-bold tracking-tight">{{ editingCarousel ? 'Edit Slide' : 'Slide Baru' }}</h2>
          <button @click="showCarouselModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-4 overflow-y-auto">
          <!-- Image Upload/Preview -->
          <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Gambar Banner</label>
            <div class="group relative border-2 border-dashed border-slate-200 rounded-xl overflow-hidden hover:border-primary-500 hover:bg-primary-50/50 transition-all cursor-pointer h-32" @click="carouselImageInput.click()">
              <div v-if="carouselImagePreview" class="absolute inset-0">
                <img :src="carouselImagePreview" class="w-full h-full object-cover" alt="Preview">
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                  <span class="text-white font-medium text-sm">Ganti Gambar</span>
                </div>
              </div>
              <div v-else class="h-full flex flex-col items-center justify-center text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 text-gray-300 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-xs font-medium text-gray-600">Klik untuk upload</span>
              </div>
              <input ref="carouselImageInput" type="file" accept="image/*" class="hidden" @change="handleCarouselImageChange">
            </div>
            <p class="text-[10px] text-slate-400 mt-1.5 ml-1">Atau masukkan URL gambar:</p>
            <input v-model="carouselForm.image_url" type="url" class="w-full bg-slate-50 border-none rounded-xl px-4 py-2.5 text-xs text-slate-600 focus:ring-2 focus:ring-primary-500/10 mt-1" placeholder="https://example.com/image.jpg">
          </div>

          <!-- Title & Subtitle -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Judul (Text Overlay) *</label>
              <input v-model="carouselForm.title" type="text" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="Diskon Januari" required>
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Periode Text</label>
              <input v-model="carouselForm.period_text" type="text" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm text-slate-600 focus:ring-2 focus:ring-primary-500/10" placeholder="Periode: 1-31 Januari 2026">
            </div>
          </div>

          <!-- Voucher Badges -->
          <div class="bg-orange-50/50 rounded-xl p-4 border border-orange-100">
            <h4 class="text-[10px] font-bold text-orange-600 uppercase tracking-widest mb-3 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
              </svg>
              Voucher Badge (Opsional)
            </h4>
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">Badge 1 Label</label>
                <input v-model="carouselForm.badge_label" type="text" class="w-full bg-white border-none rounded-lg px-3 py-2 text-xs text-slate-600 focus:ring-2 focus:ring-primary-500/10" placeholder="Pengguna Baru">
              </div>
              <div class="space-y-1">
                <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">Badge 1 Value</label>
                <input v-model="carouselForm.badge_value" type="text" class="w-full bg-white border-none rounded-lg px-3 py-2 text-xs text-slate-600 focus:ring-2 focus:ring-primary-500/10" placeholder="DISKON 15.000">
              </div>
              <div class="space-y-1">
                <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">Badge 2 Label</label>
                <input v-model="carouselForm.badge_label_2" type="text" class="w-full bg-white border-none rounded-lg px-3 py-2 text-xs text-slate-600 focus:ring-2 focus:ring-primary-500/10" placeholder="Semua Pengguna">
              </div>
              <div class="space-y-1">
                <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">Badge 2 Value</label>
                <input v-model="carouselForm.badge_value_2" type="text" class="w-full bg-white border-none rounded-lg px-3 py-2 text-xs text-slate-600 focus:ring-2 focus:ring-primary-500/10" placeholder="DISKON 5.000">
              </div>
            </div>
          </div>

          <!-- Date Range -->
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Tanggal Mulai</label>
              <input v-model="carouselForm.start_date" type="date" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm text-slate-600 focus:ring-2 focus:ring-primary-500/10">
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Tanggal Berakhir</label>
              <input v-model="carouselForm.end_date" type="date" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm text-slate-600 focus:ring-2 focus:ring-primary-500/10">
            </div>
          </div>

          <!-- Active Toggle -->
          <div class="flex items-center gap-3 p-3 bg-slate-100/50 rounded-xl border border-slate-100">
            <input v-model="carouselForm.is_active" type="checkbox" id="carousel_is_active" class="w-5 h-5 text-primary-600 rounded-lg border-slate-300 focus:ring-primary-500">
            <label for="carousel_is_active" class="text-xs font-bold text-slate-500 uppercase tracking-widest cursor-pointer select-none">Aktifkan Slide</label>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-3 pt-2">
            <button type="button" @click="showCarouselModal = false" class="flex-1 py-3.5 rounded-xl text-slate-400 font-bold text-xs hover:bg-slate-50 transition-all">
              Batal
            </button>
            <button @click="saveCarousel" class="flex-[2] bg-primary-500 py-3.5 rounded-xl text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all active:scale-[0.97]">
              Simpan Slide
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Types Management -->
    <div v-if="activeTab === 'customer_types'" class="animate-fade-in-up">
      <SettingsCustomerTypes />
    </div>

    <!-- Users Management -->
    <div v-if="activeTab === 'users'" class="animate-fade-in-up space-y-4">
      <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
        <h2 class="text-lg font-bold text-slate-800 ml-2">Manajemen User</h2>
        <button @click="openAddUser" class="px-4 py-2 rounded-xl bg-primary-500 text-white font-bold text-xs hover:bg-primary-600 transition-all shadow-md shadow-primary-500/20 flex items-center justify-center gap-2">
           <span>+ Tambah User</span>
        </button>
      </div>

      <div class="bg-white rounded-xl overflow-hidden shadow-xl shadow-slate-200/40 border border-slate-100">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-slate-50/50">
                <th class="px-6 py-5 text-left text-[9px] font-bold text-slate-400 uppercase tracking-widest">Profil</th>
                <th class="px-6 py-5 text-left text-[9px] font-bold text-slate-400 uppercase tracking-widest hidden md:table-cell">Akses</th>
                <th class="px-6 py-5 text-left text-[9px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                <th class="px-6 py-5 text-right text-[9px] font-bold text-slate-400 uppercase tracking-widest">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl overflow-hidden bg-slate-50 text-slate-500 flex items-center justify-center font-bold text-sm shrink-0 border border-slate-100 shadow-sm">
                      <img v-if="user.avatar" :src="`${STORAGE_BASE_URL}/${user.avatar}`" class="w-full h-full object-cover" />
                      <span v-else>{{ user.name.charAt(0).toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0">
                      <p class="font-bold text-slate-700 text-sm truncate leading-tight">{{ user.name }}</p>
                      <p class="text-[10px] font-semibold text-slate-400 truncate mt-0.5">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 hidden md:table-cell">
                  <span :class="['px-3 py-1 rounded-lg text-[9px] font-bold uppercase tracking-widest', 
                    user.role === 'owner' ? 'bg-purple-50 text-purple-600 border border-purple-100' :
                    user.role === 'kasir' ? 'bg-blue-50 text-blue-600 border border-blue-100' :
                    'bg-emerald-50 text-emerald-600 border border-emerald-100'
                  ]">
                    {{ getRoleLabel(user.role) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div :class="['flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest', user.is_active ? 'text-emerald-500' : 'text-slate-400']">
                    <div :class="['w-1.5 h-1.5 rounded-full', user.is_active ? 'bg-emerald-500' : 'bg-slate-300']"></div>
                    {{ user.is_active ? 'Aktif' : 'Off' }}
                  </div>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openEditUser(user)" class="w-8 h-8 rounded-xl text-slate-400 hover:text-primary-500 hover:bg-primary-50 transition-all flex items-center justify-center">
                       <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                       </svg>
                    </button>
                    <button @click="deleteUser(user)" class="w-8 h-8 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all flex items-center justify-center">
                       <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                       </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Payment Methods Management -->
    <div v-if="activeTab === 'payments'" class="animate-fade-in-up space-y-4">
      <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
        <h2 class="text-lg font-bold text-slate-800 ml-2">Metode Bayar</h2>
        <button @click="openAddPayment" class="px-4 py-2 rounded-xl bg-primary-500 text-white font-bold text-xs hover:bg-primary-600 transition-all shadow-md shadow-primary-500/20 flex items-center justify-center gap-2">
           <span>+ Tambah Metode</span>
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 pb-20">
          <div v-for="payment in paymentMethods" :key="payment.id" class="group bg-white rounded-2xl p-6 shadow-xl shadow-slate-200/40 border border-slate-100 hover:border-primary-200 transition-all active:scale-[0.99] relative">
              <div class="flex items-start justify-between mb-4">
                  <div class="flex items-center gap-4">
                      <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center text-2xl shrink-0 shadow-inner border border-slate-100">
                          {{ getPaymentTypeIcon(payment.type) }}
                      </div>
                      <div class="min-w-0">
                          <h3 class="font-bold text-slate-800 truncate text-base leading-tight">{{ payment.name }}</h3>
                          <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1 block">{{ getPaymentTypeLabel(payment.type) }}</span>
                      </div>
                  </div>
                  <!-- Toggle Status Button -->
                  <button @click="togglePaymentActive(payment)" :class="['px-2.5 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider transition-all', payment.is_active ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-200 text-slate-500']">
                    {{ payment.is_active ? 'Aktif' : 'Nonaktif' }}
                  </button>
              </div>

              <div class="space-y-3 bg-slate-50 p-4 rounded-3xl min-h-[80px] flex flex-col justify-center border border-slate-100/50">
                  <div v-if="payment.account_number">
                      <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">No. Rek / ID</p>
                      <p class="font-bold text-slate-700 text-sm tracking-tight">{{ payment.account_number }}</p>
                  </div>
                  <div v-if="payment.account_name">
                       <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Atas Nama</p>
                       <p class="font-bold text-slate-600 text-xs">{{ payment.account_name }}</p>
                  </div>
                  <div v-if="!payment.account_number && !payment.account_name" class="flex items-center gap-2 text-slate-300 italic text-[10px] font-bold">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Detail tidak tersedia
                  </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-2 mt-4">
                <button @click="openEditPayment(payment)" class="flex-1 py-2.5 bg-slate-50 rounded-xl text-slate-500 hover:text-primary-600 hover:bg-primary-50 font-bold text-[10px] uppercase tracking-widest transition-all">
                  Edit
                </button>
                <button @click="deletePayment(payment)" class="py-2.5 px-3 bg-red-50/50 rounded-xl text-red-400 hover:text-red-500 hover:bg-red-50 transition-all active:scale-95 flex items-center justify-center">
                   <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                   </svg>
                </button>
              </div>
          </div>
      </div>
    </div>

    <!-- User Modal -->
    <div v-if="showUserModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-3">
      <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col">
        <div class="px-6 pt-6 pb-2 flex justify-between items-center text-slate-800">
          <h2 class="text-xl font-bold tracking-tight">{{ editingUser ? 'Edit User' : 'User Baru' }}</h2>
          <button @click="showUserModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 pt-2 space-y-3">
          <!-- Avatar Upload -->
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Foto Profil</label>
            <div class="flex items-center gap-4">
              <!-- Avatar Preview -->
              <div class="relative group">
                <div class="w-16 h-16 rounded-full overflow-hidden bg-slate-100 border-2 border-slate-200 flex items-center justify-center">
                  <img v-if="userAvatarPreview" :src="userAvatarPreview" class="w-full h-full object-cover" />
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <button v-if="userAvatarPreview" @click="removeUserAvatar" type="button" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <!-- Upload Button -->
              <div class="flex-1">
                <input type="file" @change="handleUserAvatarChange" accept="image/*" class="hidden" ref="userAvatarInput" />
                <button @click="userAvatarInput.click()" type="button" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition-colors">
                  {{ userAvatarPreview ? 'Ganti' : 'Upload' }}
                </button>
                <p class="text-[10px] text-slate-400 mt-1">JPG, PNG, WEBP (Max 2MB)</p>
              </div>
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Nama Lengkap</label>
            <input v-model="userForm.name" type="text" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="Nama..." required />
          </div>

          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Email</label>
            <input v-model="userForm.email" type="email" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm text-slate-600 focus:ring-2 focus:ring-primary-500/10" placeholder="email@toko.com" required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Role</label>
              <select v-model="userForm.role" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-[13px] font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10 appearance-none">
                <option value="owner">Owner</option>
                <option value="kasir">Kasir</option>
                <option value="gudang">Gudang</option>
              </select>
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Password</label>
              <input v-model="userForm.password" type="password" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm text-slate-700 focus:ring-2 focus:ring-primary-500/10" :placeholder="editingUser ? 'Kosongkan...' : '••••••'" />
            </div>
          </div>

          <div class="flex items-center gap-3 p-3 bg-slate-100/50 rounded-xl border border-slate-100">
            <input v-model="userForm.is_active" type="checkbox" id="user_is_active_new" class="w-5 h-5 text-primary-600 rounded-lg border-slate-300 focus:ring-primary-500" />
            <label for="user_is_active_new" class="text-xs font-bold text-slate-500 uppercase tracking-widest cursor-pointer select-none">Status Aktif</label>
          </div>

          <div class="flex gap-3 pt-3">
            <button type="button" @click="showUserModal = false" class="flex-1 py-3.5 rounded-xl text-slate-400 font-bold text-xs hover:bg-slate-50 transition-all">
              Batal
            </button>
            <button @click="saveUser" class="flex-[2] bg-primary-500 py-3.5 rounded-xl text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all active:scale-[0.97]">
              Simpan User
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <div v-if="showPaymentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-3">
      <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col">
        <div class="px-6 pt-6 pb-2 flex justify-between items-center text-slate-800">
          <h2 class="text-xl font-bold tracking-tight">{{ editingPayment ? 'Edit Metode' : 'Metode Baru' }}</h2>
          <button @click="showPaymentModal = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 pt-2 space-y-4">
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Nama Metode</label>
            <input v-model="paymentForm.name" type="text" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="Contoh: BCA, OVO, Tunai" required />
          </div>

          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Tipe Pembayaran</label>
            <select v-model="paymentForm.type" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-[13px] font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10 appearance-none">
              <option value="cash">Cash (Tunai)</option>
              <option value="transfer">Transfer Bank</option>
              <option value="qris">QRIS</option>
              <option value="ewallet">E-Wallet</option>
              <option value="other">Lainnya</option>
            </select>
          </div>

          <div v-if="paymentForm.type === 'transfer' || paymentForm.type === 'qris' || paymentForm.type === 'ewallet'" class="space-y-3 animate-fade-in-up">
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">No. Rek / ID</label>
              <input v-model="paymentForm.account_number" type="text" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="Nomor rekening..." />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Atas Nama</label>
              <input v-model="paymentForm.account_name" type="text" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="Nama pemilik..." />
            </div>
            
            <!-- QR Code Upload (only for QRIS) -->
            <div v-if="paymentForm.type === 'qris'" class="space-y-1">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">QR Code Image</label>
              <div class="relative">
                <input 
                  type="file" 
                  @change="handleQRCodeUpload" 
                  accept="image/*"
                  class="hidden" 
                  id="qr-code-upload"
                />
                <label 
                  for="qr-code-upload" 
                  class="flex items-center justify-center gap-2 w-full bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-500 hover:border-primary-300 hover:bg-primary-50/50 transition-all cursor-pointer"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  {{ qrCodePreview ? 'Ganti QR Code' : 'Upload QR Code' }}
                </label>
              </div>
              
              <!-- QR Code Preview -->
              <div v-if="qrCodePreview" class="mt-2 relative">
                <img :src="qrCodePreview" class="w-full h-48 object-contain bg-slate-50 rounded-xl border border-slate-200" alt="QR Code Preview" />
                <button 
                  @click="qrCodePreview = null; qrCodeFile = null" 
                  type="button"
                  class="absolute top-2 right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3 p-3 bg-slate-100/50 rounded-xl border border-slate-100">
            <input v-model="paymentForm.is_active" type="checkbox" id="pay_is_active_new" class="w-5 h-5 text-primary-600 rounded-lg border-slate-300 focus:ring-primary-500" />
            <label for="pay_is_active_new" class="text-xs font-bold text-slate-500 uppercase tracking-widest cursor-pointer select-none">Metode Aktif</label>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showPaymentModal = false" class="flex-1 py-3.5 rounded-xl text-slate-400 font-bold text-xs hover:bg-slate-50 transition-all">
              Batal
            </button>
            <button @click="savePayment" class="flex-[2] bg-primary-500 py-3.5 rounded-xl text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all">
              Simpan Metode
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Automated Payment Gateways -->
    <div v-if="activeTab === 'automated_payment'" class="animate-fade-in-up space-y-6 pb-20">
      <div class="bg-white rounded-xl p-6 shadow-xl shadow-slate-200/50 border border-slate-100">
        <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-primary-100 flex items-center justify-center text-primary-600 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          Payment Gateways
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Midtrans -->
          <div class="space-y-6 p-6 rounded-2xl bg-slate-50 border border-slate-100">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-slate-700">Midtrans</span>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="settings.midtrans_enabled" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
              </label>
            </div>

            <div class="space-y-4" v-if="settings.midtrans_enabled">
              <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Mode</label>
                <select v-model="settings.midtrans_mode" class="w-full bg-white border-none rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10 appearance-none">
                  <option value="sandbox">Sandbox (Testing)</option>
                  <option value="production">Production (Live)</option>
                </select>
              </div>
              <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Client Key</label>
                <input v-model="settings.midtrans_client_key" type="text" class="w-full bg-white border-none rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="SB-Mid-client-..." />
              </div>
              <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Server Key</label>
                <input v-model="settings.midtrans_server_key" type="password" class="w-full bg-white border-none rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="SB-Mid-server-..." />
              </div>
            </div>
          </div>

          <!-- Xendit -->
          <div class="space-y-6 p-6 rounded-2xl bg-slate-50 border border-slate-100">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-slate-700">Xendit</span>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="settings.xendit_enabled" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
              </label>
            </div>

            <div class="space-y-4" v-if="settings.xendit_enabled">
              <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Secret Key</label>
                <input v-model="settings.xendit_secret_key" type="password" class="w-full bg-white border-none rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="xnd_development_..." />
              </div>
              <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Callback Token</label>
                <input v-model="settings.xendit_callback_token" type="password" class="w-full bg-white border-none rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="Token from Xendit dashboard..." />
              </div>
            </div>
          </div>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100">
          <button @click="saveSettings" :disabled="saving" class="w-full max-w-xs mx-auto block bg-primary-500 py-4 rounded-2xl text-white font-bold text-xs tracking-widest uppercase shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all active:scale-[0.98]">
            {{ saving ? 'Menyimpan...' : 'Simpan Pengaturan Gateway' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Printer Settings -->
    <div v-if="activeTab === 'printer'" class="w-full animate-fade-in-up">
      <div class="bg-white rounded-xl p-6 shadow-xl shadow-slate-200/50 border border-slate-100 space-y-6">
        <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-3">
           <div class="w-9 h-9 rounded-xl bg-orange-100 flex items-center justify-center text-orange-600 shadow-sm shadow-orange-500/10">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
              </svg>
           </div>
           Pengaturan Printer
        </h2>

        <!-- Connection Status -->
        <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center group">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7" :class="printerConnected ? 'text-green-500' : 'text-slate-300'">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-slate-800">Status Printer</h3>
                <p class="text-xs font-medium" :class="printerConnected ? 'text-green-600' : 'text-slate-400'">
                  {{ printerConnected ? 'Terhubung & Siap Mencetak' : 'Printer Belum Terhubung' }}
                </p>
              </div>
            </div>
            <div v-if="printerConnected" class="flex items-center gap-2 px-3 py-1 bg-green-50 text-green-600 rounded-full text-[10px] font-bold uppercase tracking-wider">
              <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
              Online
            </div>
          </div>

          <div class="flex flex-col md:flex-row gap-3">
            <button 
              v-if="!printerConnected"
              @click="handleConnect"
              :disabled="connecting"
              class="flex-1 bg-orange-500 text-white font-bold py-3.5 rounded-xl hover:bg-orange-600 transition-all shadow-lg shadow-orange-500/20 active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <svg v-if="connecting" class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ connecting ? 'Menghubungkan...' : 'Scan & Hubungkan Printer' }}
            </button>
            <button 
              v-else
              @click="handleDisconnect"
              class="flex-1 bg-red-50 text-red-500 border border-red-100 font-bold py-3.5 rounded-xl hover:bg-red-100 transition-all active:scale-[0.98]"
            >
              Putuskan Koneksi
            </button>
            <button 
              @click="handleTestPrint"
              :disabled="!printerConnected"
              class="px-8 bg-white text-slate-700 font-bold py-3.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition-all active:scale-[0.98] disabled:opacity-50 disabled:bg-slate-50 flex items-center justify-center gap-2"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              Test Print
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Printer Width -->
          <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100">
            <div class="flex items-center gap-2 mb-4">
              <h3 class="font-bold text-slate-800">Ukuran Kertas</h3>
              <div class="group relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 cursor-help" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-slate-800 text-white text-[10px] rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 shadow-xl">
                  Pilih ukuran kertas sesuai printer thermal Anda.
                </div>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <button
                @click="handleWidthChange('58')"
                class="p-5 rounded-2xl border-2 transition-all group relative overflow-hidden"
                :class="printerWidth === '58' 
                  ? 'border-orange-500 bg-orange-50/50 shadow-md shadow-orange-500/10' 
                  : 'border-white bg-white hover:border-orange-200'"
              >
                <div class="text-center relative z-10">
                  <div class="text-2xl font-black" :class="printerWidth === '58' ? 'text-orange-600' : 'text-slate-400'">58mm</div>
                  <div class="text-[10px] font-bold uppercase tracking-widest mt-1" :class="printerWidth === '58' ? 'text-orange-400' : 'text-slate-300'">Standard</div>
                </div>
                <div v-if="printerWidth === '58'" class="absolute -right-2 -bottom-2 opacity-10">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </button>
              <button
                @click="handleWidthChange('80')"
                class="p-5 rounded-2xl border-2 transition-all group relative overflow-hidden"
                :class="printerWidth === '80' 
                  ? 'border-orange-500 bg-orange-50/50 shadow-md shadow-orange-500/10' 
                  : 'border-white bg-white hover:border-orange-200'"
              >
                <div class="text-center relative z-10">
                  <div class="text-2xl font-black" :class="printerWidth === '80' ? 'text-orange-600' : 'text-slate-400'">80mm</div>
                  <div class="text-[10px] font-bold uppercase tracking-widest mt-1" :class="printerWidth === '80' ? 'text-orange-400' : 'text-slate-300'">Large</div>
                </div>
                <div v-if="printerWidth === '80'" class="absolute -right-2 -bottom-2 opacity-10">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </button>
            </div>
          </div>

          <!-- Auto Print Toggle -->
          <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 flex flex-col justify-center">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-bold text-slate-800">Auto Print Struk</h3>
                <p class="text-[11px] text-slate-500 mt-1 max-w-[200px]">Cetak otomatis setelah transaksi berhasil diselesaikan</p>
              </div>
              <button
                @click="handleAutoPrintChange(!autoPrint)"
                class="relative w-16 h-9 rounded-full transition-all duration-300 shadow-inner"
                :class="autoPrint ? 'bg-orange-500' : 'bg-slate-300'"
              >
                <div
                  class="absolute top-1 w-7 h-7 bg-white rounded-full shadow-lg transition-all duration-300 flex items-center justify-center"
                  :class="autoPrint ? 'translate-x-8' : 'translate-x-1'"
                >
                  <div class="w-1 h-3 bg-orange-100 rounded-full" v-if="autoPrint"></div>
                </div>
              </button>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-5">
          <div class="flex gap-4">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="text-xs text-blue-700 leading-relaxed">
              <p class="font-bold mb-2 uppercase tracking-widest text-[10px]">Petunjuk Penggunaan Printer:</p>
              <ul class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1 list-disc list-inside">
                <li>Pastikan Bluetooth perangkat menyala</li>
                <li>Printer harus mendukung perintah ESC/POS</li>
                <li>Gunakan Chrome atau Edge (Web Bluetooth)</li>
                <li>Pastikan printer sudah di-pairing dengan perangkat</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Email Settings -->
    <div v-if="activeTab === 'email'" class="w-full animate-fade-in-up">
      <div class="bg-white rounded-xl p-6 shadow-xl shadow-slate-200/50 border border-slate-100">
        <h2 class="text-lg font-bold text-slate-800 mb-2 flex items-center gap-3">
           <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shadow-sm shadow-blue-500/10">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
           </div>
           Konfigurasi Email (SMTP)
        </h2>
        <p class="text-xs text-slate-400 mb-6 ml-12">Untuk fitur reset password via email</p>

        <!-- Info Box -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
          <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div class="text-xs text-blue-700 leading-relaxed">
              <p class="font-bold mb-2">📧 Cara Setup Gmail SMTP (GRATIS):</p>
              <ol class="list-decimal list-inside space-y-1 text-[11px]">
                <li>Buka <strong>Google Account</strong> → <strong>Security</strong></li>
                <li>Aktifkan <strong>2-Step Verification</strong></li>
                <li>Buka <strong>App Passwords</strong> → Pilih <strong>Mail</strong> dan <strong>Other</strong></li>
                <li>Copy <strong>App Password</strong> yang dihasilkan (16 karakter)</li>
                <li>Paste ke field <strong>Password</strong> di bawah</li>
              </ol>
              <p class="mt-2 text-[10px] text-blue-600">💡 Limit: 500 email/hari (lebih dari cukup untuk bisnis kecil)</p>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- SMTP Host -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">SMTP Host</label>
              <input v-model="settings.mail_host" type="text" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="smtp.gmail.com" />
            </div>

            <!-- SMTP Port -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">SMTP Port</label>
              <input v-model="settings.mail_port" type="text" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="587" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Email Username -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Email / Username</label>
              <input v-model="settings.mail_username" type="email" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="your-email@gmail.com" />
            </div>

            <!-- Email Password -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Password / App Password</label>
              <input v-model="settings.mail_password" type="password" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="••••••••••••••••" />
              <p class="text-[9px] text-slate-400 ml-1">Untuk Gmail, gunakan App Password (16 karakter)</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Encryption -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Encryption</label>
              <select v-model="settings.mail_encryption" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10">
                <option value="tls">TLS (Recommended)</option>
                <option value="ssl">SSL</option>
              </select>
            </div>

            <!-- From Name -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Nama Pengirim</label>
              <input v-model="settings.mail_from_name" type="text" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="Dimsum Sarang" />
            </div>
          </div>

          <!-- From Address (Optional) -->
          <div class="space-y-1.5">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">From Email (Opsional)</label>
            <input v-model="settings.mail_from_address" type="email" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm text-slate-700 focus:ring-2 focus:ring-primary-500/10" placeholder="Kosongkan untuk menggunakan email username" />
            <p class="text-[9px] text-slate-400 ml-1">Jika kosong, akan menggunakan email dari field Username</p>
          </div>

          <!-- Save Button -->
          <button @click="saveSettings" :disabled="saving" class="w-full bg-primary-500 py-3.5 rounded-xl text-white font-bold text-[11px] tracking-widest uppercase shadow-lg shadow-primary-500/20 hover:bg-primary-600 transition-all active:scale-[0.98] disabled:opacity-70">
            {{ saving ? 'Menyimpan...' : 'Simpan Konfigurasi Email' }}
          </button>

          <!-- Test Email Section -->
          <div class="mt-6 pt-6 border-t border-slate-200">
            <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Test Konfigurasi Email
            </h3>
            <p class="text-xs text-slate-400 mb-3">Kirim email test untuk memastikan konfigurasi SMTP sudah benar</p>
            
            <div class="flex gap-2">
              <input 
                v-model="testEmail" 
                type="email" 
                class="flex-1 bg-slate-50 border-none rounded-xl px-4 py-3 text-sm text-slate-700 focus:ring-2 focus:ring-blue-500/10" 
                placeholder="Email tujuan test (contoh: your@email.com)"
              />
              <button 
                @click="sendTestEmail" 
                :disabled="testingEmail || !testEmail"
                class="px-6 py-3 bg-blue-500 text-white rounded-xl font-bold text-xs hover:bg-blue-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 whitespace-nowrap"
              >
                <svg v-if="testingEmail" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ testingEmail ? 'Mengirim...' : 'Kirim Test' }}
              </button>
            </div>

            <!-- Test Result -->
            <div v-if="testEmailResult" :class="['mt-3 p-3 rounded-xl text-xs', testEmailResult.success ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200']">
              <div class="flex items-start gap-2">
                <svg v-if="testEmailResult.success" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <p class="leading-relaxed">{{ testEmailResult.message }}</p>
              </div>
            </div>
          </div>
        </div>
    </div>
    </div>
    
    <!-- Notification Settings -->
    <div v-if="activeTab === 'notification'" class="w-full animate-fade-in-up pb-20">
      <div class="bg-white rounded-xl p-6 shadow-xl shadow-slate-200/50 border border-slate-100">
        <h2 class="text-lg font-bold text-slate-800 mb-2 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-orange-100 flex items-center justify-center text-orange-600 shadow-sm shadow-orange-500/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </div>
          Notifikasi Pesanan
        </h2>
        <p class="text-xs text-slate-400 mb-8 ml-12">Kirim notifikasi otomatis ke Telegram setiap ada pesanan masuk melalui n8n</p>

        <div class="space-y-6">
          <!-- Notification Status -->
          <div class="flex items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-100">
            <div>
              <p class="text-sm font-bold text-slate-700 tracking-tight">Status Notifikasi</p>
              <p class="text-[10px] text-slate-400">Aktifkan untuk mulai mengirim data ke n8n</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="settings.n8n_notification_enabled" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
          </div>

          <!-- n8n Webhook URL -->
          <div class="space-y-1.5">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">n8n Webhook URL</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
              </div>
              <input 
                v-model="settings.n8n_webhook_url" 
                type="text" 
                class="w-full bg-slate-50 border-none rounded-xl pl-11 pr-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-orange-500/10" 
                placeholder="https://n8n.keetech.my.id/webhook/your-id" 
              />
            </div>
            <p class="text-[9px] text-slate-400 ml-1">Gunakan <strong>Production Webhook URL</strong> dari n8n untuk menerima data pesanan.</p>
          </div>

          <!-- Save Button -->
          <button @click="saveSettings" :disabled="saving" class="w-full bg-slate-800 py-3.5 rounded-xl text-white font-bold text-[11px] tracking-widest uppercase shadow-lg shadow-slate-800/20 hover:bg-slate-900 transition-all active:scale-[0.98] disabled:opacity-70">
            {{ saving ? 'Menyimpan...' : 'Simpan Pengaturan Notifikasi' }}
          </button>

          <!-- Test Section -->
          <div v-if="settings.n8n_webhook_url" class="mt-8 pt-8 border-t border-slate-100 space-y-4">
            <div class="bg-orange-50 rounded-2xl p-4 border border-orange-100">
              <div class="flex gap-3">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center text-orange-600 shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a2 2 0 00-1.96 1.414l-.711 2.133a2 2 0 01-1.185 1.185l-2.133.711a2 2 0 00-1.414 1.96l.477 2.387a2 2 0 00.547 1.022l1.428 1.428a2 2 0 001.022.547l2.387.477a2 2 0 001.96-1.414l.711-2.133a2 2 0 011.185-1.185l2.133-.711a2 2 0 001.414-1.96l-.477-2.387a2 2 0 00-.547-1.022l-1.428-1.428z" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-xs font-bold text-orange-900">Test Webhook</h4>
                  <p class="text-[10px] text-orange-700/70">Kirim data pesanan contoh ke n8n untuk memastikan alur kerja Anda berjalan.</p>
                </div>
              </div>
              <button 
                @click="sendTestNotification" 
                :disabled="testingNotification"
                class="mt-4 w-full bg-white/50 py-2.5 rounded-xl text-orange-600 font-bold text-[10px] tracking-widest uppercase border border-orange-200 hover:bg-white transition-all disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <svg v-if="testingNotification" class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ testingNotification ? 'Mengirim...' : 'Kirim Test Notifikasi' }}
              </button>
            </div>
            
            <div class="bg-blue-50 rounded-2xl p-5 border border-blue-100">
               <h4 class="text-[10px] font-bold text-blue-900 uppercase tracking-widest mb-3">Langkah Setup</h4>
               <ul class="space-y-2.5">
                 <li class="flex gap-3 items-start">
                   <span class="w-4 h-4 rounded-full bg-blue-200 text-blue-700 text-[9px] font-bold flex items-center justify-center shrink-0 mt-0.5">1</span>
                   <p class="text-[10px] text-blue-800/80 leading-relaxed">Buat workflow baru di <strong>n8n.keetech.my.id</strong> dan tambahkan node <strong>Webhook</strong>.</p>
                 </li>
                 <li class="flex gap-3 items-start">
                   <span class="w-4 h-4 rounded-full bg-blue-200 text-blue-700 text-[9px] font-bold flex items-center justify-center shrink-0 mt-0.5">2</span>
                   <p class="text-[10px] text-blue-800/80 leading-relaxed">Set HTTP Method ke <strong>POST</strong> dan copy URL Webhook tersebut ke kolom di atas.</p>
                 </li>
                 <li class="flex gap-3 items-start">
                   <span class="w-4 h-4 rounded-full bg-blue-200 text-blue-700 text-[9px] font-bold flex items-center justify-center shrink-0 mt-0.5">3</span>
                   <p class="text-[10px] text-blue-800/80 leading-relaxed">Tambahkan node <strong>Telegram</strong> (Send Message) untuk mengirim data pesanan ke bot Anda.</p>
                 </li>
               </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Database Management -->
    <div v-if="activeTab === 'database' && authStore.user?.role === 'owner'" class="animate-fade-in-up space-y-6">
      <div class="bg-white rounded-xl p-6 shadow-xl shadow-slate-200/50 border border-slate-100">
        <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shadow-sm shadow-blue-500/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
            </svg>
          </div>
          Manajemen Database
        </h2>

        <!-- Database Info -->
        <div v-if="databaseInfo" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7" />
                </svg>
              </div>
              <div>
                <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Database</p>
                <p class="font-bold text-blue-800 text-sm">{{ databaseInfo.database_name }}</p>
              </div>
            </div>
          </div>

          <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-4 border border-emerald-200">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div>
                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">Ukuran</p>
                <p class="font-bold text-emerald-800 text-sm">{{ databaseInfo.size_mb }} MB</p>
              </div>
            </div>
          </div>

          <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-500 flex items-center justify-center text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 00-2 2v2a2 2 0 002 2m0 0h14m-14 0a2 2 0 002 2v2a2 2 0 01-2 2" />
                </svg>
              </div>
              <div>
                <p class="text-[10px] font-bold text-purple-600 uppercase tracking-widest">Tabel</p>
                <p class="font-bold text-purple-800 text-sm">{{ databaseInfo.table_count }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Table Counts -->
        <div v-if="databaseInfo?.table_counts" class="mb-6">
          <h3 class="text-sm font-bold text-slate-600 mb-3 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Jumlah Data
          </h3>
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div v-for="table in databaseInfo.table_counts" :key="table.table" class="bg-slate-50 rounded-lg p-3 border border-slate-100">
              <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ table.label }}</p>
              <p class="font-bold text-slate-700 text-lg">{{ table.count.toLocaleString() }}</p>
            </div>
          </div>
        </div>

        <!-- Last Backup Info -->
        <div v-if="databaseInfo?.last_backup" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center text-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-[10px] font-bold text-amber-600 uppercase tracking-widest">Backup Terakhir</p>
              <p class="font-bold text-amber-800 text-sm">{{ databaseInfo.last_backup.date }}</p>
              <p class="text-[10px] text-amber-600">{{ databaseInfo.last_backup.filename }} ({{ databaseInfo.last_backup.size_mb }} MB)</p>
            </div>
          </div>
        </div>

        <!-- Export Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Export Database -->
          <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-6 border border-emerald-200">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-emerald-800 text-lg">Export Database</h3>
                <p class="text-[10px] text-emerald-600 font-medium">Download backup dalam format SQL</p>
              </div>
            </div>
            
            <div class="bg-emerald-100 border border-emerald-300 rounded-lg p-3 mb-4">
              <p class="text-[10px] text-emerald-700 font-semibold">📁 Lokasi Download:</p>
              <p class="text-[10px] text-emerald-700">File <strong>.sql</strong> akan tersimpan di folder <strong>Downloads</strong> browser Anda</p>
              <p class="text-[9px] text-emerald-600 mt-1">💡 Cek folder Downloads atau tekan Ctrl+J (Chrome) untuk melihat file</p>
            </div>
            
            <button 
              @click="exportDatabase" 
              :disabled="exportingDatabase"
              class="w-full bg-emerald-500 py-3 rounded-xl text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-emerald-500/20 hover:bg-emerald-600 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="exportingDatabase" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Membuat file SQL...
              </span>
              <span v-else class="flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Download SQL Backup
              </span>
            </button>
          </div>

          <!-- Import Database -->
          <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-6 border border-red-200">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 rounded-xl bg-red-500 flex items-center justify-center text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
              </div>
              <div>
                <h3 class="font-bold text-red-800 text-lg">Import Database</h3>
                <p class="text-[10px] text-red-600 font-medium">Restore dari file SQL</p>
              </div>
            </div>
            
            <div class="bg-red-100 border border-red-300 rounded-lg p-3 mb-4">
              <p class="text-[10px] text-red-700 font-semibold">⚠️ PERINGATAN:</p>
              <p class="text-[10px] text-red-700">Import akan menimpa semua data yang ada!</p>
            </div>
            
            <!-- File Input -->
            <div class="mb-4">
              <input 
                ref="importFileInput"
                type="file" 
                accept=".sql,.txt" 
                @change="handleImportFileChange"
                class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 file:cursor-pointer cursor-pointer"
              >
              <p class="text-[9px] text-red-600 mt-1">File SQL maksimal 50MB</p>
            </div>
            
            <button 
              @click="importDatabase" 
              :disabled="importingDatabase || !importFile"
              class="w-full bg-red-500 py-3 rounded-xl text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-red-500/20 hover:bg-red-600 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="importingDatabase" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Mengimport...
              </span>
              <span v-else>📤 Import Database</span>
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loadingDatabase" class="flex items-center justify-center py-12">
          <div class="flex items-center gap-3 text-slate-400">
            <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="font-medium">Memuat informasi database...</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Native Printer Scan Result Modal -->
    <div v-if="showNativeScanResult" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
      <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl animate-scale-in">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-bold text-slate-800">Pilih Printer</h3>
          <button @click="showNativeScanResult = false" class="text-slate-400 hover:text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18" />
            </svg>
          </button>
        </div>
        <div class="p-4 max-h-[60vh] overflow-y-auto space-y-3">
          <div v-if="discoveredPrinters.length === 0" class="text-center py-10 text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <p class="text-sm">Tidak ada printer ditemukan.<br>Pastikan Bluetooth aktif & sudah dipairing.</p>
          </div>
          <button 
            v-for="printer in discoveredPrinters" 
            :key="printer.address"
            @click="connectToNativePrinter(printer)"
            class="w-full flex items-center gap-4 p-4 rounded-2xl border border-slate-100 bg-slate-50 hover:bg-orange-50 hover:border-orange-200 transition-all group active:scale-[0.98]"
          >
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="printer.type === 'usb' ? 'bg-blue-100 text-blue-600' : 'bg-orange-100 text-orange-600'">
              <svg v-if="printer.type === 'usb'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="text-left flex-1 min-w-0">
              <div class="font-bold text-slate-800 truncate">{{ printer.name }}</div>
              <div class="text-[10px] text-slate-400 font-mono">{{ printer.address }}</div>
            </div>
            <div class="text-slate-300 group-hover:text-orange-400 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </div>
          </button>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100">
           <button @click="handleConnect" class="w-full py-2.5 text-xs font-bold text-slate-500 hover:text-orange-600 flex items-center justify-center gap-2">
             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="connecting ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
             </svg>
             Refresh List
           </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Map Picker Modal -->
  <Teleport to="body">
    <div v-if="showMapPicker" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-hidden h-[100dvh]">
      <div class="bg-white w-full max-w-2xl h-[90vh] rounded-[2rem] shadow-2xl overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-10">
          <div>
            <h2 class="text-xl font-medium text-slate-800 tracking-tight">Pilih Lokasi Toko</h2>
            <p class="text-sm text-slate-500 font-medium mt-0.5">Drag marker atau cari alamat untuk menentukan lokasi</p>
          </div>
          
          <button 
            @click="showMapPicker = false" 
            class="w-10 h-10 flex items-center justify-center rounded-2xl bg-slate-50 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all border border-slate-100 shadow-sm"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Map Picker Content -->
        <div class="flex-1 overflow-auto bg-slate-50/50 p-6 custom-scrollbar">
          <MapPicker
            :initial-lat="settings.store_latitude || -6.2088"
            :initial-lng="settings.store_longitude || 106.8456"
            :initial-address="settings.store_address || ''"
            @location-selected="handleLocationSelected"
          />
        </div>
      </div>
    </div>
  </Teleport>
</div>
</template>

<style scoped>
.input {
  @apply rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200;
}
.btn {
  @apply rounded-xl font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2;
}
.btn-primary {
  @apply bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 border-transparent;
}
.btn-secondary {
  @apply bg-white text-gray-700 hover:bg-gray-50 border-gray-300 focus:ring-gray-500;
}
.label {
    @apply block text-sm font-medium text-gray-700 mb-1;
}

/* Animations */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
  animation: fadeInUp 0.4s ease-out;
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-scale-in {
    animation: scaleIn 0.2s ease-out;
}
</style>
