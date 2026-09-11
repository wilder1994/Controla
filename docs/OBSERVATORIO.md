# Observatorio (v1)

Intake público de reportes escolares y seguimiento de eventos. No es portería ni PQRS de Supervisión.

**Última actualización:** 11 septiembre 2026

## Piezas

| Pieza | Qué es |
|--------|--------|
| **Reporte** | Intake público. Fuente v1: `comunidad`. Puede llevar pin (lat/lng). |
| **Evento** | Incidente. Folio `EV-000123`. Varios reportes del mismo colegio y tipo se unen si el evento sigue abierto y el último reporte de ese tipo fue hace menos de 1 hora (`OBSERVATORY_MERGE_WINDOW_MINUTES`). Cerrado nunca recibe más reportes. |

## Público `/o/{slug}`

Ejemplo: `/o/palmas-del-ingenio`. Solo sedes `kind=colegio` y activas. Tres pasos, móvil, sin app:

1. Colegio (nombre o DANE)
2. Qué pasó + tipo (amenaza / riña / hurto / otro) + mapa satélite (pin en el colegio; se arrastra) + foto opcional
3. Anónimo o identificado

Aviso de menores (Normoteca). Anónimo no guarda nombre ni teléfono. Si no mueven el pin, se guarda el del colegio. Sin pin de colegio ni API, las coordenadas quedan vacías.

Empresa y admin del cliente copian el link en Observatorio (botón Copiar). El admin de instalaciones lo ve en el mismo listado del cliente.

## Estados

`nuevo` → `en_atencion` → `cerrado`. **Los cierra el admin de instalaciones** de esa sede (`client-installation-admin` con `site_permission=admin`). El apoyo ve y no cambia estado. Desde `cerrado` no se reabre.

Empresa y `client-admin` **ven** y no cambian estado.

## Quién ve

| Actor | Superficie |
|--------|------------|
| Comunidad | `/o/{slug}` |
| Admin instalaciones / apoyo | `/client/observatory/events` — solo sus sedes. Solo el administrador (no el apoyo) cambia estado. |
| Admin del cliente | Mismo listado, todo el cliente. Sin cambiar estado. |
| Empresa | `/company/observatory/events` — clientes de la empresa. Seguimiento, no portal, no cambia estado. |

Permisos: `observatory.view`, `observatory.events.update`. Tras el alta: `php artisan db:seed --class=RoleAndPermissionSeeder`.

## Tablero (v1)

Filtro por fechas. Cifras: eventos / nuevos / en atención / cerrados (clic filtra la tabla). Ranking de colegios con más eventos. Mapa a la izquierda, ranking y link a la derecha.

## Mapa (v1)

En Observatorio de empresa y cliente: pines de colegios + pines de cada reporte. Color por estado (ámbar nuevo, índigo en atención, gris cerrado/sin reportes). Botón **Calor** = ubicación de cada reporte abierto (no el centroide del colegio). Requiere `GOOGLE_MAPS_API_KEY`.

## Siguiente

Unir/desenganchar a mano, Policía/123, API.
