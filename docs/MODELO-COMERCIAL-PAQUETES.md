# Modelo comercial — Paquetes empresa (Controla)

Documentación de la implementación de pricing B2B para empresas de seguridad (julio 2026).

---

## Resumen ejecutivo

| Quién compra | Qué compra | Qué limita | Qué es ilimitado |
|--------------|------------|------------|------------------|
| Empresa de seguridad | Cupo de **conjuntos** (clientes) + modalidad + ciclo | Nº de `clients` que puede crear | Portafolio de cada conjunto (unidades, personas, mascotas, vehículos) |

El modelo **reemplaza** el pricing estilo Axesa por unidades (`plan_tier` / `max_structures` en `clients`). Esas columnas siguen en BD por compatibilidad pero **no limitan** el censo.

**Actualización ago 2026:** el cupo de Accesos ya no bloquea el alta de fichas. Ver [`PAQUETES-ACCESOS-Y-SUPERVISION.md`](PAQUETES-ACCESOS-Y-SUPERVISION.md).

---

## Variables comerciales

### 1. Cupo (tamaño del paquete)

Cantidad máxima de conjuntos (`clients`) que la empresa puede registrar:

| Cupo | Descuento por volumen (config) |
|------|--------------------------------|
| 1 | 0% |
| 5 | 10% |
| 10 | 15% |
| 50 | 25% |
| 100 | 30% |
| 500 | 50% |

Definido en `config/tenancy.php` → `pricing.volume_discounts`.

### 2. Modalidad

| Valor | Label UI | Incluye (feature flags) |
|-------|----------|-------------------------|
| `manual` | Sin hardware | Censo, portería manual, portal residente web |
| `hardware` | Con hardware | Lo anterior + lectores, LPR, RFID, facial, app↔dispositivos |
| `mixed` | Mixto (desde 5) | Asientos combinados; hardware en los asientos con HW |

Features en `config/tenancy.php` → `features`. Helper: `App\Support\Tenancy\CompanyPackage::allows()`.

### 3. Ciclo de facturación

| Valor | Label | Vigencia al asignar |
|-------|-------|---------------------|
| `monthly` | Mensual | +1 mes desde `package_starts_at` |
| `annual` | Anual | +1 año (descuento ~17% vs 12 mensualidades) |

Enum: `App\Enums\BillingCycle`.

---

## Precios: quién define qué

### Entrada manual (solo súper admin)

En `/admin/pricing` se editan **dos** precios unitarios (COP / cliente / mes):

- `unit_price_manual`
- `unit_price_hardware`

Persistidos en tabla `pricing_settings` (modelo `PricingSettings`).

### Cálculo automático

Servicio: `App\Services\Pricing\PriceCalculator`

```
precio_mensual_paquete = unitario × cupo × (1 - descuento_volumen)
precio_anual           = precio_mensual × 12 × (1 - descuento_anual)
```

DTO de salida: `App\Domain\Pricing\Data\PriceQuote` (incluye ahorro anual, unitario efectivo, etc.).

La matriz completa se muestra en `/admin/pricing` con toggle **Mensual / Anual**. Las celdas **no** se editan una a una.

### Snapshot en contrato

Al asignar paquete (`AssignCompanyPackageService`), se congela en `security_companies`:

- `package_sku`, `package_size`, `package_modality`, `max_clients`
- `billing_cycle`, `unit_price_snapshot`, `volume_discount_pct`, `annual_discount_pct`
- `package_price_monthly`, `package_price_annual`
- `package_starts_at`, `package_ends_at`, `subscription_status`

Cambios futuros en `pricing_settings` **no** alteran empresas ya contratadas.

---

## SKUs

Enum `App\Enums\CompanyPackageSku` — combinación `pack_{cupo}_{modality}`:

- `pack_1_manual` … `pack_100_manual`
- `pack_1_hardware` … `pack_100_hardware`

---

## Rutas y permisos

### Plataforma (`/admin`)

| Método | Ruta | Permiso | Acción |
|--------|------|---------|--------|
| GET | `/admin/pricing` | `platform.companies.view` | Ver/editar unitarios y matriz |
| PUT | `/admin/pricing` | `platform.companies.manage` | Guardar unitarios |
| GET | `/admin/companies` | `platform.companies.view` | Listado empresas |
| GET | `/admin/companies/{company}` | `platform.companies.view` | Detalle + asignar paquete/ciclo |
| PUT | `/admin/companies/{company}/package` | `platform.companies.manage` | Aplicar SKU + ciclo |

Navegación: `config/access.php` → `navigation.admin`.

### Empresa (`/company`)

- Dashboard: licencia, cupo, ciclo, CTA anual, sugerencias de upgrade de cupo.
- Clientes: alta bloqueada si `clients_count >= max_clients`.
- Sin selector de `plan_tier` al crear/editar conjunto.

---

## Base de datos

### Migraciones

1. `2026_07_20_140000_add_package_fields_to_security_companies_table.php`  
   Campos iniciales de paquete en `security_companies`.

2. `2026_07_20_150000_create_pricing_settings_and_subscription_fields.php`  
   Tabla `pricing_settings` + campos de suscripción (ciclo, snapshot, vigencia).

### Tablas clave

**`pricing_settings`**

| Columna | Descripción |
|---------|-------------|
| `unit_price_manual` | Unitario sin hardware |
| `unit_price_hardware` | Unitario con hardware |
| `currency` | COP (default) |
| `updated_by` | Usuario súper admin |

**`security_companies`** (campos comerciales)

Ver snapshot en sección anterior.

---

## Servicios y clases

```
app/
├── Domain/Pricing/Data/PriceQuote.php
├── Enums/
│   ├── BillingCycle.php
│   ├── CompanyPackageSku.php
│   ├── PackageModality.php
│   └── SubscriptionStatus.php
├── Http/Controllers/Platform/
│   ├── PricingController.php
│   └── CompanyController.php
├── Models/PricingSettings.php
├── Services/Pricing/
│   ├── PriceCalculator.php
│   └── UpdatePlatformPricingService.php
├── Services/Tenant/AssignCompanyPackageService.php
└── Support/Tenancy/CompanyPackage.php
```

---

## Tests

```bash
php artisan test --filter=PriceCalculatorTest
php artisan test --filter=PlatformDashboardTest
```

- `tests/Unit/Pricing/PriceCalculatorTest.php` — descuentos volumen y anual.
- `tests/Feature/Platform/PlatformDashboardTest.php` — acceso panel plataforma.

---

## Post-instalación / actualización

Tras `git pull` con estos cambios:

```bash
composer install
php artisan migrate
php artisan db:seed --class=TenantSeeder    # opcional: refrescar paquete demo SJ Seguridad
npm install && npm run build
```

---

## Fuera de alcance (roadmap)

- Pasarela de pago / facturación electrónica.
- Sub-tiers hardware (Access / Smart / Pro) como SKUs separados.
- Autoservicio de upgrade por empresa (hoy: solicitud + asignación plataforma).

---

## Changelog

| Fecha | Cambio |
|-------|--------|
| 2026-07-20 | Modelo inicial: cupo empresa, modalidad manual/hardware, pricing dinámico, UI admin/empresa |
