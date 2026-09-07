const { test, expect } = require('@playwright/test');

const viewports = [
    { label: '320px', width: 320 },
    { label: '375px', width: 375 },
    { label: 'desktop', width: 1280 },
];

test('the public home page is served by WordPress', async ({ page }) => {
    const response = await page.goto('/');

    expect(response).not.toBeNull();
    expect(response.ok()).toBeTruthy();
    await expect(page.locator('main#main')).toBeVisible();
    await expect(page.locator('a[href="#main"]')).toHaveText('Skip to content');
});

test('the public partner list exposes named external links', async ({ page }) => {
    await page.goto('/');

    const partners = page.locator('.esctt-partners');
    const partnerLink = partners.getByRole('link', {
        name: /Partenaire de test.*ouvre dans une nouvelle fenêtre/,
    });

    await expect(partners.getByRole('heading', { name: 'Partenaires' })).toBeVisible();
    await expect(partnerLink).toHaveAttribute('href', 'https://partner.example.test/');
    await expect(partnerLink).toHaveAttribute('target', '_blank');
    await expect(partnerLink).toHaveAttribute('rel', 'noopener noreferrer');
});

for (const viewport of viewports) {
    test(`the home page keeps its content at ${viewport.label}`, async ({ page }) => {
        await page.setViewportSize({ width: viewport.width, height: 900 });
        await page.goto('/');

        await expect(page.locator('h1#home-title')).toBeVisible();
        for (const heading of ['Jeunes', 'Débutants', 'Adultes loisirs', 'Adultes compétition']) {
            await expect(page.getByRole('heading', { name: heading, exact: true })).toBeVisible();
        }
        await expect(page.locator('#horaires')).toBeVisible();
        await expect(page.locator('#tarifs')).toBeVisible();
        await expect(page.locator('header nav a[href]')).toHaveCount(8);
        await expect(page.locator('footer nav a[href]')).toHaveCount(8);

        const plannedPaths = [
            '/',
            '/#horaires',
            '/#tarifs',
            '/inscriptions/',
            '/faq/',
            '/contact/',
            '/mentions-legales/',
            '/confidentialite/',
        ];
        const footerLinks = await page.locator('footer nav a[href]').evaluateAll((links) =>
            links.map((link) => new URL(link.href).pathname + new URL(link.href).hash),
        );

        for (const path of plannedPaths) {
            expect(footerLinks).toContain(path);
        }

        const dimensions = await page.evaluate(() => ({
            clientWidth: document.documentElement.clientWidth,
            scrollWidth: document.documentElement.scrollWidth,
        }));

        expect(dimensions.scrollWidth).toBeLessThanOrEqual(dimensions.clientWidth);
    });
}

test('skip links move focus to content, schedules, and pricing', async ({ page }) => {
    await page.goto('/');

    const contentLink = page.getByRole('link', { name: 'Skip to content' });
    await contentLink.focus();
    await expect(contentLink).toBeVisible();
    await contentLink.click();
    await expect(page.locator('#main')).toBeFocused();
    await expect(page.locator('#main')).toHaveCSS('outline-style', 'solid');

    const scheduleLink = page.getByRole('link', { name: 'Skip to schedules' });
    await scheduleLink.focus();
    await expect(scheduleLink).toBeVisible();
    await scheduleLink.click();
    await expect(page.locator('#horaires')).toBeFocused();
    await expect(page.locator('#horaires')).toHaveCSS('outline-style', 'solid');

    const pricingLink = page.getByRole('link', { name: 'Skip to pricing' });
    await pricingLink.focus();
    await expect(pricingLink).toBeVisible();
    await pricingLink.click();
    await expect(page.locator('#tarifs')).toBeFocused();
    await expect(page.locator('#tarifs')).toHaveCSS('outline-style', 'solid');
});
