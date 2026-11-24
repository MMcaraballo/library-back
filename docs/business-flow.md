# Flujo de negocio

Gestión de una biblioteca de libros organizados por categoría.

## Actores
- **Administrador / usuario autenticado**: usa la API con token Sanctum y el backoffice Filament.

## Casos de uso

### Autenticación (API)
1. `POST /api/v1/login` con email y contraseña → devuelve `{ "token": ... }` (Sanctum). Credenciales inválidas → 422. Throttle propio: 5 intentos/min. `POST /api/v1/logout` revoca el token actual (204).
2. El token se envía como `Authorization: Bearer <token>`. Sin token → 401.
3. Todas las rutas tienen límite de peticiones por minuto (60/min) → 429 al excederlo.

### Libros (API y Filament)
| Caso | Endpoint | Resultado |
|---|---|---|
| Listar | `GET /api/v1/books` | 200 con colección paginada de 50 libros por página (tamaño fijo, sin `per_page`; `links` y `meta`) |
| Filtrar por categoría | `GET /api/v1/books?category={id}` | 200 solo con libros de esa categoría; categoría inexistente → 422 |
| Crear | `POST /api/v1/books` | 201 con el libro creado |
| Editar | `PUT /api/v1/books/{id}` | 200 con el libro actualizado; no existe → 404 |
| Eliminar | `DELETE /api/v1/books/{id}` | Verifica que el libro existe; 204 si se elimina, 404 si no existe |

### Categorías
- Listar (API): `GET /api/v1/categories` → 200 con `{ "data": [{ "id", "name" }] }` ordenado por nombre, sin paginar (lista vacía → `data: []`); 401 sin token, 429 al exceder el límite.
- Crear/editar/eliminar: solo en Filament.
- Crear: el nombre debe ser único.
- Eliminar: **solo si no tiene libros asociados**; en caso contrario se bloquea con un mensaje (`CategoryHasBooksException`, equivalente a 409).

## Reglas de validación (libros)
- `title`: obligatorio, mínimo 3 caracteres.
- `author`: obligatorio.
- `description`: opcional.
- `published_year`: obligatorio, año válido (entero de 4 dígitos, no mayor al año actual).
- `category_id`: obligatorio, debe existir en `categories`. Las respuestas devuelven `category_id` y `category: { id, name }`.

## Reglas de negocio
1. Verificar la existencia del libro antes de eliminarlo.
2. No se puede eliminar una categoría con libros asociados.
3. Toda operación CRUD se ejecuta dentro de una transacción; ante error se hace rollback.

## Códigos HTTP
`200` OK · `201` creado · `204` sin contenido · `401` no autenticado · `404` no encontrado · `409` conflicto de negocio · `422` validación · `429` demasiadas peticiones.
