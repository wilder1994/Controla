# Usuarios, perfiles y ubicación

Gestión de usuarios web (`users`) por panel, perfil de empresa con geolocalización y datos de conjuntos.

**Última actualización:** 26 agosto 2026

La **ficha de empleado** (maestro, Excel) vive en el sidebar **Empleados**. Cargos, tipos y catálogos de Supervisión de campo: **Ajustes**. Ver [`EMPLEADOS-Y-CARGOS.md`](EMPLEADOS-Y-CARGOS.md) y [`SUPERVISION-CAMPO.md`](SUPERVISION-CAMPO.md). Este documento cubre **usuarios** (`users`): login y roles.

Sidebar empresa: **Mi empresa** (dashboard) · **Empleados** · **Mis datos** (este perfil) · **Ajustes** (Cargos | Tipos | Zonas | Turnos | Preoperacional).

---

## Glosario operativo (nombres canónicos)

Usar **siempre** estos nombres en UI y documentación de producto. Los slugs Spatie se mantienen por compatibilidad.

| Nombre de producto | Rol Spatie (`users`) | Pertenece a | Resumen |
|--------------------|----------------------|-------------|---------|
| **Vigilante** | `guardia` | Empresa | Opera un **puesto/cliente** a la vez. Login usuario+contraseña = control de quién está de turno. |
| **Supervisor de vigilancia** | `supervisor` | Empresa | Recorre puestos. Firma revista en puesto (código 6 dígitos) si el sitio solo tiene Accesos. Con Supervisión en ese sitio, firma en la app de campo (llena la misma minuta). Login API: `/api/supervision/login`. |
| **Administrador conjunto** | `client-admin` | Cliente (conjunto) | Administra el conjunto. **No** es supervisor ni vigilante. |
| **Administrador empresa** | `company-admin` | Empresa | Cartera, usuarios operativos, perfil. |
| **Súper administrador** | `super-admin` | Plataforma | Panel `/admin`. |

**Prohibido en producto:** llamar “guarda/guardia” al vigilante; llamar “supervisor” al admin del conjunto; inventar un segundo tipo de supervisor (p. ej. “supervisor portería” vs “supervisor empresa”). Hay **un solo** supervisor: el de vigilancia.

### Relación empresa ↔ cliente (cobros)

Controla **no** factura ni muestra deuda del conjunto hacia la empresa de seguridad. Ese cobro es externo (contrato de vigilancia).

En el cliente se registran datos comerciales (`party_type`, documento, contactos, representante) y **`service_started_at`**: fecha en que se aperturó / inició el servicio en Controla. El **tipo de estructura** se fija en el alta (`structure_type_id`). Ver [`CLIENTES-Y-ESTRUCTURA.md`](CLIENTES-Y-ESTRUCTURA.md).

---

## Reglas: Vigilante

1. Asignado a **un único** conjunto a la vez (`client_user_assignments` + `primary_client_id`).
2. Se puede **reasignar** a otro conjunto en cualquier momento.
3. Al reasignar de conjunto: **obligatorio cambiar la contraseña** (mismo email/usuario de acceso).
4. Se puede editar la **ficha de empleado** (nombre, cargo/función, foto) sin crear otro usuario (ej. portería ↔ ronda).
5. El sistema debe poder responder siempre: *¿a qué cliente está asignado este vigilante?*

Slug técnico: `guardia`. Label UI: **Vigilante**.

---

## Reglas: Supervisor de vigilancia

1. Pertenece a la **empresa** (`security_company_id`). **No** requiere asignación fija a un conjunto.
2. Al crear el usuario se genera un **`supervisor_code`**: numérico, **6 dígitos**, **permanente** hasta regeneración deliberada.
3. El código es único **por empresa**.
4. **Revista:** si el sitio solo tiene Accesos, firma en la sesión del vigilante (código). Si el sitio tiene Supervisión, la revista se hace en la app de campo; no se vuelve a firmar en puesto.
5. Con Supervisión: rito de turno (catálogo de turno/zona, EPP, flota, fotos) y 8 módulos. Ver [`SUPERVISION-CAMPO.md`](SUPERVISION-CAMPO.md).
6. No confundir con admin del conjunto ni con el vigilante de turno.

Slug técnico: `supervisor`. Label UI: **Supervisor de vigilancia**.

---

## Alcance por panel

| Panel | Rutas | Quién puede gestionar |
|-------|-------|------------------------|
| **Plataforma** | `/admin/users` | Todos los `users` y roles |
| **Empresa** | `/company/users` | Usuarios de `security_company_id` + usuarios asignados a conjuntos de esa empresa |
| **Conjunto** | `/client/users` | El propio admin + residentes/vigilantes del tenant (`client_user_assignments`) |

**No mezclar** con `structure_app_users` (usuarios APP móvil `usuario@login_suffix`) — gestionados en `/client/app-users`.

### Roles asignables

| Panel | Roles |
|-------|--------|
| Plataforma | `super-admin`, `company-admin`, `client-admin`, `guardia`, `supervisor`, `resident`, `anfitrion`, `admin-accesos` |
| Empresa | `company-admin`, `client-admin`, `guardia`, `supervisor` |
| Conjunto | `resident`, `anfitrion`, `guardia` |

Roles que requieren asignación a conjunto (`client_ids`): `client-admin`, `guardia`, `resident`, `anfitrion`.

- `guardia` (Vigilante): **exactamente un** conjunto.
- `supervisor`: **sin** `client_ids` (alcance empresa).

---

## Campos relevantes

### `users`

| Campo | Uso |
|-------|-----|
| `job_title` | Cargo / función del empleado (portería, ronda, etc.) |
| `avatar_path` | Foto de perfil (storage `public`) |
| `supervisor_code` | Código revista 6 dígitos (solo rol supervisor) |
| `primary_client_id` | Cliente actual del vigilante (y otros roles con asignación) |
| `must_change_password` | Flag existente; usable si se fuerza cambio en login |

### `clients`

| Campo | Uso |
|-------|-----|
| `service_started_at` | Fecha de inicio de servicio (única fecha comercial operativa del conjunto en Controla) |

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
| `GET/POST /company/users/create` | Crear usuario empresa / vigilante / supervisor |
| `GET/PUT /company/users/{user}/edit` | Editar (foto, cargo, reasignación, código supervisor) |
| `GET /company/settings` | **Mis datos**: perfil legal y ubicación |
| `PUT /company/settings` | Guardar perfil + ubicación |
| `GET/POST /company/clients` | Cartera de conjuntos (`service_started_at`) |

### Conjunto

| Ruta | Función |
|------|---------|
| `GET /client/users` | Residentes + vigilantes del conjunto |
| `GET/POST /client/users/create` | Crear |
| `GET/PUT /client/users/{user}/edit` | Editar |

---

## Ubicación y dirección

### Modelo

| Entidad | Campos |
|---------|--------|
| `security_companies` | `address`, `city`, `department`, `latitude`, `longitude` |
| `clients` | `address`, `city`, `department`, `latitude`, `longitude`, `service_started_at` |
| `commercial_signup_intents` | `address`, `city`, `department`, `latitude`, `longitude` (paso datos) |

Migración geo y operativos: absorbidas en creates baseline (`create_security_companies`, `create_clients`, `create_users` + FKs).

### UI compartida

Componente Blade: `x-ui.geo-address-fields` (dirección, ciudad, departamento + lat/long; botón mapa).

JS: `resources/js/geo-address-picker.js` (Places Autocomplete + Geocoding; requiere APIs en la clave Google Maps).

Icono: `resources/images/ui/map-pin.png` → servir en `public/images/ui/` (carpeta `public/images` ignorada por git).

Usado en: signup paso 1, **Mis datos** (`/company/settings`), perfil/alta admin empresa, alta/edición de conjuntos.

### Reglas de negocio empresa

- `tax_id` **inmutable** tras `hasCompletedAcceptance()` (clickwrap en expediente).
- Servicio: `UpdateCompanyProfileService` · DTO: `GeoAddressData` · reglas: `GeoAddressRules`.
- Alta admin: `CreateCompanyService` · `StoreCompanyRequest` · rutas `admin.companies.create/store`.

---

## Arquitectura

| Pieza | Ubicación |
|-------|-----------|
| Alcance queries | `UserScopeResolver` |
| Autorización | `UserPolicy`, `SecurityCompanyPolicy` |
| CRUD usuarios | `ManageScopedUserService` |
| Roles / labels | `AssignableRoles` |
| Listado paginado | `UserRepository::paginateScoped()` |

Vistas compartidas: `modules/shared/managed-user-form.blade.php`, `modules/shared/company-profile-form.blade.php`.

---

## Portería (minuta y turno)

- Firma de **revista / minuta** por código en `/access` (tipo Revista + código de catálogo o `users.supervisor_code`).
- Turno abierto del vigilante: `guard_shifts` + `TurnoService` (`/access/turnos`).
- Turno del **supervisor** (Supervisión): `supervisor_shifts` + PWA. Distinto del turno de portería.

---

## Tests

```bash
php artisan test --filter=ScopedUserManagementTest
```

---

Ver también: [`EMPLEADOS-Y-CARGOS.md`](EMPLEADOS-Y-CARGOS.md) · [`LANDING-Y-CONTRATACION.md`](LANDING-Y-CONTRATACION.md) · [`PLATAFORMA-ADMIN.md`](PLATAFORMA-ADMIN.md) · [`MODULO-DOCUMENTOS.md`](MODULO-DOCUMENTOS.md)
