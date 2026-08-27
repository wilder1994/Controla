# Supervisión de campo

Controla es la fuente de verdad. La PWA (`field-app/` y el host `controla_supervision.test`) solo captura.

No choca con: minuta de portería (`/access/supervision`), `SupervisorReview`, `PlatformDocument`, zonas/vehículos de Accesos, `locations` (accesos) ni correspondencia.

**Última actualización:** 26 agosto 2026

Árbol del cliente (instalación → puesto): [`CLIENTES-Y-ESTRUCTURA.md`](CLIENTES-Y-ESTRUCTURA.md). La app **no** usa puntos de Accesos como puesto.

---

## Empresa — Ajustes

Mismo bloque que Cargos/Tipos (`company.settings.manage`):

| Pestaña | Ruta | Tabla |
|---------|------|--------|
| Cargos | `/company/job-titles` | `company_job_titles` |
| Tipos | `/company/collaborator-types` | `company_collaborator_types` |
| **Zonas** | `/company/supervision-zones` | `supervisor_zones` (rutas de Supervisión, no `common_zones`) |
| **Turnos** | `/company/supervision-shifts` | `supervisor_shift_templates` (nombre + horario) |
| **Preoperacional** | `/company/supervision-preop` | `supervisor_checklist_items` (`ppe` / `vehicle`) |

Si el catálogo está vacío, se siembran defaults (Norte/Sur/Centro; Día 06:00–18:00 y Noche 18:00–06:00; EPP y vehículo de `ShiftIntakeCatalog`). Solo los **activos** salen en la app.

Flota: `supervisor_fleet_vehicles` (placa/marca la primera vez). **No** es `vehicles` de Accesos.

---

## App de campo

PWA en `field-app/` (copia alineada en `Controla_Supervision`). Caché SW `controla-sup-v8`.

API **siempre** Controla: si el host es `controla_supervision.test` → `http://controla.test/api`. Hard-refresh tras cambios de PWA.

1. Login (`POST /api/supervision/login`) → rito de **apertura**: turno y zona del catálogo, EPP/vehículo plegables, km + foto odómetro + selfie (cámara, no galería).
2. Hub: ficha de perfil + **Cerrar** (cierra turno y desloguea). Tres entradas: **Revista**, **Alarmas**, **Apoyos**. Ping GPS cada 30 s, silencioso.
3. **Revista** (al clic): cliente con Supervisión, **puesto** (`supervisor_posts` de una instalación de ese cliente), vigilante, foto, observaciones, novedad, guardar. Debajo, siempre visibles: inventario, documentos, carpetas, armamento y recomendaciones. Persistencia de esos logs exige revista guardada.
4. Alarmas y apoyos: cada uno abre su formulario (no cuelgan de la revista).
5. Cierre: km final + odómetro + selfie → sesión cerrada.

Pilot: `supervisor@sj-seguridad.test` / `Super123!`. Empresa: `empresa@sj-seguridad.test` / `Empresa123!`. Palmas tiene `has_supervision`. Vigilante piloto: cédula `1144001122`.

Sin puestos de Supervisión en la pestaña del cliente no se guarda revista. Los `locations` (puertas) no entran en el combo.

---

## Módulos

| Clave | Captura | Notas |
|-------|---------|--------|
| `reviews` | `POST /reviews` | Revista en `supervisor_shift_reviews`. No llena minuta Accesos. |
| `inventory` | `POST /logs` | Cuelga de la revista |
| `documents` | `POST /logs` | Cuelga de la revista. Documentos del puesto (no normoteca ni correspondencia) |
| `folders` | `POST /logs` | Cuelga de la revista |
| `weapons` | `POST /logs` | Cuelga de la revista. Serial observado, no catálogo de armas |
| `recommendations` | `POST /logs` | Cuelga de la revista. Hallazgo con ciclo de vida |
| `alarms` | `POST /logs` | Formulario propio. Requiere cliente |
| `supports` | `POST /logs` | Formulario propio. Cliente opcional (puede ser en vía) |

Contrato de campos: `GET /api/supervision/catalog` (`FieldModuleCatalog`). Logs append-only en `supervisor_field_logs` (`supervisor_shift_review_id` si cuelga de revista). Recomendaciones: `supervisor_recommendations` (`GET`/`PATCH /recommendations`, filtradas por el cliente de la revista actual).

El mapa `/company/supervision` pinta el GPS de la revista (verde / rojo si novedad), distinto del rastro de ping del turno.

---

## Panel empresa — operación

`/company/supervision`: En vivo / Historial / **Resumen**. Informe PPTX: `GET /company/supervision/informe.pptx`.

Mi empresa muestra la tira **Supervisión de campo (hoy)** aparte de las revistas de portería.

---

## API (`auth:sanctum` + `supervisor.pro`)

| Método | Ruta | Uso |
|--------|------|-----|
| POST | `/api/supervision/login` | Token supervisor |
| GET | `/api/supervision/intake` | Zonas, turnos, EPP, vehículo, flota |
| GET | `/api/supervision/shifts/current` | Turno abierto + `current_review` + actividad |
| GET | `/api/supervision/shift-photo/start-selfie` | Selfie de apertura |
| POST | `/api/supervision/shifts/open` | Multipart: `shift_template_id`, `zone_id`, checklists, fotos |
| POST | `/api/supervision/shifts/ping` | GPS silencioso |
| POST | `/api/supervision/shifts/close` | Multipart km + fotos |
| GET | `/api/supervision/sites` | Clientes con `has_supervision` |
| GET | `/api/supervision/posts` | Puestos de Supervisión del cliente (`supervisor_posts`), no `locations` |
| GET | `/api/supervision/guards` | Vigilantes por cédula |
| GET | `/api/supervision/catalog` | 8 módulos |
| POST | `/api/supervision/reviews` | Multipart: cliente, puesto, vigilante, foto, GPS, novedad |
| POST | `/api/supervision/logs` | Módulos de campo |
| GET / PATCH | `/api/supervision/recommendations` | Ciclo de recomendaciones |

Apertura exige turno/zona **activos de esa empresa** y todos los ítems preoperacionales activos en `true`.

---

## Migraciones

- `2026_07_06_080400_create_installations_table`
- `2026_07_06_080500_create_locations_table` (`installation_id`)
- `2026_08_25_230000_add_supervision_packages_and_tracking` (zonas, turnos, preop, flota, turnos de campo, `supervisor_posts`, revistas con `supervisor_post_id`)
- `2026_08_26_110000_create_supervisor_field_ops_tables` (recomendaciones y logs)

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=PilotDemoSeeder
```

Tests: `SupervisorShiftApiTest`, `SupervisorFieldLogApiTest`, `CompanySupervisionCatalogTest`, `CompanyClientSiteTreeTest` (BD `controla_test`).
