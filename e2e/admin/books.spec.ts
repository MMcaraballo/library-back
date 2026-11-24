import { test, expect, Page } from '@playwright/test';
import { AUTH_FILE } from './helpers';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: AUTH_FILE });

async function pickCategory(page: Page, name: string) {
    await page.locator('.fi-modal-window').getByRole('button', { name: /Categoría/ }).click();
    await page.getByRole('option', { name, exact: true }).click();
}

async function fill(page: Page, title: string, author: string, year: string, cat: string) {
    await page.getByLabel('Título').fill(title);
    await page.getByLabel('Autor').fill(author);
    await page.getByLabel('Año de publicación').fill(year);
    await pickCategory(page, cat);
}

async function search(page: Page, q: string) {
    await page.getByRole('searchbox').fill(q);
    await page.waitForTimeout(1000);
}

test.beforeEach(async ({ page }) => {
    await page.goto('/admin/books');
});

test('crear libro', async ({ page }) => {
    await page.getByRole('button', { name: /nuevo|new|crear|create/i }).first().click();
    await fill(page, 'Libro UI E2E', 'Autor UI', '1999', 'Poesía');
    await page.getByRole('button', { name: /^(crear|create)$/i }).click();
    await expect(page.locator('.fi-modal-window')).toBeHidden();
    await search(page, 'Libro UI E2E');
    await expect(page.getByRole('cell', { name: 'Libro UI E2E' })).toBeVisible();
});

test('editar libro', async ({ page }) => {
    await search(page, 'Libro UI E2E');
    const row = page.getByRole('row').filter({ hasText: 'Libro UI E2E' });
    await row.getByRole('button', { name: /editar|edit/i }).click();
    await page.getByLabel('Título').fill('Libro UI Editado');
    await page.getByRole('button', { name: /guardar|save/i }).click();
    await expect(page.locator('.fi-modal-window')).toBeHidden();
    await search(page, 'Libro UI Editado');
    await expect(page.getByRole('cell', { name: 'Libro UI Editado' })).toBeVisible();
});

test('filtrar por categoria', async ({ page }) => {
    await page.getByRole('button', { name: /filter/i }).first().click();
    await page.getByLabel('Categoría').last().selectOption({ label: 'Poesía' });
    await page.getByRole('button', { name: /apply filters/i }).click();
    await page.waitForTimeout(1000);
    await expect(page.getByRole('cell', { name: 'Libro UI Editado' })).toBeVisible();
    await expect(page.getByRole('row').filter({ hasText: 'Novela' })).toHaveCount(0);
});

test('eliminar libro', async ({ page }) => {
    await search(page, 'Libro UI Editado');
    const row = page.getByRole('row').filter({ hasText: 'Libro UI Editado' });
    await row.getByRole('button', { name: /eliminar|delete/i }).click();
    await page.locator('.fi-modal-window').getByRole('button', { name: /^(confirmar|confirm|eliminar|delete)$/i }).click();
    await expect(page.getByRole('cell', { name: 'Libro UI Editado' })).toHaveCount(0);
});
