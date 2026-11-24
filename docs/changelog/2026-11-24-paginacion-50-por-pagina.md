# Paginación de 50 libros por página

## Qué se pidió
`GET /api/v1/books` debe paginar con 50 libros por página (antes 15). Tamaño fijo: el cliente no puede cambiarlo (sin `per_page`); se mantienen `links` y `meta`.

## Decisión
La decisión de paginar el listado la tomó el orquestador sin consultar al usuario; posteriormente el usuario fijó el tamaño en 50 por página.

## Qué se hizo
- `ListBooks`: método estático `perPage()` (devuelve 50; una constante quedaba sin cobertura y generaba mutantes no cubiertos); se elimina el parámetro `$perPage` de `handle()` (tamaño fijo) y se usa `paginate(self::perPage())`.
- El controlador no cambia (sin lógica).
- Docs actualizadas: README.md, docs/architecture.md, docs/business-flow.md.

## Archivos tocados
- app/Actions/Books/ListBooks.php
- tests/Feature/Api/Books/ListBooksTest.php
- README.md, docs/architecture.md, docs/business-flow.md

## Tests
- Listado con 51 libros: página 1 con 50, `meta.per_page` 50, `meta.total` 51.
- Página 2 con 1 libro.
- `per_page` del cliente se ignora.
- Sin cambios necesarios en tests Unit ni en e2e/api/books.spec.ts (no asumían 15; e2e no ejecutados).
