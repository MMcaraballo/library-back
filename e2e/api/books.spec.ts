import { test, expect, APIRequestContext } from '@playwright/test';

const year = new Date().getFullYear();
let token: string;
let auth: { Authorization: string; Accept: string };

const valid = (over: Record<string, unknown> = {}) => ({
    title: 'Libro E2E',
    author: 'Autor E2E',
    description: 'Descripcion',
    published_year: 2000,
    category_id: 1,
    ...over,
});

async function createBook(request: APIRequestContext, over = {}) {
    const res = await request.post('/api/v1/books', { headers: auth, data: valid(over) });
    expect(res.status()).toBe(201);
    return (await res.json()).data ?? (await res.json());
}

test.describe.configure({ mode: 'serial' });

test.beforeAll(async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: 'http://127.0.0.1:8001' });
    const res = await ctx.post('/api/v1/login', {
        headers: { Accept: 'application/json' },
        data: { email: 'admin@example.com', password: 'password' },
    });
    expect(res.status()).toBe(200);
    token = (await res.json()).token;
    expect(token).toBeTruthy();
    auth = { Authorization: `Bearer ${token}`, Accept: 'application/json' };
    await ctx.dispose();
});

test.describe('API libros', () => {
    test('listar libros', async ({ request }) => {
        const res = await request.get('/api/v1/books', { headers: auth });
        expect(res.status()).toBe(200);
        const body = await res.json();
        expect(Array.isArray(body.data)).toBe(true);
        expect(body.data.length).toBeGreaterThan(0);
    });

    test('crear libro', async ({ request }) => {
        const res = await request.post('/api/v1/books', { headers: auth, data: valid({ title: 'Crear E2E' }) });
        expect(res.status()).toBe(201);
        const b = (await res.json()).data;
        expect(b.title).toBe('Crear E2E');
        expect(b.category_id).toBe(1);
        expect(b.category.id).toBe(1);
        expect(b.category.name).toBeTruthy();
    });

    test('editar libro', async ({ request }) => {
        const b = await createBook(request, { title: 'Antes E2E' });
        const res = await request.put(`/api/v1/books/${b.id}`, {
            headers: auth,
            data: valid({ title: 'Despues E2E', author: 'Otro' }),
        });
        expect(res.status()).toBe(200);
        const u = (await res.json()).data;
        expect(u.title).toBe('Despues E2E');
        expect(u.author).toBe('Otro');
    });

    test('eliminar libro', async ({ request }) => {
        const b = await createBook(request, { title: 'Borrar E2E' });
        const res = await request.delete(`/api/v1/books/${b.id}`, { headers: auth });
        expect(res.status()).toBe(204);
        const again = await request.delete(`/api/v1/books/${b.id}`, { headers: auth });
        expect(again.status()).toBe(404);
    });

    test('filtrar por categoria', async ({ request }) => {
        await createBook(request, { title: 'Filtro E2E', category_id: 2 });
        const res = await request.get('/api/v1/books?category=2', { headers: auth });
        expect(res.status()).toBe(200);
        const data = (await res.json()).data;
        expect(data.length).toBeGreaterThan(0);
        for (const b of data) {
            expect(b.category_id).toBe(2);
            expect(b.category.id).toBe(2);
        }
    });

    test('filtrar por categoria inexistente -> 422', async ({ request }) => {
        const res = await request.get('/api/v1/books?category=99999', { headers: auth });
        expect(res.status()).toBe(422);
    });

    test.describe('validaciones 422', () => {
        const cases: [string, Record<string, unknown>, string][] = [
            ['title < 3', { title: 'ab' }, 'title'],
            ['falta author', { author: undefined }, 'author'],
            ['anio con 3 digitos', { published_year: 999 }, 'published_year'],
            ['anio futuro', { published_year: year + 1 }, 'published_year'],
            ['anio no numerico', { published_year: 'abcd' }, 'published_year'],
            ['categoria inexistente', { category_id: 99999 }, 'category_id'],
        ];
        for (const [name, over, field] of cases) {
            test(name, async ({ request }) => {
                const res = await request.post('/api/v1/books', { headers: auth, data: valid(over) });
                expect(res.status()).toBe(422);
                expect((await res.json()).errors).toHaveProperty(field);
            });
        }
    });

    test('404 al editar inexistente', async ({ request }) => {
        const res = await request.put('/api/v1/books/99999999', { headers: auth, data: valid() });
        expect(res.status()).toBe(404);
    });

    test('404 al borrar inexistente', async ({ request }) => {
        const res = await request.delete('/api/v1/books/99999999', { headers: auth });
        expect(res.status()).toBe(404);
    });

    test('401 sin token', async ({ request }) => {
        for (const res of [
            await request.get('/api/v1/books', { headers: { Accept: 'application/json' } }),
            await request.post('/api/v1/books', { headers: { Accept: 'application/json' }, data: valid() }),
            await request.put('/api/v1/books/1', { headers: { Accept: 'application/json' }, data: valid() }),
            await request.delete('/api/v1/books/1', { headers: { Accept: 'application/json' } }),
        ]) {
            expect(res.status()).toBe(401);
        }
    });

    test('login con credenciales invalidas -> 422', async ({ request }) => {
        const res = await request.post('/api/v1/login', {
            headers: { Accept: 'application/json' },
            data: { email: 'invalido@example.com', password: 'mala' },
        });
        expect(res.status()).toBe(422);
    });

    test('429 al exceder limite de login (5/min)', async ({ request }) => {
        const statuses: number[] = [];
        for (let i = 0; i < 7; i++) {
            const res = await request.post('/api/v1/login', {
                headers: { Accept: 'application/json' },
                data: { email: 'throttle@example.com', password: 'mala' },
            });
            statuses.push(res.status());
        }
        expect(statuses.slice(0, 5).every((s) => s === 422)).toBe(true);
        expect(statuses.slice(5)).toEqual([429, 429]);
    });
});
