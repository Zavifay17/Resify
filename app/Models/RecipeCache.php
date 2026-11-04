<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RecipeCache extends Model
{
    use HasFactory;

    protected $table = 'recipes_cache';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'cooking_time_minutes',
        'difficulty',
        'servings',
        'cuisine_type',
        'region',
        'image_url',
        'meal_type',
        'ingredient_hash',
        'used_ingredients',
        'missing_ingredients',
        'instructions',
        'nutrition_facts',
        'chef_tips',
        'tags',
        'source',
        'is_demo_fallback',
        'is_favorite',
    ];

    protected function casts(): array
    {
        return [
            'used_ingredients' => 'array',
            'missing_ingredients' => 'array',
            'instructions' => 'array',
            'nutrition_facts' => 'array',
            'tags' => 'array',
            'is_favorite' => 'boolean',
            'is_demo_fallback' => 'boolean',
            'cooking_time_minutes' => 'integer',
            'servings' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($recipe) {
            if (empty($recipe->slug)) {
                $recipe->slug = Str::slug($recipe->title) . '-' . Str::random(5);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Foto hidangan (sumber terbuka) dengan placeholder lokal bila URL belum terisi,
     * sehingga kartu resep di katalog tidak pernah menampilkan gambar rusak.
     */
    public function getPhotoAttribute(): string
    {
        return $this->image_url ?: asset('images/katalog/default-nusantara.svg');
    }

    public function getShortTitleAttribute(): string
    {
        return Str::limit($this->title, 46, '');
    }

    public function substitutions(): HasMany
    {
        return $this->hasMany(RecipeSubstitution::class, 'recipe_id');
    }
}

