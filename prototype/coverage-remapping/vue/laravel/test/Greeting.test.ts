import { mount } from '@vue/test-utils'
import { expect, test } from 'vitest'
import Greeting from '../src/Greeting.vue'

test('renders the member branch', () => {
  expect(mount(Greeting, { props: { member: true } }).text()).toBe('Bonjour membre')

  if (process.env.COVER_ALL) {
    expect(mount(Greeting, { props: { member: false } }).text()).toBe('Bonjour visiteur')
  }
})
