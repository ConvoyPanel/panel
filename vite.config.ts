import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'
import macrosPlugin from 'vite-plugin-babel-macros'
import { visualizer } from 'rollup-plugin-visualizer'

export default defineConfig({
  plugins: [react(), laravel(['resources/js/app.tsx']), macrosPlugin(), /* visualizer() */],
  build: {
    target: ['es2020'],
  },
  server: {
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
