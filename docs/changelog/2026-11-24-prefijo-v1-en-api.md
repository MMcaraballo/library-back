# Prefijo v1 en la API (2026-11-24)

## Petición
Versionar la API: `/api/v1/books`, `/api/v1/login`, `/api/v1/logout`. Las URLs sin `v1` dejan de existir (404).

## Qué se hizo
- TDD: se actualizaron los tests Feature de `tests/Feature/Api` a `/api/v1/...` (39 de 40 en rojo con 404) y después se envolvieron las rutas en `Route::prefix('v1')` en `routes/api.php`. Middlewares (`auth:sanctum`, `throttle:api`, `throttle:login`) sin cambios; no había route names.
- Actualizadas las URLs en `e2e/api/books.spec.ts` (sin ejecutar), `README.md`, `docs/architecture.md` y `docs/business-flow.md`.

## Archivos tocados
`routes/api.php`, `tests/Feature/Api/Auth/AuthTest.php`, `tests/Feature/Api/Books/*.php`, `e2e/api/books.spec.ts`, `README.md`, `docs/architecture.md`, `docs/business-flow.md`.

## Tests
Sin tests nuevos; los existentes migrados a v1. Pest: 60 pasan. Pint: ok.

## Decisiones
Prefijo aplicado con `Route::prefix('v1')` dentro de `routes/api.php` (el prefijo `/api` lo sigue poniendo el framework). Sin redirecciones desde las URLs antiguas.
