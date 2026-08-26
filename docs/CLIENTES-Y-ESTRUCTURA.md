# Clientes comerciales y estructura (nodos)

**Última actualización:** agosto 2026

Modelo acordado para el alta de **cliente** (empresa de seguridad) y el árbol físico bajo ese cliente.

---

## Glosario

| Término | Significado |
|---------|-------------|
| **Cliente** | Cliente comercial de la empresa de seguridad (`clients`). No es solo un “conjunto residencial”: puede ser PH, oficinas, bodegas, etc. |
| **Tipo de estructura** | Catálogo plataforma (`structure_types`). Se **fija en el alta del cliente** (`clients.structure_type_id`). |
| **Nodo** | Registro en `structures` (ej. Torre A, Casa 12). Árbol con `parent_id`. |
| **Subnodo** | Nodo hijo (`parent_id` apunta a otro nodo). Misma tabla; no hay entidad aparte. |
| **Persona** | `structure_members`: **obligatorio** un `structure_id` (un solo nodo del árbol). |

Controla **no** cobra al cliente final por vigilancia; solo registra `service_started_at` en el cliente.

---

## Flujo

1. **Plataforma** mantiene catálogos en Ajustes:
   - Tipos de estructura → `/admin/settings/structure-types`
   - Tipos de documento de identidad → `/admin/settings/document-types`
2. **Empresa** da de alta el cliente en `/company/clients/create` o por **Carga masiva** (`Formato` Excel → preview → aceptar):
   - Datos comerciales (`party_type`, razón social, documento, contactos, representante, ubicación, inicio de servicio)
   - **Líneas de servicio** opcionales: Accesos / Supervisión Pro (el cupo es por línea, no por ficha)
   - **Tipo de estructura** obligatorio (queda fijo; el Excel no crea tipos)
   - `slug` / `login_suffix` se generan en backend (no van en el formulario)
3. **Panel cliente** (`/client/structures`): se crean **nodos** del árbol; el tipo se **hereda** del cliente (no se elige por nodo).
4. **Personas** se asignan a **un** nodo del árbol (casa, apto, oficina…). No hay asignación dual nodo+subnodo ni persona “solo al cliente” sin nodo.

Jerarquía típica:

```
Cliente (tipo fijo, ej. Propiedad horizontal)
  └── Nodo (Torre A)           ← parent_id null
        └── Subnodo (Apto 101) ← parent_id = Torre A
              └── Persona
```

También válido: nodo hoja directo (casa sin torre) → persona en ese nodo.

---

## Campos comerciales de `clients` (ago 2026)

| Campo | Uso |
|-------|-----|
| `party_type` | `legal_entity` / `natural_person` |
| `legal_name`, `name` | Razón social / nombre comercial |
| `document_type`, `tax_id` | Tipo (catálogo) + número |
| `email`, `phone` | Contacto |
| `representative_name`, `representative_email` | Representante (exigido si jurídica) |
| `structure_type_id` | Tipo de estructura fijado |
| `has_access`, `has_supervision` | Líneas de servicio (cupo Accesos / Pro) |
| `slug`, `login_suffix` | Internos (APP / tenants); auto |

Migraciones relevantes:  
`2026_08_16_180000_add_commercial_fields_to_clients_table` ·  
`2026_08_16_190000_add_structure_type_id_to_clients_table` ·  
`2026_08_16_170000_create_identity_document_types_table`

---

## Copy de UI

El panel `/client/structures` se llama **Estructura** (nodos). Crear subnodo vs nodo raíz y pestañas extra quedan para un siguiente paso.

---

## Seeds

| Seed | Incluye tipos estructura / clientes |
|------|-------------------------------------|
| `DatabaseSeeder` (mínimo) | Tipos de **documento** identidad; **no** `structure_types` ni clientes |
| `PilotDemoSeeder` | `StructureTypeSeeder` + empresa/clientes (con `structure_type_id`) + censo demo |
