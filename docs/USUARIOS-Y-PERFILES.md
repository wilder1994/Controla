# Usuarios, perfiles y ubicación

Gestión de usuarios web (`users`) por panel, perfil de empresa con geolocalización y datos de clientes.

**Última actualización:** 11 septiembre 2026

La **ficha de empleado** (listado, 4 bloques SJ-SIG, foto, Excel WM + extras) vive en el sidebar **Empleados**. El Excel **no** crea usuario: solo la persona. Reimportar el mismo documento **actualiza** la ficha (no duplica). Cargos, tipos y catálogos de Supervisión de campo: **Ajustes**. Ver [`EMPLEADOS-Y-CARGOS.md`](EMPLEADOS-Y-CARGOS.md) y [`SUPERVISION-CAMPO.md`](SUPERVISION-CAMPO.md). Este documento cubre **usuarios** (`users`): login y roles.

Sidebar empresa: **Mi empresa** (dashboard) · Facturación · Clientes · Supervisión · **Descargas** · **Empleados** · **Documentos** · Usuarios · **Mis datos** (este perfil) · **Ajustes** (Cargos | Tipos | Estructuras | Zonas | Turnos | Preoperacional | Documentos | Libros | Tipos de arma | Marcas | Riesgos | Alarmas | Apoyos). Chatbot de ayuda y PQRS: pendiente, [`SUPERVISION-CAMPO.md`](SUPERVISION-CAMPO.md).

---

## Alta de usuario en empresa (regla única)

Panel `/company/users`. **Crear y editar usan el mismo formulario** (`modules/company/users/partials/form.blade.php`). No se da acceso desde la ficha de Empleados.

1. Primera fila: **Nombre** y **Cédula**. Ambos buscan empleados activos **sin** usuario. Elegir uno llena los dos campos.
2. **Usuario de acceso** (todos los roles de empresa: admin empresa, admin del cliente, supervisor, vigilante): primer nombre + primer apellido paterno, ASCII, minúsculas, más **4 dígitos aleatorios** que cambian en cada generación (`ana.perez.4821`). No es la cédula. Único en `users.username`. Ese es el **login**.
3. **Cargo / función**: select del catálogo `company_job_titles`. Al elegir empleado se precarga el cargo de la ficha.
4. **Email personal**: el de la ficha (`employees.email`). Solo lectura. **No** es login. No se copia a `users.email` en altas nuevas. Envío de usuario/clave por correo: pendiente.
5. Rol, cliente (solo vigilante / admin del cliente), generar clave, activo.
6. Clave aleatoria; `must_change_password` en la primera entrada (pantalla `/password/primera`, no Breeze `/profile`). Supervisor: además `supervisor_code` de 6 dígitos (revista Accesos, no login).
7. La zona de Supervisión **no** se pega al usuario ni se muestra en la tabla: se elige al abrir turno.
8. **No se elimina** la cuenta. **Desactivar** (pestaña Activos) pone `is_active = false`: sale del listado activo, no puede entrar al panel ni a la PWA, el historial queda. Pestaña **Desactivados** → Reactivar. No se puede desactivar a uno mismo.

Cuentas antiguas pueden seguir entrando con `users.email` si lo tienen. Plataforma (`/admin/users`) no usa este flujo. El panel del cliente (`/client/users`) crea **administradores del cliente** (`client-admin`), no vigilantes.

---

## Glosario operativo (nombres canónicos)

Usar **siempre** estos nombres en UI y documentación de producto. Los slugs Spatie se mantienen por compatibilidad.

| Nombre de producto | Rol Spatie (`users`) | Pertenece a | Resumen |
|--------------------|----------------------|-------------|---------|
| **Vigilante** | `guardia` | Empresa | Opera **portería** de una instalación **con puertas**. Debe estar asignado a un puesto de esa instalación. Sin puertas no hay usuario de portería. |
| **Supervisor de vigilancia** | `supervisor` | Empresa | Recorre **puestos de Supervisión** (`supervisor_posts`). Login PWA: `users.username`. **No** se le pega zona. Firma revista en la **minuta de portería** con código **6 dígitos** (`supervisor_code`). Con Supervisión, la ronda de campo sigue en la app. API: `/api/supervision/login`. |
| **Administrador del cliente** | `client-admin` | Cliente | Administra el panel del cliente (estructura, personas, accesos del censo). **No** es supervisor ni vigilante. |
| **Administrador empresa** | `company-admin` | Empresa | Cartera, usuarios operativos, perfil. |
| **Súper administrador** | `super-admin` | Plataforma | Panel `/admin`. |

**Prohibido en producto:** llamar “guarda/guardia” al vigilante; llamar “supervisor” al admin del cliente; inventar un segundo tipo de supervisor (p. ej. “supervisor portería” vs “supervisor empresa”). Hay **un solo** supervisor: el de vigilancia. El término de producto es **cliente**, no conjunto.

### Relación empresa ↔ cliente (cobros)

Controla **no** factura ni muestra deuda del cliente hacia la empresa de seguridad. Ese cobro es externo (contrato de vigilancia).

En el cliente se registran datos comerciales (`party_type`, documento, contactos, representante, ciudad) y **`service_started_at`**. El **tipo de estructura** se fija en el alta. Instalaciones, accesos y puestos **no** van en el Excel; se crean en las tarjetas de la ficha. Ver [`CLIENTES-Y-ESTRUCTURA.md`](CLIENTES-Y-ESTRUCTURA.md).

---

## Reglas: Vigilante

1. Asignado a **un único** cliente a la vez (`client_user_assignments` + `primary_client_id`).
2. El empleado debe estar en un **puesto de una instalación de ese cliente**.
3. Esa instalación debe tener **puertas**. Sin puertas no se crea (ni se necesita) el usuario de portería.
4. Se puede **reasignar** a otro cliente; al reasignar es **obligatorio cambiar la contraseña**.
5. Se puede editar la **ficha de empleado** (nombre, cargo/función, foto) sin crear otro usuario (ej. portería ↔ ronda).
6. El sistema debe poder responder siempre: *¿a qué cliente e instalación está asignado este vigilante?*

Slug técnico: `guardia`. Label UI: **Vigilante**.

---

## Reglas: Supervisor de vigilancia

1. Pertenece a la **empresa** (`security_company_id`). **No** requiere asignación fija a un cliente ni a una zona.
2. Alta de acceso: **Usuarios** → Nuevo → nombre y cédula del empleado (misma fila) → se genera **usuario de acceso** (`nombre.apellido.####`) y clave. Rol supervisor. El **email personal** de la ficha se muestra; no es el login. El correo corporativo de avisos está en **Ajustes → Zonas** y se toma al **abrir turno**. La ficha de empleado (Ficha/Editar) no crea usuarios.
3. Al crear el usuario también se genera un **`supervisor_code`**: numérico, **6 dígitos**, **permanente** hasta regeneración deliberada. Sirve para **pasar revista en la minuta de portería**. No es el usuario de la PWA.
4. El código de 6 dígitos es único **por empresa**.
5. **Revista Supervisión:** en la app de campo; no se vuelve a firmar en puesto. Zona se elige al abrir turno (Norte hoy, Sur mañana, o las dos el mismo día). Ver [`SUPERVISION-CAMPO.md`](SUPERVISION-CAMPO.md).
6. No confundir con admin del cliente ni con el vigilante de turno.

Slug técnico: `supervisor`. Label UI: **Supervisor de vigilancia**.

---

## Alcance por panel

| Panel | Rutas | Quién puede gestionar |
|-------|-------|------------------------|
| **Plataforma** | `/admin/users` | Todos los `users` y roles |
| **Empresa** | `/company/users` | Usuarios de `security_company_id` + usuarios asignados a clientes de esa empresa |
| **Cliente** | `/client/users` | Administradores del cliente (`client-admin`) |

**No mezclar** con `structure_app_users` (acceso de personas del censo, `usuario@login_suffix`) — gestionados en `/client/app-users`.

### Roles asignables

| Panel | Roles |
|-------|--------|
| Plataforma | `super-admin`, `company-admin`, `client-admin`, `guardia`, `supervisor`, `resident`, `anfitrion`, `admin-accesos` |
| Empresa | `company-admin`, `client-admin`, `guardia`, `supervisor` |
| Cliente | `client-admin` |

Roles que requieren asignación a cliente (`client_ids`): `client-admin`, `guardia`, `resident`, `anfitrion`.

- `guardia` (Vigilante): **exactamente un** cliente, y solo si hay puesto + puertas en una instalación.
- `supervisor`: **sin** `client_ids` (alcance empresa).

---

## Campos relevantes

### `users`

| Campo | Uso |
|-------|-----|
| `username` | Login: `nombre.apellido.####` (4 dígitos aleatorios) para **todos** los usuarios creados en empresa |
| `email` | Opcional. No es el login de las altas nuevas. Cuentas viejas pueden seguir entrando con él. El correo de la persona es `employees.email` (email personal). |
| `job_title` | Cargo / función del empleado (portería, ronda, etc.) |
| `avatar_path` | Foto de perfil (storage `public`) |
| `supervisor_code` | Código revista Accesos 6 dígitos (solo rol supervisor; no es login PWA) |
| `primary_client_id` | Cliente actual del vigilante (y otros roles con asignación) |
| `must_change_password` | Primera entrada: redirige a `password.first` (`/password/primera`) hasta cambiar la clave |

### `clients`

| Campo | Uso |
|-------|-----|
| `service_started_at` | Fecha de inicio de servicio (única fecha comercial operativa del cliente en Controla) |

---

## Permisos (`config/access.php`)

| Permiso | Uso |
|---------|-----|
| `platform.users.view` / `platform.users.manage` | Listado y CRUD global |
| `company.users.assign` | CRUD usuarios en panel empresa |
| `company.settings.manage` | Perfil legal/geo de la empresa |
| `client.users.manage` | Administradores del cliente |

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
| `GET /company/users` | Listado (`status=active` \| `inactive`) |
| `GET/POST /company/users/create` | Crear usuario empresa / vigilante / supervisor |
| `GET/PUT /company/users/{user}/edit` | Editar (foto, cargo, reasignación, código supervisor) |
| `POST /company/users/{user}/deactivate` | Desactiva el acceso; conserva historial |
| `POST /company/users/{user}/reactivate` | Reactiva el acceso |
| `GET /company/settings` | **Mis datos**: perfil, ubicación, logo (arrastrar / pegar / recortar) e encabezado de fichas |
| `PUT /company/settings` | Guardar perfil, logo y texto de encabezado |
| `GET/POST /company/clients` | Cartera de clientes (`service_started_at`) |

### Cliente

| Ruta | Función |
|------|---------|
| `GET /client/users` | Administradores del cliente |
| `GET/POST /client/users/create` | Crear admin del cliente |
| `GET/PUT /client/users/{user}/edit` | Editar |
| `GET /client/app-users` | Acceso de personas del censo |

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

Icono dirección: `resources/images/ui/map-pin.png`. Icono GPS supervisor (mapa Supervisión): `resources/images/ui/supervisor-moto.png`. Servir ambos en `public/images/ui/` (`public/images` ignorada por git).

Usado en: signup paso 1, **Mis datos** (`/company/settings`), perfil/alta admin empresa, alta/edición de clientes.

### Reglas de negocio empresa

- `tax_id` **inmutable** tras `hasCompletedAcceptance()` (clickwrap en expediente).
- Servicio: `UpdateCompanyProfileService` · DTO: `GeoAddressData` · reglas: `GeoAddressRules`.
- Alta admin: `CreateCompanyService` · `StoreCompanyRequest` · rutas `admin.companies.create/store`.
- Tras crear la empresa a mano, **paso obligatorio**: misma ficha de empleado + acceso `company-admin` (`CreateCompanyFirstAdminService` · `admin.companies.first-admin.*`). Tipo y cargo se crean en el modal de la ficha (no se siembran solos). Sin ese admin, el detalle redirige al paso 2. Usuario `nombre.apellido.####` y clave se muestran una vez.

---

## Arquitectura

| Pieza | Ubicación |
|-------|-----------|
| Alcance queries | `UserScopeResolver` |
| Autorización | `UserPolicy`, `SecurityCompanyPolicy` |
| CRUD usuarios | `ManageScopedUserService` |
| Roles / labels | `AssignableRoles` |
| Listado paginado | `UserRepository::paginateScoped()` |

Vistas empresa (crear = editar): `modules/company/users/partials/form.blade.php`. Plataforma y cliente: `modules/shared/managed-user-form.blade.php`. Perfil: `modules/shared/company-profile-form.blade.php`.

---

## Portería (minuta y turno)

- Firma de **revista / minuta** por código en `/access` (tipo Revista + `users.supervisor_code` de 6 dígitos, o catálogo `supervision_codes`). Válido aunque el cliente también tenga Supervisión de campo.
- Turno abierto del vigilante: `guard_shifts` + `TurnoService` (`/access/turnos`).
- Turno del **supervisor** (Supervisión): `supervisor_shifts` + PWA. Distinto del turno de portería.

---

## Tests

```bash
php artisan test --filter=ScopedUserManagementTest
php artisan test --filter=PorteriaRevistaTest
php artisan test --filter=StructureModuleTest
```

---

Ver también: [`EMPLEADOS-Y-CARGOS.md`](EMPLEADOS-Y-CARGOS.md) · [`LANDING-Y-CONTRATACION.md`](LANDING-Y-CONTRATACION.md) · [`PLATAFORMA-ADMIN.md`](PLATAFORMA-ADMIN.md) · [`MODULO-DOCUMENTOS.md`](MODULO-DOCUMENTOS.md)
