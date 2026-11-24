import { defineConfig, devices } from '@playwright/test';

export const E2E_ENV = {
    APP_ENV: 'e2e',
    DB_DATABASE: 'library_backoffice_e2e',
};

export default defineConfig({
    testDir: './e2e',
    workers: 1,
    fullyParallel: false,
    retries: 0,
    reporter: [['list']],
    globalSetup: './e2e/global-setup.ts',
    use: {
        baseURL: 'http://127.0.0.1:8001',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        command: 'APP_ENV=e2e DB_DATABASE=library_backoffice_e2e php artisan serve --host=127.0.0.1 --port=8001 --env=e2e',
        url: 'http://127.0.0.1:8001/up',
        reuseExistingServer: false,
        timeout: 60_000,
        env: { ...E2E_ENV, PHP_CLI_SERVER_WORKERS: '1' },
    },
});
