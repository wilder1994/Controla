# Observatorio (v1)

Intake público de reportes escolares y seguimiento de eventos. No es portería ni PQRS de Supervisión.

**Última actualización:** 11 septiembre 2026

## Piezas

| Pieza | Qué es |
|--------|--------|
| **Reporte** | Intake público. Fuente v1: `comunidad`. |
| **Evento** | Incidente. El primer reporte crea el evento (1:1). Folio `EV-000123`. Enganchar varios reportes: después. |

## Público `/o/{slug}`

Ejemplo: `/o/palmas-del-ingenio`. Solo sedes `kind=colegio` y activas. Tres pasos, móvil, sin app:

1. Colegio (nombre o DANE)
2. Qué pasó + tipo (amenaza / riña / hurto / otro) + foto opcional
3. Anónimo o identificado

Aviso de menores (Normoteca). Anónimo no guarda nombre ni teléfono.

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

## Siguiente

Mapa/calor (factor 2): pines de colegios y eventos, concentraciones. Después: tablero, varios reportes en un evento, Policía/123, API.
