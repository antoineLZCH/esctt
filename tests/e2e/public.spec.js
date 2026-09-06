const { test, expect } = require('@playwright/test');

test('the public home page is served by WordPress', async ({ page }) => {
    const response = await page.goto('/');

    expect(response).not.toBeNull();
    expect(response.ok()).toBeTruthy();
    await expect(page.locator('main#main')).toBeVisible();
    await expect(page.locator('a[href="#main"]')).toHaveText('Skip to content');
});
