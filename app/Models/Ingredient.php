<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'region_origin',
        'default_unit',
        'typical_shelf_life_days',
        'calories_per_100g',
        'icon',
        'description',
    ];

    public function inventories(): HasMany
    {
        return $this->hasMany(KitchenInventory::class);
    }
}

