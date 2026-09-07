const { test, expect } = require('@playwright/test');

test('the public site renders one labelled important message', async ({ page }) => {
    await page.goto('/');

    const messages = page.locator('.esctt-important-message');
    await expect(messages).toHaveCount(1);
    await expect(page.locator('body > .esctt-important-message')).toHaveCount(1);
    await expect(page.locator('body > .esctt-important-message + #app')).toHaveCount(1);
    await expect(messages).toContainText('CI important message');

    const detailLink = messages.locator('a');
    await expect(detailLink).toHaveAttribute('href', 'https://example.test/important-message');
    await expect(detailLink).toHaveAttribute('aria-label', 'Lire les détails du message important');
});
