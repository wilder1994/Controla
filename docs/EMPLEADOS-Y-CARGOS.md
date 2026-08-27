# Empleados, cargos y tipos (empresa)

**Última actualización:** 26 agosto 2026

Maestro de colaboradores de la empresa de seguridad. Distinto de **usuarios** (`/company/users`): la ficha es la persona; el usuario es el login. Ver [`USUARIOS-Y-PERFILES.md`](USUARIOS-Y-PERFILES.md).

Fuente de columnas: `Maestro Colaboradores WM.xlsx`, **sin** las cuatro de asignación a cliente (razón social, instalaciones, sector, puesto). Esas viven en la ficha del cliente.

---

## Dónde vive

Sidebar **Empleados** (maestro). **Ajustes** → pestañas **Cargos** | **Tipos** | **Zonas** | **Turnos** | **Preoperacional**. Las tres últimas son de Supervisión de campo (ver [`SUPERVISION-CAMPO.md`](SUPERVISION-CAMPO.md)).

| Pieza | Dónde |
|-------|--------|
| Dashboard | Sidebar **Mi empresa** (`/company/dashboard`) |
| Perfil legal | Sidebar **Mis datos** (`/company/settings`), sin pestañas |
| Listado / alta / ficha | Sidebar **Empleados** (`/company/employees`) |
| Cargos | Ajustes → `/company/job-titles` |
| Tipos de colaborador | Ajustes → `/company/collaborator-types` |
| Zonas / turnos / preoperacional (Supervisión) | Ajustes → `/company/supervision-zones`, `…-shifts`, `…-preop` |
| Formato Excel | `GET /company/employees/template` |
| Carga masiva | modal en el listado → preview → aceptar |

Permiso: `company.settings.manage`. Dar acceso (login) también pide `company.users.assign`.

---

## Cargos y tipos

Catálogos **por empresa**, no seeder de plataforma. El formulario usa select. Si el Excel trae un cargo o un tipo que no existe, el import **lo crea** al aceptar (aviso en el preview). No se elimina un cargo o tipo con empleados.

---

## Columnas del Excel (A–Z)

Rojo = obligatorio en el archivo. Gris = opcional en el archivo.

| Col | Encabezado | Controla |
|-----|------------|----------|
| A–B | Tipo y nro. documento | Obligatorio. Tipo del catálogo (código o nombre). Número único por empresa. |
| C–D | Ap. Paterno / Materno | **Al menos uno.** Los dos es mejor, no obligatorio. |
| E | Nombres | Obligatorio |
| F | Sexo | Hombre / Mujer |
| G | Edad | **No se carga** (se calcula) |
| H | Tipo Colaborador | Obligatorio. Se crea en el catálogo Tipos si falta. |
| I | Cargo | Obligatorio. Se crea en el catálogo si falta. |
| J | Mismo CC origen? | SI / NO, opcional |
| K | Fecha Nacimiento | Obligatoria |
| L–O | Nacimiento / emergencia | Opcional |
| P | Nacionalidad | Obligatoria |
| Q | Discapacidad | SI / NO, opcional |
| R | Email Ficha | Gris en archivo, **obligatorio** en sistema. Único por empresa. |
| S–U | Expedición documento | Opcional |
| V | G.Sanguíneo | O+, O-, A+, A-, B+, B-, AB+, AB- |

**No van en este Excel:** razón social, instalaciones, sector, puesto. Eso es del **cliente** y se arma a mano en la ficha (pestañas Accesos / Supervisión). «Sector» era ciudad; la ciudad del cliente está en el Excel de clientes. Ver [`CLIENTES-Y-ESTRUCTURA.md`](CLIENTES-Y-ESTRUCTURA.md).

No hay nombre de fantasía. No se archiva ni se crea usuario desde el Excel.

---

## Carga masiva

1. **Formato:** xlsx con hoja `Empleados` (vacía, mismos encabezados y colores) + hoja `Instrucciones` (no se importa).
2. **Carga masiva:** arrastrar, elegir archivo o pegar tabla (con encabezados).
3. **Revisar datos:** KPIs válidas / avisos / errores. Nada se guarda.
4. **Aceptar** solo si hay 0 errores. Luego vuelve al listado.

El import lee la hoja `Empleados`, o `WM`, o la primera hoja.

---

## Archivar

No está en el Excel. En la ficha: `is_active = false` + `ceased_at`. Si tenía usuario, se desactiva el acceso.
