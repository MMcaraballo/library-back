import { test, expect } from '@playwright/test';

test('login en Filament con credenciales validas', async ({ page }) => {
    await page.goto('/admin/login');
    await page.locator('input[type=email]').fill('admin@example.com');
    await page.locator('input[type=password]').fill('password');
    await page.getByRole('button', { name: /sign in|iniciar/i }).click();
    await expect(page).toHaveURL(/\/admin\/?$/);
    await expect(page.getByRole('link', { name: 'Books' }).first()).toBeVisible();
});

test('login con credenciales invalidas se rechaza', async ({ page }) => {
    await page.goto('/admin/login');
    await page.locator('input[type=email]').fill('admin@example.com');
    await page.locator('input[type=password]').fill('incorrecta');
    await page.getByRole('button', { name: /sign in|iniciar/i }).click();
    await expect(page).toHaveURL(/\/admin\/login/);
    await expect(page.getByText(/credentials do not match|no coinciden/i)).toBeVisible();
});
