<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeSubstitution extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipe_id',
        'original_ingredient',
        'substitute_ingredient',
        'ratio_adjustment',
        'culinary_notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(RecipeCache::class, 'recipe_id');
    }
}

