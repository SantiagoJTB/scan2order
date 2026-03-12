<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['restaurant_id', 'category_id', 'section_id', 'name', 'description', 'image', 'price', 'active'];

    protected $casts = [
        'price' => 'float',
        'active' => 'boolean',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

