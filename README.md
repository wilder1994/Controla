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
| **2** | Operación portería (MVP) | ⏳ Pendiente |
| **3** | BI + vigilancia | ⏳ Pendiente |

Documentación detallada: [`docs/PLAN-INICIO-PROYECTO-CONTROLA.md`](docs/PLAN-INICIO-PROYECTO-CONTROLA.md) · [`docs/REFERENCIA-PLATAFORMA-CONTROL-ACCESOS.md`](docs/REFERENCIA-PLATAFORMA-CONTROL-ACCESOS.md)

---

## Superficies de producto

| Panel | Prefijo | Rol(es) | Descripción |
|-------|---------|---------|-------------|
| **Plataforma** | `/admin` | `super-admin` | KPIs globales, empresas de seguridad |
| **Empresa** | `/company` | `company-admin` | Cartera de clientes (conjuntos) |
| **Conjunto** | `/client` | `client-admin` | Censo: estructuras, personas, vehículos, autorizaciones |
| **Portería** | `/access` | `guardia`, `supervisor`, `client-admin` | Operación diaria (legacy + multi-tenant) |

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
| Guardia | `guardia@control-acceso.test` | `Guardia123!` | `/access/dashboard` |
| Residente | `anfitrion@control-acceso.test` | `Anfitrion123!` | pre-autorizaciones |

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

## Fase 0 — Multi-tenant (implementado)

### Base de datos

- `security_companies` — empresas de seguridad
- `clients` — conjuntos (`plan_tier`, `login_suffix`, `max_structures`)
- `client_user_assignments` — asignación usuario ↔ cliente
- `client_id` en tablas operativas (locations, buildings, residents, vehicles, etc.)

### Arquitectura

- `TenantContext` + `ClientScope` + trait `BelongsToClient`
- Middleware: `tenancy.access`, `tenant.unscoped`, `company`, `client.admin`, `platform.admin`
- Capas: Controllers → Services → Repositories → Models
- Policies Spatie + permisos en `config/access.php`

### Panel Empresa (`/company`)

| Ruta | Función |
|------|---------|
| `GET /company/dashboard` | Métricas de cartera |
| `GET /company/clients` | Listado de clientes |
| `POST /company/clients` | Alta de cliente |
| `GET /company/clients/select` | Selección de conjunto para operar portería |

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
| `/client/structures` | Árbol residencial + badges censo |
| `/client/members` | Directorio personas + QR |
| `/client/vehicles` | Directorio vehicular |
| `/client/authorizations` | Pre-autorizaciones |
| `/client/authorizations/import` | Import Excel (`maatwebsite/excel`) |
| `/client/app-users` | Usuarios APP móvil |

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
- `tests/Feature/Auth/LoginCsrfTest.php`

> Los tests usan `RefreshDatabase` y **recrean** `controla_test` en cada ejecución. Nunca ejecutar la suite completa contra la BD de desarrollo.

---

## Estructura de carpetas (nuevas)

```
app/
├── Domain/Structure|Tenant/     # DTOs
├── Enums/                       # StructureType, MemberType, etc.
├── Http/Controllers/
│   ├── Platform/                # Súper Admin
│   ├── Company/                 # Admin Empresa
│   └── Client/                  # Admin Cliente
├── Repositories/
├── Services/Structure|Tenant|Auth/
├── Policies/
└── Support/Tenancy/

routes/modules/
├── admin.php
├── company.php
├── client.php
└── access.php

resources/views/modules/
├── admin/
├── company/
├── client/
└── access/
```

---

## Comandos útiles

```bash
php artisan migrate                         # aplicar migraciones nuevas
php artisan db:seed                         # datos demo (aditivo, todos los seeders)
php artisan db:seed --class=DemoUsersSeeder # solo usuarios demo
php artisan db:seed --class=TenantSeeder    # solo empresa y clientes
php artisan config:clear
php artisan test                            # usa controla_test, no controla
npm run dev                                 # Vite en desarrollo
```

### Seguridad de base de datos

**Prohibido** en BD de desarrollo sin autorización explícita:

- `migrate:fresh`, `migrate:refresh`, `db:wipe`
- Ejecutar `php artisan test` sin `controla_test` configurada en `phpunit.xml`

Regla del proyecto para el agente IA: `.cursor/rules/database-safety.mdc`

### Git — remoto oficial

Publicar solo en **`wmcodesoft`** cuando se solicite explícitamente:

```bash
git push wmcodesoft main
```

---

## Licencia

Proyecto privado — uso interno Creawilder / Controla.
