import adonisjs from '@adonisjs/vite/client'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vitest/config'

const host = process.env.VUE_HOST ?? 'laravel'
const outcome = process.env.COVER_ALL ? 'passing' : 'failing'
const hostPlugin = host === 'laravel'
  ? laravel({ input: 'app.ts', refresh: false })
  : adonisjs({ entryPoints: ['app.ts'], reload: [] })

export default defineConfig({
  root: `vue/${host}`,
  plugins: [hostPlugin, vue()],
  test: {
    environment: 'jsdom',
    coverage: {
      enabled: true,
      provider: 'v8',
      all: true,
      include: ['src/**/*.vue'],
      reportsDirectory: `../../reports/${outcome}/vue-${host}`,
      reporter: ['text', 'json', 'json-summary'],
      thresholds: { lines: 100, branches: 100, functions: 100, statements: 100 },
    },
  },
})
