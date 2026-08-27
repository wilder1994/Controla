# Clientes, instalaciones, Accesos y Supervisión

**Última actualización:** 26 agosto 2026

Fuente de verdad del **cliente comercial** y de los dos árboles operativos. El censo (nodos `structures`) sigue más abajo; no se mezcla con instalaciones ni con puestos de Supervisión.

Controla **no** cobra al cliente final por vigilancia; solo registra `service_started_at`.

---

## Glosario

| Término | Significado | Dónde |
|---------|-------------|--------|
| **Cliente** | Ficha comercial (`clients`). PH, oficinas, bodegas, etc. | Alta / Excel de **clientes** / pestaña Cliente |
| **Ciudad** | Ubicación del cliente (`clients.city` + `department`). **No es un nodo del árbol.** Lo que en el Excel viejo de empleados decía «Sector» era ciudad. | Ficha y Excel de clientes |
| **Instalación** | Sitio físico del cliente. Puede ser **el mismo cliente** (sede única: una instalación con el nombre del cliente). | Pestaña Accesos y/o Supervisión |
| **Acceso** | Punto de portería (puerta, vehicular, peatonal). Tabla `locations` (`type = access_point`). **No** es un puesto de Supervisión. | Pestaña **Accesos** |
| **Puesto** | Puesto de vigilancia de Supervisión de campo. Tabla nueva `supervisor_posts`. **Nunca** un `location`. | Pestaña **Supervisión** |
| **Tipo de estructura** | Catálogo plataforma (`structure_types`), fijo en el alta (`clients.structure_type_id`). | Ficha cliente |
| **Nodo / subnodo** | Censo residencial (`structures`, `parent_id`). Torre, apto, casa. Distinto de instalación/puesto/acceso. | Panel `/client/structures` |
| **Persona (censo)** | `structure_members` en un nodo. | Panel cliente |

Accesos y Supervisión son **dos mundos**. El mismo `clients` puede tener `has_access`, `has_supervision` o ambos. Conviven en la ficha; no se cruzan los hijos: un acceso no es un puesto.

---

## Alta del cliente (formulario y Excel)

Crear cliente = **solo la ficha**, igual que el formulario de `/company/clients/create`:

tipo de cliente, nombre comercial, razón social, tipo y número de documento, contactos, representante, tipo de estructura, dirección, **ciudad**, departamento, líneas Accesos / Supervisión, inicio de servicio.

**La carga masiva de clientes no crea instalaciones, ni puestos, ni accesos.** Tampoco nodos de censo.

Instalaciones, puestos y accesos los crea **a mano** el usuario de la empresa en la ficha de ese cliente, después del alta.

| Excel | Qué crea | Qué no crea |
|-------|----------|-------------|
| Clientes | Filas `clients` (mismos campos del formulario) | Instalaciones, puestos, accesos, nodos, personas |
| Empleados | Ficha de colaborador (persona, cargo, tipo) | Cliente, instalación, puesto, acceso. **Sin** columnas razón social / instalaciones / sector / puesto |

---

## Árbol Accesos (pestaña Accesos)

Solo si `has_access`. El usuario entra a `/company/clients/{id}?vista=accesos` y:

1. Crea **instalaciones** de ese cliente (o una sola «sede = cliente»).
2. Crea **accesos** de cada instalación (`locations`).

```
Cliente
  └── Instalación (puede ser el propio cliente)
        └── Acceso (puerta / vehicular / peatonal)     ← locations
```

Hoy `locations` cuelgan de `installation_id`. Cada acceso pertenece a una instalación.

El censo (torres, aptos, personas) no se define aquí; sigue en `/client/structures`.

---

## Árbol Supervisión (pestaña Supervisión)

Solo si `has_supervision`. El usuario entra a `/company/clients/{id}?vista=supervision` y:

1. Crea **instalaciones** de ese cliente (pueden ser las **mismas** del mundo Accesos: un solo catálogo de instalaciones por cliente).
2. Crea **puestos** de cada instalación (`supervisor_posts`).

```
Cliente
  └── Instalación (puede ser el propio cliente)
        └── Puesto de Supervisión                      ← supervisor_posts
```

La app de campo (`GET /api/supervision/posts`) lista **estos puestos**, nunca `locations`.

Sin puestos de Supervisión no se guarda revista.

La pestaña Supervisión es el lugar de esos ajustes (no Ajustes globales de la empresa: allá siguen zonas/turnos/preoperacional de **ruta**).

---

## Instalación = el cliente

Si el servicio es una sola sede, se crea **una** instalación con el nombre del cliente (marca `is_client_site` o equivalente). No se usa «sector» ni ciudad como nivel intermedio.

Las instalaciones son **compartidas** entre Accesos y Supervisión (mismo sitio físico). Lo que diverge son los hijos: accesos vs puestos.

---

## Tablas

Create en la migración original de cada dominio; **sin ALTER sueltos**.

| Acción | Tabla | Rol |
|--------|--------|-----|
| **Creada** | `installations` | Sitio físico. `client_id`, nombre, flag sede-cliente, activo |
| **Creada** | `supervisor_posts` | Puesto de Supervisión. `client_id`, `installation_id`, nombre, activo |
| **Ajustada** | `locations` | Acceso de portería. `installation_id` obligatorio. No es el puesto de la app |
| **Ajustada** | `supervisor_shift_reviews` | `supervisor_post_id` (ya no `location_id`) |

No se clonan tablas de Patrulla (`review_posts`, etc.). Flota de Supervisión sigue en `supervisor_fleet_vehicles`, no en `vehicles` de Accesos.

**Puente minuta (después):** revista y portería conviven en el mismo cliente. Copiar observaciones de revista a `guard_logs` es un puente explícito, no reutilizar `locations` como puesto.

---

## Censo (nodos) — Accesos / panel cliente

Sigue siendo el árbol de **personas y unidades**, no el de portería ni el de Supervisión.

1. Plataforma: tipos de estructura y de documento en `/admin/settings/…`
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
`2026_08_16_170000_create_identity_document_types_table`

---

## Copy de UI

Pestañas de ficha empresa (`/company/clients/{id}`): **Cliente** | **Accesos** (si `has_access`) | **Supervisión** (si `has_supervision`) | Editar.

- Accesos: instalaciones + accesos de esas instalaciones.
- Supervisión: instalaciones + puestos de esas instalaciones.
- Editar: datos de la ficha (mismo conjunto de campos que el alta), no el árbol.

El panel `/client/structures` se llama **Estructura** (censo).

---

## Rutas empresa (árbol)

| Método | Ruta | Uso |
|--------|------|-----|
| POST/PUT/DELETE | `/company/clients/{id}/installations` | Instalaciones (compartidas) |
| POST/PUT/DELETE | `/company/clients/{id}/locations` | Accesos de una instalación |
| POST/PUT/DELETE | `/company/clients/{id}/posts` | Puestos de Supervisión |

Permiso: `company.clients.manage`. Portería (`/access/locations`) también crea accesos, exigiendo `installation_id` del cliente activo.

---

## Seeds

| Seed | Incluye |
|------|---------|
| `DatabaseSeeder` (mínimo) | Tipos de documento; **no** clientes ni instalaciones |
| `PilotDemoSeeder` | Empresa, clientes (ficha + líneas), censo demo. Palmas: instalación sede + 4 accesos + 2 puestos. Torres: instalación sede + 1 acceso (solo Accesos) |
