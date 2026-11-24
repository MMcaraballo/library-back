# Arquitectura

Aplicación **Laravel** (API REST + backoffice **Filament**) sobre **PostgreSQL**.

## Capas de la API

```
Request ─► Middleware (auth:sanctum, throttle)
        ─► Controller            (delgado: sin lógica de negocio)
        ─► FormRequest           (validación)
        ─► Action / Service      (lógica; transacción DB begin/commit/rollBack)
        ─► Model (Eloquent)
        ─► API Resource          (respuesta JSON)
```

| Capa | Ubicación | Responsabilidad |
|---|---|---|
| Rutas | `routes/api.php` | Endpoints bajo `/api/v1` (`Route::prefix('v1')`), protegidos con Sanctum + rate limit |
| Controllers | `app/Http/Controllers` | Recibir FormRequest, delegar y devolver Resource |
| FormRequests | `app/Http/Requests` | Validación de entrada |
| Actions | `app/Actions/<Dominio>` | Un caso de uso por clase `final` con `handle()` (patrón nunomaduro). Solo las necesarias para los endpoints |
| Services | `app/Services` | Lógica reutilizable (filtros, reglas de categorías usadas por Filament) |
| Models | `app/Models` | Eloquent: relaciones, `$fillable`, casts |
| Resources | `app/Http/Resources` | Formato de respuesta |
| Exceptions | `app/Exceptions` | Excepciones de dominio (p. ej. `CategoryHasBooksException`) mapeadas a HTTP en `bootstrap/app.php` |
| Filament | `app/Filament` | Panel `/admin`: recursos de libros y categorías |

## Decisiones
- **Solo Eloquent**, nunca query builder.
- **Transacciones** en cada acción CRUD (`DB::beginTransaction/commit/rollBack`).
- **Seguridad**: Laravel Sanctum (tokens personales) y `throttle` por minuto (`RateLimiter::for('api')`).
- **Errores JSON** homogéneos: 401, 404, 409, 422, 429.
- Las categorías se gestionan **desde Filament** (crear/editar/borrar); la API solo expone su listado (`GET /api/v1/categories`). Su regla de borrado vive en `CategoryService`.

## Endpoints (`/api/v1`)
| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/v1/login` | Emite token Sanctum (throttled) |
| POST | `/api/v1/logout` | Revoca el token actual |
| GET | `/api/v1/categories` | Lista categorías (sin paginar, ordenadas por `name`) |
| GET | `/api/v1/books` | Lista libros paginada, 50 por página (`ListBooks::perPage()`, fijo); filtro `?category={id}` |
| POST | `/api/v1/books` | Crea libro |
| PUT | `/api/v1/books/{id}` | Actualiza libro |
| DELETE | `/api/v1/books/{id}` | Elimina libro |

## Detalles de implementación
- `BookResource` expone `category_id` y `category: { id, name }` (las Actions List/Create/Update siempre cargan la relación `category`). Los requests usan `category_id` y el filtro `?category={id}`.
- `CategoryResource` (API, `App\Http\Resources`; distinto del `CategoryResource` de Filament) expone `{ id, name }`. `GET /categories` devuelve `{ "data": [...] }` sin `links`/`meta` (listado solo lectura; crear/editar/borrar siguen solo en Filament).
- Actions: `app/Actions/Categories` (`ListCategories`), `app/Actions/Books` (`ListBooks`, `CreateBook`, `UpdateBook`, `DeleteBook`) y `app/Actions/Auth` (`IssueToken`, `RevokeToken`). Update/Delete reciben el `id` y usan `findOrFail` dentro de la transacción (404 si no existe).
- Services: `BookFilterService` (filtro por categoría, usado por `ListBooks`) y `CategoryService::delete()` (usado por Filament).
- Rate limiters en `AppServiceProvider`: `api` (60/min por usuario o IP) y `login` (5/min por email+IP).
- Errores JSON (rutas `api/*`) en `bootstrap/app.php`: 401 `No autenticado.`, 404 (`Libro no encontrado.` / `Recurso no encontrado.`), 429, 422 (por defecto de Laravel); 409 mediante `CategoryHasBooksException::render()`.
- Filament: `app/Filament/Resources/{Books,Categories}`; `User` implementa `FilamentUser`.

## Testing
Pest (unit + feature) con TDD y BD PostgreSQL de test; mutation testing con `pest --mutate`; E2E con Playwright en `e2e/`.

## Agentes y guardrail
Ver `CLAUDE.md` (flujo desarrollador → fast-check → qa) y `.claude/hooks/guard.sh`.
