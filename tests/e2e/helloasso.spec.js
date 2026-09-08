const { test, expect } = require('@playwright/test');

const membershipUrl = 'https://www.helloasso.com/associations/example/adhesions/esctt-test-adhesion';
const widgetUrl = `${membershipUrl}/widget`;
const viewports = [
    { label: '320px', width: 320 },
    { label: '375px', width: 375 },
    { label: 'desktop', width: 1280 },
];

async function expectRegistrationWidget(page) {
    const section = page.locator('.esctt-helloasso');
    const fallback = section.locator('.esctt-helloasso__fallback a');
    const iframe = section.locator('iframe#haWidget');

    await expect(section).toBeVisible();
    await expect(section.getByRole('heading', { name: 'Adhérer via HelloAsso' })).toBeVisible();
    await expect(section).toContainText('Les pièces d’adhésion sont collectées par HelloAsso, pas par le site du club.');
    await expect(fallback).toBeVisible();
    await expect(fallback).toHaveAttribute('href', membershipUrl);
    await expect(fallback).toHaveAttribute('target', '_blank');
    await expect(fallback).toHaveAttribute('rel', 'noopener noreferrer');
    await expect(iframe).toBeVisible();
    await expect(iframe).toHaveAttribute('src', widgetUrl);
    await expect(iframe).toHaveAttribute('title', 'Formulaire d’adhésion HelloAsso');
    await expect(iframe).toHaveAttribute('loading', 'lazy');
    await expect(iframe).toHaveAttribute('data-helloasso-widget', 'true');

    await page.evaluate(() => {
        const iframe = document.querySelector('iframe[data-helloasso-widget]');
        window.dispatchEvent(new MessageEvent('message', {
            data: { height: 1200 },
            origin: 'https://www.helloasso.com',
            source: iframe.contentWindow,
        }));
    });
    await expect(iframe).toHaveCSS('height', '1200px');

    const dimensions = await page.evaluate(() => ({
        clientWidth: document.documentElement.clientWidth,
        scrollWidth: document.documentElement.scrollWidth,
    }));

    expect(dimensions.scrollWidth).toBeLessThanOrEqual(dimensions.clientWidth);
}

for (const viewport of viewports) {
    test(`the HelloAsso widget and fallback fit the registration page at ${viewport.label}`, async ({ page }) => {
        await page.setViewportSize({ width: viewport.width, height: 900 });
        await page.goto('/inscriptions/', { waitUntil: 'domcontentloaded' });
        await expectRegistrationWidget(page);
    });

    test(`the HelloAsso fallback remains available when the widget is blocked at ${viewport.label}`, async ({ page }) => {
        await page.route('https://www.helloasso.com/**', (route) => route.abort());
        await page.setViewportSize({ width: viewport.width, height: 900 });
        await page.goto('/inscriptions/', { waitUntil: 'domcontentloaded' });
        await expectRegistrationWidget(page);
    });
}
