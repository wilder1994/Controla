# App de campo (PWA)

Copia servible de `C:\laragon\www\Controla_Supervision`. Sin BD. Login: usuario (`nombre.apellido.0000`) o correo legado + contraseña. API: si el host tiene `controla_supervision` → `controla` + `/api`; si el puerto es `8085` (Tailscale) → mismo host `:8084/api`.

Revista: cliente con Supervisión → **puesto** (`supervisor_posts`, no puertas de Accesos) → vigilante → foto → GPS. Los módulos del puesto (inventario, libros, carpetas, armamento, recomendaciones) van en borrador hasta **Guardar revista**.

Recomendaciones: 1 a 3 tarjetas. Tipo de riesgo (catálogo Ajustes → Riesgos), texto del riesgo, P×I, consecuencia, tratamiento y 3 fotos. No hay título ni fecha límite ni ciclo abierto/cerrado.

Armamento: novedad Con/Sin, aseo Sí/No (foto de aseo solo si sí) y 5 fotos de identificación.

Alarmas: tipo (Ajustes → Alarmas), prueba o atención, resultado y GPS. Apoyos: tipo (Ajustes → Apoyos), motivo, cliente y GPS.

Mis fichas: lista del supervisor y carta HTML para imprimir.

**Offline:** login y abrir turno con internet. Después, revistas/módulos/GPS se guardan en el teléfono y se suben al reconectar. No borre datos del sitio si hay pendientes.

Fotos: la cámara no arranca sola. Trasera/Frontal o Tomar foto. Solo HTTPS (Tailscale Serve). En HTTP no hay foto de prueba.

Caché SW: `controla-sup-v26`. Hard-refresh tras cambios.

Tailscale: cámara = `https://sjpcanaope.tail5fcfbc.ts.net/` (Serve). No `http://IP:8085`. API: puerto `8085` → `:8084/api`; host `.ts.net` → mismo origen `/api`. `.env` local sigue `http://controla.test`.

Instalación: paneles **Descargas** (`/company/descargas`, `/admin/descargas`). Es PWA (añadir a inicio), no APK ni tiendas. Una URL para todas las empresas. En este corte: HTTP + `.test` + sin iconos 192/512; HTTPS e iconos van en producción.

Ver [`docs/SUPERVISION-CAMPO.md`](../docs/SUPERVISION-CAMPO.md) · [`docs/CLIENTES-Y-ESTRUCTURA.md`](../docs/CLIENTES-Y-ESTRUCTURA.md).
