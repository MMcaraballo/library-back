# Pruebas de mutación

## Petición
Poner en marcha `pest --mutate --min=80`, que fallaba porque los tests no declaraban `covers()`/`mutates()`.

## Resultado
- MSI inicial (solo con `mutates()`): 67,18 % (131 mutantes).
- MSI final: **99,24 %** (130 eliminados, 1 superviviente). Umbral: 80 %.
- Ejecución secuencial (sin `--parallel`), ~70 s.

## Cambios
- `mutates(...)` en cada fichero de test, asociando las clases de lógica: `app/Actions/{Books,Auth}/*`, `app/Services/*`, `app/Http/Requests/*`, `BookResource`, `BookCollection`, `BookController`, `AuthController` y `CategoryHasBooksException`.
  - Importante: `mutates()` debe ir DESPUÉS de los `use`, porque un nombre corto antes del `use` no se resuelve y Pest lo trata como patrón (llegó a mutar el `BookResource` de Filament).
- Tests nuevos:
  - `tests/Unit/TransactionBoundariesTest.php`: cada Action/Service CRUD abre y confirma una transacción en éxito y hace rollback sin commit en fallo (eventos `TransactionBeginning/Committed/RolledBack`).
  - `tests/Unit/FormRequestRulesTest.php`: cada regla de Store/Update/Index/Login (required, string, min, max, digits, exists, nullable).
  - Ampliados: `BookFilterServiceTest` (categoría `''` y cast a entero), `CategoryServiceTest` (mensaje y payload 409 de la excepción), `CreateBookTest`/`UpdateBookTest` (estructura completa del Resource incl. `created_at`, `updated_at`, `category`).
- `composer.json`: script `test:mutate`.
- README: sección de pruebas de mutación.
- Sin cambios en `app/`; ningún mutante reveló un bug real.

## Mutante superviviente (equivalente)
`LoginRequest` línea 20: quitar `'string'` de `email`. La regla `email` ya rechaza valores no string, por lo que el comportamiento es idéntico; no se puede matar.

## Decisiones
- Las transacciones se verifican con eventos de conexión (funcionan dentro de la transacción de `RefreshDatabase`).
- Los mutantes de `integer` en `published_year` quedan matados/cubiertos por `digits:4`/`max` sin necesidad de tests triviales.
