<?php

namespace Database\Seeders;

use App\Models\RecipeCache;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $userId = $user ? $user->id : 1;

        $recipes = [
            // ==========================================
            // 1. KULINER MODERN & KEKINIAN MAHASISWA
            // ==========================================
            [
                'title' => 'Ayam Geprek Crispy Sambal Bawang Korek',
                'description' => 'Ayam goreng tepung renyah garing berbalut sambal bawang korek minyak panas yang pedas nampol. Kuliner modern paling populer di kalangan mahasiswa kampus BSI dengan perpaduan gurih pedas menggigit.',
                'cooking_time_minutes' => 20,
                'difficulty' => 'Mudah',
                'servings' => 2,
                'cuisine_type' => 'Modern Kekinian',
                'meal_type' => 'Makan Siang',
                'ingredient_hash' => md5('ayam|cabai rawit|bawang putih@modern@siang@mudah'),
                'used_ingredients' => [
                    ['name' => 'Dada Ayam Filet', 'quantity' => '250', 'unit' => 'gram', 'source' => 'pantry'],
                    ['name' => 'Cabai Rawit Merah', 'quantity' => '15', 'unit' => 'buah', 'source' => 'pantry'],
                    ['name' => 'Bawang Putih', 'quantity' => '4', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Tepung Terigu Segitiga Biru', 'quantity' => '150', 'unit' => 'gram', 'source' => 'pantry'],
                ],
                'missing_ingredients' => [
                    ['name' => 'Minyak Goreng Sawit', 'quantity' => '200', 'unit' => 'ml', 'is_staple' => true],
                    ['name' => 'Garam & Kaldu Ayam', 'quantity' => '1', 'unit' => 'sdt', 'is_staple' => true],
                ],
                'instructions' => [
                    ['step_number' => 1, 'action' => 'Marinasi potongan ayam dengan bawang putih halus, garam, dan merica selama 10 menit.', 'estimated_minutes' => 5],
                    ['step_number' => 2, 'action' => 'Balurkan ayam ke tepung kering, celup ke air es, lalu balur kembali ke tepung sambil dicubit-cubit agar keriting renyah.', 'estimated_minutes' => 4],
                    ['step_number' => 3, 'action' => 'Goreng dalam minyak panas api sedang hingga kuning keemasan garing sempurna, tiriskan.', 'estimated_minutes' => 6],
                    ['step_number' => 4, 'action' => 'Ulek kasar cabai rawit merah, bawang putih, dan garam. Siram dengan 3 sdm minyak jelantah panas bekas menggoreng ayam.', 'estimated_minutes' => 2],
                    ['step_number' => 5, 'action' => 'Letakkan ayam goreng di atas cobek, geprek sampai memar bersatu dengan sambal bawang.', 'estimated_minutes' => 3],
                ],
                'nutrition_facts' => [
                    'calories_kcal' => 450,
                    'protein_g' => 32,
                    'carbs_g' => 28,
                    'fat_g' => 22,
                    'fiber_g' => 3
                ],
                'chef_tips' => 'Siram sambal dengan minyak goreng yang benar-benar panas mendidih agar bawang putih tidak berbau langu dan aroma sambal langsung meledak gurih!',
                'source' => 'gemini_api',
                'is_favorite' => true
            ],
            [
                'title' => 'Seblak Kuah Jeletot Komplit Sosis Telur',
                'description' => 'Jajanan khas Bandung pedas gurih beraroma kencur kuat dengan topping kerupuk aci kenyal, orak-arik telur, sosis sapi, dan sayuran segar. Pilihan utama saat cuaca dingin atau lembur tugas kuliah.',
                'cooking_time_minutes' => 15,
                'difficulty' => 'Mudah',
                'servings' => 2,
                'cuisine_type' => 'Street Food Populer',
                'meal_type' => 'Cemilan',
                'ingredient_hash' => md5('kerupuk|kencur|cabai|sosis|telur@seblak@cemilan'),
                'used_ingredients' => [
                    ['name' => 'Kerupuk Aci Bawang Warna-Warni', 'quantity' => '100', 'unit' => 'gram', 'source' => 'pantry'],
                    ['name' => 'Telur Ayam Ras', 'quantity' => '2', 'unit' => 'butir', 'source' => 'pantry'],
                    ['name' => 'Sosis Sapi / Ayam', 'quantity' => '3', 'unit' => 'buah', 'source' => 'pantry'],
                    ['name' => 'Kencur', 'quantity' => '2', 'unit' => 'ruas', 'source' => 'pantry'],
                    ['name' => 'Cabai Rawit Merah', 'quantity' => '10', 'unit' => 'buah', 'source' => 'pantry'],
                    ['name' => 'Bawang Putih', 'quantity' => '3', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Bawang Merah', 'quantity' => '4', 'unit' => 'siung', 'source' => 'pantry'],
                ],
                'missing_ingredients' => [
                    ['name' => 'Sawi / Kol', 'quantity' => 'Secukupnya', 'unit' => 'lembar', 'is_staple' => true],
                    ['name' => 'Air Bersih', 'quantity' => '400', 'unit' => 'ml', 'is_staple' => true],
                    ['name' => 'Garam & Kaldu Jamur', 'quantity' => '1', 'unit' => 'sdt', 'is_staple' => true],
                ],
                'instructions' => [
                    ['step_number' => 1, 'action' => 'Rendam kerupuk aci dalam air panas selama 10 menit hingga kenyal dan tidak kaku.', 'estimated_minutes' => 4],
                    ['step_number' => 2, 'action' => 'Haluskan kencur, cabai rawit, bawang putih, dan bawang merah.', 'estimated_minutes' => 3],
                    ['step_number' => 3, 'action' => 'Tumis bumbu halus dengan 2 sdm minyak sampai benar-benar harum dan matang.', 'estimated_minutes' => 2],
                    ['step_number' => 4, 'action' => 'Sisihkan bumbu ke pinggir wajan, ceplok telur lalu orak-arik kasar. Masukkan irisan sosis.', 'estimated_minutes' => 2],
                    ['step_number' => 5, 'action' => 'Tuang 400ml air, masukkan kerupuk kenyal, bumbui garam, gula, dan kaldu bubuk. Masak hingga kuah mengental meresap.', 'estimated_minutes' => 4],
                ],
                'nutrition_facts' => [
                    'calories_kcal' => 390,
                    'protein_g' => 18,
                    'carbs_g' => 42,
                    'fat_g' => 16,
                    'fiber_g' => 4
                ],
                'chef_tips' => 'Rendam kerupuk aci terlebih dahulu dalam air biasa lalu air hangat agar teksturnya kenyal elastis dan kuah seblak tidak berubah menjadi bubur.',
                'source' => 'gemini_api',
                'is_favorite' => true
            ],
            [
                'title' => 'Indomie Goreng Nyemek Spesial Keju Kornet Warkop',
                'description' => 'Kreasi mie instan berkuah sedikit (nyemek) kental dengan tumisan bawang, telur gurih, kornet sapi, dan taburan keju cheddar parut legit ala warkop 24 jam tongkrongan anak muda.',
                'cooking_time_minutes' => 10,
                'difficulty' => 'Sangat Mudah',
                'servings' => 1,
                'cuisine_type' => 'Modern Kekinian',
                'meal_type' => 'Makan Malam',
                'ingredient_hash' => md5('indomie|telur|kornet|keju@warkop@malam'),
                'used_ingredients' => [
                    ['name' => 'Mie Instan Indomie Goreng / Kuah', 'quantity' => '1', 'unit' => 'bungkus', 'source' => 'pantry'],
                    ['name' => 'Telur Ayam Ras', 'quantity' => '1', 'unit' => 'butir', 'source' => 'pantry'],
                    ['name' => 'Kornet Sapi Kaleng', 'quantity' => '2', 'unit' => 'sdm', 'source' => 'pantry'],
                    ['name' => 'Keju Cheddar Parut Kraft', 'quantity' => '20', 'unit' => 'gram', 'source' => 'pantry'],
                    ['name' => 'Cabai Rawit Merah', 'quantity' => '3', 'unit' => 'buah', 'source' => 'pantry'],
                    ['name' => 'Bawang Putih', 'quantity' => '1', 'unit' => 'siung', 'source' => 'pantry'],
                ],
                'missing_ingredients' => [
                    ['name' => 'Kecap Manis', 'quantity' => '1', 'unit' => 'sdt', 'is_staple' => true],
                    ['name' => 'Saus Sambal', 'quantity' => '1', 'unit' => 'sdm', 'is_staple' => true],
                ],
                'instructions' => [
                    ['step_number' => 1, 'action' => 'Tumis irisan bawang putih dan cabai rawit dengan 1 sdt margarin hingga harum.', 'estimated_minutes' => 2],
                    ['step_number' => 2, 'action' => 'Masukkan kornet sapi, tumis sebentar hingga warnanya sedikit menggelap dan beraroma gurih.', 'estimated_minutes' => 2],
                    ['step_number' => 3, 'action' => 'Tuang 250ml air, masukkan mie instan dan rebus hingga setengah matang.', 'estimated_minutes' => 2],
                    ['step_number' => 4, 'action' => 'Pecahkan telur ke dalam kuah, aduk perlahan agar kuah mie menjadi kental gurih.', 'estimated_minutes' => 2],
                    ['step_number' => 5, 'action' => 'Masukkan bumbu Indomie, saus sambal, dan kecap manis. Angkat saat kuah menyusut nyemek, beri topping keju parut melimpah.', 'estimated_minutes' => 2],
                ],
                'nutrition_facts' => [
                    'calories_kcal' => 520,
                    'protein_g' => 21,
                    'carbs_g' => 56,
                    'fat_g' => 24,
                    'fiber_g' => 3
                ],
                'chef_tips' => 'Aduk telur bersama kuah saat api kecil agar kuah berubah menjadi tekstur creamy gurih menyerupai kuah ramen Jepang versi lokal!',
                'source' => 'gemini_api',
                'is_favorite' => false
            ],

            // ==========================================
            // 2. KULINER KAKI LIMA & PEDAGANG POPULER
            // ==========================================
            [
                'title' => 'Nasi Goreng Kampung Spesial Telur Sosis Abang Kaki Lima',
                'description' => 'Nasi goreng harum beraroma wok hei dengan bumbu halus bawang merah, bawang putih, terasi bakar, dan kecap manis pekat. Dilengkapi orak-arik telur gurih, irisan sosis, dan renyahnya kerupuk.',
                'cooking_time_minutes' => 15,
                'difficulty' => 'Mudah',
                'servings' => 2,
                'cuisine_type' => 'Street Food Populer',
                'meal_type' => 'Makan Malam',
                'ingredient_hash' => md5('beras|telur|sosis|bawang merah|terasi@nasgor@malam'),
                'used_ingredients' => [
                    ['name' => 'Beras Pandan Wangi', 'quantity' => '2', 'unit' => 'piring nasi dingin', 'source' => 'pantry'],
                    ['name' => 'Telur Ayam Ras', 'quantity' => '2', 'unit' => 'butir', 'source' => 'pantry'],
                    ['name' => 'Sosis Sapi / Ayam', 'quantity' => '2', 'unit' => 'buah', 'source' => 'pantry'],
                    ['name' => 'Bawang Merah', 'quantity' => '5', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Bawang Putih', 'quantity' => '3', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Cabai Rawit Merah', 'quantity' => '4', 'unit' => 'buah', 'source' => 'pantry'],
                    ['name' => 'Terasi Udang Matang', 'quantity' => '1/2', 'unit' => 'sdt', 'source' => 'pantry'],
                    ['name' => 'Kecap Manis Bango', 'quantity' => '2.5', 'unit' => 'sdm', 'source' => 'pantry'],
                ],
                'missing_ingredients' => [
                    ['name' => 'Minyak Wijen', 'quantity' => '1/2', 'unit' => 'sdt', 'is_staple' => true],
                    ['name' => 'Garam & Kaldu Bubuk', 'quantity' => 'Secukupnya', 'unit' => 'sejumput', 'is_staple' => true],
                ],
                'instructions' => [
                    ['step_number' => 1, 'action' => 'Gunakan nasi dingin kemarin (pera). Ulek halus bawang merah, bawang putih, cabai, dan terasi.', 'estimated_minutes' => 4],
                    ['step_number' => 2, 'action' => 'Panaskan 2 sdm minyak di wajan dengan api besar, buat orak-arik telur sampai berbutir harum, sisihkan.', 'estimated_minutes' => 2],
                    ['step_number' => 3, 'action' => 'Tumis bumbu halus di wajan yang sama sampai matang kecokelatan dan harum pekat.', 'estimated_minutes' => 3],
                    ['step_number' => 4, 'action' => 'Masukkan irisan sosis dan nasi dingin, aduk cepat dengan api besar hingga butiran nasi terurai.', 'estimated_minutes' => 3],
                    ['step_number' => 5, 'action' => 'Tuang kecap manis, garam, dan sedikit minyak wijen di pinggir wajan agar terkaramelisasi. Aduk rata hingga berasap harum sedap.', 'estimated_minutes' => 3],
                ],
                'nutrition_facts' => [
                    'calories_kcal' => 480,
                    'protein_g' => 17,
                    'carbs_g' => 64,
                    'fat_g' => 18,
                    'fiber_g' => 3
                ],
                'chef_tips' => 'Tuang kecap manis di dinding wajan panas, bukan langsung ke atas nasi, agar kecap sedikit terkaramelisasi dan aroma wajan abang-abang langsung keluar sempurna!',
                'source' => 'gemini_api',
                'is_favorite' => true
            ],
            [
                'title' => 'Tahu Telur Bumbu Petis Khas Jawa Timur',
                'description' => 'Dadu tahu putih lembut digoreng bersama kocokan telur hingga mengembang krispi, disajikan di atas tauge renyah dan disiram saus petis udang legit gurih beraroma bawang putih dan kacang tanah.',
                'cooking_time_minutes' => 20,
                'difficulty' => 'Mudah',
                'servings' => 2,
                'cuisine_type' => 'Tradisional Nusantara',
                'meal_type' => 'Makan Siang',
                'ingredient_hash' => md5('tahu|telur|petis|tauge@tahutelur@siang'),
                'used_ingredients' => [
                    ['name' => 'Tahu Putih Sutra', 'quantity' => '2', 'unit' => 'kotak', 'source' => 'pantry'],
                    ['name' => 'Telur Ayam Ras', 'quantity' => '2', 'unit' => 'butir', 'source' => 'pantry'],
                    ['name' => 'Tauge / Kecambah Segar', 'quantity' => '50', 'unit' => 'gram', 'source' => 'pantry'],
                    ['name' => 'Petis Udang Hitam Khas Sidoarjo', 'quantity' => '2', 'unit' => 'sdm', 'source' => 'pantry'],
                    ['name' => 'Bawang Putih', 'quantity' => '2', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Cabai Rawit Merah', 'quantity' => '5', 'unit' => 'buah', 'source' => 'pantry'],
                ],
                'missing_ingredients' => [
                    ['name' => 'Kacang Tanah Goreng', 'quantity' => '2', 'unit' => 'sdm', 'is_staple' => true],
                    ['name' => 'Kecap Manis', 'quantity' => '1', 'unit' => 'sdm', 'is_staple' => true],
                    ['name' => 'Air Hangat', 'quantity' => '50', 'unit' => 'ml', 'is_staple' => true],
                ],
                'instructions' => [
                    ['step_number' => 1, 'action' => 'Potong tahu kecil-kecil, campur ke dalam telur kocok yang dibumbui garam dan merica.', 'estimated_minutes' => 3],
                    ['step_number' => 2, 'action' => 'Goreng dalam minyak panas melimpah hingga dadar tahu mengembang renyah keemasan, tiriskan.', 'estimated_minutes' => 5],
                    ['step_number' => 3, 'action' => 'Seduh tauge dengan air panas sebentar saja agar tetap renyah.', 'estimated_minutes' => 2],
                    ['step_number' => 4, 'action' => 'Ulek halus bawang putih, cabai rawit, kacang goreng, petis udang, kecap manis, dan sedikit air hangat hingga saus kental licin.', 'estimated_minutes' => 5],
                    ['step_number' => 5, 'action' => 'Tata tahu telur di piring, beri tauge di atasnya, lalu siram dengan saus petis melimpah.', 'estimated_minutes' => 5],
                ],
                'nutrition_facts' => [
                    'calories_kcal' => 340,
                    'protein_g' => 23,
                    'carbs_g' => 20,
                    'fat_g' => 18,
                    'fiber_g' => 4
                ],
                'chef_tips' => 'Gunakan wajan cekung kecil dan minyak panas melimpah saat menggoreng adonan tahu telur agar bentuknya membulat tebal bersarang di bagian pinggir.',
                'source' => 'gemini_api',
                'is_favorite' => false
            ],
            [
                'title' => 'Orek Tempe Basah Manis Gurih Cabai Hijau',
                'description' => 'Lauk legendaris warteg yang selalu dicari: potongan tempe kedelai yang ditumis dengan bawang merah, bawang putih, lengkuas, cabai hijau, dan karamel kecap manis pekat yang meresap hingga ke dalam serat tempe.',
                'cooking_time_minutes' => 20,
                'difficulty' => 'Sangat Mudah',
                'servings' => 3,
                'cuisine_type' => 'Street Food Populer',
                'meal_type' => 'Lauk Pendamping',
                'ingredient_hash' => md5('tempe|kecap manis|cabai rawit hijau|bawang merah@orektempe'),
                'used_ingredients' => [
                    ['name' => 'Tempe Kedelai Segar', 'quantity' => '1', 'unit' => 'papan', 'source' => 'pantry'],
                    ['name' => 'Bawang Merah', 'quantity' => '5', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Bawang Putih', 'quantity' => '3', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Cabai Rawit Hijau', 'quantity' => '5', 'unit' => 'buah', 'source' => 'pantry'],
                    ['name' => 'Lengkuas / Laos', 'quantity' => '1', 'unit' => 'ruas geprek', 'source' => 'pantry'],
                    ['name' => 'Daun Salam', 'quantity' => '2', 'unit' => 'lembar', 'source' => 'pantry'],
                    ['name' => 'Kecap Manis Bango', 'quantity' => '3', 'unit' => 'sdm', 'source' => 'pantry'],
                ],
                'missing_ingredients' => [
                    ['name' => 'Gula Merah Sisir', 'quantity' => '1', 'unit' => 'sdm', 'is_staple' => true],
                    ['name' => 'Air Bersih', 'quantity' => '50', 'unit' => 'ml', 'is_staple' => true],
                ],
                'instructions' => [
                    ['step_number' => 1, 'action' => 'Potong tempe bentuk korek api tebal. Goreng tempe hingga setengah matang (kulit berkulit tapi bagian dalam tetap basah lembut).', 'estimated_minutes' => 6],
                    ['step_number' => 2, 'action' => 'Iris tipis bawang merah, bawang putih, dan cabai hijau.', 'estimated_minutes' => 3],
                    ['step_number' => 3, 'action' => 'Tumis bumbu iris bersama lengkuas geprek dan daun salam hingga harum matang.', 'estimated_minutes' => 3],
                    ['step_number' => 4, 'action' => 'Masukkan kecap manis, gula merah, garam, dan 50ml air, aduk sampai gula larut dan kuah menggelembung karamel.', 'estimated_minutes' => 3],
                    ['step_number' => 5, 'action' => 'Masukkan tempe setengah matang, aduk cepat dengan api sedang sampai bumbu kecap meresap pekat dan licin mengkilap.', 'estimated_minutes' => 5],
                ],
                'nutrition_facts' => [
                    'calories_kcal' => 230,
                    'protein_g' => 16,
                    'carbs_g' => 24,
                    'fat_g' => 9,
                    'fiber_g' => 5
                ],
                'chef_tips' => 'Untuk orek tempe basah ala warteg, jangan menggoreng tempe sampai kering garing. Cukup goreng 2-3 menit agar tetap empuk dan menyerap bumbu kecap secara maksimal.',
                'source' => 'gemini_api',
                'is_favorite' => false
            ],

            // ==========================================
            // 3. TRADISIONAL LEGENDARIS NUSANTARA
            // ==========================================
            [
                'title' => 'Rendang Daging Sapi Karamel Khas Minang',
                'description' => 'Mahakarya kuliner Indonesia nomor satu dunia: potongan daging sapi empuk dimasak perlahan bersama rempah daun kunyit, serai, daun jeruk, dan santan kelapa pekat hingga berminyak dan berwarna cokelat kehitaman.',
                'cooking_time_minutes' => 60,
                'difficulty' => 'Mahir',
                'servings' => 4,
                'cuisine_type' => 'Tradisional Nusantara',
                'meal_type' => 'Makan Siang',
                'ingredient_hash' => md5('daging sapi|santan|daun kunyit|cabai merah keriting@rendang'),
                'used_ingredients' => [
                    ['name' => 'Daging Sapi Has / Sengkel', 'quantity' => '500', 'unit' => 'gram', 'source' => 'pantry'],
                    ['name' => 'Santan Kelapa Instan Kara', 'quantity' => '3', 'unit' => 'bungkus', 'source' => 'pantry'],
                    ['name' => 'Cabai Merah Keriting', 'quantity' => '15', 'unit' => 'buah', 'source' => 'pantry'],
                    ['name' => 'Bawang Merah', 'quantity' => '10', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Bawang Putih', 'quantity' => '6', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Lengkuas / Laos', 'quantity' => '3', 'unit' => 'ruas', 'source' => 'pantry'],
                    ['name' => 'Jahe Emprit / Gajah', 'quantity' => '2', 'unit' => 'ruas', 'source' => 'pantry'],
                    ['name' => 'Daun Kunyit', 'quantity' => '1', 'unit' => 'lembar simpul', 'source' => 'pantry'],
                    ['name' => 'Daun Jeruk Purut', 'quantity' => '4', 'unit' => 'lembar sobek', 'source' => 'pantry'],
                    ['name' => 'Serai', 'quantity' => '2', 'unit' => 'batang memarkan', 'source' => 'pantry'],
                ],
                'missing_ingredients' => [
                    ['name' => 'Ketumbar & Jintan Bubuk', 'quantity' => '1', 'unit' => 'sdt', 'is_staple' => true],
                    ['name' => 'Garam & Kaldu Sapi', 'quantity' => '1.5', 'unit' => 'sdt', 'is_staple' => true],
                ],
                'instructions' => [
                    ['step_number' => 1, 'action' => 'Potong daging sapi searah serat menjadi ukuran balok rendang.', 'estimated_minutes' => 10],
                    ['step_number' => 2, 'action' => 'Haluskan cabai merah keriting, bawang merah, bawang putih, jahe, lengkuas, dan ketumbar.', 'estimated_minutes' => 8],
                    ['step_number' => 3, 'action' => 'Masak santan encer bersama bumbu halus, serai, daun jeruk, daun salam, dan daun kunyit sambil terus ditimang agar santan tidak pecah.', 'estimated_minutes' => 15],
                    ['step_number' => 4, 'action' => 'Setelah kuah mendidih dan mulai mengeluarkan minyak (gulai), masukkan potongan daging sapi.', 'estimated_minutes' => 5],
                    ['step_number' => 5, 'action' => 'Kecilkan api, masak terus hingga kuah menyusut menjadi kalio cokelat kemerahan.', 'estimated_minutes' => 12],
                    ['step_number' => 6, 'action' => 'Aduk perlahan terus menerus dengan api lilin hingga minyak terpisah dan dedak rendang berubah menjadi cokelat gelap pekat beraroma khas.', 'estimated_minutes' => 10],
                ],
                'nutrition_facts' => [
                    'calories_kcal' => 580,
                    'protein_g' => 42,
                    'carbs_g' => 14,
                    'fat_g' => 38,
                    'fiber_g' => 4
                ],
                'chef_tips' => 'Aduk dengan spatula kayu secara perlahan dari bawah ke atas agar serat daging tidak hancur namun bumbu rendang tidak mengerak gosong di dasar wajan.',
                'source' => 'gemini_api',
                'is_favorite' => true
            ],
            [
                'title' => 'Rawon Daging Sapi Kuah Hitam Khas Jawa Timur',
                'description' => 'Sup daging sapi legendaris Jawa Timur dengan kuah hitam pekat eksotis dari fermentasi biji kluwek tua. Disajikan panas bersama tauge pendek segar, sambal terasi terpisah, dan telur asin.',
                'cooking_time_minutes' => 45,
                'difficulty' => 'Sedang',
                'servings' => 3,
                'cuisine_type' => 'Tradisional Nusantara',
                'meal_type' => 'Makan Siang',
                'ingredient_hash' => md5('daging sapi|kluwek|tauge|bawang merah@rawon'),
                'used_ingredients' => [
                    ['name' => 'Daging Sapi Has / Sengkel', 'quantity' => '350', 'unit' => 'gram', 'source' => 'pantry'],
                    ['name' => 'Kluwek', 'quantity' => '4', 'unit' => 'buah ambil isinya', 'source' => 'pantry'],
                    ['name' => 'Bawang Merah', 'quantity' => '8', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Bawang Putih', 'quantity' => '5', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Kunyit Segar / Bubuk', 'quantity' => '1', 'unit' => 'ruas', 'source' => 'pantry'],
                    ['name' => 'Jahe Emprit / Gajah', 'quantity' => '1', 'unit' => 'ruas', 'source' => 'pantry'],
                    ['name' => 'Serai', 'quantity' => '2', 'unit' => 'batang memarkan', 'source' => 'pantry'],
                    ['name' => 'Daun Jeruk Purut', 'quantity' => '4', 'unit' => 'lembar', 'source' => 'pantry'],
                    ['name' => 'Tauge / Kecambah Segar', 'quantity' => '50', 'unit' => 'gram tauge pendek', 'source' => 'pantry'],
                ],
                'missing_ingredients' => [
                    ['name' => 'Daun Bawang Iris', 'quantity' => '1', 'unit' => 'batang', 'is_staple' => true],
                    ['name' => 'Garam & Gula Pasir', 'quantity' => 'Secukupnya', 'unit' => 'sdt', 'is_staple' => true],
                ],
                'instructions' => [
                    ['step_number' => 1, 'action' => 'Rebus daging sapi sengkel dengan 1.5 liter air hingga empuk, potong dadu 2 cm dan saring air kaldunya.', 'estimated_minutes' => 15],
                    ['step_number' => 2, 'action' => 'Rendam isi kluwek dengan air panas, haluskan bersama bawang merah, bawang putih, kunyit, jahe, dan ketumbar.', 'estimated_minutes' => 8],
                    ['step_number' => 3, 'action' => 'Tumis bumbu kluwek hitam bersama serai dan daun jeruk sampai benar-benar matang dan berminyak.', 'estimated_minutes' => 7],
                    ['step_number' => 4, 'action' => 'Masukkan bumbu tumis ke dalam panci kaldu daging mendidih, masukkan potongan daging sapi.', 'estimated_minutes' => 5],
                    ['step_number' => 5, 'action' => 'Bumbui garam, gula, dan merica. Masak dengan api kecil selama 10 menit agar bumbu kluwek meresap ke dalam daging.', 'estimated_minutes' => 10],
                ],
                'nutrition_facts' => [
                    'calories_kcal' => 380,
                    'protein_g' => 36,
                    'carbs_g' => 12,
                    'fat_g' => 20,
                    'fiber_g' => 3
                ],
                'chef_tips' => 'Cicipi sedikit isi kluwek sebelum dihaluskan: jika terasa pahit, buang dan ganti yang lain. Kluwek yang bagus berbau harum gurih dan berwarna hitam legam.',
                'source' => 'gemini_api',
                'is_favorite' => false
            ],
            [
                'title' => 'Sayur Asem Segar Khas Betawi / Jakarta',
                'description' => 'Sayur berkuah bening kemerahan dengan perpaduan rasa asam segar dari asam jawa muda, manis dari jagung, dan gurih kacang tanah. Menu sehat penyeimbang lauk gorengan tempe dan ikan asin.',
                'cooking_time_minutes' => 25,
                'difficulty' => 'Mudah',
                'servings' => 3,
                'cuisine_type' => 'Tradisional Nusantara',
                'meal_type' => 'Makan Siang',
                'ingredient_hash' => md5('jagung|labu siam|kacang panjang|asam jawa@sayurasem'),
                'used_ingredients' => [
                    ['name' => 'Jagung Manis', 'quantity' => '1', 'unit' => 'tongkol potong bulat', 'source' => 'pantry'],
                    ['name' => 'Labu Siam', 'quantity' => '1', 'unit' => 'buah potong dadu', 'source' => 'pantry'],
                    ['name' => 'Kacang Panjang', 'quantity' => '5', 'unit' => 'lonjor potong 3 cm', 'source' => 'pantry'],
                    ['name' => 'Asam Jawa', 'quantity' => '2', 'unit' => 'mata asam muda', 'source' => 'pantry'],
                    ['name' => 'Bawang Merah', 'quantity' => '5', 'unit' => 'siung iris tipis', 'source' => 'pantry'],
                    ['name' => 'Bawang Putih', 'quantity' => '2', 'unit' => 'siung iris tipis', 'source' => 'pantry'],
                    ['name' => 'Cabai Merah Besar', 'quantity' => '2', 'unit' => 'buah iris serong', 'source' => 'pantry'],
                    ['name' => 'Lengkuas / Laos', 'quantity' => '1', 'unit' => 'ruas geprek', 'source' => 'pantry'],
                    ['name' => 'Daun Salam', 'quantity' => '2', 'unit' => 'lembar', 'source' => 'pantry'],
                ],
                'missing_ingredients' => [
                    ['name' => 'Kacang Tanah', 'quantity' => '2', 'unit' => 'sdm', 'is_staple' => true],
                    ['name' => 'Gula Pasir & Garam', 'quantity' => '1.5', 'unit' => 'sdm', 'is_staple' => true],
                ],
                'instructions' => [
                    ['step_number' => 1, 'action' => 'Didihkan 1 liter air di panci, masukkan potongan jagung manis dan kacang tanah terlebih dahulu sampai empuk.', 'estimated_minutes' => 8],
                    ['step_number' => 2, 'action' => 'Masukkan bumbu iris (bawang merah, bawang putih, cabai), lengkuas, daun salam, dan air perasan asam jawa.', 'estimated_minutes' => 5],
                    ['step_number' => 3, 'action' => 'Masukkan potongan labu siam, masak hingga labu siam mulai empuk.', 'estimated_minutes' => 5],
                    ['step_number' => 4, 'action' => 'Masukkan potongan kacang panjang di menit terakhir.', 'estimated_minutes' => 3],
                    ['step_number' => 5, 'action' => 'Bumbui gula pasir dan garam, koreksi rasa asam manis segarnya, matikan api segera.', 'estimated_minutes' => 4],
                ],
                'nutrition_facts' => [
                    'calories_kcal' => 160,
                    'protein_g' => 5,
                    'carbs_g' => 32,
                    'fat_g' => 2,
                    'fiber_g' => 6
                ],
                'chef_tips' => 'Gunakan asam jawa segar yang dimemarkan di panci mendidih untuk mendapatkan rasa asam buah yang alami tanpa rasa kecut bahan sintetis.',
                'source' => 'gemini_api',
                'is_favorite' => false
            ],
            [
                'title' => 'Soto Ayam Kuning Bening Lamongan Koya Gurih',
                'description' => 'Soto ayam berkuah kuning bening gurih rempah serai dan kunyit bakar, disajikan dengan suwiran ayam empuk, tauge segar, bihun jagung, taburan koya kerupuk bawang gurih, dan kucuran jeruk nipis.',
                'cooking_time_minutes' => 35,
                'difficulty' => 'Sedang',
                'servings' => 3,
                'cuisine_type' => 'Tradisional Nusantara',
                'meal_type' => 'Makan Siang',
                'ingredient_hash' => md5('paha ayam|kunyit|serai|tauge|bihun@sotoayam'),
                'used_ingredients' => [
                    ['name' => 'Paha Ayam Utuh / Bawah', 'quantity' => '350', 'unit' => 'gram', 'source' => 'pantry'],
                    ['name' => 'Bihun Jagung', 'quantity' => '1', 'unit' => 'keping seduh air hangat', 'source' => 'pantry'],
                    ['name' => 'Tauge / Kecambah Segar', 'quantity' => '50', 'unit' => 'gram', 'source' => 'pantry'],
                    ['name' => 'Kol / Kubis Segar', 'quantity' => '50', 'unit' => 'gram iris halus', 'source' => 'pantry'],
                    ['name' => 'Bawang Putih', 'quantity' => '5', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Bawang Merah', 'quantity' => '6', 'unit' => 'siung', 'source' => 'pantry'],
                    ['name' => 'Kunyit Segar / Bubuk', 'quantity' => '2', 'unit' => 'ruas bakar', 'source' => 'pantry'],
                    ['name' => 'Kemiri', 'quantity' => '3', 'unit' => 'butir sangrai', 'source' => 'pantry'],
                    ['name' => 'Serai', 'quantity' => '2', 'unit' => 'batang memarkan', 'source' => 'pantry'],
                    ['name' => 'Daun Jeruk Purut', 'quantity' => '3', 'unit' => 'lembar', 'source' => 'pantry'],
                ],
                'missing_ingredients' => [
                    ['name' => 'Kerupuk Udang & Bawang Putih Goreng (Bahan Koya)', 'quantity' => '3', 'unit' => 'buah dihaluskan', 'is_staple' => true],
                    ['name' => 'Jeruk Nipis', 'quantity' => '1', 'unit' => 'buah belah 4', 'is_staple' => true],
                ],
                'instructions' => [
                    ['step_number' => 1, 'action' => 'Rebus ayam dalam 1.2 liter air dengan api sedang untuk menghasilkan kaldu ayam yang jernih dan manis.', 'estimated_minutes' => 12],
                    ['step_number' => 2, 'action' => 'Haluskan bawang merah, bawang putih, kunyit bakar, kemiri sangrai, dan jahe. Tumis dengan serai dan daun jeruk sampai harum semerbak.', 'estimated_minutes' => 6],
                    ['step_number' => 3, 'action' => 'Tuang tumisan bumbu ke dalam panci kuah kaldu ayam, bumbui garam, merica, dan kaldu bubuk.', 'estimated_minutes' => 5],
                    ['step_number' => 4, 'action' => 'Angkat ayam dari kuah, goreng sebentar dalam minyak panas hingga berkulit, lalu suwir-suwir dagingnya.', 'estimated_minutes' => 6],
                    ['step_number' => 5, 'action' => 'Tata bihun, kol, tauge, dan ayam suwir di mangkuk. Siram kuah soto kuning mendidih, beri taburan bubuk koya dan perasan jeruk nipis.', 'estimated_minutes' => 6],
                ],
                'nutrition_facts' => [
                    'calories_kcal' => 320,
                    'protein_g' => 28,
                    'carbs_g' => 30,
                    'fat_g' => 10,
                    'fiber_g' => 3
                ],
                'chef_tips' => 'Bakar kunyit terlebih dahulu di atas api kompor sebelum dihaluskan agar warna kuah soto kuning cerah menggoda dan tidak terasa pahit getah kunyit.',
                'source' => 'gemini_api',
                'is_favorite' => false
            ]
        ];

        $katalog = $this->katalogNusantara();
        $resepUnggulan = $recipes;

        // Foto dari recipes:fetch-images disimpan berdasarkan judul, jadi
        // seed ulang tidak menghapus hasil yang sudah cocok.
        $fotoLama = RecipeCache::whereNotNull('image_url')->pluck('image_url', 'title')->all();

        // Hapus resep lama jika ada, lalu isi resep otentik
        RecipeCache::query()->delete();

        $terlewat = 0;
        foreach (array_merge($resepUnggulan, $katalog) as $r) {
            if (empty($r['title']) || empty($r['used_ingredients'])) {
                $terlewat++;
                continue;
            }

            $data = $this->lengkapi($r);
            $data['image_url'] = $data['image_url'] ?? ($fotoLama[$r['title']] ?? null);

            RecipeCache::create(array_merge($data, [
                'user_id' => $userId,
                'slug' => Str::slug($r['title']) . '-' . Str::random(5),
            ]));
        }

        if ($this->command) {
            $this->command->info('Katalog Nusantara: ' . count($katalog) . ' resep. Unggulan: ' . count($resepUnggulan) . ' resep. Dilewati: ' . $terlewat . '.');
        }
    }

    /**
     * Resep warisan leluhur per daerah, disimpan di folder data supaya mudah
     * bertambah tanpa membuat berkas seeder ini jadi ribuan baris.
     */
    private function katalogNusantara(): array
    {
        $katalog = [];

        foreach (glob(database_path('seeders/data/*.php')) ?: [] as $berkas) {
            $isi = require $berkas;
            if (is_array($isi)) {
                $katalog = array_merge($katalog, $isi);
            }
        }

        // Dua penulis bisa membuat judul yang sama, jadi pakai judul sebagai kunci.
        $unik = [];
        foreach ($katalog as $r) {
            if (empty($r['title'])) {
                continue;
            }
            $kunik = Str::lower(trim($r['title']));
            if (! isset($unik[$kunik])) {
                $unik[$kunik] = $r;
            }
        }

        return array_values($unik);
    }

    /**
     * Isi kolom yang wajib ada supaya resep katalog tidak bikin error di halaman
     * detail maupun di pencarian cache generator AI.
     */
    private function lengkapi(array $r): array
    {
        $bahan = collect($r['used_ingredients'] ?? [])
            ->map(fn($b) => Str::lower(trim($b['name'] ?? '')))
            ->filter()
            ->sort()
            ->values()
            ->implode('|');

        return array_merge([
            'cuisine_type' => 'Tradisional Nusantara',
            'meal_type' => 'Makan Siang',
            'region' => null,
            'tags' => [],
            'image_url' => null,
            'missing_ingredients' => [],
            'nutrition_facts' => [],
            'chef_tips' => null,
            'is_favorite' => false,
        ], $r, [
            'ingredient_hash' => $r['ingredient_hash'] ?? md5($bahan . '@' . Str::lower($r['cuisine_type'] ?? '') . '@katalog'),
            'source' => $r['source'] ?? 'katalog_nusantara',
            'is_demo_fallback' => true,
        ]);
    }
}
