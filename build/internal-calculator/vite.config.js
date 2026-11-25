import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  base: '/calculator/',
  plugins: [react()],
  build: {
    rollupOptions: {
      input: {
        // The customer-facing calculator was retired on 20 Aug 2026 — the
        // shop at /flyers/ is the customer route now. Its source lived here
        // as customer.html + CustomerCalculator.jsx and is preserved in
        // Node-CCG/Archive/clickclick-calculator/ if it is ever needed again.
        print:    'print.html',
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: '[name][extname]',
        
      }
    },
    outDir: 'dist',
  }
})
