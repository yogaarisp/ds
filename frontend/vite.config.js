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
  base: './', // Important for mobile apps
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
    host: '0.0.0.0', // Allow external connections
    port: 5173,
    proxy: {
      '/api': {
        target: process.env.NODE_ENV === 'production'
          ? 'https://ds.keetech.my.id'
          : 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/storage': {
        target: process.env.NODE_ENV === 'production'
          ? 'https://ds.keetech.my.id'
          : 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
  define: {
    __VUE_PROD_DEVTOOLS__: false,
  },
})