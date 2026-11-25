<?php

namespace App\Console\Commands;

use App\Models\RecipeCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FetchRecipeImagesCommand extends Command
{
    protected $signature = 'recipes:fetch-images
                            {--limit=20 : Jumlah resep yang dicari fotonya pada satu jalan}
                            {--all : Abaikan limit dan proses semua resep yang belum punya foto}
                            {--force : Cari ulang juga untuk resep yang sudah punya foto}';

    protected $description = 'Ambil foto hidangan open-source dari Wikimedia Commons untuk katalog Kuliner Nusantara';

    private const API = 'https://commons.wikimedia.org/w/api.php';

    public function handle(): int
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
        $kosong = 0;

        foreach ($recipes as $recipe) {
            $url = $this->cariFoto($recipe);

            if ($url) {
                $recipe->image_url = $url;
                $recipe->save();
                $dapat++;
            } else {
                $kosong++;
            }

            $this->output->progressAdvance();
            usleep(300000);
        }

        $this->output->progressFinish();
        $this->newLine();
        $this->info("Foto ketemu: {$dapat} resep. Belum ketemu: {$kosong} resep (pakai placeholder batik).");

        return self::SUCCESS;
    }

    /**
     * Foto diambil dari namespace 6 (File) di Commons, jadi hak pakainya bebas
     * dan tidak perlu unduh gambar ke server.
     */
    private function cariFoto(RecipeCache $recipe): ?string
    {
        $kata = $this->kataKunci($recipe);
        $utama = Str::of($kata[0] ?? '')->explode(' ')->first() ?? '';
        $cadangan = null;

        foreach ($kata as $cari) {
            foreach ($this->hasilCommons($cari) as $halaman) {
                $gambar = data_get($halaman, 'imageinfo.0', []);

                if (data_get($gambar, 'mime') !== 'image/jpeg') {
                    continue;
                }

                $url = data_get($gambar, 'thumburl') ?: data_get($gambar, 'url');
                $url = $url ? Str::before($url, '?') : null;
                $judul = Str::lower(str_replace('_', ' ', (string) data_get($halaman, 'title', '')));

                if (! $url || ! $this->layak($url, $judul)) {
                    continue;
                }

                // Nama berkas fotnya memuat seluruh frasa: paling meyakinkan.
                if ($judul !== '' && Str::contains($judul, $cari)) {
                    return $url;
                }

                // Kalaupun tidak, minimal kata utama hidangannya harus muncul,
                // supaya foto Nasi Lemak tidak dipakai untuk ilustrasi Orek Tempe.
                if ($utama && Str::contains($judul, $utama)) {
                    $cadangan ??= $url;
                }
            }
        }

        return $cadangan;
    }

    private function hasilCommons(string $kata): array
    {
        return Http::withHeaders(['User-Agent' => 'Resepify/1.0 (https://resepify.test; kontak developer@resepify.test)'])
            ->timeout(15)
            ->retry(2, 800, throw: false)
            ->get(self::API, [
                'action' => 'query',
                'format' => 'json',
                'generator' => 'search',
                'gsrsearch' => $kata,
                'gsrnamespace' => 6,
                'gsrlimit' => 8,
                'prop' => 'imageinfo',
                'iiprop' => 'url|mime',
                'iiurlwidth' => 800,
            ])
            ->json('query.pages', []);
    }

    private function kataKunci(RecipeCache $recipe): array
    {
        $kotor = Str::of($recipe->title)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s-]/', ' ')
            ->squish()
            ->explode(' ')
            ->reject(fn($kata) => in_array($kata, [
                'khas', 'ala', 'dan', 'dengan', 'isi', 'punya', 'campur', 'potong', 'gram',
                'buah', 'bumbu', 'sajian', 'versi', 'style', 'super', 'mantap', 'spesial',
            ], true))
            ->values();

        $kata = [
            $kotor->take(3)->implode(' '),
            $kotor->take(2)->implode(' '),
            $kotor->first(),
        ];

        if ($recipe->region) {
            $kata[] = $kotor->take(2)->implode(' ') . ' ' . Str::of($recipe->region)->lower();
        }

        return array_values(array_unique(array_filter($kata)));
    }

    /**
     * Foto svg/png kecil dan file hasil render biasanya bukan foto masakan,
     * jadi disaring lebih dulu supaya kartu resep tidak aneh.
     */
    /**
     * Banyak hasil Commons cuma foto bumbu, logo, atau peta. Kalau judul
     * berkasnya begitu, foto dishes-nya dicari dari kata kunci berikutnya.
     */
    private function layak(string $url, string $judulBerkas): bool
    {
        $acak = Str::lower($url . ' ' . $judulBerkas);

        foreach (['bumbu', 'resep', 'logo', 'ikon', 'icon', 'peta', 'map', 'diagram', 'chart', 'bendera', 'ilustrasi', 'cara membuat'] as $kotor) {
            if (Str::contains($acak, $kotor)) {
                return false;
            }
        }

        return true;
    }
}
