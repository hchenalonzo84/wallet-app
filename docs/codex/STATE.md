# Estado operativo de Wallet App

Inspección de bootstrap: 2026-11-16. Describe archivos y Git locales; no certifica el estado de producción.

## Estado funcional

- Backend implementado: autenticación API/Sanctum y web/sesiones; bolsillos con desactivación lógica; movimientos de creación/consulta sin edición/eliminación; balances; transferencias internas; reportes por período; pagos recurrentes con procesamiento programado.
- Web implementada en `backend/src/resources/js`: login, registro, inicio protegido y gestión de bolsillos con saldo, creación, edición, desactivación y reactivación. Reutiliza `AppHeader`, `PocketForm`, `ConfirmDialog` (Radix), `ProtectedRoute`, servicios HTTP y `AuthProvider`.
- El inicio marca movimientos, transferencias, reportes y pagos recurrentes como «Próximamente»; no tienen rutas/pantallas propias en el router actual.
- `web/` y `mobile/` contienen solo `.gitkeep`; no hay aplicación React Native/Expo implementada.

## Stack comprobado

- `composer.json`: PHP `^8.3`, Laravel `^13.17`, Sanctum `^4.0`; `composer.lock`: Laravel **13.26.1**, Sanctum **4.3.3**. Imagen PHP **8.4.27-fpm-bookworm**, con BCMath y PDO PostgreSQL.
- `package-lock.json`: React/React DOM **19.2.8**, TypeScript **7.0.2**, Vite **8.3.5**, Tailwind **4.3.3**, React Router DOM **7.18.3**, Radix Alert Dialog **1.2.1**. `tsconfig.json` tiene `strict: true`.
- Dockerfiles/Compose: Node **26.12.1**, npm **12.0.2**, PostgreSQL **18.4-alpine3.24**, Nginx **1.30.6-alpine3.24**. Host observado: Node **26.13.0**, npm **11.20.0**, Compose **5.6.1**; la diferencia de Node/npm host-contenedor no requiere alineación.

## Arquitectura relevante

- Laravel y SPA comparten `backend/src`; Blade carga React/Vite y el fallback web excluye `/api`. Router, `services`, `types`, `contexts`, `providers` y `hooks` existentes evitan duplicación.
- `bootstrap/app.php` activa API stateful de Sanctum. La web usa sesión/cookies/CSRF; Bearer queda para API/móvil. `services/http.ts` incluye cookies y cabecera XSRF.
- `BalanceService` agrega movimientos con NUMERIC en PostgreSQL. Servicios de transferencias, reportes y recurrentes usan precisión decimal; transferencias y procesamiento recurrente usan transacciones/bloqueos. Los recurrentes tienen protección de unicidad por ocurrencia.
- Compose define `database`, `api`, `scheduler`, `nginx`, `pgadmin`, `frontend` (perfil development) y `frontend-build` (perfil production). El scheduler ejecuta `schedule:work`; el comando recurrente está programado cada hora sin solapamiento.
- El entrypoint PHP sincroniza dependencias con `composer.lock`, sin ejecutar migraciones. `composer setup` y scripts de creación del esqueleto sí contienen migraciones: no usarlos como arranque ni procedimiento de producción.

## Última funcionalidad terminada

Gestión web de bolsillos (`feature: agrega gestion web de bolsillos`), integrada por PR #29 en `dev`, #30 en `qa` y #31 en `main`, según historial local y código. Los cambios de seguridad del stack preceden a esa funcionalidad.

## Estado Git

- Ramas locales permanentes `dev`, `qa`, `main`, cada una con upstream `origin` correspondiente. Rama inspeccionada: `main`; árbol inicialmente limpio. No se consultó ni actualizó el remoto.
- Flujo vigente: feature branch → dev → qa → main → producción; QA exige suite completa. Bootstrap sin cambio de rama, commit, push, merge ni despliegue.

## Pendientes conocidos y discrepancias

- README raíz desactualizado: declara planificación/inicialización y presenta React Native/Expo como aplicación; el backend y la web ya tienen funcionalidad y `mobile/` sigue vacío. README de Laravel es el del esqueleto, no documentación funcional de Wallet App. No se modificaron por quedar fuera del bootstrap.
- Pantallas de movimientos, transferencias, reportes y recurrentes siguen pendientes según el inicio y router actuales; no se agrega backlog adicional.

## Validaciones recientes

- Inspeccionados rutas, servicios, React, manifests/lockfiles, Docker, Git y pruebas. Existen pruebas Feature para autenticación API/web, registro web, bolsillos, movimientos, balances, transferencias, reportes y recurrentes/procesamiento/comando; no hay runner de tests frontend configurado en `package.json`.
- PHPUnit fuerza PostgreSQL `wallet_app_testing` antes de iniciar Laravel; `TestCase` comprueba entorno/base y rechaza configuración cacheada. No se ejecutaron pruebas de producto, builds ni auditorías de dependencias durante este cambio documental.
- `docker compose ps` no pudo conectar con Docker Desktop Linux Engine. No se iniciaron servicios ni se tocaron bases/volúmenes. PowerShell bloquea `npm.ps1`; se usaron `npm.cmd`/`npx.cmd` sin cambiar la política del sistema.
- `npx skills list --agent codex` reconoce exactamente las tres skills locales; listado global sin skills adicionales. Motor Impeccable comprobado con `engine-probe`: **0.1.24**.

## Decisiones vigentes

- Reglas permanentes en `AGENTS.md`; contexto de producto confirmado por la solicitud y código en `PRODUCT.md`. Actualizar este estado únicamente tras cambios materiales.
- Skills locales en `.agents/skills`: **impeccable 4.9.1**, **refactoring-ui 1.5.1**, **ux-heuristics 1.6.0**. Impeccable dirige UI; Refactoring UI apoya consistencia; UX Heuristics audita usabilidad.
- Impeccable instalado mediante `npx impeccable install --providers=codex --scope=project --no-hooks --yes`; el instalador guardó el motor auxiliar en `~/.impeccable/bin/0.1.24`, no una skill global. Sin hooks ni rediseño. Wondel instalado con selección explícita de cada skill y agente Codex; procedencia fijada en `skills-lock.json` (solo las dos Wondel; Impeccable lo gestiona su instalador).
- Las nuevas skills pueden requerir una nueva sesión/recarga de Codex para incorporarse al catálogo de la extensión. La verificación por CLI y archivos no certifica actualización en caliente de la sesión actual.
- Balances derivados del libro, movimientos inmutables por API, reglas financieras en backend, autenticación dual y migraciones de producción manuales se mantienen.
