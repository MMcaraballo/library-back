# 2026-11-24 — Setup de metodología y proyecto

## Petición
Definir la metodología de trabajo (agentes, TDD, guardrail, docs, flujo) antes de implementar el proyecto de biblioteca.

## Hecho
- Instalado `laravel/boost` (dev) y generadas sus guías en `CLAUDE.md`/`AGENTS.md`.
- `git init`.
- Agentes en `.claude/agents/`: `desarrollador`, `fast-check`, `qa`.
- `CLAUDE.md`: lectura obligatoria de docs al iniciar sesión, flujo entre agentes, reglas secuenciales (sin worktrees ni background/paralelo), convenciones. `AGENTS.md` es copia de `CLAUDE.md`.
- Guardrail: `.claude/hooks/guard.sh` + `.claude/settings.json` (PreToolUse), basado en el patrón *Dangerous Command Blocker* de claude-code-templates. Probado con 13 casos (bloquea/permite según lo esperado).
- `/docs`: `db-schema.md`, `architecture.md`, `business-flow.md`, `changelog/`.

## Notas
- Limitación del guardrail: es por coincidencia de texto, así que un heredoc que contenga la cadena de un comando prohibido también se bloquea (falso positivo); usar Write/Edit para escribir ese texto.
- Pendiente: dependencias (Sanctum, Filament, Pest), PostgreSQL, implementación, E2E, README y mutation testing.
