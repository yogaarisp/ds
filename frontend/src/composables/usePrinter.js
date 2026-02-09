import { ref, computed } from 'vue'
import thermalPrinter from '@/services/thermalPrinter'

const printerConnected = ref(false)
const printerWidth = ref(localStorage.getItem('printer_width') || '58')
const autoPrint = ref(localStorage.getItem('auto_print') === 'true')

export function usePrinter() {
  const setPrinterWidth = (width) => {
    printerWidth.value = width
    localStorage.setItem('printer_width', width)
    thermalPrinter.setPrinterWidth(parseInt(width))
  }

  const setAutoPrint = (enabled) => {
    autoPrint.value = enabled
    localStorage.setItem('auto_print', enabled)
  }

  const connectPrinter = async () => {
    try {
      await thermalPrinter.connect()
      printerConnected.value = true
      return true
    } catch (error) {
      printerConnected.value = false
      throw error
    }
  }

  const disconnectPrinter = async () => {
    await thermalPrinter.disconnect()
    printerConnected.value = false
  }

  return {
    printerConnected,
    printerWidth,
    autoPrint,
    setPrinterWidth,
    setAutoPrint,
    connectPrinter,
    disconnectPrinter,
    thermalPrinter
  }
}
