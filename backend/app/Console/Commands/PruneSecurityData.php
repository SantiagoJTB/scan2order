<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\EmailMfaCode;
use Illuminate\Console\Command;

class PruneSecurityData extends Command
{
    protected $signature = 'security:prune';

    protected $description = 'Prune expired MFA email codes and old audit logs';

    public function handle(): int
    {
        $usedRetentionDays = max(1, (int) config('security.mfa_used_code_retention_days', 7));
        $auditRetentionDays = max(7, (int) config('security.audit_retention_days', 180));

        $deletedExpiredMfa = EmailMfaCode::query()
            ->where(function ($query) {
                $query->where('expires_at', '<', now())
                    ->orWhereNotNull('used_at');
            })
            ->where('updated_at', '<', now()->subDays($usedRetentionDays))
            ->delete();

        $deletedOldAuditLogs = AuditLog::query()
            ->where('created_at', '<', now()->subDays($auditRetentionDays))
            ->delete();

        $this->info("Pruned MFA codes: {$deletedExpiredMfa}");
        $this->info("Pruned audit logs: {$deletedOldAuditLogs}");

        return self::SUCCESS;
    }
}
