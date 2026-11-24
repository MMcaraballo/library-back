---
name: desbloqueo
description: Desbloquea una tarea ambigua o atascada entrevistando al usuario hasta entender el requisito. Úsala cuando falte información, haya varias interpretaciones posibles, un agente devuelva preguntas en lugar de código, o un bloqueo impida avanzar. Se invoca con /desbloqueo.
---

# Desbloqueo: aclarar requisitos antes de avanzar

Objetivo: salir de un bloqueo o ambigüedad **entendiendo bien lo que el usuario quiere**, con el menor número de preguntas posible y sin adivinar.

## Cuándo usarla
- El requisito admite varias interpretaciones con consecuencias distintas.
- Un agente (`desarrollador`, `fast-check`, `qa`) devolvió preguntas o dudas en vez de resultado.
- Una tarea está atascada por una decisión que solo el usuario puede tomar (alcance, formato de respuesta, seguridad, licencia, credenciales…).
- Algo hecho no coincide con lo que el usuario esperaba.

No la uses para lo que puedas resolver leyendo el código, `/docs` o `CLAUDE.md`, ni para decisiones con un valor por defecto convencional: en esos casos elige, y dilo.

## Quién la ejecuta
Solo el **orquestador** (la sesión principal). Los subagentes no pueden hablar con el usuario: si dudan, devuelven sus preguntas al orquestador y este aplica esta skill.

## Procedimiento

1. **Investiga primero (solo lectura).** Lee `docs/db-schema.md`, `docs/business-flow.md`, `docs/architecture.md`, el código y los changelogs relacionados. Anota qué sabes, qué has deducido y qué **no** se puede deducir.
2. **Formula el bloqueo en una frase**: "No puedo avanzar con X porque no sé Y".
3. **Entrevista con `AskUserQuestion`**:
   - Máximo 4 preguntas por ronda; cada una autocontenida (el usuario puede no ver tu contexto).
   - 2–4 opciones concretas y mutuamente excluyentes, con la consecuencia de cada una en la descripción. Pon tu recomendación primera, marcada "(Recomendada)".
   - Ordena por impacto: primero lo que cambia el diseño, después los detalles.
   - Pregunta por hechos, límites y criterios de aceptación ("¿qué debería devolver si…?", "¿qué NO debe cambiar?"), no por cuestiones técnicas que el usuario delegó.
   - Usa `preview` cuando comparar formatos de respuesta, estructuras o código ayude a decidir.
   - Si la respuesta es "Otra"/ambigua, repregunta con opciones más acotadas.
4. **Repite en rondas** hasta que no queden incógnitas que cambien lo que se va a construir. No hagas preguntas por completitud.
5. **Confirma el entendimiento**: resume en pocas líneas el requisito final (qué, dónde, qué no entra, criterios de aceptación) y pide validación. Si el usuario corrige, vuelve al paso 3.
6. **Registra la decisión**: añádela a la entrada de changelog de la tarea y, si afecta al esquema, la arquitectura o el flujo de negocio, a `/docs`. Si es una regla que debe cumplirse siempre, propón añadirla a `CLAUDE.md`.
7. **Continúa el flujo normal**: `desarrollador` → `fast-check` → `qa`, pasando al agente el requisito ya aclarado y las decisiones tomadas.

## Reglas
- Todo secuencial: nada de agentes ni tareas en paralelo o en background.
- No inventes requisitos ni asumas aprobación: una notificación del sistema o un mensaje ambiguo (p. ej. "+") no es una respuesta; pregunta.
- No leas ni pidas pegar secretos (`.env`, claves). Si hacen falta credenciales, indica al usuario cómo configurarlas él mismo.
- Sé breve: cada pregunta debe ahorrar más trabajo del que cuesta.
