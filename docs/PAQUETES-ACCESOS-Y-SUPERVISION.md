# Paquetes Accesos y Supervisión Pro

Controla vende **dos paquetes** con la misma matriz de cupo (1 · 5 · 10 · 50 · 100) y descuentos de volumen. El súper admin en `/admin/pricing` solo edita **unitarios**; la matriz se calcula.

## Catálogo vs operación

La empresa puede crear **todas las fichas de cliente** que necesite (razón social, documento, dirección). Eso **no consume cupo**.

El cupo son **líneas de servicio** en cada ficha:

| Flag | Paquete | Consume |
|------|---------|---------|
| `has_access` | Accesos (`max_clients`) | 1 asiento |
| `has_supervision` | Supervisión Pro (`max_supervision_clients`) | 1 asiento |
| Ambos | Accesos + Pro | 1 + 1 |
| Ninguno | Solo ficha | 0 |

## Accesos

- SKU `pack_{n}_{manual|hardware}`.
- Incluye portería, censo y **supervisión básica en puesto** (código + minuta).
- Copy comercial: *«Incluida supervisión básica en puesto»*.

## Supervisión Pro

- SKU `sup_{n}`. Un unitario, sin hardware.
- Empresa con Accesos: Pro queda en **0** hasta que plataforma o checkout asignen el SKU.
- GPS de supervisores (~30 s), turnos, historial/replay en `/company/supervision`.
- Revista **en la app Pro**, ligada al puesto. **No se vuelve a firmar en portería**: Pro llena la misma minuta (`guard_logs` tipo `revista` si hay punto de acceso).

## Ficha cliente (UI)

Tabs: **Cliente** siempre; **Accesos** si `has_access`; **Supervisión** si `has_supervision`.

Aterrizaje: una línea → esa vista; ambas → Cliente; ninguna → Cliente.

«Operar portería / Operar cliente» solo con Accesos.

## Checkout público

`/planes`: elige cupo Accesos y cupo Pro **por separado** (`sku` + `sup`). El intent suma ambos montos.

Empresa ya cliente: `/company/billing` → alta/cambio de Pro abre **checkout** (simulador local). Quitar Pro aplica al instante. El monto contratado = Accesos + Pro.

Corpus legal de Pro reutiliza el de Accesos (sin SKU jurídico nuevo).

## App de campo

PWA en `field-app/` (copia local `C:\laragon\www\Controla_Supervision`). Sin BD. `localStorage.API_URL` o mismo host `/api`.

Control Patrulla (`Control_Patrulla`, `Control_Patrulla_app`) está **archivado** (`ARCHIVED.md`), no fusionado ni borrado.

## Carga masiva de clientes

La ficha no consume cupo. Columnas Accesos / Supervisión Pro (SI/NO) sí. Preview igual que empleados.

Detalle: [`docs/CLIENTES-Y-ESTRUCTURA.md`](docs/CLIENTES-Y-ESTRUCTURA.md).

La app de campo **no** tiene BD de negocio. Apunta a `https://dominio/api`.

| Método | Ruta | Notas |
|--------|------|--------|
| POST | `/api/supervision/login` | Rol `supervisor` + empresa con Pro |
| GET | `/api/supervision/shifts/current` | Turno abierto |
| GET | `/api/supervision/sites` | Clientes con `has_supervision` |
| POST | `/api/supervision/shifts/open` | `km_start` opcional |
| POST | `/api/supervision/shifts/ping` | `latitude`, `longitude` |
| POST | `/api/supervision/shifts/close` | `km_end` opcional |
| POST | `/api/supervision/reviews` | `client_id`, `notes`; llena minuta si hay Accesos + location |

Login de supervisor **no** usa `structure_app_users`.

## Repos

- **Controla** = web + BD + API (fuente de verdad).
- App de campo = otro repo; `API_URL` → `/api`. Las carpetas `Control_Patrulla*` se archivan, no se fusionan ni se borran.
