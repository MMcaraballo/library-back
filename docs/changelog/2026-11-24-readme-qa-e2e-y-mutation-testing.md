# 2026-11-24 — README, QA E2E y evaluación de mutation testing

## Hecho
- **Flujo completo ejecutado** en secuencia: `desarrollador` (60 tests Pest en verde) → `fast-check` (APROBADO, sin diferencias bloqueantes) → `qa` (27/27 tests Playwright en verde).
- **QA E2E**: `playwright.config.ts` (`workers: 1`, puerto 8001, BD `library_backoffice_e2e`), `e2e/global-setup.ts`, specs de API y Filament; script `npm run test:e2e`.
- **README** reescrito: descripción, requisitos, instalación paso a paso, BD PostgreSQL, seeders y credenciales, API con ejemplos, tests, E2E, estructura.

## Mutation testing: factibilidad
- **Factible**: Pest trae `--mutate` de forma nativa.
- **Bloqueo actual**: `pest --mutate` falla con *"Mutation testing requires code coverage to be enabled"* porque no hay PCOV ni Xdebug instalados.
- **Acción pendiente (usuario)**: `sudo apt install php8.5-pcov` (o `pecl install pcov`) y ejecutar `./vendor/bin/pest --mutate --min=80`. Anotar el resultado aquí.

## Observaciones de QA (no son fallos)
- La interfaz de Filament está en inglés salvo etiquetas y mensajes propios; si se localiza, hay que ajustar los selectores E2E.
- El 429 solo se prueba en login (5/min), no el límite general de 60/min.
- Los tests E2E de UI dependen de las categorías sembradas y se ejecutan en modo `serial`.
