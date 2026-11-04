<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\KitchenInventory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class KitchenInventorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) return;

        $today = Carbon::today();

        $items = [
            // AMAN (Fresh)
            [
                'name' => 'Bawang Merah',
                'category' => 'Bumbu Dapur',
                'qty' => 15,
                'unit' => 'siung',
                'location' => 'Lemari Kering (Pantry)',
                'purchased' => $today->copy()->subDays(3),
                'expires' => $today->copy()->addDays(17),
                'notes' => 'Disimpan dalam keranjang jaring bersirkulasi udara'
            ],
            [
                'name' => 'Bawang Putih',
                'category' => 'Bumbu Dapur',
                'qty' => 12,
                'unit' => 'siung',
                'location' => 'Lemari Kering (Pantry)',
                'purchased' => $today->copy()->subDays(3),
                'expires' => $today->copy()->addDays(20),
                'notes' => 'Kondisi kering dan padat'
            ],
            [
                'name' => 'Telur Ayam Ras',
                'category' => 'Protein Hewani',
                'qty' => 8,
                'unit' => 'butir',
                'location' => 'Kulkas (Chiller)',
                'purchased' => $today->copy()->subDays(2),
                'expires' => $today->copy()->addDays(14),
                'notes' => 'Beli di minimarket dekat kampus BSI'
            ],
            [
                'name' => 'Beras Pandan Wangi',
                'category' => 'Karbohidrat',
                'qty' => 3.5,
                'unit' => 'kg',
                'location' => 'Lemari Kering (Pantry)',
                'purchased' => $today->copy()->subDays(10),
                'expires' => $today->copy()->addDays(90),
                'notes' => 'Dalam wadah kedap udara anti kutu'
            ],
            [
                'name' => 'Wortel Segar',
                'category' => 'Sayuran',
                'qty' => 3,
                'unit' => 'buah',
                'location' => 'Kulkas (Chiller)',
                'purchased' => $today->copy()->subDays(1),
                'expires' => $today->copy()->addDays(8),
                'notes' => 'Kulit bersih, siap masak'
            ],
            [
                'name' => 'Kecap Manis Bango',
                'category' => 'Saus & Minyak',
                'qty' => 1,
                'unit' => 'botol',
                'location' => 'Lemari Kering (Pantry)',
                'purchased' => $today->copy()->subDays(15),
                'expires' => $today->copy()->addDays(180),
                'notes' => 'Saus bumbu pelengkap'
            ],

            // PERLU SEGERA DIMASAK (Expiring Soon: 1-3 hari lagi)
            [
                'name' => 'Kangkung',
                'category' => 'Sayuran',
                'qty' => 2,
                'unit' => 'ikat',
                'location' => 'Kulkas (Chiller)',
                'purchased' => $today->copy()->subDays(2),
                'expires' => $today->copy()->addDays(1), // 1 hari lagi!
                'notes' => 'Ujung daun mulai sedikit layu, segera tumis hari ini!'
            ],
            [
                'name' => 'Tahu Putih Sutra',
                'category' => 'Protein Nabati',
                'qty' => 1,
                'unit' => 'kotak',
                'location' => 'Kulkas (Chiller)',
                'purchased' => $today->copy()->subDays(2),
                'expires' => $today->copy()->addDays(1), // 1 hari lagi!
                'notes' => 'Air rendaman sudah diganti pagi tadi'
            ],
            [
                'name' => 'Dada Ayam Filet',
                'category' => 'Protein Hewani',
                'qty' => 350,
                'unit' => 'gram',
                'location' => 'Kulkas (Chiller)',
                'purchased' => $today->copy()->subDays(2),
                'expires' => $today->copy()->addDays(2), // 2 hari lagi!
                'notes' => 'Disimpan dalam wadah tertutup di chiller atas'
            ],
            [
                'name' => 'Santan Kelapa Cair',
                'category' => 'Saus & Minyak',
                'qty' => 1,
                'unit' => 'kotak kecil',
                'location' => 'Kulkas (Chiller)',
                'purchased' => $today->copy()->subDays(4),
                'expires' => $today->copy()->addDays(2), // 2 hari lagi!
                'notes' => 'Kemasan sudah dibuka sedikit'
            ],

            // KADALUWARSA (Expired: -1 hari untuk pengujian visual RPL)
            [
                'name' => 'Tomat Merah',
                'category' => 'Sayuran',
                'qty' => 2,
                'unit' => 'buah',
                'location' => 'Kulkas (Chiller)',
                'purchased' => $today->copy()->subDays(8),
                'expires' => $today->copy()->subDays(1), // Kadaluwarsa 1 hari lalu
                'notes' => 'Tekstur mulai lembek, periksa bagian luar sebelum diolah'
            ],
        ];

        foreach ($items as $item) {
            $master = Ingredient::where('name', $item['name'])->first();

            KitchenInventory::create([
                'user_id' => $user->id,
                'ingredient_id' => $master?->id,
                'item_name' => $item['name'],
                'category' => $item['category'],
                'quantity' => $item['qty'],
                'unit' => $item['unit'],
                'storage_location' => $item['location'],
                'purchase_date' => $item['purchased']->format('Y-m-d'),
                'expiration_date' => $item['expires']->format('Y-m-d'),
                'status' => 'safe',
                'notes' => $item['notes'],
                'supabase_id' => 'sb_demo_' . uniqid(),
                'supabase_synced_at' => now(),
            ]);
        }
    }
}

