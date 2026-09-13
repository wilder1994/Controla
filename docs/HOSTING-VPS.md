# Hosting VPS (Controla)

**Última actualización:** 13 septiembre 2026

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

Observatorio: migrate `2026_09_13_120000` (tipos de hecho + `panel_modules.observatory` en clientes ya creados). Tras migrate: `php artisan db:seed --class=RoleAndPermissionSeeder` (añade `observatory.events.update` al admin del cliente). Intake `/o/{slug}`. API `/api/observatory/*` + docs `/docs/observatory`.

```bash
SITE=/home/wcodex-controla/htdocs/controla.wcodex.cloud
cd "$SITE"
sudo -u wcodex-controla git pull --ff-only origin main
sudo -u wcodex-controla -H bash -lc "cd '$SITE' && php8.3 /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction"
sudo -u wcodex-controla -H bash -lc "cd '$SITE' && npm ci && npm run build"
php8.3 artisan migrate --force --no-interaction
php8.3 artisan db:seed --class=RoleAndPermissionSeeder --force --no-interaction
php8.3 artisan config:cache
php8.3 artisan route:cache
php8.3 artisan view:cache
chown -R wcodex-controla:wcodex-controla "$SITE"
```

Cron del sitio: `* * * * * php8.3 artisan schedule:run`.

El indexador de Documentos no depende del MIME de `.mjs` (el worker se carga como blob). Si otro módulo ES falla en consola con `application/octet-stream`, en el vhost de CloudPanel (directivas extra de Nginx):

```nginx
types { application/javascript mjs; }
```

Luego `nginx -t && systemctl reload nginx`.
