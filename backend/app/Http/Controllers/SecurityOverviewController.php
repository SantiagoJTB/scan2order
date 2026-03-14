<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SecurityOverviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');

        if ($user->role?->name !== 'superadmin') {
            return response()->json(['message' => 'Only superadmin can access security overview'], 403);
        }

        $limit = (int) $request->query('limit', 20);
        $limit = max(5, min(100, $limit));

        $action = trim((string) $request->query('action', ''));
        $fromInput = (string) $request->query('from', '');
        $toInput = (string) $request->query('to', '');

        $fromDate = null;
        $toDate = null;

        if ($fromInput !== '') {
            try {
                $fromDate = Carbon::parse($fromInput)->startOfDay();
            } catch (\Throwable $exception) {
                return response()->json(['message' => 'Invalid from date'], 422);
            }
        }

        if ($toInput !== '') {
            try {
                $toDate = Carbon::parse($toInput)->endOfDay();
            } catch (\Throwable $exception) {
                return response()->json(['message' => 'Invalid to date'], 422);
            }
        }

        if ($fromDate && $toDate && $fromDate->gt($toDate)) {
            return response()->json(['message' => 'Invalid date range: from must be before to'], 422);
        }

        $superadminRoleId = Role::where('name', 'superadmin')->value('id');

        $activeSuperadmins = $superadminRoleId
            ? User::where('role_id', $superadminRoleId)->where('status', 'active')->count()
            : 0;

        $superadminsWithMfa = $superadminRoleId
            ? User::where('role_id', $superadminRoleId)->whereNotNull('mfa_enabled_at')->count()
            : 0;

        $eventsLast24h = AuditLog::where('created_at', '>=', now()->subDay())->count();

        $highRiskActionsLast24h = AuditLog::where('created_at', '>=', now()->subDay())
            ->whereIn('action', [
                'auth.password_reset_completed',
                'user.password_update',
                'user.delete',
                'user.status_update',
                'auth.mfa_disable',
            ])
            ->count();

        $eventsQuery = AuditLog::with(['actor:id,name,email', 'target:id,name,email'])
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate));

        $recentEvents = $eventsQuery
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (AuditLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'resource_type' => $log->resource_type,
                    'resource_id' => $log->resource_id,
                    'actor' => $log->actor ? [
                        'id' => $log->actor->id,
                        'name' => $log->actor->name,
                        'email' => $log->actor->email,
                    ] : null,
                    'target' => $log->target ? [
                        'id' => $log->target->id,
                        'name' => $log->target->name,
                        'email' => $log->target->email,
                    ] : null,
                    'ip_address' => $log->ip_address,
                    'created_at' => optional($log->created_at)?->toIso8601String(),
                ];
            })
            ->values();

        $availableActions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values();

        return response()->json([
            'summary' => [
                'active_superadmins' => $activeSuperadmins,
                'superadmins_with_mfa' => $superadminsWithMfa,
                'events_last_24h' => $eventsLast24h,
                'high_risk_actions_last_24h' => $highRiskActionsLast24h,
            ],
            'available_actions' => $availableActions,
            'applied_filters' => [
                'action' => $action !== '' ? $action : null,
                'from' => $fromDate?->toDateString(),
                'to' => $toDate?->toDateString(),
                'limit' => $limit,
            ],
            'recent_events' => $recentEvents,
        ]);
    }

    public function emergencyAction(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');

        if ($user->role?->name !== 'superadmin') {
            return response()->json(['message' => 'Only superadmin can perform emergency actions'], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:activate_protocol,run_contingency_check',
            'reason' => 'nullable|string|max:500',
            'confirmed' => 'required|accepted',
        ]);

        $action = $validated['action'];
        $reason = trim((string) ($validated['reason'] ?? ''));

        if ($action === 'activate_protocol') {
            $metadata = [
                'reason' => $reason !== '' ? $reason : null,
                'performed_at' => now()->toIso8601String(),
            ];

            $this->auditAction(
                $user,
                'security.emergency.protocol_activated',
                'security_panel',
                'superadmin',
                null,
                $metadata,
                $request->ip(),
                (string) $request->userAgent()
            );

            return response()->json([
                'message' => 'Protocolo de emergencia activado y registrado',
                'action' => $action,
                'metadata' => $metadata,
            ]);
        }

        $check = $this->runContingencyCheck();

        $this->auditAction(
            $user,
            'security.emergency.contingency_check',
            'security_panel',
            'superadmin',
            null,
            [
                'reason' => $reason !== '' ? $reason : null,
                'check' => $check,
            ],
            $request->ip(),
            (string) $request->userAgent()
        );

        return response()->json([
            'message' => 'Verificación de contingencia ejecutada y registrada',
            'action' => $action,
            'check' => $check,
        ]);
    }

    public function health(Request $request)
    {
        $check = $this->runContingencyCheck();

        return response()->json([
            'status' => $check['ok'] ? 'ok' : 'degraded',
            'check' => $check,
        ]);
    }

    public function guardianStatus(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');

        if ($user->role?->name !== 'superadmin') {
            return response()->json(['message' => 'Only superadmin can access guardian status'], 403);
        }

        return response()->json([
            'ok' => true,
            'mode' => 'manual-secure',
            'message' => 'Control de contenedores desde la app deshabilitado por seguridad. Usa SSH/VPN y scripts locales.',
            'commands' => [
                'check' => './ops/container-guardian.sh check long',
                'heal' => './ops/container-guardian.sh heal long',
                'restart_all' => './ops/container-guardian.sh restart all',
                'start_watchdog' => './ops/start-guardian.sh long',
                'stop_watchdog' => './ops/stop-guardian.sh',
                'emergency_restore' => './ops/emergency-recover.sh latest',
            ],
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function guardianAction(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');

        if ($user->role?->name !== 'superadmin') {
            return response()->json(['message' => 'Only superadmin can execute guardian actions'], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:heal,restart,start_daemon,stop_daemon',
            'target' => 'nullable|in:all,mailpit,db,backend,scheduler,frontend,nginx',
            'reason' => 'nullable|string|max:500',
            'confirmed' => 'required|accepted',
        ]);

        $action = $validated['action'];
        $target = $validated['target'] ?? null;
        $reason = trim((string) ($validated['reason'] ?? ''));

        $this->auditAction(
            $user,
            'security.guardian.action',
            'security_panel',
            'superadmin',
            null,
            [
                'action' => $action,
                'target' => $target,
                'reason' => $reason !== '' ? $reason : null,
                'mode' => 'manual-secure',
            ],
            $request->ip(),
            (string) $request->userAgent()
        );

        return response()->json([
            'ok' => true,
            'message' => 'Acción registrada. Por seguridad, ejecútala manualmente por SSH/VPN en el servidor.',
            'manual_command' => match ($action) {
                'heal' => './ops/container-guardian.sh heal long',
                'restart' => './ops/container-guardian.sh restart ' . ($target ?: 'all'),
                'start_daemon' => './ops/start-guardian.sh long',
                'stop_daemon' => './ops/stop-guardian.sh',
                default => null,
            },
        ]);
    }

    private function runContingencyCheck(): array
    {
        $dbOk = false;
        $storageOk = false;
        $schedulerSignalOk = false;

        try {
            DB::select('SELECT 1');
            $dbOk = true;
        } catch (\Throwable $exception) {
            $dbOk = false;
        }

        try {
            $disk = Storage::disk('public');
            $probeFile = 'health/.probe';
            $disk->makeDirectory('health');
            $disk->put($probeFile, now()->toIso8601String());
            $storageOk = $disk->exists($probeFile);
            $disk->delete($probeFile);
        } catch (\Throwable $exception) {
            $storageOk = false;
        }

        $schedulerSignalOk = file_exists(storage_path('logs/laravel.log'));

        $ok = $dbOk && $storageOk;

        return [
            'ok' => $ok,
            'database' => $dbOk,
            'storage' => $storageOk,
            'scheduler_signal' => $schedulerSignalOk,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
