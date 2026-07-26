# Controla

Plataforma SaaS B2B de **control de accesos y vigilancia** para empresas de seguridad privada y conjuntos residenciales en Colombia. Construida sobre **Laravel 11** (Laragon), con referencia funcional **Axesa Control v13**.

**Repositorio:** [github.com/wmcodesoft/Controla](https://github.com/wmcodesoft/Controla)

---

## Estado del proyecto

| Fase | Nombre | Estado |
|------|--------|--------|
| **0** | Fundación multi-tenant | ✅ Implementada |
| **1** | Estructura / censo | ✅ Implementada |
| **Limpieza** | Panel plataforma + residuos Breeze | ✅ Implementada |
| **Landing** | Vista pública `/` (welcome) | ✅ Implementada |
| **Auth** | Login `/login` (AuthLayout) | ✅ Implementada |
| **2** | Operación portería (MVP) — Hub operaciones, lista bloqueo, salida masiva | ✅ Implementada |
| **3** | BI + vigilancia — Reportes mejorados con exportación | ✅ Implementada |
| **4** | API REST (Sanctum) + Portal Residente web | ✅ Implementada |
| **Comercial** | Paquetes empresa + tabla de precios + facturación mensual/anual | ✅ Implementada |
| **UI Empresa** | Design system `x-ui.*`, dashboard licencia + cartera, nav Portería/Conjunto | ✅ Implementada (v1) |
| **UI Plataforma** | Dashboard analítico (mapa, KPIs, cartera, facturación) | ✅ Implementada (v2) |
| **Ciclo comercial** | Gracia, suspensión, archivo, purga retención legal | ✅ Implementada |

Documentación detallada: [`docs/PLAN-INICIO-PROYECTO-CONTROLA.md`](docs/PLAN-INICIO-PROYECTO-CONTROLA.md) · [`docs/REFERENCIA-PLATAFORMA-CONTROL-ACCESOS.md`](docs/REFERENCIA-PLATAFORMA-CONTROL-ACCESOS.md) · [`docs/MODELO-COMERCIAL-PAQUETES.md`](docs/MODELO-COMERCIAL-PAQUETES.md) · [**Diseño UI**](docs/DISENO-UI-CONTROLA.md) · [**Panel Plataforma**](docs/PLATAFORMA-ADMIN.md)

---

## Superficies de producto

| Panel | Prefijo | Rol(es) | Descripción |
|-------|---------|---------|-------------|
| **Plataforma** | `/admin` | `super-admin` | Dashboard analítico, precios, empresas y paquetes |
| **Empresa** | `/company` | `company-admin` | Licencia, cupo de clientes, cartera de conjuntos |
| **Conjunto** | `/client` | `client-admin` | Censo: estructuras, personas, vehículos, mascotas, autorizaciones |
| **Portería** | `/access` | `guardia`, `supervisor`, `client-admin` | Operación diaria: hub operaciones, bloqueo, reportes |
| **Residente** | `/resident` | `resident`, `anfitrion` | Portal web: pre-autorizaciones y correspondencia |
| **API** | `/api` | Token-based | Sanctum: auth, pre-autorizaciones, correspondencia |

Tras el login, cada rol es redirigido a su **home** vía `ResolveUserHomeRoute` → ruta `/home`.

---

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8+
- Node.js 18+ (assets Vite)
- [Laragon](https://laragon.org/) (recomendado) o entorno equivalente

---

## Instalación

```bash
git clone https://github.com/wmcodesoft/Controla.git
cd Controla
composer install
cp .env.example .env   # o copiar .env manualmente
php artisan key:generate
```

Configurar en `.env`:

```env
APP_URL=http://controla.test
DB_DATABASE=controla
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=file

# Mapa del dashboard plataforma (opcional)
GOOGLE_MAPS_API_KEY=
GOOGLE_MAPS_DEFAULT_LAT=4.5709
GOOGLE_MAPS_DEFAULT_LNG=-74.2973
GOOGLE_MAPS_DEFAULT_ZOOM=6
```

```bash
php artisan migrate          # solo migraciones aditivas
php artisan db:seed
npm install && npm run build
```

### Assets estáticos (imágenes)

Las imágenes del producto viven en `resources/images/`. Para servirlas con `asset('images/...')`, crear un enlace en Laragon (Windows):

```powershell
cd C:\laragon\www\Controla
New-Item -ItemType Junction -Path "public\images" -Target "C:\laragon\www\Controla\resources\images" -Force
```

### Node.js en Laragon

Si `npm` no se reconoce en la terminal, agregar al PATH de Windows:

```
C:\laragon\bin\nodejs\node-v18
```

Luego: `npm run build` o `npm run dev`.

> **Importante:** No ejecutar `migrate:fresh` ni `db:wipe` en entornos con datos reales sin autorización explícita.

---

## Credenciales demo (tras `db:seed`)

| Rol | Email | Contraseña | Home |
|-----|-------|------------|------|
| Súper Admin | `admin@control-acceso.test` | `Admin123!` | `/admin/dashboard` |
| Admin Empresa | `empresa@sj-seguridad.test` | `Empresa123!` | `/company/dashboard` |
| Admin Cliente | `admin@palmasdelingenio.test` | `Cliente123!` | `/client/dashboard` |
| Guardia | `guardia@control-acceso.test` | `Guardia123!` | `/access/operations` |
| Residente | `anfitrion@control-acceso.test` | `Anfitrion123!` | `/resident/dashboard` |

**Datos piloto:** empresa SJ Seguridad, clientes *Palmas del Ingenio* y *Torres de la Loma*, Torre A + 10 apartamentos, 20 personas en censo.

Los usuarios demo se crean en `DemoUsersSeeder` (idempotente con `updateOrCreate`). Orden de ejecución en `DatabaseSeeder`:

1. `RoleAndPermissionSeeder` — roles y permisos Spatie
2. `LocationSeeder` — ubicaciones base
3. `TenantSeeder` — empresa + clientes piloto
4. `DemoUsersSeeder` — **todos** los usuarios demo (plataforma, empresa, cliente, portería, residente)
5. `StructureSeeder` — árbol residencial y censo piloto

```bash
php artisan db:seed --class=DemoUsersSeeder   # solo usuarios demo
```

---

## Landing pública (`/`)

Vista de bienvenida para invitados (`resources/views/welcome.blade.php`). Usuarios autenticados siguen yendo a `/home`.

| Asset | Ruta |
|-------|------|
| Logo | `resources/images/branding/logo-controla.png` |
| Favicon | `resources/images/branding/favicon.ico` |
| Fondo | `resources/images/welcome/hero-background.png` |
| Hero dashboard | `resources/images/welcome/hero-dashboard.png` |

Diseño: una pantalla sin scroll (`h-screen`), hero 40/60 (texto / imagen), 3 cards (Portería, Censo, Multi-cliente), CTA a `/login`.

---

## Login (`/login`)

Vista de autenticación con layout dedicado `AuthLayout` (`resources/views/layouts/auth.blade.php`).

| Elemento | Detalle |
|----------|---------|
| Fondo | `hero-background.png` con opacidad reducida + overlay oscuro |
| Formulario | Card glass centrado, tema cyan/slate |
| Textos | Español (B2B, sin registro público) |
| Logo | `logo-controla.png` sobre el formulario |
| Volver | Enlace a `/` |

Componente: `app/View/Components/AuthLayout.php` · Vista: `resources/views/auth/login.blade.php`

Otras rutas auth (recuperar contraseña, etc.) siguen usando `GuestLayout` de Breeze hasta migrarlas.

---

## Fase 0 — Multi-tenant (implementado)

### Base de datos

- `security_companies` — empresas de seguridad + **paquete comercial** (`package_sku`, `package_size`, `package_modality`, `max_clients`, `package_price_monthly`)
- `clients` — conjuntos (`login_suffix`; columnas legacy `plan_tier`/`max_structures` ya no limitan el portafolio)
- `client_user_assignments` — asignación usuario ↔ cliente
- `client_id` en tablas operativas (locations, buildings, residents, vehicles, etc.)

### Paquetes comerciales (empresa)

La empresa contrata un **cupo de conjuntos** (1 / 5 / 10 / 50 / 100) × **modalidad** (sin hardware / con hardware) × **ciclo** (mensual / anual).

| Concepto | Regla |
|----------|--------|
| Precios base | Solo el **súper admin** define 2 unitarios (manual y hardware) en `/admin/pricing` |
| Matriz | Se calcula sola: descuento por volumen + descuento anual (~17%) |
| Cupo | Máximo de conjuntos (`clients`) que puede crear la empresa |
| Portafolio del conjunto | **Ilimitado** (unidades, personas, mascotas, vehículos) |
| Snapshot | Al asignar paquete se congelan precio, descuentos y vigencia en la empresa |

Catálogo de reglas: `config/tenancy.php` → `pricing` · Motor: `App\Services\Pricing\PriceCalculator`

**Documentación completa:** [`docs/MODELO-COMERCIAL-PAQUETES.md`](docs/MODELO-COMERCIAL-PAQUETES.md)

#### Ejemplo de matriz (valores dependen de unitarios en BD)

| Cupo | Desc. vol. | Manual / mes | Hardware / mes |
|------|------------|--------------|----------------|
| 1 | 0% | unitario × 1 | unitario × 1 |
| 5 | 10% | × 0,90 | × 0,90 |
| 10 | 15% | × 0,85 | × 0,85 |
| 50 | 25% | × 0,75 | × 0,75 |
| 100 | 30% | × 0,70 | × 0,70 |

Ciclo **anual**: total mensual × 12 × (1 − 17%). El súper admin solo edita los dos unitarios en `/admin/pricing`.

### Arquitectura

- `TenantContext` + `ClientScope` + trait `BelongsToClient`
- Middleware: `tenancy.access`, `tenant.unscoped`, `company`, `client.admin`, `platform.admin`
- Capas: Controllers → Services → Repositories → Models
- Policies Spatie + permisos en `config/access.php`
- Helper: `App\Support\Tenancy\CompanyPackage` · `AssignCompanyPackageService`

### Panel Plataforma (`/admin`)

Documentación completa: [`docs/PLATAFORMA-ADMIN.md`](docs/PLATAFORMA-ADMIN.md)

| Ruta | Función |
|------|---------|
| `GET /admin/dashboard` | Dashboard analítico: mapa, KPIs, cartera, paquetes, TOP facturación, tendencia MRR |
| `POST /admin/companies/{id}/archive` | Archivar empresa (cascada a clientes) |
| `POST /admin/companies/{id}/clients/{client}/release` | Retirar conjunto y liberar cupo |
| `GET /admin/pricing` | Tabla de precios (editar unitarios, matriz calculada) |
| `PUT /admin/pricing` | Guardar unitarios manual/hardware |
| `GET /admin/companies` | Listado empresas + cupo operativo/paquete/ciclo |
| `GET /admin/companies/{id}` | Detalle y cambio de paquete + ciclo |
| `PUT /admin/companies/{id}/package` | Asignar SKU comercial y facturación |

**Ciclo comercial:** gracia → suspensión → archivo (`subscriptions:process-lifecycle`, diario 02:00).

**Retención legal:** purga datos operativos tras 365 días y anonimización comercial tras 5 años (`data:purge-retention`, mensual día 1 03:00). Config: `config/retention.php`.

**Cupo:** solo `lifecycle = active` consume slot (`operationalClientsCount()`).

### Panel Empresa (`/company`)

| Ruta | Función |
|------|---------|
| `GET /company/dashboard` | Licencia: cupo, ciclo, precio, upgrades sugeridos |
| `GET /company/clients` | Cartera de conjuntos (búsqueda, dirección, operar) |
| `GET /company/clients?modo=operar` | Modo portería: elegir conjunto y entrar |
| `GET /company/porteria` | Entrada inteligente a portería (auto si hay 1 conjunto) |
| `POST /company/clients` | Alta de cliente (bloqueada si cupo lleno) |

`/company/clients/select` redirige a `/company/porteria` (vista eliminada).

### Diseño UI — Panel Empresa (`/company`)

Sistema visual unificado para el shell y formularios del panel empresa. **Guía completa:** [`docs/DISENO-UI-CONTROLA.md`](docs/DISENO-UI-CONTROLA.md)

| Elemento | Detalle |
|----------|---------|
| Layout | `resources/views/layouts/company.blade.php` — header con título, empresa, **Portería** y **+ Conjunto**; sidebar Resumen + Clientes |
| Dashboard | Franja de licencia, tabla de conjuntos (protagonista) y panel lateral **Cuenta** (ciclo, ampliar cupo, features) |
| Componentes | `x-ui.button`, `x-ui.label`, `x-ui.input`, `x-ui.field-error` en `resources/views/components/ui/` |
| Contexto cupo | `CompanyLayoutComposer` inyecta `companyContext` en el layout |
| Vistas migradas | `company/dashboard`, `company/clients/index`, `company/clients/create`, `company/clients/edit` |

Variantes de botón: `primary` (indigo), `secondary`, `success` (emerald), `platform` (violet en `/admin`). Tamaños: `sm`, `md`.

**Pendiente migración:** `company/clients/show` y paneles conjunto/portería con acentos propios (teal/indigo).

### Diseño UI — Panel Plataforma (`/admin`)

| Elemento | Detalle |
|----------|---------|
| Layout | `resources/views/layouts/admin.blade.php` — sidebar violet, header con Precios/Empresas |
| Dashboard | Mapa geográfico (Google Maps), KPIs, estado de cartera, modalidad/cupo/ciclo, TOP 5 facturación, KPIs comerciales, tendencia MRR (Chart.js) |
| Analytics | `PlatformDashboardAnalytics` — agregación de métricas y marcadores del mapa |
| Geolocalización | `latitude`/`longitude` en empresas y conjuntos; migración `2026_07_26_120000_add_geolocation_to_companies_and_clients` |
| Mapa | Toggle Empresa / Clientes; requiere `GOOGLE_MAPS_API_KEY` en `.env` |
| Componentes | Mismos `x-ui.*` con `variant="platform"` y `accent="platform"` en inputs |
| Vistas migradas | `admin/dashboard`, `admin/companies/*`, `admin/pricing/edit` |

---

## Fase 1 — Estructura / censo (implementado)

### Modelo unificado `structures`

Árbol autoreferencial: conjunto → torre → apartamento (tipos: `general_area`, `block`, `apartment`, `house`, `office`, `commercial_store`).

Tablas relacionadas:

- `structure_members` — personas del censo + `access_code` (QR)
- `structure_pets` — mascotas
- `visitor_pre_authorizations` — pre-autorizaciones con `qr_auth_token`
- `structure_app_users` — usuarios APP (`usuario@login_suffix`)
- `vehicles.structure_id` — vehículos vinculados a unidad

### Panel Conjunto (`/client`)

| Ruta | Módulo |
|------|--------|
| `/client/dashboard` | Resumen unidades |
| `/client/structures` | Árbol residencial + badges censo (incluye conteo de mascotas) |
| `/client/members` | Directorio personas + QR + **Exportar listado asamblea** |
| `/client/pets` | Directorio de mascotas por unidad |
| `/client/vehicles` | Directorio vehicular |
| `/client/authorizations` | Pre-autorizaciones |
| `/client/authorizations/import` | Import Excel (`maatwebsite/excel`) |
| `/client/app-users` | Usuarios APP móvil |

### Mascotas (`/client/pets`) — CRUD completo

- **Rutas**: `index`, `create`, `store`, `show` (sigue el patrón de Members)
- **Especies**: Perro, Gato, Ave, Otro/exótico (enum `PetSpecies`)
- **Marcador de peligrosidad**: `is_potentially_dangerous` con badge rojo
- **Filtros**: búsqueda por nombre/raza, filtro por unidad
- **Vista de estructura**: lista de mascotas embebida en la vista `structures.show`

### Exportar listado asamblea (`/client/members/export`)

Descarga un archivo Excel con el censo completo de personas para juntas de propietarios:

- **Clase**: `MembersAssemblyExport` (maatwebsite/excel)
- **Columnas**: Nombre completo, Documento, Tipo (propietario/inquilino), Unidad, Teléfono, Email, Acceso APP
- **Ordenado**: por apellido y nombre
- Botón `Exportar` en la vista de directorio de personas

### Servicios clave

- `StructureRepository` — árbol, contadores censo
- `MigrateLegacyStructuresService` — buildings/housing_units → structures
- `SeedPilotStructuresService` — datos piloto
- `ImportAuthorizationsService` — Excel columnas: `visitante`, `estructura`, `fecha`

---

## Limpieza arquitectónica (implementado)

- Eliminado dashboard genérico Breeze (`/dashboard` + `dashboard.blade.php`)
- `/dashboard` redirige a `/home` (resolver por rol)
- Panel **Plataforma** `/admin/dashboard` para Súper Admin
- `ResolveUserHomeRoute` centraliza redirects post-login
- Permisos explícitos: `platform.dashboard`, `platform.companies.*`

---

## Módulo Portería (`/access`) — línea base

Dashboard operativo con KPIs (personas dentro, visitantes, correspondencia pendiente, etc.). Sigue usando layout Breeze (`x-app-layout`) y modelos legacy (`buildings`, `housing_units`, `residents`) en paralelo al nuevo censo `structures`.

### Fase 2 — Hub de Operaciones (`/access/operations`)

**Centro de operaciones unificado** que reemplaza el dashboard como pantalla principal del guardia:

- **Matriz 3×3 de acceso rápido**: Ingreso Peatonal, Ingreso Vehicular, Registrar Salida, Pre-Autorizaciones, Correspondencia, Minutas, Personas Dentro, Reportes, Búsqueda Rápida
- Cada botón se muestra según los permisos del usuario
- **Personas Dentro**: tabla en tiempo real con nombre, documento, tipo, destino, ubicación, tiempo transcurrido
- **Alertas >12h**: las personas con más de 12 horas dentro se marcan en rojo con ícono de advertencia y un resumen de alerta al final
- **Estadísticas rápidas**: dentro, hoy, correspondencia pendiente, pre-autorizaciones pendientes
- **Salida directa**: botón "Salida" en cada fila que registra la salida con confirmación

### Fase 2 — Lista de Bloqueo (`/access/blocklist`)

Permite denegar acceso a personas o vehículos desde la portería:

- **Tabla `blocklist`**: polimórfica (`blockable_type`/`blockable_id`) para visitantes, vehículos y residentes
- **CRUD completo**: crear con búsqueda por tipo, listar activos, remover bloqueo
- **Expiración opcional**: se puede establecer fecha de expiración del bloqueo
- **Permiso**: `access.manage.blocklist` (asignado a guardia, supervisor, client-admin)

### Fase 2 — Salida Masiva

Botón `Salida Masiva` en la vista de Ingreso/Salida que marca como `completed` todos los registros activos del día:

- Método `bulkExit()` en `AccessLogController`
- Confirmación antes de ejecutar
- Útil para cierre de turno o jornada

### Fase 3 — Reportes Mejorados (`/access/reports`)

- **Nuevos filtros**: tipo de acceso (visitante, vehicular, residente) además de fecha, estado y ubicación
- **Exportación a Excel**: botón `Exportar Excel` que descarga los resultados filtrados como `.xlsx`
- **Nuevas estadísticas**: total ingresos, dentro, hoy, visitantes, promedio de estadía
- Clase `AccessLogsExport` con `FromQuery`, `WithMapping`, `WithHeadings`, `ShouldAutoSize`

---

## Tests

Los tests usan una **base de datos aislada** (`controla_test`), configurada en `phpunit.xml`. No tocan la BD de desarrollo (`controla` en `.env`).

Crear la BD de test una sola vez (Laragon / MySQL):

```sql
CREATE DATABASE IF NOT EXISTS controla_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan test
```

Suites relevantes:

- `tests/Feature/Tenancy/TenantIsolationTest.php`
- `tests/Feature/Structure/StructureModuleTest.php`
- `tests/Feature/Platform/PlatformDashboardTest.php`
- `tests/Unit/Platform/DataRetentionPurgeTest.php`
- `tests/Feature/Auth/LoginCsrfTest.php`
- `tests/Unit/Pricing/PriceCalculatorTest.php`

> Los tests usan `RefreshDatabase` y **recrean** `controla_test` en cada ejecución. Nunca ejecutar la suite completa contra la BD de desarrollo.

---

## Estructura de carpetas (nuevas)

### Portal Residente (`/resident`) — Fase 4

Panel web para residentes con pre-autorizaciones y seguimiento de correspondencia:

| Ruta | Función |
|------|---------|
| `/resident/dashboard` | Resumen: pre-autorizaciones activas y correspondencia pendiente |
| `/resident/pre-authorizations` | Listado y cancelación de pre-autorizaciones |
| `/resident/pre-authorizations/create` | Crear nueva autorización de visita (nombre, documento, fecha, ubicación) |
| `/resident/correspondence` | Historial de correspondencia recibida y entregada |
| `/resident/correspondence/{id}` | Detalle de paquete o encomienda |

- Layout dedicado: `layouts/resident.blade.php` (tema oscuro, navegación propia)
- Componente Blade: `ResidentLayout`
- Roles: `resident`, `anfitrion`
- Redirección post-login: `ResolveUserHomeRoute` → `/resident/dashboard`

## API REST (`/api`) — Sanctum

API autenticada con tokens Laravel Sanctum para consumo desde app móvil futura.

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/auth/login` | POST | Login con email+password, devuelve token |
| `/api/auth/me` | GET | Datos del usuario autenticado |
| `/api/auth/logout` | POST | Revoca token actual |
| `/api/pre-authorizations` | GET | Lista de pre-autorizaciones del usuario |
| `/api/pre-authorizations` | POST | Crear pre-autorización |
| `/api/pre-authorizations/{id}` | GET | Detalle de pre-autorización |
| `/api/pre-authorizations/{id}` | DELETE | Cancelar pre-autorización |
| `/api/correspondence` | GET | Lista de correspondencia del usuario |
| `/api/correspondence/{id}` | GET | Detalle de correspondencia |
| `/api/visitors/search` | GET | Buscar visitantes por nombre/documento |

```
app/
├── Domain/Pricing|Structure|Tenant/  # DTOs (PriceQuote, CreateClientData, etc.)
├── Enums/                          # BillingCycle, CompanyPackageSku, PackageModality, etc.
├── Exports/                        # AccessLogsExport, MembersAssemblyExport
├── Http/Controllers/
│   ├── Api/                        # Sanctum API
│   ├── Platform/                   # Dashboard, Pricing, Company (súper admin)
│   ├── Company/                    # Admin Empresa
│   ├── Client/                     # Admin Cliente
│   ├── Resident/                   # Portal Residente
│   └── Access/                     # Portería
├── Models/PricingSettings.php      # Unitarios editables por súper admin
├── Repositories/
├── Services/Pricing/               # PriceCalculator, UpdatePlatformPricingService
├── Services/Platform/              # Dashboard analytics, archivo, retiro, lifecycle, purga retención
├── Services/Tenant/                # AssignCompanyPackageService, CreateClientService, EnterPorteriaService
├── Policies/
├── View/Components/
├── View/Composers/CompanyLayoutComposer.php
└── Support/Tenancy/CompanyPackage.php

resources/views/components/ui/     # x-ui.* (button, label, input, field-error)

routes/modules/
├── admin.php
├── company.php
├── client.php
├── access.php
└── resident.php

routes/api.php                   # Sanctum endpoints
```

---

## Comandos útiles

```bash
php artisan migrate                         # aplicar migraciones nuevas
php artisan subscriptions:process-lifecycle # gracia → suspensión → archivo (también programado diario)
php artisan data:purge-retention            # purga censo post-retención (también programado mensual)
php artisan db:seed                         # datos demo (aditivo, todos los seeders)
php artisan db:seed --class=RoleAndPermissionSeeder  # sincronizar permisos tras cambios en config/access.php
php artisan db:seed --class=DemoUsersSeeder # solo usuarios demo
php artisan db:seed --class=TenantSeeder    # solo empresa y clientes
php artisan config:clear
php artisan route:list --path=admin         # rutas plataforma (pricing, empresas)
php artisan route:list --path=company       # rutas admin empresa
php artisan route:list --path=api           # ver rutas API
php artisan test                            # usa controla_test, no controla
npm run build                               # compilar assets Vite para producción
npm run dev                                 # Vite en desarrollo
```

### Seguridad de base de datos

**Prohibido** en BD de desarrollo sin autorización explícita:

- `migrate:fresh`, `migrate:refresh`, `db:wipe`
- Ejecutar `php artisan test` sin `controla_test` configurada en `phpunit.xml`

Regla del proyecto para el agente IA: `.cursor/rules/database-safety.mdc`

### Git — remoto oficial

Publicar en la rama de integración del fork (flujo acordado):

```bash
git push origin main:creawilder
```

Para publicar directamente en `main` de **wmcodesoft** solo cuando se solicite explícitamente:

```bash
git push wmcodesoft main
```

---

## Licencia

Proyecto privado — uso interno Creawilder / Controla.
