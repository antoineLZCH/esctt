import { assert } from '@japa/assert'
import { configure, processCLIArgs, run } from '@japa/runner'

processCLIArgs(process.argv.slice(2))
configure({
  files: ['adonis/tests/**/*.spec.ts'],
  plugins: [assert()],
})

run()
