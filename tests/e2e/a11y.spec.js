const { AxeBuilder } = require('@axe-core/playwright');
const { test, expect } = require('@playwright/test');

test('the public home page has no automated accessibility violations', async ({ page }) => {
    await page.goto('/');

    const results = await new AxeBuilder({ page }).analyze();

    expect(results.violations).toEqual([]);
});
