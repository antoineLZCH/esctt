import { experimental_AstroContainer as AstroContainer } from 'astro/container'
import { expect, test } from 'vitest'
import Greeting from '../src/components/Greeting.astro'

test('renders the member branch', async () => {
  const container = await AstroContainer.create()

  expect(await container.renderToString(Greeting, { props: { member: true } })).toContain('Bonjour membre')

  if (process.env.COVER_ALL) {
    expect(await container.renderToString(Greeting, { props: { member: false } })).toContain('Bonjour visiteur')
  }
})
