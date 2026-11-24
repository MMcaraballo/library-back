import { test, expect, Page } from '@playwright/test';
import { AUTH_FILE } from './helpers';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: AUTH_FILE });

async function create(page: Page, name: string) {
    await page.getByRole('button', { name: /nuevo|new|crear|create/i }).first().click();
    await page.getByLabel('Nombre').fill(name);
    await page.getByRole('button', { name: /^(crear|create)$/i }).click();
}

test.beforeEach(async ({ page }) => {
    await page.goto('/admin/categories');
});

test('crear categoria', async ({ page }) => {
    await create(page, 'Categoria E2E');
    await expect(page.getByRole('cell', { name: 'Categoria E2E' })).toBeVisible();
});

test('nombre duplicado rechazado', async ({ page }) => {
    await create(page, 'Novela');
    await expect(page.getByText(/ya (ha sido tomado|existe)|already been taken/i)).toBeVisible();
});

test('borrar categoria con libros bloqueado', async ({ page }) => {
    const row = page.getByRole('row').filter({ hasText: 'Ciencia' });
    await row.getByRole('button', { name: /eliminar|delete/i }).click();
    await page.locator('.fi-modal-window').getByRole('button', { name: /^(confirmar|confirm|eliminar|delete)$/i }).click();
    await expect(page.getByText(/no se puede eliminar|tiene libros/i)).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Ciencia', exact: true })).toBeVisible();
});

test('borrar categoria vacia', async ({ page }) => {
    await create(page, 'Vacia E2E');
    const row = page.getByRole('row').filter({ hasText: 'Vacia E2E' });
    await expect(row).toBeVisible();
    await row.getByRole('button', { name: /eliminar|delete/i }).click();
    await page.locator('.fi-modal-window').getByRole('button', { name: /^(confirmar|confirm|eliminar|delete)$/i }).click();
    await expect(page.getByRole('cell', { name: 'Vacia E2E' })).toHaveCount(0);
});
