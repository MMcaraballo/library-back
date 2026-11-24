# Ocultar category_id en respuestas de la API

## Petición
Las respuestas no deben exponer `category_id` ni `category.id`; el `id` del libro se mantiene (decisión confirmada con el usuario).

## Cambios
- `BookResource`: eliminados `category_id` y `category.id`; `category` (whenLoaded) devuelve solo `name`.
- Las Actions List/Create/Update ya cargaban `category` con Eloquent; sin cambios.
- Requests (`category_id`) y filtro `?category={id}` sin cambios.

## Tests
- Actualizados `tests/Feature/Api/Books/{Create,Update,List}BookTest.php`: nueva estructura y aserciones de ausencia de `category_id` y `category.id` (vistos en rojo antes del cambio).
- `e2e/api/books.spec.ts` actualizado (no ejecutado).

## Docs
README.md (ejemplo de respuesta), docs/architecture.md, docs/business-flow.md.

## Resultados
Pest 112 pasados; mutación 99.22% (1 mutante sin cubrir preexistente en LoginRequest).
