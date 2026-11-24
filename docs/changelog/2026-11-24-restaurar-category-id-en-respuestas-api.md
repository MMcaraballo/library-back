# Restaurar category_id en respuestas de la API

## Petición
A petición del usuario, se revierte la tarea "ocultar category_id en respuestas de la API" (`2026-11-24-ocultar-category-id-en-respuestas-api.md`, que se conserva como histórico).

## Cambios
- `BookResource`: vuelve a devolver `category_id` y `category` (whenLoaded) con `id` y `name`.
- Tests `tests/Feature/Api/Books/{Create,Update,List}BookTest.php`: exigen `category_id` y `category.id` (incluido el filtro por categoría con `data.0.category_id`); vistos en rojo antes del cambio.
- `e2e/api/books.spec.ts`: eliminadas las comprobaciones de ausencia; ahora verifican presencia (no ejecutado).
- Docs: README.md (ejemplo de respuesta), docs/architecture.md, docs/business-flow.md.

## Decisiones
Requests y filtro `?category={id}` sin cambios.
