# Paquetes Accesos, Supervisión, Indexación y Observatorio

Catálogo vigente (29 sep 2026). Detalle de precios y clases: [`MODELO-COMERCIAL-PAQUETES.md`](MODELO-COMERCIAL-PAQUETES.md).

## Metales (mismos 4 en Accesos, Supervisión e Indexación)

| Plan | Instalaciones | Empleados |
|------|---------------|-----------|
| Bronce | 1–5 (pack 5) | 3.000 |
| Plata | 6–15 (pack 15) | 6.000 |
| Oro | 16–40 (pack 40) | 9.000 |
| Platino | 41–100 (pack 100) | 12.000 |

Unidades sueltas al **100%**. Si N supera el pack, se cobra el **mayor pack que quepa + extras**. Con Accesos + Supervisión el tope de empleados es el **mayor** de los dos.

## Productos

**Accesos** — cupo de instalaciones activas (`max_clients`). Menú: Mi empresa, Facturación, Clientes, Instalaciones, Pánicos, Empleados, Usuarios, Mis datos, Ajustes. **No** Descargas.

**Supervisión** — mismo metal. Menú: Facturación, Clientes, Instalaciones, Supervisión, Pánicos, Descargas, Empleados, Usuarios, Mis datos, Ajustes. **No** Mi empresa. GPS y app de campo: [`SUPERVISION-CAMPO.md`](SUPERVISION-CAMPO.md).

**Indexación** — sola: lista (cupo + carpetas). Con Accesos o Supervisión: **add-on más barato**, solo carpetas (no suma cupo). Flags `has_indexing`.

**Observatorio** — un solo módulo. Precio según el metal de Accesos. Sin Accesos no se vende. Flag `has_observatory`.

Módulos por producto se marcan en `/admin/pricing` → Editar catálogo.

## Cupo de sedes

Crear cliente o instalación pide 1 cupo libre. Archivar sede libera. `has_access` / `has_supervision` por cliente siguen aparte. Excel de clientes solo da de alta la ficha. [`CLIENTES-Y-ESTRUCTURA.md`](CLIENTES-Y-ESTRUCTURA.md).

## Checkout

`/` y `/planes` muestran el catálogo nuevo. El alta pública (`signup`) aún mapea metales a SKU legacy (`pack_5/10/50/100_manual`). La ficha empresa aplica Accesos + Supervisión ya / fecha / corte. Cobro aparte.

## App de campo

PWA `field-app/` · API `/api/supervision/*` · **Descargas** (APK + QR). No Play Store.
