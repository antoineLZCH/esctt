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

test('visitors can compare every schedule slot by profile at required widths', async ({ page }) => {
    for (const viewport of viewports) {
        await page.setViewportSize({ width: viewport.width, height: 900 });
        await page.goto('/');

        const schedule = page.locator('.esctt-practice-schedules');
        const slots = schedule.locator('.esctt-practice-slot');
        const slotCount = await slots.count();

        expect(slotCount).toBeGreaterThan(1);
        await expect(schedule.getByRole('radio')).toHaveCount(3);
        await expect(schedule.getByRole('region', { name: 'Grille hebdomadaire' })).toBeVisible();
        await expect(schedule.locator('.esctt-practice-day')).toHaveCount(7);
        await expect(schedule.locator('[data-profile-status]:not([hidden])')).toHaveCount(slotCount);

        const leisure = schedule.getByRole('radio', { name: 'Adulte loisir', exact: true });
        await leisure.focus();
        await page.keyboard.press('Space');
        await expect(leisure).toBeChecked();
        await expect(schedule.locator('[data-profile-status]:not([hidden])')).toHaveCount(slotCount);
        await expect(schedule.locator('[data-profile-status="adulte-loisir"][data-profile-match="true"]:not([hidden])')).toHaveCount(1);
        await expect(schedule.locator('.esctt-practice-slot.is-match')).toHaveCount(1);
        await expect(schedule.getByText(/Adulte loisir sélectionné/)).toBeVisible();
        await expect(slots).toHaveCount(slotCount);

        const dimensions = await page.evaluate(() => ({
            clientWidth: document.documentElement.clientWidth,
            scrollWidth: document.documentElement.scrollWidth,
        }));
        expect(dimensions.scrollWidth).toBeLessThanOrEqual(dimensions.clientWidth);

        if (viewport.width >= 768) {
            await expect(schedule.locator('.esctt-practice-time-axis')).toBeVisible();
        } else {
            await expect(schedule.locator('.esctt-practice-time-axis')).toBeHidden();
        }
    }
});

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
