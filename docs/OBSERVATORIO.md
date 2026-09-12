# Observatorio (tablero + API)

Intake de reportes escolares y seguimiento de eventos. No es portería ni PQRS de Supervisión.

**Última actualización:** 12 septiembre 2026

## Piezas

| Pieza | Qué es |
|--------|--------|
| **Reporte** | Denuncia. Canal (`source`) + quién (`reporter_role`). Puede llevar pin (lat/lng). |
| **Evento** | Incidente. Folio `EV-000123`. Varios reportes del mismo colegio y tipo se unen si el evento sigue abierto y el último reporte de ese tipo fue hace menos de 1 hora (`OBSERVATORY_MERGE_WINDOW_MINUTES`). Cerrado nunca recibe más reportes. El admin de esa sede puede unir folios a mano o sacar un reporte a un folio nuevo. |

## Fuentes

En la ficha: `tipo · Canal · Rol · fecha` y abajo el nombre, o **Anónimo**.

| Quién | Dónde | Canal · Rol |
|--------|--------|-------------|
| Alumno, padre, vecino | Link `/o/{slug}` | Comunidad · Alumno / Padre / Vecino |
| Rector / apoyo | Panel `/client/observatory` (ya tienen usuario) | Panel · Rector / Apoyo |
| Supervisor de patrulla | PWA Supervisión → Observatorio | App de patrulla · Supervisor |
| Vigilante | Minuta, novedad, si la sede es colegio y tiene puerta | Portería · Vigilante |
| Software de Secretaría | `POST /api/observatory/reports` | Integración · Sistema externo |

Anónimo (todas las superficies): aviso «se oculta tu nombre y teléfono; solo se muestra la denuncia». No se guarda nombre, teléfono ni `reported_by_user_id`. Sí quedan canal y rol.

## Público `/o/{slug}`

Ejemplo: `/o/palmas-del-ingenio`. Solo sedes `kind=colegio` y activas. Tres pasos, móvil, sin app:

1. Colegio (nombre o DANE)
2. Qué pasó + tipo (amenaza / riña / hurto / otro) + mapa satélite (pin en el colegio; se arrastra) + foto opcional
3. Quién eres (alumno / padre / vecino) + anónimo o identificado + «Recordarme en este teléfono»

Aviso de menores (Normoteca). Anónimo no guarda nombre ni teléfono. Alumno: el nombre de ese reporte no se guarda en el teléfono (Ley 1581). Padre/vecino: si marcan recordarme, el siguiente ingreso en **ese** celular salta a rellenar colegio/hecho. Otro celular empieza de nuevo.

Si no mueven el pin, se guarda el del colegio. Sin pin de colegio ni API, las coordenadas quedan vacías.

Empresa y admin del cliente copian el link en Observatorio (botón Copiar). Rector y apoyo reportan identificados desde **Nuevo reporte** en el panel del cliente.

## App de patrulla

Turno abierto. Entrada **Observatorio** (aparte de la revista). Colegio, tipo, texto, foto opcional, anónimo. Colegios de la empresa en el paquete offline. `POST /api/supervision/observatory/reports`.

## Minuta

En `/access/guard_logs/create`, tipo **Novedad** y ubicación de un **colegio con puerta**: check «También al Observatorio» + tipo + anónimo. Sin puerta de colegio, no se muestra. No es el pánico.

## Estados

`nuevo` → `en_atencion` → `cerrado`. **Los cierra el admin de instalaciones** de esa sede (`client-installation-admin` con `site_permission=admin`). El mismo admin puede **unir** otro folio del mismo colegio (el otro se elimina) o **sacar** un reporte a un folio nuevo (el evento debe tener al menos dos reportes). No se une ni se saca de un evento cerrado. El apoyo ve, reporta y no cambia estado ni une. Desde `cerrado` no se reabre.

Empresa y `client-admin` **ven** y no cambian estado ni unen ni reportan desde el panel.

## Quién ve

| Actor | Superficie |
|--------|------------|
| Comunidad | `/o/{slug}` |
| Admin instalaciones / apoyo | `/client/observatory/events` — solo sus sedes. Solo el administrador (no el apoyo) cambia estado, une folios o saca un reporte. Ambos pueden **Nuevo reporte**. |
| Admin del cliente | Mismo listado, todo el cliente. Sin cambiar estado, unir ni reportar desde el panel. |
| Empresa | `/company/observatory/events` — clientes de la empresa. Seguimiento, no portal, no cambia estado ni une. |
| Supervisor | PWA Observatorio |
| Vigilante | Minuta → Observatorio si hay puerta de colegio |
| Software de Secretaría | `/docs/observatory` + token Sanctum. Solo Observatorio de ese cliente |

Permisos: `observatory.view`, `observatory.events.update`. Tras el alta: `php artisan db:seed --class=RoleAndPermissionSeeder`.

## Unir / sacar (v1)

En la ficha del evento (`/client/observatory/events/{id}`), solo el admin de esa sede:

- **Unir aquí:** elige otro folio del mismo colegio. Los reportes pasan a este evento y el otro folio se elimina. No se une a un evento cerrado.
- **Sacar a folio nuevo:** en cada reporte, si el evento está abierto y tiene dos o más. Crea un `EV-` nuevo (`nuevo`) con ese reporte. No se saca de un evento cerrado.

Empresa, `client-admin` y apoyo no ven esos botones.

## Tablero

Filtro por fechas. KPIs (clic filtra la tabla). Línea de eventos por día, barras por tipo, torta por canal, medidor de % cerrados (0 si no hay eventos; los ejes se ven). Ranking de colegios. Mapa a la izquierda. Botón **API** → `/docs/observatory`.

## API

Para el software de Secretaría (u otro sistema). Token Sanctum (`POST /api/auth/login`). Solo Observatorio de ese cliente o, en empresa, de sus clientes. No censo ni portería.

| Método | Ruta | Quién |
|--------|------|--------|
| GET | `/api/observatory/events` | Secretaría, rector/apoyo (sus sedes), empresa |
| GET | `/api/observatory/events/{id}` | Igual |
| GET | `/api/observatory/board` | Igual |
| GET | `/api/observatory/sites` | Igual |
| POST | `/api/observatory/reports` | Secretaría = canal Integración. Rector/apoyo = Panel. Empresa no |
| GET | `/api/observatory/openapi.json` | Público (contrato) |
| GET | `/docs/observatory` | Público (Swagger) |

El POST usa las mismas reglas de unión (1 h, mismo colegio y tipo). Anónimo oculta nombre y teléfono.

## Mapa (v1)

En Observatorio de empresa y cliente: pines de colegios + pines de cada reporte. Color por estado (ámbar nuevo, índigo en atención, gris cerrado/sin reportes). Botón **Calor** = ubicación de cada reporte abierto (no el centroide del colegio). Requiere `GOOGLE_MAPS_API_KEY`.

## Siguiente

Capas por comuna. Catálogo MEN. Policía/123 (sin convenio).
