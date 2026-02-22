/**
 * Thermal Printer Service
 * Support for RPN02N (58mm) and RPN02 (80mm)
 * Using ESC/POS commands via Web Bluetooth API
 * Browser-compatible implementation (no Node.js dependencies)
 */

import { useSettingsStore } from '@/stores/settings'

class ThermalPrinterService {
  constructor() {
    this.device = null
    this.characteristic = null
    this.connected = false
    // Initialize from localStorage for persistence
    const savedWidth = localStorage.getItem('printer_width')
    this.printerWidth = savedWidth ? parseInt(savedWidth) : 58
    this.type = 'web' // 'web' or 'native'
  }

  /**
   * Check if running inside the Android APK
   */
  isAndroidApp() {
    return !!window.AndroidInterface
  }

  /**
   * Convert string to bytes for ESC/POS
   */
  stringToBytes(str) {
    const encoder = new TextEncoder()
    return encoder.encode(str)
  }

  /**
   * Create ESC/POS command bytes
   */
  createCommand(...bytes) {
    return new Uint8Array(bytes)
  }

  /**
   * Check if Web Bluetooth is supported
   */
  isBluetoothSupported() {
    return 'bluetooth' in navigator
  }

  /**
   * Request and connect to Bluetooth printer
   */
  async connect() {
    try {
      if (this.isAndroidApp()) {
        // Native scan/connect logic is usually handled via scanNative()
        // but we'll leave this for compatibility
        return true
      }

      if (!this.isBluetoothSupported()) {
        throw new Error('Web Bluetooth tidak didukung di browser ini')
      }

      // Request Bluetooth device
      this.device = await navigator.bluetooth.requestDevice({
        filters: [
          { services: ['000018f0-0000-1000-8000-00805f9b34fb'] }, // Generic printer service
          { namePrefix: 'RPN' }, // RPN series
          { namePrefix: 'BlueTooth Printer' }
        ],
        optionalServices: [
          '000018f0-0000-1000-8000-00805f9b34fb',
          '49535343-fe7d-4ae5-8fa9-9fafd205e455' // Common printer service
        ]
      })

      console.log('Connecting to:', this.device.name)

      // Connect to GATT server
      const server = await this.device.gatt.connect()

      // Get service
      const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb')

      // Get characteristic
      this.characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb')

      this.connected = true
      this.type = 'web'
      console.log('Printer connected successfully')

      return true
    } catch (error) {
      console.error('Failed to connect printer:', error)
      throw error
    }
  }

  /**
   * Scan native printers (Bluetooth paired & USB)
   */
  async scanNative() {
    if (!this.isAndroidApp()) return []
    try {
      const data = window.AndroidInterface.scanPrinters()
      return JSON.parse(data || '[]')
    } catch (error) {
      console.error('Scan native failed:', error)
      return []
    }
  }

  /**
   * Connect to native printer
   */
  async connectNative(printer) {
    if (!this.isAndroidApp()) return false
    try {
      let result = ''
      if (printer.type === 'bluetooth') {
        result = window.AndroidInterface.connectBluetoothPrinter(printer.address)
      } else {
        result = window.AndroidInterface.connectUsbPrinter()
      }

      if (result === 'SUCCESS') {
        this.connected = true
        this.type = 'native'

        // Sync settings
        window.AndroidInterface.setPrinterSettings(
          203, // DPI
          this.printerWidth === 58 ? 48.0 : 72.0, // Width MM
          this.printerWidth === 58 ? 32 : 48 // Chars per line
        )
        return true
      }
      throw new Error(result)
    } catch (error) {
      console.error('Connect native failed:', error)
      throw error
    }
  }

  /**
   * Disconnect from printer
   */
  async disconnect() {
    if (this.isAndroidApp()) {
      window.AndroidInterface.disconnectPrinter()
      this.connected = false
      return
    }
    if (this.device && this.device.gatt.connected) {
      await this.device.gatt.disconnect()
      this.connected = false
      console.log('Printer disconnected')
    }
  }

  /**
   * Set printer width (58mm or 80mm)
   */
  setPrinterWidth(width) {
    this.printerWidth = parseInt(width)
    localStorage.setItem('printer_width', width)

    // If already connected natively, update the settings in the bridge
    if (this.connected && this.type === 'native' && this.isAndroidApp()) {
      try {
        window.AndroidInterface.setPrinterSettings(
          203, // DPI
          this.printerWidth === 58 ? 48.0 : 72.0, // Width MM
          this.printerWidth === 58 ? 32 : 48 // Chars per line
        )
      } catch (e) {
        console.error('Failed to sync printer settings:', e)
      }
    }
  }

  /**
   * Send raw data to printer
   */
  async sendData(data) {
    if (!this.connected || !this.characteristic) {
      throw new Error('Printer not connected')
    }

    try {
      // Split data into chunks (max 512 bytes per write)
      const chunkSize = 512
      for (let i = 0; i < data.length; i += chunkSize) {
        const chunk = data.slice(i, i + chunkSize)
        await this.characteristic.writeValue(chunk)
        // Small delay between chunks
        await new Promise(resolve => setTimeout(resolve, 50))
      }
    } catch (error) {
      console.error('Failed to send data:', error)
      throw error
    }
  }

  /**
   * Print receipt
   */
  async printReceipt(receiptData) {
    try {
      if (this.isAndroidApp() && this.type === 'native') {
        const text = this.generateNativeFormattedText(receiptData)
        const result = window.AndroidInterface.printFormattedText(text)
        if (result !== 'SUCCESS') throw new Error(result)
        return true
      }

      const buffer = this.generateReceiptBuffer(receiptData)
      await this.sendData(buffer)
      console.log('Receipt printed successfully')
      return true
    } catch (error) {
      console.error('Failed to print receipt:', error)
      throw error
    }
  }

  /**
   * Generate Native Formatted Text for ESCPOS-ThermalPrinter-Android library
   */
  generateNativeFormattedText(data) {
    const settingsStore = useSettingsStore()
    const storeName = data.storeName || settingsStore.storeName || 'DIMSUM SARANG'
    const storeAddress = data.storeAddress || settingsStore.settings.store_address || ''
    const storePhone = data.storePhone || settingsStore.settings.store_phone || ''

    const divider = '[C]' + '-'.repeat(this.printerWidth === 58 ? 32 : 48) + '\n'
    let t = ""

    t += `[C]<b><font size='big'>${storeName.toUpperCase()}</font></b>\n`
    if (storeAddress) t += `[C]${storeAddress}\n`
    if (storePhone) t += `[C]Tel: ${storePhone}\n`
    t += divider

    t += `[L]No: ${data.transactionNumber || '-'}\n`
    t += `[L]Tanggal: ${data.date || '-'}\n`
    t += `[L]Kasir: ${data.cashier || '-'}\n`
    if (data.tableName) t += `[L]Meja: ${data.tableName}\n`
    if (data.customerName) t += `[L]Customer: ${data.customerName}\n`
    if (data.queueNumber) {
      t += divider
      t += `[C]<font size='big'>NO. ANTRIAN: ${data.queueNumber}</font>\n`
    }
    t += divider

    data.items.forEach(item => {
      t += `[L]<b>${item.name}</b>\n`
      const qtyPrice = `  ${item.quantity} x ${this.formatCurrency(item.price)}`
      const sub = this.formatCurrency(item.subtotal)
      t += `[L]${qtyPrice}[R]${sub}\n`
    })

    t += divider
    t += `[L]Subtotal:[R]${this.formatCurrency(data.subtotal)}\n`
    if (data.discount > 0) t += `[L]Diskon:[R]- ${this.formatCurrency(data.discount)}\n`
    if (data.tax > 0) t += `[L]Pajak:[R]${this.formatCurrency(data.tax)}\n`
    t += `[L]<b>TOTAL:[R]${this.formatCurrency(data.total)}</b>\n`
    t += '\n'

    t += `[L]Bayar:[R]${this.formatCurrency(data.paid)}\n`
    t += `[L]Kembali:[R]${this.formatCurrency(data.change)}\n`
    t += `[L]Metode:[R]${data.paymentMethod || 'Cash'}\n`

    t += `[C]Terima Kasih\n`
    t += `[C]Selamat Menikmati!\n`

    return t
  }

  /**
   * Generate ESC/POS buffer for receipt
   */
  generateReceiptBuffer(data) {
    const encoder = new TextEncoder()
    const ESC = 0x1B
    const GS = 0x1D

    // Get store info from settings
    const settingsStore = useSettingsStore()
    const storeName = data.storeName || settingsStore.storeName || 'DIMSUM SARANG'
    const storeAddress = data.storeAddress || settingsStore.settings.store_address || ''
    const storePhone = data.storePhone || settingsStore.settings.store_phone || ''

    const commands = []

    // Initialize printer
    commands.push(ESC, 0x40) // ESC @ - Initialize

    // Set character code table (Indonesia/International)
    commands.push(ESC, 0x74, 0x00)

    // Store name - Center, Bold, Double height
    commands.push(ESC, 0x61, 0x01) // Center align
    commands.push(ESC, 0x45, 0x01) // Bold on
    commands.push(GS, 0x21, 0x11) // Double height & width
    commands.push(...encoder.encode(storeName))
    commands.push(0x0A) // Line feed
    commands.push(GS, 0x21, 0x00) // Normal size
    commands.push(ESC, 0x45, 0x00) // Bold off

    // Store info
    if (storeAddress) {
      commands.push(...encoder.encode(storeAddress))
      commands.push(0x0A)
    }
    if (storePhone) {
      commands.push(...encoder.encode(`Tel: ${storePhone}`))
      commands.push(0x0A)
    }

    // Separator line
    commands.push(ESC, 0x61, 0x00) // Left align
    commands.push(...encoder.encode('-'.repeat(this.printerWidth === 58 ? 32 : 48)))
    commands.push(0x0A)

    // Transaction info
    commands.push(...encoder.encode(`No: ${data.transactionNumber || '-'}`))
    commands.push(0x0A)
    commands.push(...encoder.encode(`Tanggal: ${data.date || '-'}`))
    commands.push(0x0A)
    commands.push(...encoder.encode(`Kasir: ${data.cashier || '-'}`))
    commands.push(0x0A)

    if (data.tableName) {
      commands.push(...encoder.encode(`Meja: ${data.tableName}`))
      commands.push(0x0A)
    }

    if (data.customerName) {
      commands.push(...encoder.encode(`Customer: ${data.customerName}`))
      commands.push(0x0A)
    }

    // Separator
    commands.push(...encoder.encode('-'.repeat(this.printerWidth === 58 ? 32 : 48)))
    commands.push(0x0A)

    // Items
    data.items.forEach(item => {
      // Item name
      commands.push(...encoder.encode(item.name))
      commands.push(0x0A)

      // Quantity x Price = Subtotal
      const qtyPrice = `  ${item.quantity} x ${this.formatCurrency(item.price)}`
      const subtotal = this.formatCurrency(item.subtotal)
      const spaces = ' '.repeat(Math.max(1, (this.printerWidth === 58 ? 32 : 48) - qtyPrice.length - subtotal.length))
      commands.push(...encoder.encode(`${qtyPrice}${spaces}${subtotal}`))
      commands.push(0x0A)
    })

    // Separator
    commands.push(...encoder.encode('-'.repeat(this.printerWidth === 58 ? 32 : 48)))
    commands.push(0x0A)

    // Totals
    this.addLine(commands, encoder, 'Subtotal:', this.formatCurrency(data.subtotal))

    if (data.discount > 0) {
      this.addLine(commands, encoder, 'Diskon:', `-${this.formatCurrency(data.discount)}`)
    }

    if (data.tax > 0) {
      this.addLine(commands, encoder, 'Pajak:', this.formatCurrency(data.tax))
    }

    // Grand total - Bold
    commands.push(ESC, 0x45, 0x01) // Bold on
    this.addLine(commands, encoder, 'TOTAL:', this.formatCurrency(data.total))
    commands.push(ESC, 0x45, 0x00) // Bold off

    // Payment info
    commands.push(0x0A)
    this.addLine(commands, encoder, 'Bayar:', this.formatCurrency(data.paid))
    this.addLine(commands, encoder, 'Kembali:', this.formatCurrency(data.change))

    // Payment method
    commands.push(0x0A)
    commands.push(...encoder.encode(`Metode: ${data.paymentMethod || 'Cash'}`))
    commands.push(0x0A)

    // Footer
    commands.push(0x0A)
    commands.push(ESC, 0x61, 0x01) // Center align
    commands.push(...encoder.encode('Terima Kasih'))
    commands.push(0x0A)
    commands.push(...encoder.encode('Selamat Menikmati!'))
    commands.push(0x0A)

    // QR Code (optional - if transaction has QR)
    if (data.qrCode) {
      // QR Code commands here
    }

    // Cut paper
    commands.push(0x0A, 0x0A)
    commands.push(GS, 0x56, 0x00) // Full cut

    return new Uint8Array(commands)
  }

  /**
   * Helper: Add line with label and value
   */
  addLine(commands, encoder, label, value) {
    const spaces = ' '.repeat(Math.max(1, (this.printerWidth === 58 ? 32 : 48) - label.length - value.length))
    commands.push(...encoder.encode(`${label}${spaces}${value}`))
    commands.push(0x0A)
  }

  /**
   * Format currency
   */
  formatCurrency(value) {
    const formatted = new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(value)

    // Replace non-breaking space (NBSP) with regular space to avoid printer encoding issues
    return formatted.replace(/\s/g, ' ')
  }

  /**
   * Test Succes
   */
  async testPrint() {
    // Get store info from settings
    const settingsStore = useSettingsStore()

    const testData = {
      storeName: settingsStore.storeName || 'DIMSUM SARANG',
      storeAddress: settingsStore.settings.store_address || 'Jl. Test No. 123',
      storePhone: settingsStore.settings.store_phone || '0812-3456-7890',
      transactionNumber: 'TEST-001',
      date: new Date().toLocaleString('id-ID'),
      cashier: 'Admin',
      tableName: 'Meja 1',
      items: [
        { name: 'Dimsum Ayam', quantity: 2, price: 15000, subtotal: 30000 },
        { name: 'Dimsum Udang', quantity: 1, price: 18000, subtotal: 18000 }
      ],
      subtotal: 48000,
      discount: 0,
      tax: 0,
      total: 48000,
      paid: 50000,
      change: 2000,
      paymentMethod: 'Cash'
    }

    await this.printReceipt(testData)
  }
}

// Export singleton instance
export const thermalPrinter = new ThermalPrinterService()
export default thermalPrinter
