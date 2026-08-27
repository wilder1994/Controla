# Paquetes Accesos y Supervisión

Controla vende **Accesos** y **Supervisión** por separado. El súper admin en `/admin/pricing` solo edita **unitarios**; la matriz se calcula.

## Accesos

Cupos: **1 · 5 · 10 · 50 · 100 · 500**.

| Cupo | Mixto hardware | Supervisión |
|------|----------------|-------------|
| 1 | No | No se vende |
| 5 | Sí (ej. 3 sin HW + 2 con HW) | Oferta: **10** clientes, mismo % de volumen (10 %) |
| 10 | Sí | Oferta: **20** clientes, mismo % (15 %) |
| 50 / 100 / 500 | Sí | Oferta: **ilimitada** (2× pack 100) con el % del paquete Accesos (25 / 30 / 50 %). En `/planes` se puede elegir **cualquier** cupo de Supervisión, no solo la oferta. |

Descuentos de volumen Accesos: 1=0 % · 5=10 % · 10=15 % · 50=25 % · 100=30 % · 500=50 %.

Precio mixto: `(manual × unitario_manual + hardware × unitario_hardware) × (1 − desc. del cupo)`.

La ficha de cliente **no** consume cupo. El cupo son las líneas `has_access` / `has_supervision`.

El Excel de clientes **solo** da de alta la ficha. Instalaciones, accesos y puestos se crean a mano en las tarjetas de la ficha. Ver [`CLIENTES-Y-ESTRUCTURA.md`](CLIENTES-Y-ESTRUCTURA.md).

Accesos incluye **supervisión básica en puesto** (código + minuta de portería). Eso no convierte un acceso en puesto de la app de campo.

## Supervisión (catálogo suelto)

1 · 5 · 10 · 50 · 100 e **ilimitado** (precio = **2×** el paquete de 100). Requiere Accesos de 5 o más.

GPS (~30 s), turnos, mapa/replay. Revista en la app; no se vuelve a firmar en portería.

## Checkout y cambios

`/planes`: cupo Accesos, mezcla hardware (desde 5) y Supervisión (oferta del cupo o cualquier otro del catálogo).

Empresa ya cliente: cambios se **programan y aplican al corte** (Accesos, mixto y Supervisión). Quitar Supervisión no cobra; alta/cambio abre checkout.

## App de campo y catálogos

Detalle: [`SUPERVISION-CAMPO.md`](SUPERVISION-CAMPO.md).

PWA en `field-app/` (copia alineada: `Controla_Supervision`). API `/api/supervision/*`. Login de supervisor no usa `structure_app_users`.

Ajustes empresa: **Zonas / Turnos / Preoperacional / Documentos / Libros / Tipos de arma / Marcas / Riesgos** (además de Cargos y Tipos). La app solo muestra ítems activos. Recomendaciones: registro de riesgo por puesto (hasta 3), no ticket.

Captura: tras login, rito de turno (catálogo de turno y zona, EPP/vehículo plegables, odómetro + selfie al abrir y al cerrar). Hub: perfil + revista (cliente, puesto, vigilante, foto, GPS al guardar) + módulos colgados + alarmas/apoyos/documentos. Flota en `supervisor_fleet_vehicles`, no en `vehicles` de Accesos.

No choca con `/access/supervision` (código en puesto), `SupervisorReview`, `PlatformDocument` ni correspondencia de Accesos.
