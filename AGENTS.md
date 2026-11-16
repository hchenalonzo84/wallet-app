# Instrucciones para Codex

## Continuidad y forma de trabajar
- Lee `docs/codex/STATE.md` al comenzar; para UX/UI consulta también `PRODUCT.md`. Verifica el código y la configuración actuales antes de confiar en documentación antigua.
- Ejecuta tú mismo la inspección, implementación y validaciones autorizadas. Solicita solo aprobaciones necesarias; no delegues al usuario comandos que puedas ejecutar.
- Mantén los cambios dentro del alcance solicitado y conserva trabajo ajeno. Busca implementaciones existentes antes de crear servicios, componentes, hooks, helpers, tipos o abstracciones.
- Actualiza `STATE.md` al terminar cambios materiales; mantenlo compacto, sin diario ni backlog inventado. Las reglas permanentes pertenecen aquí.

## Arquitectura y código
- Laravel reside en `backend/src`; la SPA React/TypeScript está en `backend/src/resources/js` y los estilos en `resources/css`. `web/` y `mobile/` no son las aplicaciones implementadas.
- Conserva la arquitectura: controladores y Form Requests, servicios financieros en `app/Services`, modelos en `app/Models`; React usa `services`, `types`, contexto/proveedor de autenticación y router existentes.
- Respeta las versiones de manifests, lockfiles, Dockerfiles y `compose.yaml`. No fuerces igualdad de revisiones Node entre Windows y contenedores.
- TypeScript permanece estricto; usa interfaces y tipos explícitos, y `unknown` cuando corresponda. Evita `any` salvo justificación técnica real. Escribe comentarios breves solo para decisiones no evidentes.
- Reutiliza dependencias. Antes de añadir una, comprueba alternativas existentes, mantenimiento, compatibilidad, seguridad e impacto en bundle, y explica su necesidad.

## Autenticación y finanzas
- API/clientes móviles: Sanctum con Bearer tokens. Web: sesión Laravel, cookies y CSRF, con API stateful de Sanctum. No guardes tokens Bearer de autenticación web en `localStorage`; no cambies estos mecanismos sin autorización explícita.
- Los balances derivan del libro de movimientos en Laravel. React presenta los importes y consume la API; no implementa cálculos financieros ni reglas de negocio independientes.
- Los movimientos existentes son inmutables mediante la API. Conserva aislamiento por usuario, precisión decimal y transacciones/bloqueos existentes; no sustituyas BCMath/NUMERIC por aritmética flotante financiera.
- Bolsillos y pagos recurrentes se desactivan lógicamente para preservar el historial. Respeta la idempotencia del procesamiento recurrente.

## Infraestructura y seguridad
- Administra servicios, reconstrucciones e imágenes mediante Docker Compose y `compose.yaml`; evita procedimientos paralelos. Desarrollo: Windows 11/VS Code/Git Bash; producción: Ubuntu Server y acceso privado con Tailscale.
- Las migraciones de producción son manuales. Nunca agregues migraciones automáticas al Dockerfile, Compose, entrypoint o arranque. No uses `composer setup` para operar producción: contiene migraciones y generación de claves.
- No elimines volúmenes sin autorización explícita. Nunca ejecutes operaciones destructivas sobre PostgreSQL de producción.
- No muestres, copies ni modifiques innecesariamente `.env`, APP_KEY, contraseñas, tokens, claves API o credenciales PostgreSQL/Tailscale. No pidas secretos completos en el chat ni imprimas configuración Compose con secretos interpolados.

## Git y validación
- Flujo: feature branch → `dev` → `qa` → `main` → producción. No desarrolles funcionalidades directamente en `main`. Prefiere `git switch` y `git branch -vv`.
- No hagas push, merge, rebase destructivo, force push, publicación, despliegue ni eliminación de ramas remotas salvo solicitud explícita. Haz commits solo cuando se soliciten; para funcionalidades usa `feature:`, nunca `feat:`.
- Ejecuta pruebas relacionadas con el cambio. Antes de QA comprueba validaciones relevantes; QA debe ejecutar la suite completa antes de promover a `main`.
- Según el cambio: TypeScript (`npx tsc --noEmit`), Vite (`npm run build`) y `npm audit` desde `backend/src`; pruebas Laravel desde ese directorio o mediante `docker compose exec api php artisan test`; comprobación visual para UI y `git diff --check`.
- Antes de pruebas Laravel verifica el destino exclusivo `wallet_app_testing` y conserva las protecciones de `tests/bootstrap.php` y `tests/TestCase.php`; no ejecutes pruebas contra datos reales. No ejecutes tareas costosas ajenas al cambio. Informa qué se validó y qué no pudo validarse.

## UX/UI y skills locales
- Skills del proyecto: `.agents/skills`. Usa `$impeccable` como dirección visual principal; `refactoring-ui` apoya jerarquía, espaciado, tipografía, contraste y consistencia; `ux-heuristics` audita navegación, feedback, errores, fricción y carga cognitiva.
- Establece una dirección visual y después audítala; no ejecutes tres rediseños independientes. Respeta patrones aprobados y el alcance de cada solicitud.
- Prioriza claridad, accesibilidad, jerarquía, consistencia, responsive y estética, en ese orden. Mantén una interfaz sobria, moderna, limpia y profesional, con lectura financiera clara.
- Carga las skills pertinentes solo cuando la tarea lo necesite. No instales colecciones completas, otras skills, MCP o frameworks UI sin autorización.
