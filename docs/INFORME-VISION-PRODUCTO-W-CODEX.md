# Informe de visión de producto — Controla (W Codex Solution)

**Producto:** Controla  
**Dueña:** W Codex Solution  
**Estado:** decisión de producto (sin cambio de código en este corte)  
**Fecha:** 2026-09-11  
**Objeto:** bitácora de cómo se vende Controla, qué se toma de SJ-SIG y cómo avanza la geometría (instalación, censo, usuarios).

Este documento es bitácora. No se implementa nada aquí: primero se registra la decisión, después se abre fase de código.

Documentos que este informe no sustituye: [`CLIENTES-Y-ESTRUCTURA.md`](CLIENTES-Y-ESTRUCTURA.md), [`EMPLEADOS-Y-CARGOS.md`](EMPLEADOS-Y-CARGOS.md), [`SUPERVISION-CAMPO.md`](SUPERVISION-CAMPO.md), [`PAQUETES-ACCESOS-Y-SUPERVISION.md`](PAQUETES-ACCESOS-Y-SUPERVISION.md). SJ-SIG sigue documentado en su propio repo (`SJ-SIG/docs/informe-definicion.md`).

---

## 1. Dueña y marcas

| Campo | Valor |
|--------|--------|
| Empresa de software | **W Codex Solution** |
| Producto comercial | **Controla** (SaaS B2B de accesos, vigilancia y supervisión de campo) |
| Cantera / prototipo contractual | **SJ-SIG** (Laravel 13, factor 4.2.4, repo aparte) |
| App de campo hoy | PWA `Controla_Supervision` / `field-app/` (sin BD; API de Controla) |
| App de campo objetivo | **APK nativa** (Capacitor sobre la PWA) para GPS con pantalla apagada |

Controla se construye para **venderse** a empresas de seguridad y, con el mismo esqueleto, a entidades (Secretaría, Alcaldía, colegios). Una licitación concreta (SJ, Educación de Cali, etc.) es un **cliente** o un **módulo**, no una marca nueva ni un segundo sistema.

SJ-SIG nació para un pliego. El avance útil (mapa, unidades, expediente, tablero de entidad) se **porta a Controla**. No se fusionan los repos (Laravel 11 vs 13; tenancy distinto). No se despliega SJ-SIG como producto paralelo.

---

## 2. Principios

1. **Un producto, muchos tipos de cliente.** PH, bodega, contrato de vigilancia, Secretaría con colegios: misma geometría, fichas distintas.
2. **La gente de vigilancia es de la empresa.** Nunca del PH ni del colegio. Se **asigna** a un puesto.
3. **El puesto cuelga de una instalación** y tiene **modalidad** (8 / 12 / 24 h) y cupo (unidades por cargo).
4. **La instalación es el sitio físico.** Ahí viven mapa, código, administradores opcionales y la estructura (apto o salón).
5. **Accesos ≠ Supervisión ≠ Observatorio.** Puertas, puestos de revista y denuncias ciudadanas no comparten tablas operativas.
6. **Trabajar en Controla.** SJ-SIG es referencia; no se mantiene dos árboles de instalaciones.
7. **La PWA no basta para tracking de fondo.** GPS con pantalla apagada o en segundo plano = app nativa (APK primero).

---

## 3. Geometría unificada (la que se va a completar)

Hoy Controla ya tiene empresa → clientes → instalaciones, con tres hijos: puertas (`locations`, Accesos), puestos (`supervisor_posts`, compartidos) y **censo** (`structures.installation_id`). Falta el cupo (unidades por cargo) y admins de sede.

**Modelo objetivo:**

```
W Codex / empresa de seguridad (tenant Controla)
  └── Empleados (maestro de la empresa)
        └── asignación → Puesto

Cliente (PH, bodega, Secretaría, contrato…)
  └── Instalación (sede, colegio, predio)
        ├── Administradores opcionales (rector, auxiliar, admin del sitio)
        ├── Accesos (puertas)                    ← mundo Accesos
        ├── Puestos (modalidad + unidades)       ← mundo Supervisión
        │     └── empleados asignados
        └── Estructura (nodos)                   ← hecho 2026-09-11
              └── Personas del sitio
                    PH: torre → apto → residente
                    Colegio: salón 6A → estudiantes
```

Tres reglas fijas:

| Regla | Detalle |
|--------|---------|
| Vigilante | Ficha en **Empleados** de la empresa. El Excel de empleados no elige cliente ni puesto (sigue [`EMPLEADOS-Y-CARGOS.md`](EMPLEADOS-Y-CARGOS.md)). La asignación es un **segundo paso**. |
| Puesto | Siempre de una instalación. Siempre con modalidad 8/12/24 h y unidades por cargo (de SJ-SIG: `GuardRole` + `post_staffings`; en Controla enriquecer `supervisor_posts`). |
| Estructura | Cuelga de la **instalación**, no del cliente suelto (`structures.installation_id`). Un PH de una sola sede no cambia de cara (instalación = el mismo cliente). Un colegio o un cliente con dos sedes sí lo necesita. **Hecho 2026-09-11.** |

Accesos y Supervisión siguen siendo dos mundos: un acceso no es un puesto. Las **instalaciones son compartidas**.

---

## 4. Qué se toma de SJ-SIG (cantera, no fusión)

SJ-SIG no se pega al código de Controla. Se portan flujos y reglas, servicio por servicio.

| Pieza SJ-SIG | Qué aporta | Dónde cae en Controla |
|--------------|------------|------------------------|
| Places + pin de cliente/instalación | Dirección real, mapa, pines PNG | Ficha de cliente e **instalación** (hoy la instalación es poco más que un nombre) |
| Código automático de sede | `{sigla}-01` | Alta de instalación |
| Puesto: modalidad + unidades por cargo | Cupo contratado, no “quién está hoy” | `supervisor_posts` (+ staffing) |
| Bitácora de cambio | Motivo según instalación / puesto / cupo | Edición de instalación/puestos |
| Excel personal + preview/diff | Ritual ya similar al maestro WM | Maestro WM **ampliado** con columnas SJ-SIG opcionales (ver §5) |
| Indexador HV / contratación / cursos / afiliaciones | Expediente contractual | Módulo vendible **Expediente / SIG** (paquete, como Accesos/Supervisión) |
| Tablero de entidad (mapa + KPIs) | Lo que ve el supervisor del cliente | Rol de entidad sobre el cliente/contrato |
| `Tenant::displayName()`, burbujas de mapa | UX | Mapa empresa / tablero |

**No portar como sistema aparte:** tenancy 1:1 de SJ-SIG, seed de licitación, vhost 8086. SJ como cliente de Controla cubre el Anexo 7.4 cuando el módulo Expediente + tablero de entidad estén activos.

Laravel 11 (Controla) vs 13 (SJ-SIG): no mezclar bases ni `composer.json`.

---

## 5. Empleados: ficha de empresa, ritual WM

| | Controla (hoy) | SJ-SIG (cantera) |
|---|----------------|------------------|
| Quién es | Colaborador de **la empresa** | Vigilante del **contrato** |
| Pantalla | Listado + ficha en 4 bloques + foto (Personal SJ-SIG, estilos Controla) | `/personal` |
| Excel | `Maestro Colaboradores WM` (A–Z) + columnas SJ-SIG opcionales | `ficha_empleados SJ-SIG` |
| Campos | Identidad Controla (nombres partidos, DIVIPOLA, catálogos) + contacto, vinculación, seguridad social | Lo mismo en un `full_name` y cargo libre |
| Puesto | Fuera del Excel. Asignar/reasignar: ficha del cliente (Accesos/Supervisión) | Fuera del Excel |
| Expediente PDF | Sidebar **Documentos** (indexador empresa). Cliente: flag + solo puestos del cliente | Documentos / indexador |
| Usuario | Se crea en **Usuarios** (`nombre.apellido.####`) | Login de entidad, no del vigilante |

**Hecho (2026-09-10):** la ficha de Empleados de la empresa ya es la de SJ-SIG Personal. El maestro WM **se queda**; las columnas extras son opcionales. **Hecho (2026-09-10):** indexador de carpetas en sidebar **Documentos** (empresa, todos los empleados). El cliente lo ve solo si Accesos + `show_personnel_folders`, y solo de vigilantes asignados a un puesto suyo.

La **asignación empleado → puesto** ya está en la ficha del cliente (mismo puesto en Accesos y Supervisión). El Excel de empleados no elige puesto. Cupo por cargo y filtro de empleados por cliente siguen aparte.

---

## 6. Instalación enriquecida y administradores de sede

Hoy el alta de instalación en Controla es nombre + “sede = cliente” + mapa + puestos (modalidad y vigilantes) + puertas. Falta código de sede, cupo por cargo y admin de sede.

**Objetivo de la ficha de instalación:**

- Nombre, dirección (Places), ciudad, pin en mapa
- Código automático
- Flag sede-cliente (`is_client_site`)
- Tipo/kind opcional: `conjunto` · `bodega` · `colegio` · … (para no pedir código DANE en una bodega)
- **Administradores opcionales** (N personas, cargo del catálogo: rector, auxiliar, administrador del sitio, coordinador…)

El `client-admin` sigue viendo **todo el cliente**. El admin de instalación solo **esa sede**. Imprescindible si el cliente es una Secretaría con cientos de colegios: el rector de Santa Librada no puede ver los 400 planteles.

Sirve igual al PH: admin del cliente ≠ conserje de una torre.

El contacto del directorio (rector, teléfono, código oficial, comuna) es la **misma** ficha de instalación + su admin, no una tabla suelta de “colegios”.

---

## 7. Colegios y licitaciones de Educación

No es un fork de Accesos. Es **el mismo árbol** con tipo de cliente/estructura `Colegio` y un módulo **Observatorio** encima.

**Tenancy de Educación (Cali u otra ciudad):**

```
Cliente: Secretaría de Educación
  └── Instalación: INEM Jorge Isaacs
        · código oficial, comuna, contacto
        · admin: rector (+ auxiliares)
        ├── Puesto: Portería principal · 24 h · 2 unidades
        │     └── empleados de la empresa de vigilancia
        └── Estructura
              └── Salón 6A → estudiantes (tipo de persona del censo)
              └── Salón 6B → estudiantes
```

- **Un cliente = la Secretaría. Muchas instalaciones = colegios.**  
  Si cada colegio fuera un cliente, el mapa de la ciudad, el ranking y el Secretario rompen el aislamiento.
- Estudiantes = `structure_members` con tipo `estudiante` (grado, jornada, acudiente). No una tabla “alumnos” el día 1.
- El salón no es un acceso. La puerta del colegio sí (`locations`).
- El buscador “INEM Jorge Isaacs” busca **instalaciones**.

### 7.1 Seis tareas de demostración (observatorio)

Capa que **lee** instalaciones/colegios. No vive en minuta ni en `locations`.

| # | Tarea (lenguaje ciudadano) | Dónde |
|---|----------------------------|--------|
| 1 | Directorio de colegios (código, comuna, rector, contacto) | Instalación + admins |
| 2 | Mapa de la ciudad: pines por tipo de alerta + mapa de calor | Observatorio (nuevo) |
| 3 y 7 | Denuncias de padre, Policía, Línea 123 en la misma base | Observatorio + conectores (convenio, no solo JSON) |
| 4 | Botón de pánico anónimo (ocultar nombre y teléfono) | Observatorio; no es el pánico de portería |
| 5 | Enganchar reportes repetidos en un incidente | Observatorio |
| 6 | Tablero, gráficas, ranking colegio más peligroso → más seguro | Observatorio |

El pánico de Accesos (portería) **no** cumple el módulo 4 escolar.

### 7.2 Stack de esa licitación

El texto tipo “PostgreSQL + PostGIS + Python/Node + React/Angular” es receta, no obligación de logo.

| Momento | Decisión |
|---------|----------|
| Demo en semanas | Laravel (oficio de W Codex) + mapa + polígonos de comunas. Calor por recuento o capa Maps |
| Producto / puntaje geo | PostgreSQL + PostGIS (límites, distancias, calor de verdad). El backend puede seguir en Laravel |
| Portal ciudadano | Web móvil de 3 pasos. El padre **no** instala APK para denunciar |
| Panel Secretaría | Mapa + tablas + gráficos, privado |

PostGIS **no cabe** en hosting compartido MySQL (plan Ilimitado típico). Va en VPS. Línea 123 y Policía son **convenio**; el día 1 pueden simularse conectores si el pliego no exige el tubo en vivo.

---

## 8. App de campo (APK)

Decisión de valor para W Codex: las licitaciones de **vigilancia** y el mapa en vivo piden tracking aunque el celular esté en segundo plano o la pantalla apagada.

| Escenario | Superficie |
|-----------|------------|
| Revista, fotos, cola offline, mapa con la app abierta | PWA actual ([`SUPERVISION-CAMPO.md`](SUPERVISION-CAMPO.md)) |
| Punto en el mapa con pantalla off / segundo plano | **APK** (Capacitor + *foreground service* Android: notificación “Turno de supervisión activo”) |
| Portal de denuncias escolares | Web móvil, no APK |
| iOS | Después; el campo en Colombia es mayoritariamente Android |

La PWA **congela el JavaScript** con la pantalla apagada. Tailscale no lo arregla. Eso ya está anotado en supervisión de campo.

Matices de producto:

- En Android moderno el GPS de fondo **no puede ser invisible** (notificación persistente).
- 15 s fijos con pantalla off gasta batería; en campo suele ir 30–60 s, o más frecuente en movimiento.
- Play Console: ~USD 25 una vez + verificación + (cuentas nuevas) 12 testers × 14 días. La tienda pública no es obligatoria el día 1: Descargas + APK o Play privado para empresas cliente.
- Una sola APK para todas las empresas; el login identifica la empresa (igual que la PWA).

No reescribir la revista en React Native. Envolver lo que ya captura.

---

## 9. Hosting y dominio

**Proveedor elegido:** Hostinger, contrato **48 meses** (prioridad: no renegociar y dejar URL pública).

El plazo largo sirve. El **plan** tiene que aguantar lo que se va a meter:

| Carga | ¿Plan compartido Ilimitado (~50 GB, MySQL)? |
|-------|-----------------------------------------------|
| Landing W Codex + Controla + PWA | Arranque sí |
| Fotos de revista + PDF + GPS | Se llena; hay que rotar o disco aparte |
| Observatorio con PostGIS | **No** |

**Decisión de capacidad:** los 4 años en **Cloud** o **VPS** Hostinger, no en el Ilimitado compartido si van Controla + fotos + (luego) geo escolar. El Ilimitado se renueva ~4× más caro y no instala PostGIS.

Mismo servidor: Controla sí. Observatorio: misma máquina solo si es VPS y **base aparte**.

**Dominio comercial (padre = W Codex, no un producto):**

| Host | Uso |
|------|-----|
| `www.wcodex.co` (o `.com.co` / `.solutions`) | Estudio / empresa |
| `controla.wcodex.co` | SaaS |
| `campo.wcodex.co` | PWA y, después, la APK contra la misma API |

Si Controla se vende a terceros con marca propia, más adelante `controla.co` y W Codex queda como “hecho por”. Evitar `controla_supervision.…`, `sj-sig.controla.…` y guiones innecesarios.

El pliego de SJ puede seguir diciendo **SJ-SIG** en el Anexo; por detrás es Controla con el módulo Expediente. No uses `sjsig.` como casa del producto.

---

## 10. Cómo se cubre la licitación de vigilancia (factor 4.2.4)

No se entrega un segundo hosting “SJ-SIG producción”.

SJ (o la empresa que gane) es una **empresa en Controla**. Se activan Accesos/Supervisión según el contrato y el paquete **Expediente / SIG** (HV, cursos, parafiscales, tablero que ve el usuario de la entidad). El supervisor de la Alcaldía es usuario de **entidad** de ese cliente, no entra al Command Center ni a la PWA de campo.

Aislamiento: un supervisor de la entidad A no existe en el universo B (igual que hoy `tenant`/`client` en Controla).

---

## 11. Orden de trabajo (cuando haya fase de código)

No se abre código en este corte. El orden acordado:

1. Puesto: modalidad + unidades por cargo (de SJ-SIG, sobre `supervisor_posts`).
2. Asignación empleado de empresa → puesto.
3. Instalación: Places, mapa, código, kind.
4. Administradores opcionales por instalación.
5. ~~Censo colgando de la instalación (salón / apto).~~ **Hecho 2026-09-11.** Personas asignadas al nodo; acceso de persona (`structure_app_users`). Vigilante de portería solo si hay puertas. Supervisor firma revista en minuta con código de 6 dígitos.
6. Paquete Expediente / SIG (indexador + tablero de entidad) — cubre pliego de vigilancia.
7. APK de campo (GPS de fondo) — diferenciador comercial.
8. Observatorio escolar (6 módulos) — producto/capa aparte, misma geometría de instalaciones.

---

## 12. Fuera de alcance de este informe (no hacer)

- Fusionar repos Controla + SJ-SIG.
- Sustituir el Excel WM por el de SJ-SIG.
- Meter denuncias, calor o Línea 123 en minuta / `locations`.
- Tratar el pánico de portería como pánico anónimo escolar.
- Hacer cada colegio un `clients` aparte.
- Publicar Play Store antes de tener el *foreground service* real.
- Asumir que Hostinger Ilimitado compartido es el sitio del observatorio PostGIS.

---

## 13. Historial

| Fecha | Cambio |
|--------|--------|
| 2026-09-10 | Alta del informe. W Codex dueña; Controla producto; SJ-SIG cantera. Geometría instalación + puesto con modalidad + admins de sede + censo bajo instalación. APK para GPS de fondo. Hostinger 48 meses en Cloud/VPS. Observatorio escolar como capa, no como Accesos. |
| 2026-09-10 | Empleados de empresa: ficha SJ-SIG Personal (4 bloques + foto). Excel WM ampliado, no sustituido. Sin indexador ni asignación a puesto. |
| 2026-09-10 | Instalaciones con georreferencia. Casilla «La instalación es el mismo cliente» copia nombre + pin. Un catálogo para Accesos y Supervisión. Cartera vacía habla de clientes, no de conjuntos. |
| 2026-09-10 | Puesto compartido Accesos/Supervisión: modalidad 8/12/24 h + vigilantes. Puertas solo en Accesos (`locations`). |
| 2026-09-10 | Ficha del cliente: una tarjeta de sitio (instalaciones + puestos) y otra de puertas (solo Accesos). Sin segundo árbol de Supervisión. |
| 2026-09-10 | Sitio sin bloque de revistas (van a `/company/supervision`). Varios vigilantes por puesto; un empleado = un puesto; Reasignar en la ficha del empleado. |
| 2026-09-10 | Documentos de personal (indexador SJ-SIG) en empresa. Cliente: flag Accesos + solo empleados de sus puestos, solo lectura. Distinto de Normoteca. |
| 2026-09-11 | Censo bajo instalación (`structures.installation_id`). Panel cliente: elige instalación → nodos → personas → acceso. Usuarios del cliente = `client-admin`. Vigilante portería solo con puertas. Supervisor firma revista en minuta. Término de producto: **cliente**, no conjunto. |
