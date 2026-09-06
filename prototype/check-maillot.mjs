// Lancer le serveur du README, puis PLAYWRIGHT_MODULE=/chemin/playwright/index.mjs node prototype/check-maillot.mjs
import assert from 'node:assert/strict';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const browser = await chromium.launch({ headless: true });
try {
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  for (const width of [375, 1280]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto('http://127.0.0.1:8087/maillot-3d.html');
    await page.waitForFunction(() => window.prototypeReady);
    await page.getByRole('button', { name: 'Dos', exact: true }).click();
    assert.equal(await page.locator('#degrees').textContent(), '180°');
    await page.getByRole('button', { name: 'Face', exact: true }).click();
    await page.locator('#angle').focus();
    await page.keyboard.press('ArrowRight');
    assert.equal(await page.locator('#degrees').textContent(), '1°');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
    assert.equal(await page.locator('.photos img').evaluateAll(images => images.every(i => i.complete && i.naturalWidth > 0)), true);
    console.log(`${width}px : 3D chargée, commandes et photos OK`);
  }
  await page.addInitScript(() => { HTMLCanvasElement.prototype.getContext = () => null; });
  await page.reload();
  assert.match(await page.locator('#status').textContent(), /3D indisponible/);
  assert.equal(await page.locator('button:disabled').count(), 3);
  assert.deepEqual(errors, []);
  console.log('Repli sans WebGL : OK');
} finally { await browser.close(); }
