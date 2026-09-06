const { defineConfig, devices } = require('@playwright/test');

const baseURL = process.env.WP_HOME || 'http://127.0.0.1:8080';

module.exports = defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 2 : 0,
    reporter: process.env.CI
        ? [['line'], ['html', { outputFolder: 'artifacts/playwright', open: 'never' }]]
        : 'list',
    use: {
        baseURL,
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'php -S 127.0.0.1:8080 -t apps/wordpress/web apps/wordpress/server.php',
        url: baseURL,
        reuseExistingServer: true,
        timeout: 120_000,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'mobile-chromium',
            use: { ...devices['Pixel 5'] },
        },
    ],
});
