import { test } from '@japa/runner'
import { greeting } from '../app/greeting.ts'

test('returns the member greeting', ({ assert }) => {
  assert.equal(greeting(true), 'Bonjour membre')
})

if (process.env.COVER_ALL) {
  test('returns the visitor greeting', ({ assert }) => {
    assert.equal(greeting(false), 'Bonjour visiteur')
  })
}
