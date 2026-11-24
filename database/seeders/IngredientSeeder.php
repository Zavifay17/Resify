<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            // ==========================================
            // 1. BUMBU DASAR, REMPAH & DAUN AROMATIK NUSANTARA
            // ==========================================
            ['name' => 'Bawang Merah', 'category' => 'Bumbu Dapur', 'default_unit' => 'siung', 'typical_shelf_life_days' => 20, 'calories_per_100g' => 40, 'icon' => '🧅', 'description' => 'Bumbu dasar paling utama masakan Nusantara'],
            ['name' => 'Bawang Putih', 'category' => 'Bumbu Dapur', 'default_unit' => 'siung', 'typical_shelf_life_days' => 25, 'calories_per_100g' => 149, 'icon' => '🧄', 'description' => 'Aroma gurih esensial penyedap alami'],
            ['name' => 'Bawang Bombay', 'category' => 'Bumbu Dapur', 'default_unit' => 'buah', 'typical_shelf_life_days' => 21, 'calories_per_100g' => 40, 'icon' => '🧅', 'description' => 'Manis dan renyah untuk tumisan dan saus kekinian'],
            ['name' => 'Cabai Rawit Merah', 'category' => 'Bumbu Dapur', 'default_unit' => 'buah', 'typical_shelf_life_days' => 10, 'calories_per_100g' => 40, 'icon' => '🌶️', 'description' => 'Sensasi pedas membakar khas ayam geprek dan sambal korek'],
            ['name' => 'Cabai Rawit Hijau', 'category' => 'Bumbu Dapur', 'default_unit' => 'buah', 'typical_shelf_life_days' => 12, 'calories_per_100g' => 38, 'icon' => '🌶️', 'description' => 'Aroma segar untuk sambal hijau dan tempe mendoan'],
            ['name' => 'Cabai Merah Keriting', 'category' => 'Bumbu Dapur', 'default_unit' => 'buah', 'typical_shelf_life_days' => 10, 'calories_per_100g' => 38, 'icon' => '🌶️', 'description' => 'Memberi warna merah menggoda dan pedas sedap'],
            ['name' => 'Cabai Merah Besar', 'category' => 'Bumbu Dapur', 'default_unit' => 'buah', 'typical_shelf_life_days' => 10, 'calories_per_100g' => 32, 'icon' => '🌶️', 'description' => 'Cocok untuk irisan tumisan dan rendang'],
            ['name' => 'Kemiri', 'category' => 'Bumbu Dapur', 'default_unit' => 'butir', 'typical_shelf_life_days' => 60, 'calories_per_100g' => 686, 'icon' => '🌰', 'description' => 'Pengental alami dan pemberi cita rasa gurih legit'],
            ['name' => 'Ketumbar Butir / Bubuk', 'category' => 'Bumbu Dapur', 'default_unit' => 'sdt', 'typical_shelf_life_days' => 90, 'calories_per_100g' => 297, 'icon' => '🌿', 'description' => 'Aroma rempah khas gorengan tempe dan ungkep ayam'],
            ['name' => 'Merica / Lada Putih', 'category' => 'Bumbu Dapur', 'default_unit' => 'sdt', 'typical_shelf_life_days' => 120, 'calories_per_100g' => 251, 'icon' => '⚪', 'description' => 'Kehangatan pedas lada untuk sup, soto, dan bakso'],
            ['name' => 'Kencur', 'category' => 'Bumbu Dapur', 'default_unit' => 'ruas', 'typical_shelf_life_days' => 25, 'calories_per_100g' => 85, 'icon' => '🫚', 'description' => 'Kunci utama rasa seblak jeletot, urap, dan pecel'],
            ['name' => 'Kluwek', 'category' => 'Bumbu Dapur', 'default_unit' => 'buah', 'typical_shelf_life_days' => 60, 'calories_per_100g' => 275, 'icon' => '🟤', 'description' => 'Kunci warna hitam legam dan rasa gurih pekat Rawon Surabaya'],
            ['name' => 'Jahe Emprit / Gajah', 'category' => 'Bumbu Dapur', 'default_unit' => 'ruas', 'typical_shelf_life_days' => 30, 'calories_per_100g' => 80, 'icon' => '🫚', 'description' => 'Rempah penghangat dan penetral bau amis daging'],
            ['name' => 'Lengkuas / Laos', 'category' => 'Bumbu Dapur', 'default_unit' => 'ruas', 'typical_shelf_life_days' => 25, 'calories_per_100g' => 71, 'icon' => '🫚', 'description' => 'Aroma sitrus khas untuk sayur asem, lodeh, dan rendang'],
            ['name' => 'Kunyit Segar / Bubuk', 'category' => 'Bumbu Dapur', 'default_unit' => 'ruas', 'typical_shelf_life_days' => 30, 'calories_per_100g' => 354, 'icon' => '🫚', 'description' => 'Pewarna kuning alami dan aroma soto, opor, dan gulai'],
            ['name' => 'Serai', 'category' => 'Bumbu Dapur', 'default_unit' => 'batang', 'typical_shelf_life_days' => 14, 'calories_per_100g' => 99, 'icon' => '🌾', 'description' => 'Aroma segar sitrun herba untuk rebusan dan gulai'],
            ['name' => 'Daun Salam', 'category' => 'Bumbu Dapur', 'default_unit' => 'lembar', 'typical_shelf_life_days' => 20, 'calories_per_100g' => 313, 'icon' => '🍃', 'description' => 'Daun aromatik penambah selera gulai, sayur asem, dan opor'],
            ['name' => 'Daun Jeruk Purut', 'category' => 'Bumbu Dapur', 'default_unit' => 'lembar', 'typical_shelf_life_days' => 14, 'calories_per_100g' => 130, 'icon' => '🍃', 'description' => 'Aroma segar wangi jeruk untuk rendang dan rica-rica'],
            ['name' => 'Daun Kunyit', 'category' => 'Bumbu Dapur', 'default_unit' => 'lembar', 'typical_shelf_life_days' => 7, 'calories_per_100g' => 90, 'icon' => '🍃', 'description' => 'Kunci aroma wangi otentik rendang Minang dan gulai'],
            ['name' => 'Daun Pandan', 'category' => 'Bumbu Dapur', 'default_unit' => 'lembar', 'typical_shelf_life_days' => 10, 'calories_per_100g' => 60, 'icon' => '🌿', 'description' => 'Aroma wangi nasi uduk, kolak, dan olahan ayam'],
            ['name' => 'Asam Jawa', 'category' => 'Bumbu Dapur', 'default_unit' => 'mata', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 239, 'icon' => '🫘', 'description' => 'Rasa asam segar alami kuah sayur asem dan kuah cuko pempek'],
            ['name' => 'Terasi Udang Matang', 'category' => 'Bumbu Dapur', 'default_unit' => 'sdt', 'typical_shelf_life_days' => 120, 'calories_per_100g' => 150, 'icon' => '🦐', 'description' => 'Penyedap umami tradisional untuk sambal terasi dan kangkung'],

            // ==========================================
            // 2. PROTEIN HEWANI & SEAFOOD
            // ==========================================
            ['name' => 'Dada Ayam Filet', 'category' => 'Protein Hewani', 'default_unit' => 'gram', 'typical_shelf_life_days' => 4, 'calories_per_100g' => 165, 'icon' => '🍗', 'description' => 'Tinggi protein tanpa lemak, favorit menu diet mahasiswa'],
            ['name' => 'Paha Ayam Utuh / Bawah', 'category' => 'Protein Hewani', 'default_unit' => 'gram', 'typical_shelf_life_days' => 4, 'calories_per_100g' => 209, 'icon' => '🍗', 'description' => 'Juicy dan gurih untuk opor, soto, dan ayam geprek'],
            ['name' => 'Telur Ayam Ras', 'category' => 'Protein Hewani', 'default_unit' => 'butir', 'typical_shelf_life_days' => 21, 'calories_per_100g' => 143, 'icon' => '🥚', 'description' => 'Bahan pangan paling serbaguna dan ekonomis untuk anak kos'],
            ['name' => 'Daging Sapi Has / Sengkel', 'category' => 'Protein Hewani', 'default_unit' => 'gram', 'typical_shelf_life_days' => 4, 'calories_per_100g' => 215, 'icon' => '🥩', 'description' => 'Potongan sempurna untuk rendang empuk dan rawon pekat'],
            ['name' => 'Daging Sapi Giling', 'category' => 'Protein Hewani', 'default_unit' => 'gram', 'typical_shelf_life_days' => 3, 'calories_per_100g' => 250, 'icon' => '🥩', 'description' => 'Bahan utama martabak telur kulit lumpia dan bakso'],
            ['name' => 'Bakso Sapi Halus', 'category' => 'Protein Hewani', 'default_unit' => 'butir', 'typical_shelf_life_days' => 7, 'calories_per_100g' => 202, 'icon' => '🧆', 'description' => 'Olahan daging kenyal favorit pelengkap mie, capcay, dan nasi goreng'],
            ['name' => 'Sosis Sapi / Ayam', 'category' => 'Protein Hewani', 'default_unit' => 'buah', 'typical_shelf_life_days' => 14, 'calories_per_100g' => 260, 'icon' => '🌭', 'description' => 'Campuran praktis nasi gila, seblak, dan mie nyemek'],
            ['name' => 'Udang Segar Kupas', 'category' => 'Protein Hewani', 'default_unit' => 'gram', 'typical_shelf_life_days' => 2, 'calories_per_100g' => 99, 'icon' => '🦐', 'description' => 'Manis laut segar, cepat matang hanya dalam 3 menit'],
            ['name' => 'Ikan Gurame / Nila Segar', 'category' => 'Protein Hewani', 'default_unit' => 'ekor', 'typical_shelf_life_days' => 2, 'calories_per_100g' => 128, 'icon' => '🐟', 'description' => 'Ikan air tawar gurih untuk bakar kecap atau pesmol'],
            ['name' => 'Ikan Tongkol Suwir', 'category' => 'Protein Hewani', 'default_unit' => 'gram', 'typical_shelf_life_days' => 4, 'calories_per_100g' => 150, 'icon' => '🐟', 'description' => 'Daging padat mantap untuk tongkol balado kemangi'],
            ['name' => 'Ikan Teri Medan / Asin', 'category' => 'Protein Hewani', 'default_unit' => 'gram', 'typical_shelf_life_days' => 45, 'calories_per_100g' => 210, 'icon' => '🐟', 'description' => 'Garing gurih renyah untuk sambal teri kacang dan nasi liwet'],
            ['name' => 'Kornet Sapi Kaleng', 'category' => 'Protein Hewani', 'default_unit' => 'sdm', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 250, 'icon' => '🥫', 'description' => 'Topping legendaris Indomie Warkop internet (Indomie Telur Kornet)'],

            // ==========================================
            // 3. PROTEIN NABATI TRADISIONAL
            // ==========================================
            ['name' => 'Tempe Kedelai Segar', 'category' => 'Protein Nabati', 'default_unit' => 'papan', 'typical_shelf_life_days' => 4, 'calories_per_100g' => 193, 'icon' => '🥪', 'description' => 'Superfood lokal asli Indonesia kaya serat dan protein nabati'],
            ['name' => 'Tahu Putih Sutra', 'category' => 'Protein Nabati', 'default_unit' => 'kotak', 'typical_shelf_life_days' => 3, 'calories_per_100g' => 76, 'icon' => '🧈', 'description' => 'Sangat lembut, menyerap kaldu gurih dengan sempurna'],
            ['name' => 'Tahu Kuning Bandung', 'category' => 'Protein Nabati', 'default_unit' => 'kotak', 'typical_shelf_life_days' => 4, 'calories_per_100g' => 80, 'icon' => '🧈', 'description' => 'Gurih alami beraroma kunyit tanpa bahan pengawet'],
            ['name' => 'Tahu Pong / Goreng', 'category' => 'Protein Nabati', 'default_unit' => 'buah', 'typical_shelf_life_days' => 5, 'calories_per_100g' => 115, 'icon' => '🧈', 'description' => 'Rongga dalamnya siap diisi sayuran atau disiram petis'],

            // ==========================================
            // 4. SAYURAN SEGAR NUSANTARA
            // ==========================================
            ['name' => 'Kangkung Akar', 'category' => 'Sayuran', 'default_unit' => 'ikat', 'typical_shelf_life_days' => 3, 'calories_per_100g' => 19, 'icon' => '🌿', 'description' => 'Sayur hijau renyah untuk tumis terasi pedas abang resto'],
            ['name' => 'Bayam Hijau Cabut', 'category' => 'Sayuran', 'default_unit' => 'ikat', 'typical_shelf_life_days' => 3, 'calories_per_100g' => 23, 'icon' => '🌿', 'description' => 'Kaya zat besi untuk sayur bening jagung manis'],
            ['name' => 'Wortel Segar', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 14, 'calories_per_100g' => 41, 'icon' => '🥕', 'description' => 'Warna oranye cerah, manis dan renyah untuk sop dan capcay'],
            ['name' => 'Wortel Brastagi', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 14, 'calories_per_100g' => 41, 'icon' => '🥕', 'description' => 'Warna oranye cerah, manis dan renyah untuk sop dan capcay'],
            ['name' => 'Kol / Kubis Segar', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 14, 'calories_per_100g' => 25, 'icon' => '🥬', 'description' => 'Tahan lama disimpan, cocok untuk mie goreng dan tongseng'],
            ['name' => 'Kubis Putih', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 14, 'calories_per_100g' => 25, 'icon' => '🥬', 'description' => 'Renyah segar untuk lalapan, orak-arik telur, dan soto'],
            ['name' => 'Selada Hijau', 'category' => 'Sayuran', 'default_unit' => 'ikat', 'typical_shelf_life_days' => 5, 'calories_per_100g' => 15, 'icon' => '🥬', 'description' => 'Daun renyah segar untuk salad, lalapan ayam bakar, dan gado-gado'],
            ['name' => 'Paprika Merah', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 10, 'calories_per_100g' => 31, 'icon' => '🫑', 'description' => 'Manis segar penuh vitamin C untuk tumis sapi lada hitam'],
            ['name' => 'Paprika Kuning', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 10, 'calories_per_100g' => 27, 'icon' => '🫑', 'description' => 'Warna kuning cerah penggugah selera tumisan kekinian'],
            ['name' => 'Paprika Hijau', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 12, 'calories_per_100g' => 20, 'icon' => '🫑', 'description' => 'Aroma khas renyah untuk tumisan dan fajitas'],
            ['name' => 'Timun Jepang', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 10, 'calories_per_100g' => 15, 'icon' => '🥒', 'description' => 'Kaya air dan renyah tanpa biji berlebih untuk salad dan acar'],
            ['name' => 'Mentimun', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 10, 'calories_per_100g' => 15, 'icon' => '🥒', 'description' => 'Lalapan segar pendingin pedas dan pelengkap acar'],
            ['name' => 'Terong Ungu', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 7, 'calories_per_100g' => 25, 'icon' => '🍆', 'description' => 'Tekstur lembut lezat untuk terong balado dan sayur lodeh'],
            ['name' => 'Kembang Kol', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 8, 'calories_per_100g' => 25, 'icon' => '🥦', 'description' => 'Sayuran bunga gurih renyah untuk capcay kuah kental'],
            ['name' => 'Brokoli Segar', 'category' => 'Sayuran', 'default_unit' => 'bonggol', 'typical_shelf_life_days' => 6, 'calories_per_100g' => 34, 'icon' => '🥦', 'description' => 'Superfood kaya serat dan vitamin untuk tumisan sehat'],
            ['name' => 'Daun Bawang', 'category' => 'Bumbu Dapur', 'default_unit' => 'batang', 'typical_shelf_life_days' => 7, 'calories_per_100g' => 32, 'icon' => '🌿', 'description' => 'Aroma wangi esensial martabak telur, sop ayam, dan nasi goreng'],
            ['name' => 'Seledri Segar', 'category' => 'Bumbu Dapur', 'default_unit' => 'ikat', 'typical_shelf_life_days' => 7, 'calories_per_100g' => 16, 'icon' => '🌿', 'description' => 'Taburan harum wajib kuah bakso dan soto'],
            ['name' => 'Labu Siam', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 10, 'calories_per_100g' => 19, 'icon' => '🍐', 'description' => 'Segar dan lembut untuk sayur lodeh dan sayur asem'],
            ['name' => 'Kacang Panjang', 'category' => 'Sayuran', 'default_unit' => 'ikat', 'typical_shelf_life_days' => 6, 'calories_per_100g' => 47, 'icon' => '🫛', 'description' => 'Renyah untuk tumisan tempe orek dan gado-gado'],
            ['name' => 'Tauge / Kecambah Segar', 'category' => 'Sayuran', 'default_unit' => 'gram', 'typical_shelf_life_days' => 3, 'calories_per_100g' => 34, 'icon' => '🌱', 'description' => 'Kress segar penambah selera soto, rawon, dan tahu telur'],
            ['name' => 'Jagung Manis', 'category' => 'Sayuran', 'default_unit' => 'tongkol', 'typical_shelf_life_days' => 7, 'calories_per_100g' => 86, 'icon' => '🌽', 'description' => 'Manis alami untuk bakwan jagung renyah dan sayur asem'],
            ['name' => 'Daun Singkong Muda', 'category' => 'Sayuran', 'default_unit' => 'ikat', 'typical_shelf_life_days' => 4, 'calories_per_100g' => 73, 'icon' => '🌿', 'description' => 'Sayuran wajib khas rumah makan Padang untuk gulai daun singkong'],
            ['name' => 'Daun Kemangi', 'category' => 'Sayuran', 'default_unit' => 'ikat', 'typical_shelf_life_days' => 3, 'calories_per_100g' => 22, 'icon' => '🍃', 'description' => 'Aroma wangi khas untuk pepes, rica-rica, dan lalapan'],
            ['name' => 'Tomat Merah Segar', 'category' => 'Sayuran', 'default_unit' => 'buah', 'typical_shelf_life_days' => 7, 'calories_per_100g' => 18, 'icon' => '🍅', 'description' => 'Rasa asam segar pembawa kesegaran alami masakan'],
            ['name' => 'Petai Kupas', 'category' => 'Sayuran', 'default_unit' => 'papan', 'typical_shelf_life_days' => 10, 'calories_per_100g' => 142, 'icon' => '🫘', 'description' => 'Aroma tajam pembangkit selera sambal goreng ati dan nasi goreng'],
            ['name' => 'Apel Segar', 'category' => 'Buah', 'default_unit' => 'buah', 'typical_shelf_life_days' => 21, 'calories_per_100g' => 52, 'icon' => '🍎', 'description' => 'Manis renyah kaya vitamin untuk penutup makan sehat'],

            // ==========================================
            // 5. KARBOHIDRAT & STAPLE MAKANAN INDONESIA
            // ==========================================
            ['name' => 'Beras Pandan Wangi', 'category' => 'Karbohidrat', 'default_unit' => 'kg', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 130, 'icon' => '🍚', 'description' => 'Beras pulen harum makanan pokok nomor satu masyarakat Indonesia'],
            ['name' => 'Mie Instan Indomie Goreng / Kuah', 'category' => 'Karbohidrat', 'default_unit' => 'bungkus', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 460, 'icon' => '🍜', 'description' => 'Makanan penyelamat mahasiswa di akhir bulan dengan rasa legendaris'],
            ['name' => 'Mie Telur Kuning Basah / Kering', 'category' => 'Karbohidrat', 'default_unit' => 'bungkus', 'typical_shelf_life_days' => 60, 'calories_per_100g' => 380, 'icon' => '🍝', 'description' => 'Bahan dasar mie goreng Jawa, mie tek-tek, dan bakmi'],
            ['name' => 'Bihun Jagung', 'category' => 'Karbohidrat', 'default_unit' => 'bungkus', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 360, 'icon' => '🥢', 'description' => 'Tekstur kenyal transparan untuk pelengkap soto dan bihun goreng'],
            ['name' => 'Kerupuk Aci Bawang Warna-Warni', 'category' => 'Karbohidrat', 'default_unit' => 'gram', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 380, 'icon' => '🍥', 'description' => 'Bahan mentah wajib untuk seblak basah kenyal Bandung'],
            ['name' => 'Tepung Tapioka / Sagu Tani', 'category' => 'Karbohidrat', 'default_unit' => 'gram', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 358, 'icon' => '🌾', 'description' => 'Bahan utama pempek kenyal, cireng, dan pengental kuah capcay'],
            ['name' => 'Tepung Terigu Segitiga Biru', 'category' => 'Karbohidrat', 'default_unit' => 'gram', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 364, 'icon' => '🌾', 'description' => 'Tepung serbaguna untuk bakwan, ayam krispi, dan martabak'],
            ['name' => 'Kentang Dieng', 'category' => 'Karbohidrat', 'default_unit' => 'buah', 'typical_shelf_life_days' => 30, 'calories_per_100g' => 77, 'icon' => '🥔', 'description' => 'Padat gurih untuk perkedel kentang dan sambal goreng ati'],

            // ==========================================
            // 6. SAUS, KONDIMEN & KULINER KEKINIAN
            // ==========================================
            ['name' => 'Kecap Manis Bango', 'category' => 'Saus & Minyak', 'default_unit' => 'sdm', 'typical_shelf_life_days' => 365, 'calories_per_100g' => 250, 'icon' => '🍶', 'description' => 'Rasa manis kedelai hitam alami kunci kelezatan kuliner Nusantara'],
            ['name' => 'Saus Tiram Saori', 'category' => 'Saus & Minyak', 'default_unit' => 'sdm', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 51, 'icon' => '🦪', 'description' => 'Ekstrak tiram asli untuk tumisan lezat dalam sekejap'],
            ['name' => 'Minyak Goreng Sawit', 'category' => 'Saus & Minyak', 'default_unit' => 'ml', 'typical_shelf_life_days' => 360, 'calories_per_100g' => 884, 'icon' => '🫗', 'description' => 'Minyak jernih untuk menggoreng renyah dan menumis harum'],
            ['name' => 'Minyak Wijen', 'category' => 'Saus & Minyak', 'default_unit' => 'sdt', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 884, 'icon' => '🧴', 'description' => 'Aroma wangi pemikat untuk nasi goreng abang kaki lima dan capcay'],
            ['name' => 'Santan Kelapa Instan Kara', 'category' => 'Saus & Minyak', 'default_unit' => 'bungkus', 'typical_shelf_life_days' => 14, 'calories_per_100g' => 230, 'icon' => '🥥', 'description' => 'Santan kelapa kental praktis untuk gulai, rendang, dan soto'],
            ['name' => 'Kecap Asin Cap Hati Angsa / Indofood', 'category' => 'Saus & Minyak', 'default_unit' => 'sdm', 'typical_shelf_life_days' => 365, 'calories_per_100g' => 60, 'icon' => '🍶', 'description' => 'Gurih asin kedelai fermentasi untuk kuah dan tumisan'],
            ['name' => 'Petis Udang Hitam Khas Sidoarjo', 'category' => 'Saus & Minyak', 'default_unit' => 'sdm', 'typical_shelf_life_days' => 120, 'calories_per_100g' => 180, 'icon' => '🖤', 'description' => 'Pasta udang hitam legit kunci rasa tahu telur dan rujak cingur'],
            ['name' => 'Keju Cheddar Parut Kraft', 'category' => 'Susu & Olahan', 'default_unit' => 'gram', 'typical_shelf_life_days' => 30, 'calories_per_100g' => 403, 'icon' => '🧀', 'description' => 'Topping gurih asin favorit menu modern mahasiswa (Indomie Keju)'],
            ['name' => 'Saus Sambal Botol Belibis', 'category' => 'Saus & Minyak', 'default_unit' => 'sdm', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 90, 'icon' => '🌶️', 'description' => 'Cocolan pedas bawang legendaris untuk gorengan dan bakso'],
            ['name' => 'Margarin Blue Band', 'category' => 'Saus & Minyak', 'default_unit' => 'sdm', 'typical_shelf_life_days' => 180, 'calories_per_100g' => 717, 'icon' => '🧈', 'description' => 'Aroma mentega gurih penggugah selera untuk nasi goreng dan martabak'],
        ];

        foreach ($ingredients as $data) {
            Ingredient::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
