<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProductionReadinessCheck extends Command
{
    protected $signature = 'production:check {--strict : Exit with error code when issues are found}';

    protected $description = 'Validate production readiness configuration for security, auth and operations';

    public function handle(): int
    {
        $checks = [
            ['APP_ENV', env('APP_ENV') === 'production', 'APP_ENV debe ser production'],
            ['APP_DEBUG', (bool) env('APP_DEBUG') === false, 'APP_DEBUG debe ser false'],
            ['APP_URL', str_starts_with((string) env('APP_URL', ''), 'https://'), 'APP_URL debe usar https://'],
            ['QUEUE_CONNECTION', !in_array((string) env('QUEUE_CONNECTION', 'sync'), ['sync', ''], true), 'QUEUE_CONNECTION no debe ser sync en producción'],
            ['MAIL_MAILER', !in_array((string) env('MAIL_MAILER', 'smtp'), ['log', 'array'], true), 'MAIL_MAILER no debe ser log/array en producción'],
            ['MAIL_HOST', !in_array((string) env('MAIL_HOST', ''), ['mailpit', 'localhost', '127.0.0.1'], true), 'MAIL_HOST no debe apuntar a mailpit/localhost en producción'],
            ['MFA_EMAIL_CODE_TTL_MINUTES', (int) env('MFA_EMAIL_CODE_TTL_MINUTES', 10) <= 10, 'MFA_EMAIL_CODE_TTL_MINUTES recomendado <= 10'],
            ['MFA_EMAIL_MAX_ATTEMPTS', (int) env('MFA_EMAIL_MAX_ATTEMPTS', 5) <= 5, 'MFA_EMAIL_MAX_ATTEMPTS recomendado <= 5'],
            ['SECURITY_AUDIT_RETENTION_DAYS', (int) env('SECURITY_AUDIT_RETENTION_DAYS', 180) <= 365, 'SECURITY_AUDIT_RETENTION_DAYS recomendado <= 365'],
            ['SANCTUM_EXPIRATION', (int) env('SANCTUM_EXPIRATION', 0) > 0, 'Configura expiración de tokens (SANCTUM_EXPIRATION > 0)'],
        ];

        $issues = [];

        $this->line('Production readiness check');
        $this->line(str_repeat('-', 40));

        foreach ($checks as [$name, $ok, $message]) {
            if ($ok) {
                $this->info("[OK] {$name}");
                continue;
            }

            $issues[] = $message;
            $this->warn("[WARN] {$name} -> {$message}");
        }

        $this->line(str_repeat('-', 40));

        if (empty($issues)) {
            $this->info('No se detectaron riesgos de configuración críticos.');
            return self::SUCCESS;
        }

        $this->warn('Se detectaron puntos a corregir antes de producción.');
        foreach ($issues as $index => $issue) {
            $this->line(($index + 1) . '. ' . $issue);
        }

        if ($this->option('strict')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
