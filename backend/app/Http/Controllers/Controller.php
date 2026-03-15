<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function auditAction(
        ?User $actor,
        string $action,
        ?string $resourceType = null,
        null|string|int $resourceId = null,
        ?User $targetUser = null,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        try {
            AuditLog::create([
                'actor_user_id' => $actor?->id,
                'target_user_id' => $targetUser?->id,
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId !== null ? (string) $resourceId : null,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            // Do not block primary flow if audit storage fails.
        }
    }

    protected function managedRestaurantIds(?User $user): array
    {
        if (!$user) {
            return [];
        }

        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        if ($user->hasRole('superadmin')) {
            return Restaurant::pluck('id')->all();
        }

        if ($user->hasRole('admin')) {
            return Restaurant::query()
                ->where(function (Builder $query) use ($user) {
                    $query->whereHas('admins', function (Builder $adminQuery) use ($user) {
                        $adminQuery->where('users.id', $user->id);
                    })->orWhere('created_by', $user->id);
                })
                ->pluck('id')
                ->all();
        }

        if ($user->hasAnyRole(['staff'])) {
            $linkedIds = $user->restaurants()->pluck('restaurants.id')->all();

            return array_values(array_unique(array_map('intval', $linkedIds)));
        }

        return [];
    }

    protected function canAccessRestaurant(?User $user, int $restaurantId): bool
    {
        if (!$user) {
            return false;
        }

        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('cliente')) {
            return false;
        }

        return in_array($restaurantId, $this->managedRestaurantIds($user), true);
    }
}
