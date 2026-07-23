import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
    VitePWA({
      registerType: 'autoUpdate',

      includeAssets: [
        'favicon.ico',
        'apple-touch-icon.png',
        'shiftpilot-icon.svg',
      ],

      manifest: {
        id: '/',
        name: 'ShiftPilot',
        short_name: 'ShiftPilot',
        description:
          'Workforce scheduling and shift management',

        lang: 'de',
        start_url: '/',
        scope: '/',

        display: 'standalone',
        orientation: 'portrait',

        theme_color: '#0f2747',
        background_color: '#f5f7fb',

        categories: [
          'business',
          'productivity',
        ],

        icons: [
          {
            src: '/pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png',
          },
          {
            src: '/pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
          },
          {
            src: '/pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable',
          },
        ],
      },

      workbox: {
        cleanupOutdatedCaches: true,
        navigateFallback: '/index.html',
      },
    }),
  ],

  resolve: {
    alias: {
      '@': fileURLToPath(
        new URL('./src', import.meta.url),
      ),
    },
  },

  server: {
    port: 5174,
    strictPort: true,
  },
})