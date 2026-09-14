# Hosting VPS (Controla)

**Última actualización:** 14 septiembre 2026

Sitio público: [https://controla.wcodex.cloud](https://controla.wcodex.cloud)

| Dato | Valor |
|------|--------|
| Proveedor | Hostinger VPS KVM 2, São Paulo, Ubuntu 24.04 + CloudPanel |
| IP | `82.25.66.93` |
| Usuario del sitio | `wcodex-controla` |
| Código | `/home/wcodex-controla/htdocs/controla.wcodex.cloud` |
| Document root | `…/public` |
| PHP | 8.3 |
| Repo | `https://github.com/wilder1994/Controla.git` rama `main` |

Flujo: push local a `origin` (`wilder1994/Controla`, `main`). El VPS solo hace `git pull` + composer/npm/migrate. No FTP ni ZIP. No `migrate:fresh` ni `db:wipe` en producción.

Si el commit añade roles o permisos (`config/access.php`), tras migrate corre `php artisan db:seed --class=RoleAndPermissionSeeder` (sync Spatie; no vacía datos). El resto de seeders no se corre salvo petición explícita.

Este deploy **no** hace `migrate:fresh`. Las `add_*` unificadas en `create_*` solo aplican en instalación limpia. En el VPS ya corrido, `migrate` solo aplica pendientes (p. ej. `user_module_grants`). Backup selectivo de empleados/PDFs: carpeta `controla-YYYYMMDD` en el home del sitio; borrar **después** de restaurar y verificar, no en este pull.

Observatorio: migrate `2026_09_13_120000` (tipos + módulo). Tras ese migrate: `RoleAndPermissionSeeder` (permiso `observatory.events.update`). Intake `/o/{slug}` (solo colegios). API `/api/observatory/*`. Comunas: `resources/data/cali-comunas.geojson`, el mapa las pide en `/geo/cali-comunas.geojson` (relativo). **No** cargar `libraries=visualization`: Maps JS 3.65 quitó HeatmapLayer. PPTX: `GET /company/observatory/tablero.pptx` y `/client/observatory/tablero.pptx` (mismo permiso `observatory.view`; `route:cache` + `view:cache`). Cortes UX: `npm run build` + `view:cache`. Sin migrate ni seeder.

Avisos en español (13 sep 2026): `lang/es/*`, toast centrado, PWA `controla-sup-v41`.

Colaborador + Clientes (13 sep 2026): `ClientController` / `ClientPolicy`. Corte PHP: pull + `view:cache` + `config:cache`. Sin migrate, seeder ni `npm run build`.

Puestos + mapas (14 sep 2026): varios puestos por instalación; pines cliente/instalación en Mi empresa y Supervisión. Pull + `view:cache` + `config:cache` + `route:cache`. Sin migrate ni seeder.

App de campo (14 sep 2026): PWA en `https://controla.wcodex.cloud/campo/` (no hay DNS `controla_supervision.wcodex.cloud`). APK v1.1 en `public/downloads/controla-supervision.apk` (primer ingreso: usuario + clave). En VPS: `SUPERVISION_PWA_URL=https://controla.wcodex.cloud/campo/` + pull + `route:cache` + `view:cache` + `config:cache`. Sin migrate ni seeder.

Observatorio Compartir link (14 sep 2026): modal con scroll y buscador si hay muchos clientes. Pull + `npm run build` + `view:cache`. Sin migrate ni seeder.

Observatorio campo (14 sep 2026): mapa pin + 1–3 fotos en PWA/APK. Migrate `2026_09_14_120000` (`photo_paths`). Pull + `migrate --force` + `view:cache` + `route:cache`. Copiar PWA `public/campo` (va en el git) y APK v1.2. Sin seeder. `npm run build` por el Blade de la ficha.

Landing welcome (14 sep 2026): scroll (`min-h-screen`), H1 Accesos/supervisión/observatorio, 3 cards, marca WCodex. Pull + `npm run build` + `view:cache`. Sin migrate ni seeder.

Observatorio fotos link + tipos empresa (14 sep 2026): `/o/{slug}` y panel rector con cámara/miniaturas (hasta 3, opcional). Leyenda empresa por slug. Pull + `npm run build` + `view:cache`. Sin migrate ni seeder.

Menú móvil paneles (14 sep 2026): hamburguesa + drawer en cliente/empresa/plataforma/portería. Pull + `npm run build` + `view:cache`. Sin migrate ni seeder.

Parafiscales planilla (14 sep 2026): recorte = copia del xlsx original (fondo/logo) + filas del cotizante; una carga sustituye la anterior. Overlay con %. Sin migrate. Pull + `view:cache` + `route:cache`.

Plan súper admin (14 sep 2026): ficha empresa aplica Accesos+Supervisión ya / fecha / al corte; cartera de clientes con scroll. Sin migrate ni seeder. Pull + `npm run build` + `route:cache` + `view:cache` + `config:cache`.

**Artisan cache siempre como `wcodex-controla`.** Si `view:cache` corre como root, Gestionar ficha da 500 (`Permission denied` al escribir `storage/framework/views`). Tras el cache: `chown -R wcodex-controla:wcodex-controla "$SITE"`.

APK Supervisión: el VPS no lo fabrica. Va en el repo (`public/downloads/controla-supervision.apk`). Tras pull: `route:cache` + `view:cache`. Descargas: `/company/descargas` y `/admin/descargas`.

```bash
SITE=/home/wcodex-controla/htdocs/controla.wcodex.cloud
cd "$SITE"
sudo -u wcodex-controla git pull --ff-only origin main
sudo -u wcodex-controla -H bash -lc "cd '$SITE' && php8.3 /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction"
sudo -u wcodex-controla -H bash -lc "cd '$SITE' && npm ci && npm run build"
sudo -u wcodex-controla -H bash -lc "cd '$SITE' && php8.3 artisan migrate --force --no-interaction"
sudo -u wcodex-controla -H bash -lc "cd '$SITE' && php8.3 artisan db:seed --class=RoleAndPermissionSeeder --force --no-interaction"
sudo -u wcodex-controla -H bash -lc "cd '$SITE' && php8.3 artisan config:cache && php8.3 artisan route:cache && php8.3 artisan view:cache"
chown -R wcodex-controla:wcodex-controla "$SITE"
```

Cron del sitio: `* * * * * php8.3 artisan schedule:run`.

El indexador de Documentos no depende del MIME de `.mjs` (el worker se carga como blob). Si otro módulo ES falla en consola con `application/octet-stream`, en el vhost de CloudPanel (directivas extra de Nginx):

```nginx
types { application/javascript mjs; }
```

Luego `nginx -t && systemctl reload nginx`.
