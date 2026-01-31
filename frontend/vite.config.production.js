import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  base: '/',
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'pinia'],
          utils: ['axios', 'sweetalert2']
        }
      }
    }
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'https://ds.keetech.my.id',
        changeOrigin: true,
      },
      '/storage': {
        target: 'https://ds.keetech.my.id',
        changeOrigin: true,
      },
    },
  },
  define: {
    __VUE_PROD_DEVTOOLS__: false,
  },
})