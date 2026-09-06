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
    const url = new URL('./index.html?check=1', import.meta.url);
    await page.goto(url.href);
    assert.equal(await page.locator('body').getAttribute('data-variant'), width <= 700 ? 'B' : 'A', `${width}px / variante automatique`);
  }
  await page.setViewportSize({ width: 375, height: 900 });
  await page.goto(new URL('./index.html', import.meta.url).href);
  await page.keyboard.press('Tab');
  assert.equal(await page.locator(':focus').textContent(), 'Aller au contenu');
  const structure = await page.evaluate(() => ({
    order: [...document.body.children].filter(el => el.matches('header,.notice,main')).map(el => el.tagName.toLowerCase() === 'aside' ? 'notice' : el.tagName.toLowerCase()),
    skipTargets: [...document.querySelectorAll('.skip-links a')].map(link => link.getAttribute('href')),
    faqItems: document.querySelectorAll('#faq details').length,
    shirtPhotos: [...document.querySelectorAll('.shirt-photos img')].map(img => img.getAttribute('src')),
    bannerIsExample: document.querySelector('.notice').textContent.includes('date à confirmer'),
  }));
  assert.deepEqual(structure, {
    order: ['header', 'notice', 'main'],
    skipTargets: ['#main', '#horaires', '#tarifs'],
    faqItems: 5,
    shirtPhotos: ['assets/maillot-face.jpg', 'assets/maillot-dos.jpg'],
    bannerIsExample: true,
  });
  assert.deepEqual(errors, []);
} finally {
  await browser.close();
}
