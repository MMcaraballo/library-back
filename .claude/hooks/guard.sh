#!/usr/bin/env bash
# Guardrail PreToolUse para library-backoffice.
# Basado en el patrón "Dangerous Command Blocker" de davila7/claude-code-templates (aitmpl.com),
# ampliado con reglas del proyecto: ejecución secuencial y protección de secretos.
# Entrada: JSON del hook por stdin. Salida: exit 2 + motivo en stderr para bloquear.

set -uo pipefail

INPUT="$(cat)"
TOOL="$(jq -r '.tool_name // empty' <<<"$INPUT")"

deny() {
  echo "GUARDRAIL: $1" >&2
  exit 2
}

case "$TOOL" in
  Bash)
    CMD="$(jq -r '.tool_input.command // empty' <<<"$INPUT")"
    BG="$(jq -r '.tool_input.run_in_background // false' <<<"$INPUT")"

    [[ "$BG" == "true" ]] && deny "Prohibido ejecutar en background: todas las tareas son secuenciales."

    # Comandos destructivos del sistema de archivos
    grep -Eq '(^|[;&|[:space:]])rm[[:space:]]+(-[a-zA-Z]*[rf][a-zA-Z]*[[:space:]]+)+(/|~|\$HOME|\.\.?)([[:space:]]|/?$|/\*)' <<<"$CMD" \
      && deny "rm -rf sobre raíz, home o directorios padre."
    grep -Eq '(^|[;&|[:space:]])(mkfs|dd[[:space:]]+if=|shred)\b' <<<"$CMD" && deny "Comando destructivo de disco."
    grep -Eq 'chmod[[:space:]]+(-R[[:space:]]+)?0?777' <<<"$CMD" && deny "chmod 777 no permitido."
    grep -Eq '(curl|wget)[^|]*\|[[:space:]]*(sudo[[:space:]]+)?(ba|z)?sh' <<<"$CMD" && deny "Prohibido piping de descargas a un shell."

    # Git destructivo
    grep -Eq 'git[[:space:]]+push[^;&|]*(--force|[[:space:]]-f\b|--force-with-lease)' <<<"$CMD" && deny "git push --force no permitido."
    grep -Eq 'git[[:space:]]+reset[[:space:]]+--hard' <<<"$CMD" && deny "git reset --hard no permitido."
    grep -Eq 'git[[:space:]]+clean[[:space:]]+-[a-zA-Z]*f' <<<"$CMD" && deny "git clean -f no permitido."
    grep -Eq 'git[[:space:]]+(checkout|restore)[[:space:]]+(--[[:space:]]+)?\.($|[[:space:]])' <<<"$CMD" && deny "Descartar todos los cambios no permitido."
    grep -Eq 'git[[:space:]]+branch[[:space:]]+-D' <<<"$CMD" && deny "git branch -D no permitido."
    grep -Eq 'git[[:space:]]+worktree' <<<"$CMD" && deny "Prohibido usar git worktree: todo el trabajo es secuencial en el árbol principal."

    # Base de datos destructiva
    grep -Eiq 'drop[[:space:]]+(database|schema)|truncate[[:space:]]+table' <<<"$CMD" && deny "Operación destructiva de base de datos."
    if grep -Eq 'artisan[[:space:]]+(migrate:fresh|migrate:reset|db:wipe)' <<<"$CMD"; then
      grep -Eq '(--env[= ](testing|e2e))|APP_ENV=(testing|e2e)' <<<"$CMD" \
        || deny "migrate:fresh/reset/db:wipe solo con --env=testing o --env=e2e."
    fi

    # Secretos
    grep -Eq '(cat|less|more|head|tail|bat|grep|sed|awk|cp|mv|source|\.)[[:space:]]+[^;&|]*\.env(\.[a-z]+)?([[:space:]]|$)' <<<"$CMD" \
      && ! grep -Eq '\.env\.example' <<<"$CMD" && deny "Acceso a .env no permitido (usa .env.example)."
    grep -Eq '(printenv|env)[[:space:]]*($|[|;&])' <<<"$CMD" && deny "Volcar variables de entorno no permitido."
    ;;

  Read|Edit|Write|MultiEdit)
    FILE="$(jq -r '.tool_input.file_path // empty' <<<"$INPUT")"
    case "$FILE" in
      *.env.example) ;;
      */.env|*/.env.*|.env|.env.*) deny "Acceso a archivos .env no permitido." ;;
      *.key|*.pem|*/auth.json|*/id_rsa*|*/.ssh/*) deny "Acceso a credenciales/llaves no permitido." ;;
      */vendor/*|*/node_modules/*)
        [[ "$TOOL" != "Read" ]] && deny "No se editan dependencias (vendor/node_modules)." ;;
    esac
    ;;

  EnterWorktree)
    deny "Prohibido usar worktrees: todas las tareas son secuenciales."
    ;;

  Agent|Task)
    ISO="$(jq -r '.tool_input.isolation // empty' <<<"$INPUT")"
    BG="$(jq -r '.tool_input.run_in_background // false' <<<"$INPUT")"
    [[ -n "$ISO" ]] && deny "Prohibido aislar agentes en worktrees: todo es secuencial."
    [[ "$BG" == "true" ]] && deny "Prohibido lanzar agentes en background: todo es secuencial."
    ;;
esac

exit 0
