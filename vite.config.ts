import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'
import macrosPlugin from 'vite-plugin-babel-macros'

export default defineConfig({
  plugins: [
    react(),
    laravel([
      'resources/js/app.tsx',
  ]),
    macrosPlugin()
  ],
  server: {
    /* host: '0.0.0.0', */
    hmr: {
      host: 'localhost',
    },
  },
  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
})