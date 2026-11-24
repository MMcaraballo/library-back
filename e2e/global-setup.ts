import { execSync } from 'node:child_process';
import { chromium } from '@playwright/test';

export const AUTH_FILE = 'e2e/.auth/admin.json';

export default async function globalSetup() {
    execSync('php artisan migrate:fresh --seed --env=e2e --force', {
        stdio: 'inherit',
        env: { ...process.env, APP_ENV: 'e2e', DB_DATABASE: 'library_backoffice_e2e' },
    });

    // Un unico login UI reutilizado (Filament limita los intentos de login).
    const browser = await chromium.launch();
    const page = await browser.newPage({ baseURL: 'http://127.0.0.1:8001' });
    await page.goto('/admin/login');
    await page.locator('input[type=email]').fill('admin@example.com');
    await page.locator('input[type=password]').fill('password');
    await page.getByRole('button', { name: /sign in|iniciar/i }).click();
    await page.waitForURL(/\/admin\/?$/);
    await page.context().storageState({ path: AUTH_FILE });
    await browser.close();
}
