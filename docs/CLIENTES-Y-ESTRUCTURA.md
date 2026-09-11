# Clientes, instalaciones, Accesos y Supervisión

**Última actualización:** 10 septiembre 2026

Fuente de verdad del **cliente comercial** y de los dos árboles operativos. El censo (nodos `structures`) sigue más abajo; no se mezcla con instalaciones ni con puestos de Supervisión.

Controla **no** cobra al cliente final por vigilancia; solo registra `service_started_at`.

---

## Glosario

| Término | Significado | Dónde |
|---------|-------------|--------|
| **Cliente** | Ficha comercial (`clients`). PH, oficinas, bodegas, etc. | Alta / Excel de **clientes** / pestaña Cliente |
| **Ciudad** | Ubicación del cliente (`clients.city` + `department`). **No es un nodo del árbol.** Lo que en el Excel viejo de empleados decía «Sector» era ciudad. | Ficha y Excel de clientes |
| **Instalación** | Sitio físico del cliente, **siempre con georreferencia**. Puede ser **el mismo cliente** (copia nombre + pin de la ficha). | Tarjeta **Instalaciones y puestos** |
| **Puerta** | Punto de portería (peatonal, vehicular, principal). Tabla `locations` (`type = access_point`). Solo Accesos. **No** es un puesto. | Tarjeta **Puertas** |
| **Puesto** | Puesto de vigilancia (`supervisor_posts`): modalidad 8/12/24 h y vigilantes asignados. Un catálogo. **Nunca** un `location`. | Tarjeta **Instalaciones y puestos** |
| **Tipo de estructura** | Catálogo **por empresa** (`structure_types.security_company_id`), fijo en el alta (`clients.structure_type_id`). | Ajustes → Estructuras / ficha cliente |
| **Nodo / subnodo** | Censo residencial (`structures`, `parent_id`). Torre, apto, casa. Distinto de instalación/puesto/acceso. | Panel `/client/structures` |
| **Persona (censo)** | `structure_members` en un nodo. | Panel cliente |

La ficha corta por **objeto**, no por línea comercial. **Instalación y puesto** se editan una sola vez. Las **puertas** son otra tarjeta, solo si hay Accesos. Operar portería / operar cliente también exigen Accesos. Supervisión de campo se opera en la app y en `/company/supervision`, no en un segundo árbol.

Al **Ver** el cliente, el header es **Cliente | Resumen**. Resumen (si `has_access`) son los KPIs de portería. Tarjetas: **Instalaciones y puestos** (`?vista=sitio`, si Accesos o Supervisión) y **Puertas** (`?vista=puertas`, solo Accesos). `?vista=accesos` y `?vista=supervision` redirigen al sitio.

---

## Alta del cliente (formulario y Excel)

Crear cliente = **solo la ficha**, igual que el formulario de `/company/clients/create`:

tipo de cliente, nombre comercial, razón social, tipo y número de documento, contactos, representante, tipo de estructura, dirección, **ciudad**, departamento, líneas Accesos / Supervisión, inicio de servicio.

**La carga masiva de clientes no crea instalaciones, ni puestos, ni accesos.** Tampoco nodos de censo.

Instalaciones, puestos y accesos los crea **a mano** el usuario de la empresa en la ficha de ese cliente, después del alta.

| Excel | Qué crea | Qué no crea |
|-------|----------|-------------|
| Clientes | Filas `clients` (mismos campos del formulario) | Instalaciones, puestos, accesos, nodos, personas |
| Empleados | Ficha de colaborador (alta o **actualización** si el documento ya existe; cargo incluido) | Cliente, instalación, puesto, acceso, usuario. **Sin** columnas razón social / instalaciones / sector / puesto |

---

## Árbol del sitio (tarjeta Instalaciones y puestos)

Si `has_access` o `has_supervision`. Abre `/company/clients/{id}?vista=sitio`:

1. Crea **instalaciones** (casilla «La instalación es el mismo cliente» o nombre + mapa). Toda instalación lleva georreferencia.
2. Crea **puestos** (`supervisor_posts`: modalidad + vigilantes). Un puesto admite **varios** empleados. Un empleado solo puede estar en **un** puesto; si ya tiene uno, se reasigna desde su ficha (buscador por cédula/nombre).

Esta tarjeta **no** lista revistas. Las revistas de campo viven en `/company/supervision`.

```
Cliente
  └── Instalación (puede ser el propio cliente)
        └── Puesto (8 / 12 / 24 h + empleados)         ← supervisor_posts
```

El censo (torres, aptos, personas) no se define aquí; sigue en `/client/structures`.

La app de campo (`GET /api/supervision/posts`) lista **estos puestos**, nunca `locations`. Sin puestos no se guarda revista.

---

## Puertas (tarjeta Puertas)

Solo si `has_access`. Abre `/company/clients/{id}?vista=puertas`. Cuelga `locations` de instalaciones ya creadas. No se crea la instalación aquí.

Solo Supervisión: ve el sitio, no puertas ni Operar. Solo Accesos: sitio + puertas + Operar. Ambas: lo mismo, un solo puesto.

---

## Instalación = el cliente

Casilla **«La instalación es el mismo cliente»** (`is_client_site`):

- **Chuleada:** el nombre y la ubicación (dirección, ciudad, depto, lat/lng) se copian de la ficha del cliente. Si el cliente no tiene pin, no se crea.
- **Sin chulear:** se escribe el nombre y se fija el pin con el mismo mapa de la ficha (depto, ciudad, dirección). Sin coordenadas no se guarda.

Toda instalación queda con georreferencia. No se usa «sector» ni ciudad como nivel intermedio del árbol.

Las instalaciones y los puestos se editan en **una** tarjeta. Las **puertas** son otra, solo Accesos.

---

## Tablas

Create en la migración original de cada dominio; **sin ALTER sueltos**.

| Acción | Tabla | Rol |
|--------|--------|-----|
| **Creada** | `installations` | Sitio físico. `client_id`, nombre, flag mismo-cliente, activo, dirección/ciudad/depto/lat/lng |
| **Creada** | `supervisor_posts` | Puesto compartido. `client_id`, `installation_id`, nombre, `modality` (8/12/24), activo |
| **Creada** | `supervisor_post_employee` | Vigilantes asignados al puesto |
| **Ajustada** | `locations` | Puerta de portería. `installation_id` obligatorio. No es el puesto |
| **Ajustada** | `supervisor_shift_reviews` | `supervisor_post_id` (ya no `location_id`) |

No se clonan tablas de Patrulla (`review_posts`, etc.). Flota de Supervisión sigue en `supervisor_fleet_vehicles`, no en `vehicles` de Accesos.

**Puente minuta (después):** revista y portería conviven en el mismo cliente. Copiar observaciones de revista a `guard_logs` es un puente explícito, no reutilizar `locations` como puesto.

---

## Censo (nodos) — Accesos / panel cliente

Sigue siendo el árbol de **personas y unidades**, no el de portería ni el de Supervisión.

1. Empresa: tipos de estructura en Ajustes → Estructuras. Plataforma: tipos de documento en `/admin/settings/document-types`.
2. Empresa: alta de cliente (ficha o Excel de clientes).
3. Panel cliente (`/client/structures`): nodos; el tipo se **hereda** del cliente.
4. Personas en **un** nodo.

```
Cliente (tipo fijo, ej. Propiedad horizontal)
  └── Nodo (Torre A)           ← parent_id null
        └── Subnodo (Apto 101) ← parent_id = Torre A
              └── Persona
```

También válido: nodo hoja directo (casa sin torre) → persona en ese nodo.

---

## Campos comerciales de `clients`

| Campo | Uso |
|-------|-----|
| `party_type` | `legal_entity` / `natural_person` |
| `legal_name`, `name` | Razón social / nombre comercial |
| `document_type`, `tax_id` | Tipo (catálogo) + número |
| `email`, `phone` | Contacto |
| `representative_name`, `representative_email` | Representante (exigido si jurídica) |
| `structure_type_id` | Tipo de estructura fijado |
| `address`, `city`, `department` | Ubicación (ciudad ≠ instalación) |
| `has_access`, `has_supervision` | Líneas de servicio (cupo) |
| `slug`, `login_suffix` | Internos; auto |

Migraciones de ficha:  
`2026_08_16_180000_add_commercial_fields_to_clients_table` ·  
`2026_08_16_190000_add_structure_type_id_to_clients_table` ·  
`2026_08_16_170000_create_identity_document_types_table` ·  
`2026_09_03_144000_add_security_company_id_to_structure_types` (catálogo de tipos por empresa)

---

## Copy de UI

Listado `/company/clients` vacío: **«Aún no tienes clientes creados en la cartera»** + **Crear cliente** (no «conjunto»).

Pestañas de ficha empresa (`/company/clients/{id}`): **Cliente** | **Resumen** (si `has_access`).

- Cliente: ficha + **Operar portería** / **Operar cliente** (solo Accesos), **Editar**, **Instalaciones y puestos**, **Puertas** (solo Accesos).
- Sitio: instalaciones + puestos (modalidad y vigilantes). Sin revistas aquí.
- Puertas: `locations` de esas instalaciones.

El panel `/client/structures` se llama **Estructura** (censo).

---

## Rutas empresa (árbol)

| Método | Ruta | Uso |
|--------|------|-----|
| POST/PUT/DELETE | `/company/clients/{id}/installations` | Instalaciones (compartidas) |
| POST/PUT/DELETE | `/company/clients/{id}/locations` | Puertas de una instalación (solo Accesos) |
| POST/PUT/DELETE | `/company/clients/{id}/posts` | Puestos compartidos (Accesos y Supervisión) |

Permiso: `company.clients.manage`. Portería (`/access/locations`) también crea accesos, exigiendo `installation_id` del cliente activo.

---

## Seeds

| Seed | Incluye |
|------|---------|
| `DatabaseSeeder` (mínimo) | Tipos de documento; **no** clientes ni instalaciones |
| `PilotDemoSeeder` | Empresa, clientes (ficha + líneas), censo demo. Palmas: instalación sede + 4 puertas + 2 puestos. Torres: instalación sede + 1 puerta + 1 puesto (solo Accesos) |
