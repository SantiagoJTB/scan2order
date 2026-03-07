<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'phone', 'active', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function catalogs()
    {
        return $this->hasMany(Catalog::class)->orderBy('order');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function tables()
    {
        return $this->hasMany(Table::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_restaurant')
                    ->withPivot('role_id');
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'user_restaurant')
            ->withPivot('role_id')
            ->whereHas('role', function ($query) {
                $query->where('name', 'admin');
            });
    }
}
