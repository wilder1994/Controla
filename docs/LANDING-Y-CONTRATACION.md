# Landing y contratación pública

Flujo guest: welcome → planes → contratación → checkout simulado → cuenta activa **solo** si el pago se aprueba.

**Última actualización:** agosto 2026

---

## Welcome (`GET /`)

Vista: `resources/views/welcome.blade.php` · Controlador: `App\Http\Controllers\Public\WelcomeController` (invocable).

Usuarios autenticados son redirigidos a `/home`. Invitados ven la landing completa.

### Layout (una pantalla, sin scroll)

| Zona | Detalle |
|------|---------|
| Contenedor | `h-screen overflow-hidden` — header, main flexible y footer fijos |
| Header | Logo Controla + botón cyan «Iniciar sesión» (`/login`) |
| Hero | `flex-1` en el main: grid 40/60 (`lg:grid-cols-5`, texto `col-span-2`, imagen `col-span-3`) |
| Texto hero | Eyebrow «PLATAFORMA CONTROLA» + H1 «Control de accesos inteligente» (sin párrafo largo) |
| Tarjeta comercial | Solo `@guest`: pills Licencia SaaS + descuento anual, precio mínimo, CTA «Ver planes y contratar» → `/planes`, enlace secundario login |
| Imagen | `hero-dashboard.png` a altura del hero (`h-full object-cover`) |
| Cards inferiores | 3 cards glass (Portería, Censo, Multi-cliente) — `shrink-0`, sin cambio de copy |
| Footer | Copyright + WM CodeSoft |

### Datos dinámicos

| Variable | Origen |
|----------|--------|
| `$minMonthly` | `PriceCalculator::quote(Manual, 1, Monthly)` con `PricingSettings::current()` |
| `$annualDiscount` | `config('tenancy.pricing.annual_discount')` (default 17%) |

El precio mostrado es el **mínimo mensual** (1 conjunto, modalidad manual), no un paquete arbitrario.

### Assets

| Archivo | Uso |
|---------|-----|
| `resources/images/branding/logo-controla.png` | Header |
| `resources/images/welcome/hero-background.png` | Fondo fijo |
| `resources/images/welcome/hero-dashboard.png` | Hero derecho |

Requiere junction `public/images` → `resources/images` (ver README § Assets estáticos).

---

## Rutas públicas

| Ruta | Función |
|------|---------|
| `GET /` | Welcome con tarjeta comercial |
| `GET /planes` | Matriz de precios (PriceCalculator) |
| `GET /contratar?sku=&cycle=` | Crea `commercial_signup_intents` |
| `GET/POST /contratar/datos/{token}` | Paso 1: datos empresa, contacto, dirección/geo, contraseña |
| `GET/POST /contratar/legal/{token}` | Paso 2 clickwrap |
| `GET /contratar/resumen/{token}` | Resumen |
| `POST /contratar/pagar/{token}` | Checkout simulado |
| `GET /contratar/checkout/{token}` | Aprobar / Rechazar |
| `POST /contratar/checkout/{token}/approve` | Activa cuenta |
| `POST /contratar/checkout/{token}/reject` | Sin user en BD |

### Servicios y modelo

| Pieza | Ubicación |
|-------|-----------|
| Intents | `App\Models\CommercialSignupIntent` + enum `SignupIntentStatus` |
| Wizard | `App\Http\Controllers\Public\SignupController` |
| Checkout guest | `App\Http\Controllers\Public\SignupCheckoutController` |
| Completar signup | `App\Services\Public\CompletePublicSignupService` |
| Rutas | `routes/modules/public.php` (middleware `guest`) |
| Layout público | `resources/views/layouts/public.blade.php` |
| Vistas wizard | `resources/views/modules/public/signup/*` |
| Planes | `resources/views/modules/public/plans/index.blade.php` |

Migración: `2026_08_02_160000_create_commercial_signup_intents_table.php`

---

## Regla de BD

| Etapa | `users` | `security_companies` |
|-------|---------|----------------------|
| `/planes` | No | No |
| Intent draft | No | No |
| Checkout pendiente | No | No |
| Pago aprobado | Sí | Sí |
| Pago rechazado | No | No |

Tabla temporal: `commercial_signup_intents` (TTL configurable).

---

## Config `.env`

```env
BILLING_GATEWAY_DRIVER=local
BILLING_MODE=demo
BILLING_SIGNUP_INTENT_TTL_HOURS=24
BILLING_ALLOW_PUBLIC_REGISTER=false
```

`BILLING_ALLOW_PUBLIC_REGISTER=false` desactiva el `/register` genérico de Breeze; la contratación usa `/contratar`.

---

## Tests

```bash
php artisan test --filter=PublicSignupFlowTest
```

---

Ver también: [`BILLING-LOCAL-Y-MIGRACION.md`](BILLING-LOCAL-Y-MIGRACION.md) · [`MODULO-DOCUMENTOS.md`](MODULO-DOCUMENTOS.md) · [`USUARIOS-Y-PERFILES.md`](USUARIOS-Y-PERFILES.md)
