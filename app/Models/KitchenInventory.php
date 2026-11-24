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
        'archived_at',
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
            'archived_at' => 'datetime',
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
     * Bahan yang sudah diarsipkan tidak masuk lagi ke pilihan generator resep
     */
    public function getIsArchivedAttribute(): bool
    {
        return !empty($this->archived_at);
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

    /**
     * Bahan yang masih boleh dipakai untuk generator resep AI
     */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Arsip bahan yang sudah lewat kedaluwarsa (tetap bisa dibaca, tidak dipakai lagi)
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Alias accessor icon untuk kemudahan pemanggilan di Blade
     */
    public function getIconAttribute(): string
    {
        return $this->display_icon;
    }

    /**
     * Resolusi cerdas icon emoji bahan makanan khas Indonesia.
     * Tidak lagi menampilkan salad generic (🥗) atau es batu (🧊) untuk tahu/sayuran.
     */
    public function getDisplayIconAttribute(): string
    {
        if ($this->ingredient && !empty($this->ingredient->icon) && !in_array($this->ingredient->icon, ['🥗', '🧊'])) {
            return $this->ingredient->icon;
        }

        return static::resolveIconByName($this->item_name, $this->category);
    }

    /**
     * Kamus pencocokan nama bahan lokal Indonesia ke emoji yang representatif dan akurat
     */
    public static function resolveIconByName(?string $name, ?string $category = null): string
    {
        $raw = mb_strtolower(trim((string) $name));

        // Protein Nabati Utama (Tahu, Tempe - dicek lebih dulu sebelum kacang/kedelai)
        if (str_contains($raw, 'tahu')) return '🧈';
        if (str_contains($raw, 'tempe')) return '🥪';

        // Sayuran & Hortikultura Nusantara
        if (str_contains($raw, 'wortel')) return '🥕';
        if (str_contains($raw, 'paprika')) return '🫑';
        if (str_contains($raw, 'timun') || str_contains($raw, 'mentimun')) return '🥒';
        if (str_contains($raw, 'terong')) return '🍆';
        if (str_contains($raw, 'jagung')) return '🌽';
        if (str_contains($raw, 'tomat')) return '🍅';
        if (str_contains($raw, 'kentang')) return '🥔';
        if (str_contains($raw, 'brokoli') || str_contains($raw, 'kembang kol') || str_contains($raw, 'kol kembang')) return '🥦';
        if (str_contains($raw, 'kubis') || str_contains($raw, 'kol') || str_contains($raw, 'selada') || str_contains($raw, 'sawi') || str_contains($raw, 'pakcoy') || str_contains($raw, 'pokchoy') || str_contains($raw, 'bok choy') || str_contains($raw, 'kailan')) return '🥬';
        if (str_contains($raw, 'kangkung') || str_contains($raw, 'bayam') || str_contains($raw, 'seledri') || str_contains($raw, 'daun bawang') || str_contains($raw, 'daun')) return '🌿';
        if (str_contains($raw, 'buncis') || str_contains($raw, 'kacang panjang') || str_contains($raw, 'edamame') || str_contains($raw, 'polong') || str_contains($raw, 'kapri')) return '🫛';
        if (str_contains($raw, 'petai') || str_contains($raw, 'pete') || str_contains($raw, 'jengkol') || str_contains($raw, 'kedelai') || str_contains($raw, 'kacang')) return '🫘';
        if (preg_match('/\bubi\b/u', $raw) || str_contains($raw, 'singkong')) return '🍠';
        if (str_contains($raw, 'jamur')) return '🍄';
        if (str_contains($raw, 'labu')) return '🍐';
        if (str_contains($raw, 'alpukat')) return '🥑';

        // Bumbu Dapur & Rempah Alami
        if (str_contains($raw, 'cabai') || str_contains($raw, 'cabe') || str_contains($raw, 'rawit') || str_contains($raw, 'sambal')) return '🌶️';
        if (str_contains($raw, 'daun bawang')) return '🌿';
        if (str_contains($raw, 'bawang putih')) return '🧄';
        if (str_contains($raw, 'bawang')) return '🧅'; // merah, bombay
        if (str_contains($raw, 'jahe') || str_contains($raw, 'kunyit') || str_contains($raw, 'lengkuas') || str_contains($raw, 'kencur') || str_contains($raw, 'laos') || str_contains($raw, 'temulawak')) return '🫚';
        if (str_contains($raw, 'serai') || str_contains($raw, 'sereh') || str_contains($raw, 'ketumbar') || str_contains($raw, 'kemangi') || str_contains($raw, 'kemiri')) return '🌿';
        if (str_contains($raw, 'merica') || str_contains($raw, 'lada') || str_contains($raw, 'garam')) return '🧂';

        // Protein Hewani & Olahan Daging
        if (str_contains($raw, 'telur')) return '🥚';
        if (str_contains($raw, 'ayam') || str_contains($raw, 'bebek') || str_contains($raw, 'unggas') || str_contains($raw, 'burung')) return '🍗';
        if (str_contains($raw, 'sapi') || str_contains($raw, 'kambing') || str_contains($raw, 'daging') || str_contains($raw, 'iga') || str_contains($raw, 'buntut')) return '🥩';
        if (str_contains($raw, 'ikan') || str_contains($raw, 'tongkol') || str_contains($raw, 'gurame') || str_contains($raw, 'nila') || str_contains($raw, 'lele') || str_contains($raw, 'salmon') || str_contains($raw, 'tuna') || str_contains($raw, 'teri') || str_contains($raw, 'patin') || str_contains($raw, 'bandeng') || str_contains($raw, 'kakap')) return '🐟';
        if (str_contains($raw, 'udang')) return '🦐';
        if (str_contains($raw, 'cumi') || str_contains($raw, 'sotong') || str_contains($raw, 'gurita')) return '🦑';
        if (str_contains($raw, 'kepiting') || str_contains($raw, 'rajungan')) return '🦀';
        if (str_contains($raw, 'kerang')) return '🦪';
        if (str_contains($raw, 'bakso') || str_contains($raw, 'pentol')) return '🧆';
        if (str_contains($raw, 'sosis')) return '🌭';
        if (str_contains($raw, 'nugget') || str_contains($raw, 'kornet')) return '🥫';

        // Protein Nabati (Tahu, Tempe)
        if (str_contains($raw, 'tahu')) return '🧈';
        if (str_contains($raw, 'tempe')) return '🥪';

        // Buah-buahan
        if (str_contains($raw, 'apel')) return '🍎';
        if (str_contains($raw, 'jeruk') || str_contains($raw, 'lemon') || str_contains($raw, 'nipis') || str_contains($raw, 'limau')) return '🍋';
        if (str_contains($raw, 'pisang')) return '🍌';
        if (str_contains($raw, 'semangka')) return '🍉';
        if (str_contains($raw, 'mangga')) return '🥭';
        if (str_contains($raw, 'nanas')) return '🍍';
        if (str_contains($raw, 'stroberi') || str_contains($raw, 'strawberry')) return '🍓';
        if (str_contains($raw, 'anggur')) return '🍇';
        if (str_contains($raw, 'kelapa') || str_contains($raw, 'santan')) return '🥥';

        // Karbohidrat & Olahan
        if (str_contains($raw, 'beras') || str_contains($raw, 'nasi')) return '🍚';
        if (str_contains($raw, 'mie') || str_contains($raw, 'indomie') || str_contains($raw, 'ramen') || str_contains($raw, 'bihun') || str_contains($raw, 'kwetiau') || str_contains($raw, 'pasta') || str_contains($raw, 'spaghetti')) return '🍜';
        if (str_contains($raw, 'roti')) return '🍞';
        if (str_contains($raw, 'tepung') || str_contains($raw, 'tapioka') || str_contains($raw, 'terigu') || str_contains($raw, 'maizena')) return '🌾';
        if (str_contains($raw, 'keju')) return '🧀';
        if (str_contains($raw, 'susu')) return '🥛';
        if (str_contains($raw, 'mentega') || str_contains($raw, 'margarin')) return '🧈';
        if (str_contains($raw, 'minyak') || str_contains($raw, 'kecap') || str_contains($raw, 'saus') || str_contains($raw, 'cuka') || str_contains($raw, 'petis')) return '🍶';
        if (str_contains($raw, 'kerupuk') || str_contains($raw, 'krupuk') || str_contains($raw, 'emping')) return '🍥';

        // Kategori cadangan bila nama tidak cocok keyword
        return match ($category) {
            'Sayuran' => '🥦',
            'Protein Hewani' => '🍗',
            'Protein Nabati' => '🧈',
            'Bumbu Dapur' => '🧂',
            'Karbohidrat' => '🍚',
            'Buah' => '🍎',
            'Susu & Olahan' => '🧀',
            'Saus & Minyak' => '🍶',
            default => '🥕',
        };
    }
}

