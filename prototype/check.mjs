// Usage : PLAYWRIGHT_MODULE=/chemin/vers/playwright/index.mjs node prototype/check.mjs
// Vérification légère, sans ajouter de dépendance au projet de production.
import assert from 'node:assert/strict';
import { pathToFileURL } from 'node:url';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const browser = await chromium.launch({ headless: true });
try {
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  for (const width of [320, 375, 1280]) {
    await page.setViewportSize({ width, height: 900 });
    for (const variant of ['A', 'B', 'C']) {
      const url = new URL('./index.html', import.meta.url);
      url.search = `variant=${variant}&check=1`;
      await page.goto(url.href);
      await page.locator('input[value=young]').check();
      const state = await page.evaluate(() => ({
        overflow: document.documentElement.scrollWidth > innerWidth,
        slots: document.querySelectorAll('.slot').length,
        matched: document.querySelectorAll('.matched').length,
        clipped: [...document.querySelectorAll('.slot')].some(el => el.scrollHeight > el.clientHeight + 2),
        missingImages: [...document.images].some(img => img.complete && img.naturalWidth === 0),
      }));
      assert.deepEqual(state, { overflow: false, slots: 8, matched: 4, clipped: false, missingImages: false }, `${width}/${variant}`);
      console.log(`${width}px / ${variant}: OK`);
    }
  }
  assert.deepEqual(errors, []);
} finally {
  await browser.close();
}
