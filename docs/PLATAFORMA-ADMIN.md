# Panel Plataforma — Súper Admin

Documentación del panel `/admin`: dashboard operativo, ciclo comercial, archivo de cartera y retención legal de datos.

**Última actualización:** julio 2026

---

## Superficie y roles

| Prefijo | Rol | Permisos clave |
|---------|-----|----------------|
| `/admin` | `super-admin` | `platform.dashboard`, `platform.companies.view`, `platform.companies.manage` |

Layout: `resources/views/layouts/admin.blade.php` (acento **violet**).  
Guía visual: [`DISENO-UI-CONTROLA.md`](DISENO-UI-CONTROLA.md) §13.

---

## Dashboard (`GET /admin/dashboard`)

Pantalla analítica de la plataforma. Métricas comerciales, distribución geográfica y tendencias de facturación.

### Layout (v2 — julio 2026)

| Bloque | Contenido |
|--------|-----------|
| **Fila superior** | Mapa (Google Maps) + 4 KPIs + donut estado de cartera |
| **Paquetes** | Modalidad (manual/hardware), cupo contratado (1/5/10/50/100), ciclo (mensual/anual) |
| **Facturación** | TOP 5 empresas por MRR, KPIs comerciales, nuevos vs retenidos |
| **Tendencia** | Línea MRR (12 meses) + conjuntos activos (eje dual) |

Gráficas: **Chart.js 4** (CDN). Mapa: **Google Maps JavaScript API** con toggle **Empresa / Clientes**.

### KPIs principales

| Métrica | Fuente |
|---------|--------|
| Empresas activas | Empresas no archivadas |
| Conjuntos operativos | `clients` con `lifecycle = active` |
| MRR estimado | Suma mensualizada de paquetes (`package_price_monthly` o anual ÷ 12) |
| Tasa retención | % empresas en bucket «Al día» |

### Estado de cartera

Donut con buckets `CompanyAlertBucket`: Al día, Por vencer, Vencidos, Archivados.  
Leyenda HTML a la izquierda; gráfico a la derecha. Motor: `CompanySubscriptionState`.

### Mapa geográfico

| Capa | Marcadores |
|------|------------|
| **Empresa** | `security_companies` (coordenadas propias o promedio de conjuntos) |
| **Clientes** | `clients` con `latitude`/`longitude` |

Configuración: `config/google-maps.php`

```env
GOOGLE_MAPS_API_KEY=tu_clave
GOOGLE_MAPS_DEFAULT_LAT=4.5709
GOOGLE_MAPS_DEFAULT_LNG=-74.2973
GOOGLE_MAPS_DEFAULT_ZOOM=6
```

Sin API key se muestra aviso en el contenedor del mapa.

### TOP empresas por facturación

Tabla con altura fija (alineada a la columna derecha del grid). Scroll interno cuando hay más filas. Cabecera sticky.

### Acciones de cartera (desde otras rutas)

Las acciones de archivo y retiro de conjunto siguen disponibles en el detalle de empresa:

| Acción | Ruta | Efecto |
|--------|------|--------|
| Archivar empresa (baja) | `POST /admin/companies/{company}/archive` | Suspende servicio, cascada `lifecycle = archived_company` en clientes |
| Retirar conjunto | `POST /admin/companies/{company}/clients/{client}/release` | `lifecycle = released`, libera cupo, datos en retención |
| Gestionar paquete | `GET /admin/companies/{company}` | Asignación de SKU y ciclo |

---

## Ciclo comercial de licencia

```
Venta → vigencia → gracia (30 días) → sin pago → suspender → archivo por recuperar
Cancelación voluntaria → archivo inmediato (motivo cancelled)
```

### Estados (`SubscriptionStatus`)

`active` · `grace` · `expired` · `suspended`

### Campos empresa (`security_companies`)

| Campo | Uso |
|-------|-----|
| `package_ends_at` | Fin de vigencia contratada |
| `grace_ends_at` | Fin del mes de gracia |
| `suspended_at` | Fecha de suspensión |
| `archived_at` | Fecha de archivo |
| `archive_reason` | `cancelled` \| `recovery` |

### Campos conjunto (`clients`)

| Campo | Uso |
|-------|-----|
| `lifecycle` | `active` · `released` · `archived_company` |
| `released_at` | Retiro de conjunto (empresa activa) |
| `archived_at` | Archivo en cascada por empresa |
| `tenant_data_purged_at` | Purga operativa completada |

### Cupo operativo

Solo cuentan clientes con `lifecycle = active`:

```php
SecurityCompany::operationalClientsCount()
```

`released` y `archived_company` **no consumen cupo**.

### Job automático

```bash
php artisan subscriptions:process-lifecycle
```

Programado: **diario a las 02:00** (`routes/console.php`).

Servicio: `App\Services\Platform\ProcessSubscriptionLifecycleService`

---

## Retención y purga legal de datos

> Orientación de diseño alineada a Ley 1581 de 2012 (Colombia). No constituye asesoría legal.

### Política implementada

| Tipo de dato | Retención | Acción |
|--------------|-----------|--------|
| **Operativo** (censo, visitantes, logs, estructuras) | 365 días tras retiro/archivo | Purga tablas tenant + anonimización del registro `clients` |
| **Comercial** (metadatos empresa archivada) | 5 años tras `archived_at` | Anonimización de PII en `security_companies` |

Configuración: `config/retention.php`

```env
RETENTION_CENSUS_DAYS=365
RETENTION_COMMERCIAL_YEARS=5
```

### Flujo de datos del conjunto

```
operativo (active)
  → retirado/archivado (released | archived_company)
  → retención read-only (sin portería)
  → purga (tenant_data_purged_at)
```

Tras la purga se conserva el registro `clients` con nombre anonimizado (`Conjunto purgado #ID`) para trazabilidad comercial; se eliminan filas en tablas con `client_id`.

### Tablas purgadas

Definidas en `config/retention.php` → `purge_tables`: access_logs, correspondence, pre_authorizations, visitor_pre_authorizations, guard_logs, blocklist, structure_*, visitors, residents, housing_units, buildings, locations, client_user_assignments.

### Job automático

```bash
php artisan data:purge-retention
```

Programado: **día 1 de cada mes a las 03:00**.

Servicios:
- `ProcessDataRetentionPurgeService` — orquesta elegibles
- `PurgeClientTenantDataService` — borra datos tenant y anonimiza conjunto

---

## Rutas completas

| Método | Ruta | Función |
|--------|------|---------|
| GET | `/admin/dashboard` | Dashboard analítico (mapa, KPIs, cartera, facturación) |
| POST | `/admin/companies/{company}/archive` | Archivar empresa |
| POST | `/admin/companies/{company}/clients/{client}/release` | Retirar conjunto |
| GET | `/admin/pricing` | Tabla de precios |
| PUT | `/admin/pricing` | Guardar unitarios |
| GET | `/admin/companies` | Listado empresas |
| GET | `/admin/companies/{company}` | Detalle y cambio de paquete |
| PUT | `/admin/companies/{company}/package` | Asignar SKU y ciclo |

Archivo: `routes/modules/admin.php`

---

## Servicios y enums

```
app/Services/Platform/
├── PlatformDashboardService.php      # Orquesta datos del dashboard
├── PlatformDashboardAnalytics.php    # KPIs, gráficas, marcadores mapa, TOP facturación
├── ArchiveCompanyService.php         # Archivo en cascada
├── ReleaseClientService.php          # Retiro de conjunto
├── ProcessSubscriptionLifecycleService.php
├── ProcessDataRetentionPurgeService.php
└── PurgeClientTenantDataService.php

config/google-maps.php                # API key y centro por defecto (Colombia)

app/Enums/
├── ArchiveReason.php                 # cancelled, recovery
├── ClientLifecycle.php               # active, released, archived_company
├── CompanyAlertBucket.php            # current, due_soon, overdue, archived
└── SubscriptionStatus.php            # incluye Suspended

app/Support/Tenancy/CompanySubscriptionState.php
```

---

## Migraciones (julio 2026)

| Archivo | Cambios |
|---------|---------|
| `2026_07_20_160000_add_address_to_clients_table.php` | Campo `address` en conjuntos |
| `2026_07_20_170000_add_archive_and_lifecycle_fields.php` | Archivo, gracia, lifecycle |
| `2026_07_20_180000_add_data_retention_purge_fields.php` | `tenant_data_purged_at`, `commercial_anonymized_at` |
| `2026_07_26_120000_add_geolocation_to_companies_and_clients.php` | `address`, `latitude`, `longitude` en empresas; coords en conjuntos |

```bash
php artisan migrate
```

---

## Tests

```bash
php artisan test --filter=PlatformDashboardTest
php artisan test --filter=DataRetentionPurgeTest
```

- `tests/Feature/Platform/PlatformDashboardTest.php`
- `tests/Unit/Platform/DataRetentionPurgeTest.php`

---

## Pendiente producto / legal

- Política de Tratamiento de Datos (PTD) publicada
- Contrato de encargo de tratamiento con empresas
- Exportación de datos al responsable antes de purga (opcional)
- Log de auditoría de acciones admin (archivar/retirar)
