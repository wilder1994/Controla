# Supervisión de campo

Controla es la fuente de verdad. La PWA (`field-app/` y el host `controla_supervision.test`) solo captura.

No choca con: minuta de portería (`/access/supervision`), `SupervisorReview`, `PlatformDocument`, zonas/vehículos de Accesos, `locations` (accesos) ni correspondencia.

**Última actualización:** 27 agosto 2026

Árbol del cliente (instalación → puesto): [`CLIENTES-Y-ESTRUCTURA.md`](CLIENTES-Y-ESTRUCTURA.md). La app **no** usa puntos de Accesos como puesto.

---

## Empresa — Ajustes

Mismo bloque que Cargos/Tipos (`company.settings.manage`):

| Pestaña | Ruta | Tabla |
|---------|------|--------|
| Cargos | `/company/job-titles` | `company_job_titles` |
| Tipos | `/company/collaborator-types` | `company_collaborator_types` |
| **Zonas** | `/company/supervision-zones` | `supervisor_zones` (rutas de Supervisión, no `common_zones`) |
| **Turnos** | `/company/supervision-shifts` | `supervisor_shift_templates` (nombre + horario) |
| **Preoperacional** | `/company/supervision-preop` | `supervisor_checklist_items` (`ppe` / `vehicle`) |
| **Documentos** | `/company/supervision-document-types` | `supervisor_document_types` (entregados/pendientes del turno; vacío hasta que la empresa los cree) |
| **Libros** | `/company/supervision-control-book-types` | `supervisor_control_book_types` (libros del puesto; vacío hasta que la empresa los cree) |
| **Tipos de arma** | `/company/supervision-weapon-types` | `supervisor_weapon_types` |
| **Marcas** | `/company/supervision-weapon-brands` | `supervisor_weapon_brands` |
| **Riesgos** | `/company/supervision-risk-types` | `supervisor_risk_types` (tipos de la recomendación; vacío hasta que la empresa los cree) |
| **Alarmas** | `/company/supervision-alarm-types` | `supervisor_alarm_types` (pánico, incendio, etc.; vacío hasta que la empresa los cree) |
| **Apoyos** | `/company/supervision-support-types` | `supervisor_support_types` (refuerzo, escolta, etc.; vacío hasta que la empresa los cree) |

Si Zonas/Turnos/Preoperacional están vacíos, se siembran defaults (Norte/Sur/Centro; Día 06:00–18:00 y Noche 18:00–06:00; EPP y vehículo de `ShiftIntakeCatalog`). **Documentos**, **Libros**, **Tipos de arma**, **Marcas**, **Riesgos**, **Alarmas** y **Apoyos** no se siembran: la empresa define los tipos. Solo los **activos** salen en la app.

Flota: `supervisor_fleet_vehicles` (placa/marca la primera vez). **No** es `vehicles` de Accesos.

---

## App de campo

PWA en `field-app/` (copia alineada en `Controla_Supervision`). Caché SW `controla-sup-v20`.

Login: solo correo y contraseña. API **siempre** Controla: host `controla_supervision` → mismo esquema + host `controla` + `/api`; puerto `8085` → mismo host `:8084/api`. No hay campo de API. Instalación: **Descargas** en empresa (`/company/descargas`) y plataforma (`/admin/descargas`); QR + enlace (`SUPERVISION_PWA_URL`). Hard-refresh tras cambios de PWA.

Fotos: no se enciende la cámara al abrir. **Trasera** / **Frontal** o **Tomar foto** piden `getUserMedia` (hace falta HTTPS o localhost). Tras capturar se apaga. Galería solo si no hay contexto seguro.

1. Login (`POST /api/supervision/login`) → rito de **apertura**: turno y zona del catálogo, EPP/vehículo plegables, km + foto odómetro + selfie (cámara, no galería).
2. Hub: ficha de perfil + **Cerrar**. Cuatro entradas: **Revista**, **Alarmas**, **Apoyos**, **Documentos**. Ping GPS cada 30 s, silencioso.
3. **Revista** (al clic): cliente, puesto, vigilante, foto. Los módulos del puesto (inventario, libros de control, etc.) se registran en borrador. **Guardar revista** (abajo) envía GPS + foto + módulos juntos. Si cancela, no queda nada.
4. Alarmas, apoyos y documentos: cada uno abre su formulario (no cuelgan de la revista). Documentos no pide cliente.
5. Cierre: km final + odómetro + selfie → sesión cerrada.

Pilot: `supervisor@sj-seguridad.test` / `Super123!`. Empresa: `empresa@sj-seguridad.test` / `Empresa123!`. Palmas tiene `has_supervision`. Vigilante piloto: cédula `1144001122`.

Sin puestos de Supervisión en la ficha del cliente no se guarda revista. Los `locations` (puertas) no entran en el combo.

---

## Módulos

| Clave | Captura | Notas |
|-------|---------|--------|
| `reviews` | `POST /reviews` | Revista en `supervisor_shift_reviews`. No llena minuta Accesos. |
| `inventory` | `POST /logs` | Cuelga de la revista. Varios elementos (tipo, estado, observación) |
| `control_books` | `POST /logs` | Cuelga de la revista. Tipos del catálogo empresa; con/sin novedad |
| `folders` | `POST /logs` | Cuelga de la revista |
| `weapons` | `POST /logs` | Cuelga de la revista. Tipo/marca, permiso, novedad, aseo opcional (foto de aseo solo si sí) y 5 fotos de identificación |
| `recommendations` | `POST /logs` | Cuelga de la revista. 1 a 3 riesgos (tipo de catálogo, P×I, consecuencia, 3 fotos). Registro, no ticket |
| `alarms` | `POST /logs` | Formulario propio. Requiere cliente. Tipo, modalidad (prueba/atención) y resultado |
| `supports` | `POST /logs` | Formulario propio. Tipo + motivo. Cliente opcional (puede ser en vía) |
| `documents` | `POST /logs` | Del turno. Sin cliente ni puesto. Tipos + entregado/pendiente |

Contrato de campos: `GET /api/supervision/catalog` (`FieldModuleCatalog`). Logs append-only en `supervisor_field_logs` (`supervisor_shift_review_id` si cuelga de revista). Recomendaciones: `supervisor_recommendations` (registro inmutable del turno; `GET /recommendations` lista recientes).

El mapa `/company/supervision` pinta el GPS de la revista (verde / rojo si novedad), distinto del rastro de ping del turno.

---

## Recomendaciones (riesgo)

No son tickets. El supervisor **registra y envía** en la revista; el tratamiento es interno (fuera de la app). Sin fecha límite y sin abierto / en proceso / cerrado.

Hasta **3 tarjetas** por puesto (un solo registro de módulo). Cada tarjeta:

| Campo | Origen |
|-------|--------|
| Tipo de riesgo | Select del catálogo empresa (`supervisor_risk_types`) |
| Riesgo | Texto: qué identificó |
| Probabilidad | 1–5 (muy baja → muy alta) |
| Impacto | 1–5 (insignificante → catastrófico) |
| Nivel | Calculado P×I (bajo / medio / alto / extremo). No lo elige el supervisor |
| Consecuencia | Texto: qué pasaría |
| Recomendación | Texto: qué hacer |
| Evidencia | 3 fotos obligatorias |

Matriz (ISO 31000 operacionalizada 5×5): score = probabilidad × impacto. 1–4 bajo, 5–9 medio, 10–16 alto, 17–25 extremo. El `priority` interno (baja/normal/alta/urgente) sale del nivel.

Panel: KPIs de **volumen y nivel**, no de tickets abiertos. Tira de hoy: recomendaciones del día.

---

## Panel empresa — operación

`/company/supervision`: En vivo / Historial / **Resumen**. Header: filtros (año, mes, rango o un día, zona, supervisor) y **Descargar PPTX**. Sin conteo de en vivo/turnos/revistas en el header. El PPTX vigente sale de un clic (`GET /company/supervision/informe.pptx`, mismo filtro). Resumen: KPI (cobertura de sitios, revistas, km, recs por nivel) y gráficos por módulo; sin texto explicativo del periodo (el recorte lo marcan los filtros). Año (>45 días) agrupa por mes; si no, por día. Compositor con narrativa e IA: § Informe PPTX (pendiente).

`/company/descargas` y `/admin/descargas`: tarjeta **App de Supervisión** (QR, abrir, copiar, pasos Android/iPhone). Una sola PWA para todas las empresas; el login identifica la empresa. No es la app de residentes de Accesos.

### Distribución (alcance actual)

**PWA, no APK.** No hay Capacitor, Play Store ni App Store. No se genera un binario por empresa.

El supervisor abre el enlace (`SUPERVISION_PWA_URL`, por defecto `http://controla_supervision.test`) e instala desde el navegador:

- Android: Chrome → menú → **Instalar aplicación** o **Añadir a pantalla de inicio**.
- iPhone: Safari → compartir → **Añadir a pantalla de inicio** (no hay “Instalar” tipo Android).

Limitaciones de este corte (Laragon / piloto): HTTP y host `.test` (el teléfono no resuelve Laragon); el manifest no trae iconos 192/512, así que Chrome puede no ofrecer “Instalar”. En producción hará falta HTTPS, URL pública y esos iconos. APK/tiendas queda para un corte posterior.

**Tailscale (verificado en consola, plan Gratis: MagicDNS sí, registros A custom no).** Vhosts nuevos `00-aad-controla-tailscale.conf`: API `:8084`, PWA `:8085`, alias `100.108.131.98` y `sjpcanaope.tail9f649e.ts.net`. No usa `:80`/`:8082`/`:8083` ni `172.16.16.70`. En el celular (Tailscale + datos): `http://100.108.131.98:8085`. Reiniciar Apache tras el `Listen`. Firewall Windows: TCP 8084 y 8085. Si cambia la IP Tailscale del PC, actualizar el `ServerAlias` del vhost.

Mi empresa muestra la tira **Supervisión de campo (hoy)** aparte de las revistas de portería (incluye recomendaciones del día).

---

## Informe PPTX (pendiente — no implementado)

Hoy el PPTX es **solo cifras y gráficos** (`ExportSupervisionExecutiveReportService`). El siguiente corte **no descarga de un clic**: abre un **compositor** (preview HTML, no el `.pptx` en el navegador). Los números no se editan. Un textarea por lámina de contenido; DeepSeek (API servidor, `DEEPSEEK_API_KEY`) propone redacción; el usuario acepta o corrige y recién entonces se genera el archivo.

### Mazo objetivo

| # | Lámina | Editable |
|---|--------|----------|
| 1 | Portada (Controla, título, empresa, periodo) | No |
| 2 | KPIs (cobertura, revistas, km, recomendaciones) + **introducción** | Párrafo intro |
| 3 | Actividad (grano día/mes) | Un párrafo de esa lámina |
| 4 | Puesto: inventario, libros, carpetas, documentos | Un párrafo |
| 5 | Actividad por supervisor (barras) | Un párrafo |
| 6 | Alarmas y apoyos | Un párrafo |
| 7 | Sitios y recomendaciones | Un párrafo |
| 8 | Alertas del periodo | Un párrafo (lectura; no repetir el listado) |
| 9 | Cierre: **GRACIAS** grande al centro; línea corta opcional (empresa / periodo) | Línea opcional |

IA: no inventa KPIs. El prompt lleva solo el snapshot de **esa** empresa y filtro. El chat de **ayuda a redactar** vive en el compositor (ver § Chatbot y PQRS). El canal PQRS es persistido y distinto.

Rutas previstas (cuando se implemente): compositor con el mismo query que el resumen; `POST` del PPTX con los textos. El `GET …/informe.pptx` actual se sustituye o queda como atajo sin narrativa.

---

## Chatbot y PQRS (pendiente — no implementado)

Mismo proveedor: **DeepSeek** en servidor (`DEEPSEEK_API_KEY`). El navegador no ve la clave. El prompt **nunca** incluye datos de otra empresa.

Son **dos usos** de IA, no un solo widget genérico.

### A. Ayuda (chat de producto)

Panel lateral en el **compositor del informe** (y, si aplica, en Supervisión). Responde dudas de uso: filtros, qué es cobertura, por qué Accesos no entra en el PPTX, cómo leer alarmas, qué hace cada lámina.

- No sustituye los textareas: puede **proponer** un párrafo; el usuario lo pega o usa «Mejorar redacción» del campo.
- No persiste tickets. Historial de chat: sesión o descarte al cerrar (definir en implementación).
- Si no sabe, dice que no consta; no inventa cifras ni políticas.

### B. PQRS (preguntas, quejas, sugerencias)

Canal de **registro**, no solo conversación. El usuario elige tipo (pregunta / queja / sugerencia), escribe, y puede pedir a la IA que **aclare o redacte** el mensaje antes de enviar.

Al enviar se guarda un ticket scoped a la empresa (`security_company_id`, usuario, tipo, asunto, cuerpo, estado abierto). La IA puede clasificar y devolver un acuse; **no cierra** quejas sola.

Bandeja prevista: panel empresa (listado de los tickets de **esa** empresa). Plataforma (`/admin`): ver todos es un extra posterior, no el corte mínimo.

Fuera de alcance de este corte: chatbot en la PWA de campo, app de residentes, WhatsApp, mail automático al cliente del conjunto.

---

## API (`auth:sanctum` + `supervisor.pro`)

| Método | Ruta | Uso |
|--------|------|-----|
| POST | `/api/supervision/login` | Token supervisor |
| GET | `/api/supervision/intake` | Zonas, turnos, EPP, vehículo, flota |
| GET | `/api/supervision/shifts/current` | Turno abierto + `current_review` + actividad |
| GET | `/api/supervision/shift-photo/start-selfie` | Selfie de apertura |
| POST | `/api/supervision/shifts/open` | Multipart: `shift_template_id`, `zone_id`, checklists, fotos |
| POST | `/api/supervision/shifts/ping` | GPS silencioso |
| POST | `/api/supervision/shifts/close` | Multipart km + fotos |
| GET | `/api/supervision/sites` | Clientes con `has_supervision` |
| GET | `/api/supervision/posts` | Puestos de Supervisión del cliente (`supervisor_posts`), no `locations` |
| GET | `/api/supervision/guards` | Vigilantes por cédula |
| GET | `/api/supervision/catalog` | 8 módulos |
| POST | `/api/supervision/reviews` | Multipart: cliente, puesto, vigilante, foto, GPS, novedad |
| POST | `/api/supervision/logs` | Módulos de campo |
| GET | `/api/supervision/recommendations` | Recomendaciones registradas |

Apertura exige turno/zona **activos de esa empresa** y todos los ítems preoperacionales activos en `true`.

---

## Migraciones

- `2026_07_06_080400_create_installations_table`
- `2026_07_06_080500_create_locations_table` (`installation_id`)
- `2026_08_25_230000_add_supervision_packages_and_tracking` (zonas, turnos, preop, flota, turnos de campo, `supervisor_posts`, revistas con `supervisor_post_id`)
- `2026_08_26_110000_create_supervisor_field_ops_tables` (recomendaciones y logs)
- `2026_08_26_223500_create_supervisor_document_types_table`
- `2026_08_26_224800_create_supervisor_control_book_types_table`
- `2026_08_26_230200_create_supervisor_weapon_catalogs_table`
- `2026_08_26_233000_add_risk_fields_to_supervisor_recommendations`
- `2026_08_26_234500_create_supervisor_risk_types_table`
- `2026_08_26_234800_replace_recommendation_title_with_risk_type`
- `2026_08_27_140000_create_supervisor_alarm_and_support_types`

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=PilotDemoSeeder
```

Tests: `SupervisorShiftApiTest`, `SupervisorFieldLogApiTest`, `CompanySupervisionCatalogTest`, `CompanyClientSiteTreeTest` (BD `controla_test`).
