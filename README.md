# Library Backoffice

API REST y backoffice para gestionar una biblioteca de libros organizados por categoría.

- **Backoffice**: [Filament](https://filamentphp.com) en `/admin` (libros y categorías).
- **API**: Laravel + Laravel Sanctum en `/api/v1` (CRUD de libros con filtro por categoría y listado de categorías), con límite de peticiones por minuto.
- **Base de datos**: PostgreSQL.
- **Tests**: Pest (TDD), pruebas de mutación (`pest --mutate`) y E2E con Playwright.

## Funcionalidades
- Listar, crear, editar y eliminar libros (cada libro pertenece a una categoría).
- Filtrar libros por categoría: `GET /api/v1/books?category={id}`.
- Listar categorías: `GET /api/v1/categories`.
- Categorías con nombre único, gestionadas desde Filament; **no se puede eliminar una categoría con libros** (notificación de error en Filament).
- Se verifica que el libro exista antes de eliminarlo (404 si no existe).

## Requisitos
| Herramienta | Versión |
|---|---|
| PHP | >= 8.3 (extensiones `pdo_pgsql`, `pgsql`, `mbstring`, `xml`, `intl`, `curl`, `zip`) |
| Composer | 2.x |
| PostgreSQL | 14+ |
| Node.js / npm | 20+ (para assets y Playwright) |
| PCOV o Xdebug | opcional, solo para cobertura y pruebas de mutación |

## Puesta en marcha

### 1. Dependencias
```bash
composer install
npm install
```

### 2. Entorno
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Bases de datos PostgreSQL
Crea un rol y tres bases de datos (desarrollo, tests y E2E):
```bash
sudo -u postgres psql -c "CREATE ROLE library LOGIN PASSWORD 'tu_clave' CREATEDB;"
sudo -u postgres createdb -O library library_backoffice
sudo -u postgres createdb -O library library_backoffice_test
sudo -u postgres createdb -O library library_backoffice_e2e
```
Edita `.env` con tus credenciales:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=library_backoffice
DB_USERNAME=library
DB_PASSWORD=tu_clave
```
Los tests usan `library_backoffice_test` (definida en `phpunit.xml`) y el E2E `library_backoffice_e2e`, con las mismas credenciales.

### 4. Migraciones y datos de ejemplo
```bash
php artisan migrate --seed
```
Crea el usuario administrador, categorías y libros de ejemplo.

| Usuario | Contraseña |
|---|---|
| `admin@example.com` | `password` |

> Cambia estas credenciales fuera de entornos locales.

### 5. Servidor
```bash
php artisan serve        # http://localhost:8000
```
- Backoffice: <http://localhost:8000/admin>
- API: <http://localhost:8000/api/v1>

## API
Todas las rutas (excepto `login`) requieren `Authorization: Bearer <token>` (Sanctum) y están limitadas a **60 peticiones/min**. El login está limitado a **5 intentos/min**.

| Método | Ruta | Descripción | Respuestas |
|---|---|---|---|
| POST | `/api/v1/login` | Emite token | 200, 422, 429 |
| POST | `/api/v1/logout` | Revoca el token actual | 204, 401 |
| GET | `/api/v1/categories` | Lista de categorías ordenada por nombre, sin paginar (`{ "data": [{id, name}] }`) | 200, 401, 429 |
| GET | `/api/v1/books` | Lista paginada (50 por página, tamaño fijo; incluye `links` y `meta`); filtro `?category={id}` | 200, 401, 422 |
| POST | `/api/v1/books` | Crea un libro | 201, 401, 422 |
| PUT | `/api/v1/books/{id}` | Edita un libro | 200, 401, 404, 422 |
| DELETE | `/api/v1/books/{id}` | Elimina un libro | 204, 401, 404 |

Ejemplo:
```bash
TOKEN=$(curl -s -X POST localhost:8000/api/v1/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"password"}' | jq -r .token)

curl -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" localhost:8000/api/v1/categories

curl -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" "localhost:8000/api/v1/books?category=1"

curl -X POST localhost:8000/api/v1/books \
  -H 'Accept: application/json' -H 'Content-Type: application/json' -H "Authorization: Bearer $TOKEN" \
  -d '{"title":"Cien años de soledad","author":"Gabriel García Márquez","published_year":1967,"category_id":1}'
```

**Validaciones de libro**: `title` obligatorio (mín. 3 caracteres), `author` obligatorio, `description` opcional, `published_year` obligatorio (año de 4 dígitos, no futuro), `category_id` obligatorio y existente.

**Respuesta de libro** (`data` en create/update, cada elemento en list). Incluye `category_id` y `category` (`id`, `name`):

```json
{
  "data": {
    "id": 1,
    "title": "Cien años de soledad",
    "author": "Gabriel García Márquez",
    "description": null,
    "published_year": 1967,
    "category_id": 1,
    "category": { "id": 1, "name": "Novela" },
    "created_at": "2026-11-24T10:00:00.000000Z",
    "updated_at": "2026-11-24T10:00:00.000000Z"
  }
}
```

## Tests

```bash
./vendor/bin/pest                         # unit + feature (PostgreSQL de test)
vendor/bin/pint --dirty --format agent    # formato de código
```

### Pruebas de mutación
Evitan regresiones comprobando que los tests detectan cambios introducidos en el código. Requieren un driver de cobertura:
```bash
sudo apt install php8.5-pcov              # o: pecl install pcov
composer test:mutate                      # = pest --mutate --min=80 (secuencial, sin --parallel)
```
Cada fichero de test declara con `mutates(...)` las clases que ejercita (Actions, Services, FormRequests, Resources, controladores API y `CategoryHasBooksException`). Resultado actual: **MSI 99,28 %** (138 mutantes probados, 1 sin cubrir; mínimo exigido 80 %). El único superviviente es equivalente (ver `docs/changelog/2026-11-24-pruebas-de-mutacion.md`).

### E2E (Playwright)
```bash
npx playwright install chromium           # primera vez
npm run test:e2e
```
Arranca su propio servidor en el puerto 8001, resetea `library_backoffice_e2e` con `migrate:fresh --seed` y ejecuta los tests en secuencia (`workers: 1`). Cubre la API y la UI de Filament.

## Estructura
```
app/Actions/{Books,Categories,Auth}  Casos de uso (patrón Actions, un caso por clase)
app/Services               Lógica de apoyo (filtro de libros, reglas de categorías)
app/Http                   Controllers (delgados), FormRequests, Resources
app/Filament               Panel /admin
database/                  Migraciones, factories, seeders
tests/                     Pest (Feature, Unit)
e2e/                       Playwright
docs/                      Esquema de BD, arquitectura, flujo de negocio, changelog
.claude/                   Agentes y guardrail
```

## Documentación y metodología
- [`docs/db-schema.md`](docs/db-schema.md) · [`docs/architecture.md`](docs/architecture.md) · [`docs/business-flow.md`](docs/business-flow.md) · [`docs/changelog/`](docs/changelog)
- El desarrollo sigue un flujo secuencial de tres agentes (`desarrollador` → `fast-check` → `qa`) con TDD y un guardrail de comandos; ver [`CLAUDE.md`](CLAUDE.md).

## Licencia
MIT.
