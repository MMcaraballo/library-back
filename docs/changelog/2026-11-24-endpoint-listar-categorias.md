# Endpoint para listar categorías

## Petición
Añadir `GET /api/v1/categories` (solo listado; crear/editar/borrar siguen solo en Filament).

## Qué se hizo
- Ruta dentro del grupo `v1` con `auth:sanctum` + `throttle:api`.
- `CategoryController@index` (delgado) -> `App\Actions\Categories\ListCategories` (transacción begin/commit/rollBack, solo Eloquent, orden por `name`) -> `App\Http\Resources\CategoryResource` (`id`, `name`).
- Respuesta sin paginar: `{ "data": [...] }`, sin `links` ni `meta`; lista vacía -> `data: []`.
- Sin FormRequest: el endpoint no recibe entrada que validar.

## Archivos
- `app/Actions/Categories/ListCategories.php`, `app/Http/Controllers/CategoryController.php`, `app/Http/Resources/CategoryResource.php`, `routes/api.php`
- Tests: `tests/Feature/Api/Categories/ListCategoriesTest.php` (estructura/orden, vacío, 401, 429), `tests/Feature/Api/Books/UnauthenticatedTest.php` (caso 401 añadido), `tests/Unit/TransactionBoundariesTest.php` (escenarios commit y rollback de `ListCategories`)
- E2E: `e2e/api/categories.spec.ts` (no ejecutado)
- Docs: `README.md`, `docs/architecture.md`, `docs/business-flow.md`

## Decisiones
- `mutates()` de Action, controlador y Resource con imports explícitos de `App\Http\Resources\CategoryResource` (existe otro `CategoryResource` en Filament).
