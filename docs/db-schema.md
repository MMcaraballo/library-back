# Esquema de base de datos

Motor: **PostgreSQL**. Bases de datos: `library_backoffice` (desarrollo), `library_backoffice_test` (tests), `library_backoffice_e2e` (E2E).

## Diagrama

```mermaid
erDiagram
    categories ||--o{ books : "tiene"
    categories {
        bigint id PK
        varchar name UK "requerido, único"
        timestamp created_at
        timestamp updated_at
    }
    books {
        bigint id PK
        varchar title "requerido, mín. 3 caracteres (validación)"
        varchar author "requerido"
        text description "opcional"
        smallint published_year "requerido, año válido"
        bigint category_id FK "categories.id, RESTRICT"
        timestamp created_at
        timestamp updated_at
    }
```

## Tablas

### `categories`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint (autoincremental) | PK |
| name | varchar(255) | NOT NULL, UNIQUE |
| created_at / updated_at | timestamp | nullable |

### `books`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint (autoincremental) | PK |
| title | varchar(255) | NOT NULL |
| author | varchar(255) | NOT NULL |
| description | text | NULL |
| published_year | smallint unsigned | NOT NULL |
| category_id | bigint | NOT NULL, FK → `categories.id`, `ON DELETE RESTRICT`, índice |
| created_at / updated_at | timestamp | nullable |

**Relaciones Eloquent**: `Category hasMany Book`; `Book belongsTo Category`.

**Integridad**: `ON DELETE RESTRICT` impide a nivel de BD borrar una categoría con libros (refuerza la regla de negocio, que además se valida en código).

## Tablas de infraestructura (skeleton de Laravel)
`users` (acceso al panel Filament y a la API), `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, y `personal_access_tokens` (Laravel Sanctum).

## Seeders
`UserSeeder` (usuario administrador), `CategorySeeder`, `BookSeeder`; orquestados por `DatabaseSeeder`.
