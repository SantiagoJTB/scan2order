# Lanzamiento a producción (checklist)

## 1) Configuración y secretos

- Definir `.env` de producción con:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://tu-dominio`
  - `QUEUE_CONNECTION=database` o `redis`
  - `MAIL_MAILER=smtp` (proveedor real)
  - `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`
  - `MFA_EMAIL_CODE_TTL_MINUTES=10`
  - `MFA_EMAIL_MAX_ATTEMPTS=5`
  - `MFA_USED_CODE_RETENTION_DAYS=7`
  - `SECURITY_AUDIT_RETENTION_DAYS=180`
  - `SANCTUM_EXPIRATION` (minutos)

- Puedes partir de la plantilla:
  - `backend/.env.production.example`

## 2) Correo (MFA y recuperación)

- Configurar dominio y registros DNS:
  - SPF
  - DKIM
  - DMARC
- Verificar entregabilidad con una cuenta real de prueba.

## 3) Base de datos y despliegue

- Ejecutar migraciones:
  - `php artisan migrate --force`
- Cachear configuración/rutas/vistas:
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`

## 4) Procesos obligatorios

- Worker de colas activo (supervisor/systemd):
  - `php artisan queue:work --tries=3 --timeout=90`
- Scheduler activo (cron cada minuto):
  - `* * * * * php /ruta/app/artisan schedule:run >> /dev/null 2>&1`

## 5) Seguridad operativa

- HTTPS forzado en frontend y backend.
- Restringir CORS al dominio de producción.
- Rotar secretos y credenciales antes del go-live.
- Confirmar que Mailpit/log mailers no están activos en producción.

## 6) Verificación final

- Ejecutar preflight:
  - `php artisan production:check --strict`
- Probar flujo real de superadmin:
  1. Login con email/contraseña.
  2. Recepción de código MFA por email.
  3. Acceso con código válido.
- Probar recuperación de contraseña completa.
- Revisar panel de seguridad superadmin (`/admin/security`) y confirmar eventos de auditoría.

## 7) Comando de despliegue recomendado

- Script listo para ejecutar pasos críticos en orden:
  - `backend/scripts/production-release.sh`
