# App de campo (PWA)

Copia servible de `C:\laragon\www\Controla_Supervision`. Sin BD. `API_URL` → `/api` de Controla.

Revista: cliente con Supervisión → **puesto** (`supervisor_posts`, no puertas de Accesos) → vigilante → foto → GPS. Los módulos del puesto (inventario, libros, carpetas, armamento, recomendaciones) van en borrador hasta **Guardar revista**.

Recomendaciones: 1 a 3 tarjetas. Tipo de riesgo (catálogo Ajustes → Riesgos), texto del riesgo, P×I, consecuencia, tratamiento y 3 fotos. No hay título ni fecha límite ni ciclo abierto/cerrado.

Armamento: novedad Con/Sin, aseo Sí/No (foto de aseo solo si sí) y 5 fotos de identificación.

Alarmas: tipo (Ajustes → Alarmas), prueba o atención, resultado. Apoyos: tipo (Ajustes → Apoyos) + motivo.

Caché SW: `controla-sup-v16`. Hard-refresh tras cambios.

Ver [`docs/SUPERVISION-CAMPO.md`](../docs/SUPERVISION-CAMPO.md) · [`docs/CLIENTES-Y-ESTRUCTURA.md`](../docs/CLIENTES-Y-ESTRUCTURA.md).
