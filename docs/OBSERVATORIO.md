# Observatorio (tablero + API)

Intake de reportes escolares y seguimiento de eventos. No es portería ni PQRS de Supervisión.

**Última actualización:** 12 septiembre 2026 (export PPTX del tablero)

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

En el Tablero, **Compartir link** abre el modal con la URL `/o/{slug}` (Copiar / Cerrar). Rector y apoyo reportan desde **Nuevo reporte** (botón en el header del panel cliente, slot `$actions`). Empresa o admin del cliente ven el botón; al abrir, el sistema dice que con ese usuario no pueden reportar.

## App de patrulla

Turno abierto. Entrada **Observatorio** (aparte de la revista). Colegio, tipo, texto, foto opcional, anónimo. Colegios de la empresa en el paquete offline. `POST /api/supervision/observatory/reports`.

## Minuta

En `/access/guard_logs/create`, tipo **Novedad** y ubicación de un **colegio con puerta**: check «También al Observatorio» + tipo + anónimo. Sin puerta de colegio, no se muestra. No es el pánico.

## Estados

`nuevo` → `en_atencion` → `cerrado`. **Los gestiona el admin de instalaciones** de esa sede (`client-installation-admin` con `site_permission=admin`) en la ficha:

- **Agregar:** observación obligatoria. Desde Nuevo pasa a En atención. Si ya está en atención, el folio no cambia y la nota va a la bitácora.
- **Cerrar folio:** solo En atención; observación obligatoria. No se reabre.

Cancelar cierra el recuadro, no el folio. El apoyo ve, reporta y no cambia estado ni une. Empresa y `client-admin` solo leen. El botón **Nuevo reporte** se ve siempre en el panel cliente (y si la empresa opera el cliente); si el usuario no es rector/apoyo, al abrir dice que con ese usuario no puede reportar.

El mismo admin puede **unir** otro folio del mismo colegio o **sacar** un reporte a un folio nuevo (el evento debe tener al menos dos reportes). No se une ni se saca de un evento cerrado.

## Quién ve

| Actor | Superficie |
|--------|------------|
| Comunidad | `/o/{slug}` |
| Admin instalaciones / apoyo | `/client/observatory/events` — solo sus sedes. Solo el administrador (no el apoyo) cambia estado, une folios o saca un reporte. Ambos pueden **Nuevo reporte**. |
| Admin del cliente | Mismo listado, todo el cliente. Ve **Nuevo reporte** pero no puede enviarlo. Sin cambiar estado ni unir. |
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

## Tablero y Eventos

Pestañas **Tablero** | **Eventos** (cuelgan del header, no van dentro de la barra).

- **Tablero:** filtros en una sola fila (xl): **Cliente** angosto (solo empresa), **Desde — Hasta** (ícono; modal Aceptar/Cerrar), Líneas, Buscar, **Filtrar**, Compartir link, **PPTX**, API. `?comuna=` va hidden. Fila 1: mapa + leyenda. **Todas** encuadra Cali y pinta las 22 comunas. Una comuna hace zoom a ese polígono. Calor = círculos (Google quitó HeatmapLayer). Fila 2: sedes por riesgo + tendencia. Fila 3: picos · canal · cierre. El PPTX usa los mismos filtros (fechas, cliente, comuna, grano); solo cifras.
- **Eventos:** tabla folio / sede / tipo / estado / abierto / Ver (+ cliente en empresa). **Ver** abre la ficha (mapa del folio, reportes, bitácora).

## Ficha

Expediente del folio: cabecera, mapa de pines de *ese* evento, novedades (hora, canal, rol, denunciante), bitácora (estado + observación + quién + cuándo). Rector: Agregar / Cerrar folio.

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

## Tipos (catálogo del cliente)

Solo el **admin del cliente** crea y edita: nombre + nivel 1–3 + color. La empresa los ve (filtro por cliente). Semilla: Amenaza N3, Riña N2, Hurto N2, Otro N1. El link `/o/{slug}`, el panel, la PWA y la minuta usan esos tipos.

Puntaje del colegio = suma de niveles de sus reportes. Tablero: líneas por tipo (día/mes/año) y días pico.

## Mapa

Círculo = sede activa con coordenadas (cualquier `kind`). Gota = reporte. Modos: Pines · Calor sede · Calor riesgo (círculos propios; Google quitó `HeatmapLayer` en Maps JS 3.65). Requiere `GOOGLE_MAPS_API_KEY`. El intake público `/o/{slug}` sigue siendo solo `kind=colegio`.

Capa y filtro: **22 comunas urbanas de Cali** (IDESC), ruta relativa `/geo/cali-comunas.geojson`. **Todas** (sin `?comuna=`) encuadra Cali y pinta las 22. Una comuna recorta y hace zoom a ese polígono. Fuera del perímetro: **Fuera de Cali**.

La **ficha de la instalación** usa la misma capa: al poner el pin, si cae en Cali se guarda y se muestra la comuna IDESC (contorno en el mapa de al lado). Fuera de Cali no hay polígono; el área sigue saliendo de Places.

## Módulo

En la ficha del cliente, checkbox **Observatorio**. Si no está chuleado, no aparece en el sidebar del cliente. Empresa siempre ve `/company/observatory`.

## Pliego (Anexo 7.5)

Texto literal: fuentes diversas + origen; **no** nombra Policía ni Línea 123. Visita: Secretaría de Educación de Cali.

| Pedido | Estado |
|--------|--------|
| Maestro, mapa, pines, calor, intake, folio, bitácora, tablero, API | Hecho |
| Priorización configurable | Hecho: catálogo tipo + nivel + color |
| Filtros/capas territoriales | Hecho: comunas urbanas Cali (IDESC). No cubren el Valle |
| Salida de resultados (export) | Hecho: PPTX del tablero (`/company/observatory/tablero.pptx` y `/client/observatory/tablero.pptx`) |

## Siguiente

v1 del Anexo 7.5 cerrada. Sin conector 123.
