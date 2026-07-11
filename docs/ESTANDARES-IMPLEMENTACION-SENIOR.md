# Estándares de implementación — Nivel senior

> **Propósito:** Contrato de calidad transversal para **todo** código y UI que entra a Controla.  
> **Versión:** 1.0  
> **Fecha:** 2026-07-11  
> **Estado:** Vigente — aplica desde Fase 2 en adelante y en refactors de fases ya entregadas  
> **Relacionado:** [ESTRATEGIA-VERSIONES-Y-ALCANCE.md](./ESTRATEGIA-VERSIONES-Y-ALCANCE.md) · [PLAN-INICIO-PROYECTO-CONTROLA.md](./PLAN-INICIO-PROYECTO-CONTROLA.md)

---

## 1. Principio rector

> **No entregamos features “básicas” ni placeholders de producción.**  
> Cada módulo liberado en un release debe ser **usable en piloto real** dentro de su alcance, con arquitectura preparada para escalar (multi-tenant, API, integraciones).

**“Senior”** no significa “implementar todas las fases antes de publicar”. Significa que **cada entrega cumple el mismo estándar**, sin deuda deliberada del tipo “lo arreglamos en v2”.

---

## 2. Definition of Done (DoD) — obligatorio por PR

Un ítem **no está hecho** hasta cumplir **todos** los puntos aplicables:

### 2.1 Multi-tenant y seguridad

- [ ] Toda query de dominio respeta `company_id` / `client_id` (scope, middleware o repository).
- [ ] Ruta protegida con `middleware` de permiso Spatie y/o **Policy** explícita.
- [ ] **Prohibido** autorizar solo en Blade (`@can` sin policy en backend no basta para mutaciones).
- [ ] Test Feature de **aislamiento** cuando el endpoint lee/escribe datos scoped (cliente A ≠ cliente B).
- [ ] FormRequest con validación completa; mensajes en español cuando la UI es español.

### 2.2 Arquitectura por capas

```
HTTP (Controller / FormRequest)
    → Service (caso de uso, transacción DB)
        → Repository (queries, scopes)
            → Model (relaciones, casts, enums)
```

- [ ] Controller delgado: delega a Service, sin lógica de negocio extensa.
- [ ] Un Service por caso de uso (`RegisterPedestrianEntryService`, no `AccessService` monolítico).
- [ ] Archivos nuevos en `app/`: `declare(strict_types=1);` salvo vistas/config migradas.
- [ ] Enums tipados (`app/Enums/`) en lugar de strings mágicos en dominio nuevo.

### 2.3 Base de datos

- [ ] Migraciones **aditivas**; no alterar migraciones ya en `main`.
- [ ] Índices en FKs y columnas de filtro frecuente (`client_id`, fechas, `exit_time IS NULL`).
- [ ] Seeders **idempotentes** (`updateOrCreate` / equivalente).
- [ ] **Prohibido** `migrate:fresh`, `db:wipe` en BD de desarrollo sin autorización explícita (ver `.cursor/rules/database-safety.mdc`).

### 2.4 UI / UX

- [ ] Estados vacíos, errores de validación y mensajes flash coherentes.
- [ ] Copy en español en paneles B2B (Controla Colombia).
- [ ] Responsive usable en tablet (portería).
- [ ] Accesibilidad mínima: labels en inputs, contraste legible, foco en modales.
- [ ] Sin enlaces rotos ni acciones que fallen silenciosamente.

### 2.5 Tests

- [ ] Feature test del happy path del endpoint o flujo crítico.
- [ ] Unit test del Service cuando hay reglas de negocio no triviales.
- [ ] Suite contra `controla_test` únicamente (`phpunit.xml`).
- [ ] CI/local: el PR no rompe tests existentes del módulo tocado.

### 2.6 Documentación y trazabilidad

- [ ] PR/commit cita sección de referencia si aplica (ej. `§2.2 Personas adentro`, `Plan Fase 2.1`).
- [ ] README o doc de módulo actualizado si cambia instalación, rutas o credenciales demo.
- [ ] Cambios de alcance de release reflejados en [ESTRATEGIA-VERSIONES-Y-ALCANCE.md](./ESTRATEGIA-VERSIONES-Y-ALCANCE.md) cuando corresponda.

---

## 3. Anti-patrones prohibidos

| Anti-patrón | Por qué |
|-------------|---------|
| `if ($user->hasRole(...))` en Blade como única protección | Bypass vía HTTP directo |
| Queries sin scope en módulos `/access`, `/client`, `/company` | Fuga cross-tenant |
| “Pantalla demo” con datos hardcodeados en producción | No sirve en piloto |
| Servicios de 500+ líneas multi-responsabilidad | Imposible testear y extender |
| Mezclar contabilidad PH con portería en mismo bounded context | Deuda Axesa-style |
| Integraciones síncronas bloqueantes en request de portería | UX > 45 s, timeouts |
| Commits sin tests en flujos de acceso/censo | Riesgo legal y operativo |

---

## 4. Extension points (preparar desde ya)

Aunque un módulo enterprise llegue en v1.1+, el núcleo **debe** dejar ganchos:

| Necesidad futura | Preparación mínima |
|------------------|-------------------|
| White label | `clients.logo_path`, branding en PDF/export; evitar colores hardcodeados únicos |
| API residente / móvil | Rutas `/api/v1/*`, Resources, Sanctum; no lógica solo en Blade |
| Notificaciones push | Eventos de dominio (`CorrespondenceReceived`, `PanicTriggered`) + cola |
| Hardware RFID/LPR | Capa `AccessHardware` / adapters; ingreso manual siempre como fallback |
| Antecedentes legales | Servicio aislado con timeout, auditoría, consentimiento; no bloquear portería |
| BI | Endpoints JSON agregados; no solo HTML de reportes |

---

## 5. Checklist de revisión de PR (revisor humano o agente)

```markdown
## Revisión Controla — Senior DoD

- [ ] Scope tenant correcto (¿puede cliente A ver datos de B?)
- [ ] Policy/middleware en ruta
- [ ] Service + transacción donde hay mutación multi-tabla
- [ ] FormRequest / validación
- [ ] Tests Feature (y Unit si aplica)
- [ ] UX: vacíos, errores, español
- [ ] Sin migrate:fresh / db:wipe en instrucciones
- [ ] Referencia docs citada
```

---

## 6. KPIs técnicos (alineados al plan)

| KPI | Meta |
|-----|------|
| Endpoints `/access/*` con policy | 100 % |
| Cobertura tests Services críticos | ≥ 80 % |
| Tiempo respuesta “personas adentro” | < 500 ms p95 |
| Incidentes fuga datos cross-tenant | **0** |

---

## 7. Registro de cambios

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 1.0 | 2026-07-11 | DoD senior, anti-patrones, extension points, checklist PR |

---

*Documento vivo. Cualquier excepción al DoD debe quedar explícita en el PR y aprobada.*
