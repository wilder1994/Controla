# App de campo (PWA)

Copia servible de `C:\laragon\www\Controla_Supervision`. Sin BD. Login: correo + contraseña. API: si el host tiene `controla_supervision` → `controla` + `/api`; si el puerto es `8085` (Tailscale) → mismo host `:8084/api`.

Revista: cliente con Supervisión → **puesto** (`supervisor_posts`, no puertas de Accesos) → vigilante → foto → GPS. Los módulos del puesto (inventario, libros, carpetas, armamento, recomendaciones) van en borrador hasta **Guardar revista**.

Recomendaciones: 1 a 3 tarjetas. Tipo de riesgo (catálogo Ajustes → Riesgos), texto del riesgo, P×I, consecuencia, tratamiento y 3 fotos. No hay título ni fecha límite ni ciclo abierto/cerrado.

Armamento: novedad Con/Sin, aseo Sí/No (foto de aseo solo si sí) y 5 fotos de identificación.

Alarmas: tipo (Ajustes → Alarmas), prueba o atención, resultado. Apoyos: tipo (Ajustes → Apoyos) + motivo.

Fotos: la cámara no arranca sola. Trasera/Frontal o Tomar foto (HTTPS). Se apaga al capturar.

Caché SW: `controla-sup-v20`. Hard-refresh tras cambios.

Tailscale (Laragon, sin tocar Armory/LegalSuite): PWA `http://100.108.131.98:8085` o `http://sjpcanaope.tail9f649e.ts.net:8085`. API en `:8084`. `.env` local sigue `http://controla.test`.

Instalación: paneles **Descargas** (`/company/descargas`, `/admin/descargas`). Es PWA (añadir a inicio), no APK ni tiendas. Una URL para todas las empresas. En este corte: HTTP + `.test` + sin iconos 192/512; HTTPS e iconos van en producción.

Ver [`docs/SUPERVISION-CAMPO.md`](../docs/SUPERVISION-CAMPO.md) · [`docs/CLIENTES-Y-ESTRUCTURA.md`](../docs/CLIENTES-Y-ESTRUCTURA.md).
