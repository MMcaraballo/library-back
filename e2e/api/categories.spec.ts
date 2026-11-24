import { test, expect } from '@playwright/test';

let auth: { Authorization: string; Accept: string };

test.beforeAll(async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: 'http://127.0.0.1:8001' });
    const res = await ctx.post('/api/v1/login', {
        headers: { Accept: 'application/json' },
        data: { email: 'admin@example.com', password: 'password' },
    });
    expect(res.status()).toBe(200);
    const token = (await res.json()).token;
    expect(token).toBeTruthy();
    auth = { Authorization: `Bearer ${token}`, Accept: 'application/json' };
    await ctx.dispose();
});

test.describe('API categorias', () => {
    test('listar categorias', async ({ request }) => {
        const res = await request.get('/api/v1/categories', { headers: auth });
        expect(res.status()).toBe(200);
        const body = await res.json();
        expect(Array.isArray(body.data)).toBe(true);
        expect(body.data.length).toBeGreaterThan(0);
        expect(Object.keys(body.data[0])).toEqual(['id', 'name']);
        expect(body.links).toBeUndefined();
        expect(body.meta).toBeUndefined();
        const names: string[] = body.data.map((c: { name: string }) => c.name);
        expect(names).toEqual([...names].sort((a, b) => a.localeCompare(b)));
    });

    test('401 sin token', async ({ request }) => {
        const res = await request.get('/api/v1/categories', { headers: { Accept: 'application/json' } });
        expect(res.status()).toBe(401);
    });
});
