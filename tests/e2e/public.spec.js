const { test, expect } = require('@playwright/test');

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
