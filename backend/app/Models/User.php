<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role_id',
        'created_by',
        'status',
        'staff_creation_limit',
        'mfa_secret',
        'mfa_enabled_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'mfa_enabled_at' => 'datetime',
    ];

    /**
     * The user's role.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * User who created this user.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Restaurants the user belongs to (pivot stores role_id).
     */
    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'user_restaurant')
                    ->withPivot('role_id');
    }

    /**
     * Global roles assigned to the user.
     */
    public function globalRoles()
    {
        return $this->belongsToMany(Role::class, 'user_global_role');
    }

    /**
     * Orders placed by the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole($roleName)
    {
        if (!$this->role) {
            return false;
        }
        return $this->role->name === $roleName;
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole($roles)
    {
        $roles = is_array($roles) ? $roles : func_get_args();
        if (!$this->role) {
            return false;
        }
        return in_array($this->role->name, $roles);
    }

    /**
     * Get permissions from role.
     */
    public function permissions()
    {
        return $this->role ? $this->role->permissions : collect();
    }

    /**
     * Check if user has permission.
     */
    public function hasPermission($permissionName)
    {
        return $this->permissions()->contains('name', $permissionName);
    }
}

