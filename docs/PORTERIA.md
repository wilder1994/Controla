# Portería (`/access`)

**Última actualización:** 17 septiembre 2026

Consola de **turno del vigilante**. Login rol `guardia` → `/access`. El admin de empresa entra con **Operar portería**. El supervisor de vigilancia **no** usa este panel (solo APK/PWA).

## Empleados vs personas

| | **Empleado** | **Persona** |
|--|--------------|-------------|
| Quién | Colaborador de la **empresa de seguridad** | Gente de la **instalación** (censo) |
| Tabla | ficha `employees` | `structure_members` en un **nodo** |
| Dónde | `/company/employees` | `/client/members` (y consulta en portería) |
| Ejemplos | vigilante, supervisor, admin interno | residente, alumno, ocupante del predio |
| En portería | Opera el turno (usuario + puerta) | Entra, sale o se busca por nodo |

El vigilante es **empleado** asignado a un **puesto**. No es una persona del censo. Un **visitante** no es empleado ni persona del nodo.

Detalle de fichas de empresa: [`EMPLEADOS-Y-CARGOS.md`](EMPLEADOS-Y-CARGOS.md). Censo: [`CLIENTES-Y-ESTRUCTURA.md`](CLIENTES-Y-ESTRUCTURA.md).

## Puerta de operación

Tabla `locations` (no confundir con **puesto**). Solo el **vigilante** (`guardia`) al entrar a `/access`. **Operar portería** desde el expediente no pide puerta ni turno.

1. **Una puerta activa:** el sistema la liga al vigilante. No pide elegir.
2. **Varias puertas:** el vigilante elige al abrir turno. Queda en `guard_shifts.location_id` y en sesión.
3. Sin puertas activas el vigilante no opera.

El pánico del vigilante usa **ese usuario + esa puerta**. El admin de empresa en portería dispara pánico del cliente (sin elegir puerta).

## Pánico

Un solo canal: el de admin cliente, admin instalaciones y supervisor.

- Clic → `POST /access/ops/panic` (sin modal ni minuta).
- Overlay + sonido en la empresa (`/company/ops/alerts.json`). **Enterado** / **Atender** (`/company/panics`).
- Quien pulsa **no** oye su propio pánico.
- GPS si el navegador lo da.

Se eliminó el pánico viejo (`POST /access/guard_logs/panic`, modal de minuta `is_panic`). Las minutas no disparan pánico.

## Menú

Resumen · Ingreso y salida · Personas · Vehículos · Mascotas · Correspondencia · Autorizaciones · Reservas · Minutas · Lista de bloqueo · Instalaciones · Visitantes · Turnos.

## Ingreso y salida

Una sola pantalla: peatón o vehículo, censo (`structure_members`) o visitante. Busca documento/nombre/placa; si no existe, se da de alta al vuelo (visitante por documento; persona del censo exige nodo). Fotos opcionales con cámara del PC (`photo_path` / `vehicle_photo_path` en `access_logs`). La puerta es la del turno/sesión. Bloqueados no entran.

Personas / vehículos / mascotas / instalaciones leen el censo del cliente (no `employees`). Visitantes: pestaña personas y pestaña vehículos de visita. Autorizaciones: `visitor_pre_authorizations`. Reservas: zonas comunes.

Pánico: ver arriba. Tests: `PorteriaDoorAndPanicTest`, `PorteriaConsoleTest`.
