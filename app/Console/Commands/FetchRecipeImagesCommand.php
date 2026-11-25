<?php

namespace App\Console\Commands;

use App\Models\RecipeCache;
use App\Services\HidanganFotoService;
use Illuminate\Console\Command;

class FetchRecipeImagesCommand extends Command
{
    protected $signature = 'recipes:fetch-images
                            {--limit=20 : Jumlah resep yang dicari fotonya pada satu jalan}
                            {--all : Abaikan limit dan proses semua resep yang belum punya foto}
                            {--force : Cari ulang juga untuk resep yang sudah punya foto}';

    protected $description = 'Ambil foto hidangan open-source dari Wikimedia Commons untuk katalog Kuliner Nusantara';

    public function handle(HidanganFotoService $fotos): int
    {
        $query = RecipeCache::query();

        if (! $this->option('force')) {
            $query->where(fn($q) => $q->whereNull('image_url')->orWhere('image_url', ''));
        }

        if (! $this->option('all')) {
            $query->limit((int) $this->option('limit'));
        }

        $recipes = $query->get();

        if ($recipes->isEmpty()) {
            $this->info('Semua resep sudah punya foto. Tidak ada yang perlu dicari.');

            return self::SUCCESS;
        }

        $this->line('Mencari foto untuk <b>' . $recipes->count() . '</b> resep di Wikimedia Commons...');
        $this->output->progressStart($recipes->count());

        $dapat = 0;
        $kosong = [];

        foreach ($recipes as $recipe) {
            if ($fotos->pasang($recipe, 12)) {
                $dapat++;
            } else {
                $kosong[] = $recipe->title;
            }

            $this->output->progressAdvance();
            usleep(200000);
        }

        $this->output->progressFinish();
        $this->newLine();
        $this->info("Foto ketemu: {$dapat} resep. Belum ketemu: " . count($kosong) . ' resep (pakai placeholder batik).');

        if ($kosong !== []) {
            $this->line('<comment>Belum ada foto yang bisa dipercaya namanya:</comment> ' . implode(', ', $kosong));
        }

        return self::SUCCESS;
    }
}
