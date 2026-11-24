# Implementación CRUD de libros y categorías

## Petición
Implementar API de libros (list/create/update/delete con filtro por categoría), auth Sanctum (login/logout), categorías con regla de borrado y backoffice Filament, con TDD (Pest), Actions, Services, FormRequests, Resources y transacciones.

## Qué se hizo
- Migraciones `categories` y `books` (FK `restrictOnDelete`, índice en `category_id`), modelos, factories y seeders (admin@example.com / password).
- Actions `Books/{ListBooks,CreateBook,UpdateBook,DeleteBook}` y `Auth/{IssueToken,RevokeToken}` con transacción begin/commit/rollBack.
- Services `BookFilterService` y `CategoryService`; `CategoryHasBooksException` (409).
- Controladores `BookController`, `AuthController`; FormRequests; `BookResource` y `BookCollection`.
- Rutas con `auth:sanctum` + `throttle:api` (60/min); `throttle:login` (5/min).
- Manejo de errores JSON en `bootstrap/app.php` (401, 404, 429; 409 vía excepción; 422 por defecto).
- Filament: `BookResource` (filtro por categoría), `CategoryResource` (nombre único, borrado individual y masivo vía `CategoryService` con notificación). `User` con `HasApiTokens` y `FilamentUser`.

## Archivos principales
`app/Actions/**`, `app/Services/**`, `app/Http/{Controllers,Requests,Resources}`, `app/Models/{Book,Category,User}.php`, `app/Exceptions/CategoryHasBooksException.php`, `app/Filament/Resources/**`, `app/Providers/AppServiceProvider.php`, `bootstrap/app.php`, `routes/api.php`, `database/{migrations,factories,seeders}`, `tests/Pest.php`.

## Tests
`tests/Feature/Api/Books/*`, `tests/Feature/Api/Auth/AuthTest.php`, `tests/Feature/Filament/*`, `tests/Unit/*` (filtros, servicio de categorías, rollback de Actions). 60 tests en verde.

## Decisiones
- Update/Delete reciben `id` y usan `findOrFail` dentro de la transacción (en lugar de route model binding) para verificar existencia explícitamente.
- `RefreshDatabase` activado globalmente para Feature y Unit en `tests/Pest.php`.
- Mensajes de error en español.
