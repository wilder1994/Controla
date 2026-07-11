# Controla

Plataforma SaaS B2B de **control de accesos y vigilancia** para empresas de seguridad privada y conjuntos residenciales en Colombia. Construida sobre **Laravel 11** (Laragon), con referencia funcional **Axesa Control v13**.

**Repositorio:** [github.com/wmcodesoft/Controla](https://github.com/wmcodesoft/Controla)

---

## Estado del proyecto

| Fase / release | Nombre | Estado |
|----------------|--------|--------|
| **0** | Fundación multi-tenant | ✅ Cerrada (gate 2026-07-11) |
| **1** | Estructura / censo | ✅ Cerrada (gate 2026-07-11) |
| **Limpieza** | Panel plataforma + residuos Breeze | ✅ Implementada |
| **Landing** | Vista pública `/` (welcome) | ✅ Implementada |
| **Auth** | Login `/login` (AuthLayout) | ✅ Implementada |
| **2 → v1.0** | Operación portería (piloto comercial) | 🚀 **En curso — próximo hito** |
| **3–4 → v1.1** | BI + vigilancia + portal residente | ⏳ Pendiente |
| **5 → v2.0** | Enterprise (white label, hardware, PH, antecedentes) | ⏳ Pendiente |

### Calidad y releases

- **Estándar obligatorio:** todo PR cumple [DoD senior](docs/ESTANDARES-IMPLEMENTACION-SENIOR.md) — no hay entregables “básicos” ni placeholders de producción.
- **v1.0** = multi-tenant + censo + **Fase 2 portería completa** (piloto vendible sin Excel).
- **v1.1** = paridad operativa Axesa (BI, minutas geo, API residente).
- **v2.0** = módulos enterprise antes etiquetados “OMITIR v1” (white label, RFID/LPR, PH avanzado, antecedentes).

Detalle: [`docs/ESTRATEGIA-VERSIONES-Y-ALCANCE.md`](docs/ESTRATEGIA-VERSIONES-Y-ALCANCE.md)

### Documentación

| Documento | Contenido |
|-----------|-----------|
| [`docs/PLAN-INICIO-PROYECTO-CONTROLA.md`](docs/PLAN-INICIO-PROYECTO-CONTROLA.md) | Roadmap fases 0–5 |
| [`docs/ESTRATEGIA-VERSIONES-Y-ALCANCE.md`](docs/ESTRATEGIA-VERSIONES-Y-ALCANCE.md) | **v1.0 / v1.1 / v2.0** — releases comerciales |
| [`docs/ESTANDARES-IMPLEMENTACION-SENIOR.md`](docs/ESTANDARES-IMPLEMENTACION-SENIOR.md) | **DoD senior** — calidad obligatoria por PR |
| [`docs/REFERENCIA-PLATAFORMA-CONTROL-ACCESOS.md`](docs/REFERENCIA-PLATAFORMA-CONTROL-ACCESOS.md) | Referencia Axesa v13 |

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
| Supervisor | `supervisor@sj-seguridad.test` | `Supervisor123!` | `/access/dashboard` |
| Guardia | `guardia@control-acceso.test` | `Guardia123!` | `/access/dashboard` |
| Residente | `anfitrion@control-acceso.test` | `Anfitrion123!` | pre-autorizaciones |

**Datos piloto:** empresa SJ Seguridad, clientes *Palmas del Ingenio* y *Torres de la Loma*, Torre A + 10 apartamentos, 20 personas en censo.

**Asignar operativos:** login como Admin Empresa → detalle de cliente → sección «Operativos asignados». El supervisor demo debe asignarse a un conjunto antes de operar portería.

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

## Fase 0 — Multi-tenant ✅ (gate 2026-07-11)

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
- Usuario con **varios clientes** debe elegir conjunto en `/company/clients/select` (no se auto-asigna por `primary_client_id`)

### Panel Empresa (`/company`)

| Ruta | Función |
|------|---------|
| `GET /company/dashboard` | Métricas de cartera |
| `GET /company/clients` | Listado de clientes |
| `POST /company/clients` | Alta de cliente (sin Súper Admin) |
| `GET /company/clients/select` | Selección de conjunto para operar portería |
| `POST /company/clients/{id}/assign` | Asignar guardas/supervisores al cliente |
| `DELETE /company/clients/{id}/assign/{user}` | Desasignar operativo |

### Seguridad portería

Todas las rutas `/access/*` exigen:

1. Sesión activa con `client_id` válido (`InitializeAccessTenancy`)
2. Permiso Spatie por recurso (`routes/modules/access.php`)

---

## Fase 1 — Estructura / censo ✅ (gate 2026-07-11)

### Modelo unificado `structures`

Árbol autoreferencial: conjunto → torre → apartamento (tipos: `general_area`, `block`, `apartment`, `house`, `office`, `commercial_store`).

Tablas relacionadas:

- `structure_members` — personas del censo + `access_code` (QR) + tab accesos portería
- `structure_pets` — mascotas (seed piloto; UI P1)
- `visitor_pre_authorizations` — pre-autorizaciones con `qr_auth_token`
- `structure_app_users` — usuarios APP (`usuario@login_suffix`)
- `vehicles.structure_id` — vehículos vinculados a unidad (SOAT, carnet, edición)

### Panel Conjunto (`/client`)

| Ruta | Módulo |
|------|--------|
| `/client/dashboard` | Resumen unidades |
| `/client/structures` | Árbol residencial + badges censo |
| `/client/structures/{id}` | Detalle con tabs Datos / Visitas / Correspondencia |
| `/client/members` | Directorio personas |
| `/client/members/create` | Wizard paso 1 (datos básicos) |
| `/client/members/create/confirm` | Wizard paso 2 (APP, porterías, QR) |
| `/client/members/{id}` | Detalle + QR + tab accesos portería |
| `/client/vehicles` | Directorio vehicular |
| `/client/vehicles/{id}/edit` | Edición vehículo |
| `/client/authorizations` | Pre-autorizaciones |
| `/client/authorizations/import` | Import Excel (`maatwebsite/excel`) |
| `/client/app-users` | Usuarios APP móvil |

### Servicios clave

- `StructureRepository` — árbol, contadores censo
- `MigrateLegacyStructuresService` — buildings/housing_units → structures
- `SeedPilotStructuresService` — torre + 10 aptos + 20 personas piloto
- `ImportAuthorizationsService` — Excel columnas: `visitante`, `estructura`, `fecha`
- `AssignClientUsersService` — asignación operativos empresa → cliente

### DoD verificado (tests automatizados)

Ver sección [Tests](#tests).

## Limpieza arquitectónica (implementado)

- Eliminado dashboard genérico Breeze (`/dashboard` + `dashboard.blade.php`)
- `/dashboard` redirige a `/home` (resolver por rol)
- Panel **Plataforma** `/admin/dashboard` para Súper Admin
- `ResolveUserHomeRoute` centraliza redirects post-login
- Permisos explícitos: `platform.dashboard`, `platform.companies.*`

---

## Módulo Portería (`/access`) — Fase 2 en curso

Dashboard operativo con KPIs (personas dentro, visitantes, correspondencia pendiente, etc.). Usa layout Breeze (`x-app-layout`) y modelos **legacy** (`buildings`, `housing_units`, `residents`) en paralelo al censo unificado (`structures`).

**Próximo hito v1.0:** integrar ingresos/salidas con `structures` + `structure_members` + pre-autorizaciones del panel `/client`.

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

Suites Fases 0–1 (17 tests):

| Suite | Qué valida |
|-------|------------|
| `tests/Feature/Tenancy/TenantIsolationTest.php` | Aislamiento tenant, panel empresa |
| `tests/Feature/Company/CompanyClientTest.php` | Alta cliente + asignación operativos |
| `tests/Feature/Access/AccessAuthorizationTest.php` | Permisos `/access/*`, selección conjunto |
| `tests/Feature/Structure/StructureModuleTest.php` | CRUD estructura, persona, vehículo |
| `tests/Feature/Structure/AuthorizationImportTest.php` | Import Excel 50 filas |
| `tests/Feature/Platform/PlatformDashboardTest.php` | Panel plataforma |
| `tests/Feature/Auth/LoginCsrfTest.php` | Login y CSRF |

Filtrar solo Fases 0–1:

```bash
php artisan test --filter="TenantIsolationTest|StructureModuleTest|PlatformDashboardTest|CompanyClientTest|AccessAuthorizationTest|AuthorizationImportTest"
```

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
├── View/Components/AuthLayout.php
└── Support/Tenancy/

routes/modules/
├── admin.php
├── company.php
├── client.php
└── access.php

resources/views/
├── layouts/
│   ├── auth.blade.php           # Login Controla
│   └── guest.blade.php          # Breeze (otras rutas auth)
├── auth/
│   └── login.blade.php
└── modules/
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

Reglas del agente IA: `.cursor/rules/database-safety.mdc` · `.cursor/rules/implementation-senior.mdc`

### Git — remoto oficial

Repositorio canónico: **wmcodesoft/Controla**. Publicar con:

```bash
git push wmcodesoft main
```

Política completa: `.cursor/rules/git-remote.mdc`

---

## Licencia

Proyecto privado — uso interno Creawilder / Controla.
