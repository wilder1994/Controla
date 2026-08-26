# Supervisión de campo

Controla es la fuente de verdad. La PWA (`field-app/` y el host `controla_supervision.test`) solo captura.

No choca con: minuta de portería (`/access/supervision`), `SupervisorReview`, `PlatformDocument`, zonas/vehículos de Accesos ni correspondencia.

**Última actualización:** 26 agosto 2026

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

PWA en `field-app/` (copia alineada en `Controla_Supervision`). Caché SW `controla-sup-v4`.

API **siempre** Controla: si el host es `controla_supervision.test` → `http://controla.test/api`. Hard-refresh tras cambios de PWA.

1. Login (`POST /api/supervision/login`) → rito de **apertura**: turno y zona del catálogo, EPP/vehículo plegables, km + foto odómetro + selfie (cámara, no galería).
2. Hub de 8 módulos. GPS ~30 s (`POST /shifts/ping`).
3. Cierre: km final + odómetro + selfie.

Pilot: `supervisor@sj-seguridad.test` / `Super123!`. Empresa: `empresa@sj-seguridad.test` / `Empresa123!`. Palmas tiene `has_supervision`.

---

## Módulos

| Clave | Captura | Notas |
|-------|---------|--------|
| `reviews` | `POST /reviews` | Revista. Si el sitio también tiene Accesos, llena la minuta del puesto (`supervisor_shift_reviews`). |
| `inventory` | `POST /logs` | Estado del puesto |
| `documents` | `POST /logs` | Documentos **del puesto** (no normoteca ni correspondencia) |
| `folders` | `POST /logs` | Carpeta completa / faltantes |
| `weapons` | `POST /logs` | Serial observado, no catálogo de armas |
| `recommendations` | `POST /logs` | Hallazgo con ciclo de vida; no se pierde al cerrar turno |
| `alarms` | `POST /logs` | Prueba de alarma |
| `supports` | `POST /logs` | Sitio opcional (puede ser en vía) |

Contrato de campos: `GET /api/supervision/catalog` (`FieldModuleCatalog`). Logs append-only en `supervisor_field_logs`. Recomendaciones: `supervisor_recommendations` (`GET`/`PATCH /recommendations`).

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
| GET | `/api/supervision/shifts/current` | Turno abierto + actividad |
| POST | `/api/supervision/shifts/open` | Multipart: `shift_template_id`, `zone_id`, checklists, fotos |
| POST | `/api/supervision/shifts/ping` | GPS |
| POST | `/api/supervision/shifts/close` | Multipart km + fotos |
| GET | `/api/supervision/sites` | Clientes con `has_supervision` |
| GET | `/api/supervision/catalog` | 8 módulos |
| POST | `/api/supervision/reviews` | Revista |
| POST | `/api/supervision/logs` | Módulos de campo |
| GET / PATCH | `/api/supervision/recommendations` | Ciclo de recomendaciones |

Apertura exige turno/zona **activos de esa empresa** y todos los ítems preoperacionales activos en `true`.

---

## Migraciones

- `2026_08_26_110000_create_supervisor_field_ops_tables`
- `2026_08_26_123000_add_supervisor_shift_intake`
- `2026_08_26_125300_create_supervisor_company_catalogs`

```bash
php artisan migrate
```

Tests: `SupervisorShiftApiTest`, `SupervisorFieldLogApiTest`, `CompanySupervisionCatalogTest` (BD `controla_test`).
