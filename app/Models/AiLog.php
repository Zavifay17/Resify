<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'model_used',
        'prompt_summary',
        'prompt_tokens',
        'response_tokens',
        'total_tokens',
        'execution_time_ms',
        'status',
        'error_message',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'response_tokens' => 'integer',
            'total_tokens' => 'integer',
            'execution_time_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action_type) {
            'recipe_generation' => 'Generator Resep Masakan',
            'vision_detection' => 'Computer Vision Bahan Makanan',
            'substitute_suggestion' => 'Pencarian Bahan Pengganti',
            'nutrition_estimation' => 'Estimasi Nutrisi AI',
            'expiration_rescue' => 'Zero-Waste Resep Penyelamat',
            'test_connection' => 'Uji Koneksi API Gemini',
            default => ucfirst(str_replace('_', ' ', $this->action_type)),
        };
    }
}

