# Usuarios, perfiles y ubicación

Gestión de usuarios web (`users`) por panel, perfil de empresa con geolocalización y datos de conjuntos.

**Última actualización:** agosto 2026

---

## Alcance por panel

| Panel | Rutas | Quién puede gestionar |
|-------|-------|------------------------|
| **Plataforma** | `/admin/users` | Todos los `users` y roles |
| **Empresa** | `/company/users` | Usuarios de `security_company_id` + usuarios asignados a conjuntos de esa empresa |
| **Conjunto** | `/client/users` | El propio admin + residentes/guardias del tenant (`client_user_assignments`) |

**No mezclar** con `structure_app_users` (usuarios APP móvil `usuario@login_suffix`) — gestionados en `/client/app-users`.

### Roles asignables

| Panel | Roles |
|-------|--------|
| Plataforma | `super-admin`, `company-admin`, `client-admin`, `guardia`, `supervisor`, `resident`, `anfitrion`, `admin-accesos` |
| Empresa | `company-admin`, `client-admin`, `guardia`, `supervisor` |
| Conjunto | `resident`, `anfitrion`, `guardia`, `supervisor` |

Roles que requieren asignación a conjunto (`client_ids`): `client-admin`, `guardia`, `supervisor`, `resident`, `anfitrion`.

---

## Permisos (`config/access.php`)

| Permiso | Uso |
|---------|-----|
| `platform.users.view` / `platform.users.manage` | Listado y CRUD global |
| `company.users.assign` | CRUD usuarios en panel empresa |
| `company.settings.manage` | Perfil legal/geo de la empresa |
| `client.users.manage` | Usuarios portal del conjunto |

Tras cambios en permisos:

```bash
php artisan db:seed --class=RoleAndPermissionSeeder
```

---

## Rutas

### Plataforma

| Ruta | Función |
|------|---------|
| `GET /admin/users` | Listado global |
| `GET/POST /admin/users/create` | Crear usuario |
| `GET/PUT /admin/users/{user}/edit` | Editar usuario |
| `GET /admin/companies/{company}/profile` | Perfil empresa (legal + geo) |
| `PUT /admin/companies/{company}/profile` | Guardar perfil |

### Empresa

| Ruta | Función |
|------|---------|
| `GET /company/users` | Listado scoped |
| `GET/POST /company/users/create` | Crear usuario empresa/conjunto |
| `GET/PUT /company/users/{user}/edit` | Editar |
| `GET /company/settings` | Perfil de mi empresa |
| `PUT /company/settings` | Guardar perfil + ubicación |

### Conjunto

| Ruta | Función |
|------|---------|
| `GET /client/users` | Residentes + guardias del conjunto |
| `GET/POST /client/users/create` | Crear |
| `GET/PUT /client/users/{user}/edit` | Editar |

---

## Ubicación y dirección

### Modelo

| Entidad | Campos |
|---------|--------|
| `security_companies` | `address`, `latitude`, `longitude` |
| `clients` | `address`, `latitude`, `longitude` |
| `commercial_signup_intents` | `address`, `latitude`, `longitude` (paso datos) |

### UI compartida

Componente Blade: `x-ui.geo-address-fields` (dirección + lat/long opcionales).

Usado en: signup paso 1, `/company/settings`, perfil admin empresa, alta/edición de conjuntos.

El mapa del dashboard plataforma (`PlatformDashboardAnalytics`) consume coords de empresa o promedio de conjuntos.

### Reglas de negocio empresa

- `tax_id` **inmutable** tras `hasCompletedAcceptance()` (clickwrap en expediente).
- Servicio: `UpdateCompanyProfileService` · DTO: `GeoAddressData`.

---

## Arquitectura

| Pieza | Ubicación |
|-------|-----------|
| Alcance queries | `UserScopeResolver` |
| Autorización | `UserPolicy`, `SecurityCompanyPolicy` |
| CRUD usuarios | `ManageScopedUserService` |
| Roles por panel | `AssignableRoles` |
| Listado paginado | `UserRepository::paginateScoped()` |

Vistas compartidas: `modules/shared/managed-user-form.blade.php`, `modules/shared/company-profile-form.blade.php`.

---

## Tests

```bash
php artisan test --filter=ScopedUserManagementTest
```

---

Ver también: [`LANDING-Y-CONTRATACION.md`](LANDING-Y-CONTRATACION.md) · [`PLATAFORMA-ADMIN.md`](PLATAFORMA-ADMIN.md)
