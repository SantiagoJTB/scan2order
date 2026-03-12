<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

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

            if ($user->created_by) {
                $creatorAdminIds = Restaurant::query()
                    ->where(function (Builder $query) use ($user) {
                        $query->whereHas('admins', function (Builder $adminQuery) use ($user) {
                            $adminQuery->where('users.id', $user->created_by);
                        })->orWhere('created_by', $user->created_by);
                    })
                    ->pluck('id')
                    ->all();

                $linkedIds = array_merge($linkedIds, $creatorAdminIds);
            }

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
