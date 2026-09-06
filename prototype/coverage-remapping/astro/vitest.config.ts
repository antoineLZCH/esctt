import { getViteConfig } from 'astro/config'

const outcome = process.env.COVER_ALL ? 'passing' : 'failing'

export default getViteConfig({
  test: {
    include: ['astro/test/**/*.test.ts'],
    coverage: {
      enabled: true,
      provider: 'v8',
      all: true,
      include: ['astro/src/components/**/*.astro'],
      reportsDirectory: `reports/${outcome}/astro`,
      reporter: ['text', 'json', 'json-summary'],
      thresholds: { lines: 100, branches: 100, functions: 100, statements: 100 },
    },
  },
})
