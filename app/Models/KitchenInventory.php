<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ingredient_id',
        'item_name',
        'category',
        'quantity',
        'unit',
        'storage_location',
        'purchase_date',
        'expiration_date',
        'status',
        'notes',
        'photo_path',
        'supabase_id',
        'supabase_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'expiration_date' => 'date',
            'quantity' => 'decimal:2',
            'supabase_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Hitung sisa hari kedaluwarsa secara dinamis
     */
    public function getDaysUntilExpirationAttribute(): int
    {
        if (!$this->expiration_date) {
            return 999;
        }

        $now = Carbon::today();
        return (int) $now->diffInDays($this->expiration_date, false);
    }

    /**
     * Label sisa hari yang aman dibaca walau tanggalnya sudah lewat
     */
    public function getExpiryLabelAttribute(): string
    {
        $days = $this->days_until_expiration;

        if ($days >= 999) {
            return 'Tanpa tanggal';
        }

        if ($days < 0) {
            return 'Lewat ' . abs($days) . ' hari';
        }

        return $days === 0 ? 'Habis hari ini' : "Sisa {$days} hari";
    }

    /**
     * Hitung status mutakhir berdasarkan sisa hari
     */
    public function getComputedStatusAttribute(): string    {
        $days = $this->days_until_expiration;
        if ($days < 0) {
            return 'expired';
        }
        if ($days <= 3) {
            return 'expiring_soon';
        }
        return 'safe';
    }

    /**
     * Badge visual status untuk UI
     */
    public function getStatusBadgeAttribute(): array
    {
        $status = $this->computed_status;
        $days = $this->days_until_expiration;

        if ($status === 'expired') {
            return [
                'label' => 'Kadaluwarsa (' . abs($days) . ' hari lalu)',
                'class' => 'chip chip-danger',
                'dot' => 'dot-danger',
                'severity' => 'danger'
            ];
        }

        if ($status === 'expiring_soon') {
            $text = $days === 0 ? 'Kedaluwarsa Hari Ini!' : ($days === 1 ? '1 Hari Lagi!' : "{$days} Hari Lagi");
            return [
                'label' => "Perlu Segera Dimasak ({$text})",
                'class' => 'chip chip-warn',
                'dot' => 'dot-warn',
                'severity' => 'warning'
            ];
        }

        return [
            'label' => "Aman ({$days} hari)",
            'class' => 'chip chip-ok',
            'dot' => 'dot-ok',
            'severity' => 'success'
        ];
    }

    // Scopes
    public function scopeSafe($query)
    {
        return $query->whereDate('expiration_date', '>', Carbon::today()->addDays(3));
    }

    public function scopeExpiringSoon($query)
    {
        return $query->whereDate('expiration_date', '>=', Carbon::today())
                     ->whereDate('expiration_date', '<=', Carbon::today()->addDays(3));
    }

    public function scopeExpired($query)
    {
        return $query->whereDate('expiration_date', '<', Carbon::today());
    }
}

