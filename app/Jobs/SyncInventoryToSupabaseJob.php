<?php

namespace App\Jobs;

use App\Models\KitchenInventory;
use App\Services\SupabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Sinkronisasi satu bahan ke Supabase Cloud dikerjakan di luar request
 * supaya halaman inventaris tetap cepat walau koneksi cloud sedang lambat.
 */
class SyncInventoryToSupabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(public KitchenInventory $item)
    {
        $this->onQueue('supabase');
    }

    public function handle(SupabaseService $supabase): void
    {
        $hasil = $supabase->syncInventoryItem($this->item);

        if (! ($hasil['synced'] ?? false)) {
            // Supabase sedang mati bukan alasan membuat antrean gagal total:
            // data sudah aman di MySQL, cukup dicatat supaya terlihat di log.
            logger()->info('Sinkronisasi Supabase ditunda: '.$hasil['message']);

            return;
        }

        logger()->info('Bahan #'.$this->item->id.' sinkron ke Supabase '.$hasil['supabase_id']);
    }

    public function failed(Throwable $exception): void
    {
        logger()->warning('Sinkronisasi Supabase untuk bahan #'.$this->item->id.' gagal: '.$exception->getMessage());
    }
}
