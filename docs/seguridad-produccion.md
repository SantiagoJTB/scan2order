# Seguridad para producción (MFA + recuperación)

## Requisitos mínimos

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` con dominio real y HTTPS
- `MAIL_MAILER` con proveedor real (no Mailpit)
- `QUEUE_CONNECTION=database` o `redis` (evitar `sync`)

## Variables recomendadas

En `backend/.env` de producción:

- `MFA_EMAIL_CODE_TTL_MINUTES=10`
- `MFA_EMAIL_MAX_ATTEMPTS=5`
- `MFA_USED_CODE_RETENTION_DAYS=7`
- `SECURITY_AUDIT_RETENTION_DAYS=180`

## Tareas operativas

1. Ejecutar migraciones:
   - `php artisan migrate --force`
2. Ejecutar worker de colas:
   - `php artisan queue:work --tries=3 --timeout=90`
3. Programar scheduler (cron cada minuto):
   - `* * * * * php /ruta/app/artisan schedule:run >> /dev/null 2>&1`

## Preflight antes de go-live

- Ejecutar chequeo de configuración de producción:
   - `php artisan production:check --strict`

## Checklist completa

- Revisa también: `docs/lanzamiento-produccion.md`

## Controles ya aplicados en código

- Rate limit específico para login, MFA setup/verify y password reset.
- Códigos MFA por email almacenados con hash.
- Límite de intentos de código MFA.
- Limpieza diaria automática de datos de seguridad (`security:prune`).
- Auditoría de eventos sensibles para panel de superadmin.
