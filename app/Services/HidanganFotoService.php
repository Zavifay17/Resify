<?php

namespace App\Services;

use App\Models\RecipeCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Cari foto hidangan open-source di Wikimedia Commons.
 * Tidak pernah melempar error: kalau jaringan mati atau hasilnya tidak meyakinkan,
 * pengembalian null berarti pemanggil pakai placeholder.
 */
class HidanganFotoService
{
    private const API = 'https://commons.wikimedia.org/w/api.php';

    /** Kata pemasaran/sifat yang tidak menggambarkan hidangannya. */
    private const KATA_SAMPING = [
        'khas', 'ala', 'gaya', 'dan', 'dengan', 'isi', 'punya', 'potong', 'gram', 'buah',
        'bumbu', 'sajian', 'versi', 'style', 'super', 'mantap', 'spesial', 'gurih', 'harum',
        'pedas', 'lezat', 'enak', 'nikmat', 'aroma', 'leleh', 'renyah', 'segar', 'kremi',
        'kripi', 'krispi', 'empuk', 'wangi', 'rumahan', 'resep', 'penyelamat', 'dapur',
        'kaki', 'lima', 'emas', 'kampung', 'racik', 'hijau', 'besar', 'manis', 'asam',
        'asap', 'sutra', 'bening', 'kuah', 'sederhana', 'kecap', 'oriental',
    ];

    /** Hasil Commons yang bukan foto masakan. */
    private const KATA_TOLAK = [
        'bumbu', 'resep', 'logo', 'ikon', 'icon', 'peta', 'map', 'diagram', 'chart',
        'bendera', 'ilustrasi', 'cartoon', 'cara membuat', 'how to', 'raw', 'uncooked',
        'field', 'plant', 'tree', 'flower', 'market', 'sold at', 'making', 'making of',
        'beetle', 'larva', 'imago', 'species', 'warming', 'building', 'museum', 'statue',
        'poster', 'banner', 'cover', 'book', 'carton', 'cartons', 'braising', 'braised',
        'calligraphy', 'packaging', 'ingredients', 'shelf', 'harvest', 'garden', 'farm',
        'cooker', 'jati', 'zucchini', 'zoodle', 'zoodles', 'newspaper', 'paper',
        'profile', 'news', 'tissue', 'soap', 'shampoo', 'mesjid', 'mosque', 'church',
        'temple', 'pura', 'city', 'village', 'street', 'architecture', 'illustration',
        'kaempferia', 'simpur', 'close up', 'logo', 'replica', 'exhibition', 'wax',
        'candi', 'monument', 'primitive', 'valley', 'mountain', 'range', 'portrait',
        'halte', 'station', 'platform', 'corridor', 'kindergarten', 'tiles',
        'factory', 'machine', 'grinding', 'stone', 'vessel', 'pottery', 'casserole',
        'kidney', 'canned', 'jar', 'sachet', 'satchet', 'packet', 'candi', 'monument',
        'halte', 'metro', 'tiles', 'scrofa', 'domesticus', 'cochon', 'primitive',
        'thumbnail', 'school', 'uniform', 'train', 'airport', 'office', 'interior',
        'monastery', 'buddhist', 'pagoda', 'shrine', 'carnival', 'ethno', 'parade',
        'scenery', 'landscape', 'panorama', 'beach', 'sunset', 'sunrise', 'waterfall',
        'bridge', 'island', 'district', 'town', 'road', 'coastal', 'harbour', 'port',
    ];

    /**
     * Kata yang menandakan foto itu hidangan jadi, bukan bahan mentah atau
     * barang di luar dapur. Dipakai untuk frasa yang dicari dari daftar bahan.
     */
    private const KATA_OLAHAN = [
        'stir', 'fried', 'fry', 'baked', 'boiled', 'grilled', 'steamed', 'roasted',
        'cooked', 'cooking', 'soup', 'curry', 'salad', 'sambal', 'sauce', 'cake',
        'kue', 'rice', 'noodle', 'noodles', 'bread', 'toast', 'sandwich', 'wrap',
        'plate', 'dish', 'dishes', 'food', 'meal', 'bowl', 'snack', 'dessert',
        'drink', 'juice', 'pancake', 'omelette', 'porridge', 'pudding', 'pie',
        'satay', 'sate', 'sup', 'soto', 'gado', 'pecel', 'ketupat', 'lontong',
        'goreng', 'tumis', 'rebus', 'bakar', 'panggang', 'kukus', 'balado', 'rica',
    ];

    /**
     * Lauk/protein dan sebutan lainnya. Kalau foto memuat lauk yang tidak ada di
     * judul resep, foto itu hidangan lain: "Kwetiau Goreng Kepiting" bukan
     * "Kwetiau Goreng Sapi". Nilai = golongan; dua sebutan satu golongan dianggap
     * sama agar "ikan" tetap menerima foto "tuna".
     */
    private const LAUK_CANON = [
        'ayam' => 'chicken', 'chicken' => 'chicken',
        'sapi' => 'beef', 'beef' => 'beef',
        'kambing' => 'goat', 'goat' => 'goat', 'mutton' => 'goat', 'lamb' => 'goat',
        'babi' => 'pork', 'pork' => 'pork',
        'bebek' => 'duck', 'duck' => 'duck',
        'ikan' => 'fish', 'fish' => 'fish', 'tuna' => 'fish', 'salmon' => 'fish',
        'catfish' => 'fish', 'tilapia' => 'fish', 'snapper' => 'fish', 'lele' => 'fish',
        'patin' => 'fish', 'mujair' => 'fish', 'wader' => 'fish', 'tongkol' => 'fish',
        'udang' => 'prawn', 'prawn' => 'prawn', 'shrimp' => 'prawn', 'ebi' => 'prawn',
        'cumi' => 'squid', 'squid' => 'squid', 'sotong' => 'squid', 'calamari' => 'squid',
        'kepiting' => 'crab', 'crab' => 'crab', 'lobster' => 'crab', 'kerang' => 'crab',
        'shellfish' => 'crab',
        'teri' => 'anchovy', 'anchovy' => 'anchovy', 'anchovies' => 'anchovy',
        'telur' => 'egg', 'egg' => 'egg',
        'tahu' => 'tofu', 'tofu' => 'tofu',
        'tempe' => 'tempeh', 'tempeh' => 'tempeh',
        'bakso' => 'meatball', 'meatball' => 'meatball',
        'sosis' => 'sausage', 'sausage' => 'sausage',
    ];

    /** Kata yang terlalu umum untuk membuktikan sebuah foto cocok dengan hidangannya. */
    private const KATA_LOKOMOTIF = [
        'stir', 'fried', 'fry', 'frying', 'cooked', 'cooking', 'food', 'dish', 'dishes',
        'meal', 'plate', 'file', 'with', 'and', 'of', 'the', 'jpg', 'png', 'jpeg', 'style',
        'special', 'delicious', 'tasty', 'traditional', 'indonesian', 'indonesia', 'asian',
        'chinese', 'homemade', 'made', 'mixed', 'savory', 'spiced', 'sliced', 'fresh',
    ];

    /**
     * Nama berkas yang memuat kata-kata ini bertentangan dengan cara masak
     * di judul resep, jadi fotonya dipakai untuk hidangan lain.
     */
    private const BERTENTANGAN = [
        'tumis' => ['soup', 'sup', 'sop', 'stew', 'boiled', 'dessert', 'cake'],
        'oseng' => ['soup', 'sup', 'sop', 'stew', 'boiled', 'dessert', 'cake'],
        'goreng' => ['soup', 'sup', 'sop', 'stew', 'boiled', 'steamed', 'dessert', 'cake'],
        'bakar' => ['soup', 'sup', 'sop', 'stew', 'boiled', 'steamed', 'dessert', 'cake'],
        'panggang' => ['soup', 'sup', 'sop', 'stew', 'boiled', 'steamed', 'raw', 'dessert'],
        'rebus' => ['fried', 'grilled', 'baked', 'dessert', 'cake'],
        'kukus' => ['fried', 'grilled', 'deep', 'dessert', 'cake'],
        'sop' => ['fried', 'grilled', 'baked', 'cake'],
        'sup' => ['fried', 'grilled', 'baked', 'cake'],
        'sayur' => ['dessert', 'cake', 'juice', 'smoothie', 'soup'],
        'es' => ['vegetables', 'vegetable', 'salad', 'curry', 'sambal', 'noodles', 'meat', 'rice', 'chicken', 'fish'],
        'kue' => ['vegetables', 'vegetable', 'curry', 'sambal', 'sate'],
        'bolu' => ['vegetables', 'vegetable', 'curry', 'sambal'],
        'putu' => ['vegetables', 'vegetable', 'curry', 'sambal'],
        'getas' => ['vegetables', 'vegetable', 'curry', 'sambal'],
        'wedang' => ['vegetables', 'vegetable', 'noodles', 'rice', 'cake'],
        'bubur' => ['vegetables', 'vegetable', 'noodles', 'cake'],
        'kopi' => ['vegetables', 'vegetable', 'noodles', 'rice'],
    ];

    /**
     * Nama bahan yang berdiri sendiri: "jeruk" mencari foto jeruk sekeranjang,
     * bukan "Tempe Krispi Daun Jeruk". Tidak boleh dipakai sebagai kunci satu kata.
     */
    private const KATA_BAHAN = [
        'jeruk', 'jahe', 'kunyit', 'kencur', 'laos', 'serai', 'sereh', 'terasi', 'garam',
        'gula', 'aren', 'santan', 'kelapa', 'kanji', 'tapioka', 'sagu', 'tepung', 'terigu',
        'bumbu', 'merica', 'ketumbar', 'jinten', 'cengkih', 'kemukus', 'pala', 'kayu',
        'nanas', 'mangga', 'kedondong', 'kincung', 'petai', 'jengkol', 'rebung', 'packoi',
    ];

    /** Nama gaya asing/daerah yang bukan nama hidangan, tidak boleh dicari satu kata. */
    private const KATA_ASING = [
        'korean', 'japanese', 'chinese', 'western', 'oriental', 'thai', 'italian',
        'american', 'european', 'india', 'korea', 'jepang', 'cina', 'belanda', 'inggris',
        'bali', 'lombok', 'malang', 'medan', 'medaan', 'batang', 'saparua', 'progo',
        'halmahera', 'ngaju', 'buntu', 'manado', 'palembang', 'sunda', 'crete', 'resto',
        'classic', 'original', 'special', 'kekinian', 'teler', 'gaya', 'spesial',
    ];

    /** Sayuran yang cukup umum difoto di Commons untuk dipakai sebagai kategori. */
    private const KELOMPOK_SAYUR = [
        'sayur', 'sayuran', 'campur', 'kacang', 'polong', 'kapri', 'buncis', 'wortel',
        'brokoli', 'kembangkol', 'kubis', 'kol', 'sawi', 'kangkung', 'bayam', 'pare',
        'labu', 'jagung', 'terong', 'tomat', 'selada', 'seledri', 'jamur', 'pakis',
    ];

    /** Lauk/protein utama -> nama hidangan kategori dalam bahasa Inggris. */
    private const KELOMPOK_LAUK = [
        'chicken' => ['ayam'],
        'beef' => ['sapi', 'daging'],
        'fish' => ['ikan', 'wader', 'tongkol', 'patin', 'mujair', 'lele'],
        'prawn' => ['udang', 'ebi'],
        'squid' => ['cumi', 'sotong'],
        'egg' => ['telur'],
        'tempeh' => ['tempe'],
        'tofu' => ['tahu'],
        'duck' => ['bebek'],
        'goat' => ['kambing'],
    ];

    /** Frasa Indonesia -> Inggris, diperiksa dari yang paling panjang. */
    private const TERJEMAH_FRASA = [
        'orak arik' => 'scrambled egg',
        'rice cooker' => '',
        'bawang putih' => 'garlic',
        'bawang merah' => 'shallot',
        'bawang bombay' => 'onion',
        'kacang polong' => 'peas',
        'jantung pisang' => 'banana blossom',
        'daun salam' => 'indonesian bay leaf',
        'daun jeruk' => 'kaffir lime leaf',
        'daun kemangi' => 'lemon basil',
        'jeruk nipis' => 'lime',
        'jeruk purut' => 'kaffir lime',
        'santan kental' => 'coconut milk',
        'ubi ungu' => 'purple sweet potato',
        'ubi jalar' => 'sweet potato',
        'kentang keju' => 'cheese potato',
        'nasi goreng' => 'nasi goreng',
        'mie goreng' => 'fried noodles',
        'bihun goreng' => 'fried vermicelli',
        'tahu sutra' => 'silken tofu',
        'tahu tahu' => 'tofu',
        'tempe gembus' => 'tempeh',
        'sayur asam' => 'vegetable soup',
        'sayur lodeh' => 'vegetable coconut soup',
        'kacang panjang' => 'long bean',
        'buncis' => 'green bean',
        'jagung muda' => 'baby corn',
        'jagung manis' => 'sweet corn',
        'jagung pipil' => 'corn',
        'udang windu' => 'prawn',
        'ikan patin' => 'catfish',
        'ikan tongkol' => 'tuna',
        'ikan kakap' => 'snapper',
        'ikan mujair' => 'tilapia',
        'ikan lele' => 'catfish',
        'ikan wader' => 'small fish',
        'kerupuk' => 'cracker',
    ];

    /** Kata tunggal Indonesia -> Inggris. */
    private const TERJEMAH_KATA = [        'tumis' => 'stir fried',
        'oseng' => 'stir fried',
        'cap cay' => 'stir fried vegetables',
        'goreng' => 'fried',
        'goreengan' => 'fried',
        'rebus' => 'boiled',
        'bakar' => 'grilled',
        'panggang' => 'baked',
        'kukus' => 'steamed',
        'campur' => 'mixed',
        'sambal' => 'sambal',
        'gulai' => 'gulai',
        'gudeg' => 'gudeg',
        'rendang' => 'rendang',
        'sate' => 'satay',
        'rawon' => 'beef soup',
        'sayur' => 'vegetables',
        'sayuran' => 'vegetables',
        'asin' => 'fish soup',
        'pindang' => 'fish soup',
        'mangut' => 'fish curry',
        'ayam' => 'chicken',
        'bebek' => 'duck',
        'sapi' => 'beef',
        'kambing' => 'goat',
        'ikan' => 'fish',
        'udang' => 'prawn',
        'ebi' => 'prawn',
        'cumi' => 'squid',
        'kerang' => 'shellfish',
        'teri' => 'anchovy',
        'wader' => 'small fish',
        'tahu' => 'tofu',
        'tempe' => 'tempeh',
        'telur' => 'egg',
        'kentang' => 'potato',
        'terong' => 'eggplant',
        'tomat' => 'tomato',
        'wortel' => 'carrot',
        'brokoli' => 'broccoli',
        'kembang kol' => 'cauliflower',
        'kembangkol' => 'cauliflower',
        'kubis' => 'cabbage',
        'kol' => 'cabbage',
        'sawi' => 'mustard greens',
        'kangkung' => 'water spinach',
        'bayam' => 'spinach',
        'pare' => 'bitter gourd',
        'labu' => 'pumpkin',
        'jagung' => 'corn',
        'kapri' => 'snow pea',
        'polong' => 'peas',
        'kacang' => 'beans',
        'kedelai' => 'soybean',
        'bawang' => 'onion',
        'cabai' => 'chili',
        'cabe' => 'chili',
        'sosis' => 'sausage',
        'bakso' => 'meatball',
        'nugget' => 'nugget',
        'keju' => 'cheese',
        'mozzarella' => 'mozzarella',
        'makaroni' => 'macaroni',
        'spaghetti' => 'spaghetti',
        'mie' => 'noodles',
        'mi' => 'noodles',
        'bihun' => 'vermicelli',
        'nasi' => 'rice',
        'roti' => 'bread',
        'pisang' => 'banana',
        'nanas' => 'pineapple',
        'mangga' => 'mango',
        'semangka' => 'watermelon',
        'kelapa' => 'coconut',
        'sagu' => 'sago',
        'talas' => 'taro',
        'singkong' => 'cassava',
        'ubi' => 'sweet potato',
        'wijen' => 'sesame',
        'kurma' => 'date',
        'asparagus' => 'asparagus',
        'jamur' => 'mushroom',
        'pakis' => 'fern shoot',
        'kemangi' => 'basil',
        'selada' => 'lettuce',
        'seledri' => 'celery',
        'daun' => 'leaf',
    ];

    /**
     * @param  array<int,string>  $bahan  Nama bahan, dipakai bila judul tidak menemukan foto
     * @return string|null  URL foto Wikimedia Commons atau null bila tidak ada yang meyakinkan
     */
    public function cari(string $judul, ?string $wilayah = null, array $bahan = [], int $maksCari = 10): ?string
    {
        $kandidat = $this->kandidat($judul, $wilayah, $bahan);
        $haram = $this->kataHaram($judul);
        $kataResep = $this->kataResep($judul, $bahan);
        $ijinLauk = $this->ijinLauk($kataResep);
        $urlTerbaik = null;
        $nilaiTerbaik = -1;
        $sisa = max(1, $maksCari);

        foreach ($kandidat as $frasa => $asal) {
            if ($sisa-- <= 0) {
                break;
            }

            foreach ($this->hasilCommons($frasa) as $halaman) {
                $gambar = data_get($halaman, 'imageinfo.0', []);

                if (data_get($gambar, 'mime') !== 'image/jpeg') {
                    continue;
                }

                $url = data_get($gambar, 'thumburl') ?: data_get($gambar, 'url');
                $url = $url ? Str::before($url, '?') : null;
                $namaBerkas = $this->bersihkan((string) data_get($halaman, 'title', ''));

                if (! $url || $this->ditolak($url, $namaBerkas, $haram)
                    || $this->laukAsing($namaBerkas, $ijinLauk)) {
                    continue;
                }

                $skor = $this->skor($frasa, $namaBerkas);

                if ($skor === 0) {
                    continue;
                }

                // Frasa hasil terjemahan ("stir fried mixed vegetables") tidak berbagi
                // kata dengan judul Indonesia, jadi hanya diterima bila cocok utuh.
                // Frasa dari daftar bahan harus membuktikan dirinya makanan jadi.
                $terjemahan = $asal !== 'judul';

                if ($terjemahan) {
                    if ($skor !== 3) {
                        continue;
                    }

                    if ($asal === 'bahan' && ! $this->adaOlahan($namaBerkas)) {
                        continue;
                    }

                    $cocok = 3;
                } else {
                    $cocok = $this->kecocokan($kataResep, $namaBerkas);
                    $sendiri = str_word_count($frasa) === 1;
                    $minimum = $skor === 3 || $sendiri ? 1 : 2;

                    if ($cocok < $minimum) {
                        continue;
                    }
                }

                $nilai = ($skor === 3 ? 100 : 0) + $cocok;

                if ($nilai > $nilaiTerbaik) {
                    $nilaiTerbaik = $nilai;
                    $urlTerbaik = $url;
                }
            }
        }

        return $urlTerbaik;
    }

    /**
     * Isi image_url milik resep, simpan bila ketemu.
     */
    public function pasang(RecipeCache $resep, int $maksCari = 10): bool
    {
        $url = $this->cari((string) $resep->title, $resep->region, $this->namaBahan($resep), $maksCari);

        if (! $url) {
            return false;
        }

        $resep->image_url = $url;
        $resep->save();

        return true;
    }

    /**
     * @return array<int,string>
     */
    private function namaBahan(RecipeCache $resep): array
    {
        $bahan = array_merge((array) ($resep->used_ingredients ?? []), (array) ($resep->missing_ingredients ?? []));

        return collect($bahan)
            ->map(fn($item) => is_array($item) ? (string) ($item['name'] ?? $item['item_name'] ?? '') : (string) $item)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Kata yang menjelaskan resep ini beserta bobotnya: kata Indonesia bernilai
     * dua karena nama hidangan Nusantara di Commons persis sama dengan judul,
     * kata hasil terjemahan hanya bernilai satu.
     *
     * @return array<string,int>
     */
    private function kataResep(string $judul, array $bahan): array
    {
        $kata = $this->tokens($judul);
        $isi = $this->tokens(implode(' ', array_slice($bahan, 0, 6)));

        $berbobot = fn(array $daftar, int $bobot) => collect($daftar)
            ->flatMap(fn($frasa) => explode(' ', (string) $frasa))
            ->reject(fn($k) => strlen($k) < 3 || in_array($k, self::KATA_LOKOMOTIF, true))
            ->mapWithKeys(fn($k) => [$k => $bobot]);

        return $berbobot($kata->all(), 2)
            ->merge($berbobot($this->keInggris($kata->all())->all(), 1))
            ->merge($berbobot($this->keInggris($isi->all())->all(), 1))
            ->all();
    }

    /**
     * Seberapa banyak kata resep yang muncul di nama berkas Commons.
     *
     * @param  array<string,int>  $kataResep
     */
    private function kecocokan(array $kataResep, string $namaBerkas): int
    {
        $beres = ' ' . $namaBerkas . ' ';

        return collect($kataResep)
            ->filter(fn($bobot, $k) => Str::contains($beres, ' ' . $k . ' ')
                || Str::contains($beres, ' ' . $k . 's ')
                || Str::contains($beres, ' ' . $k . 'es '))
            ->sum();
    }

    /**
     * Tangga kata kunci: frasa Indonesia -> frasa Inggris -> kategori hidangan -> bahan utama.
     *
     * @return array<string,string>  frasa pencarian => asal frasa (judul/inggris/kategori/bahan)
     */
    private function kandidat(string $judul, ?string $wilayah, array $bahan): array
    {
        $kata = $this->tokens($judul);
        $utama = $this->tokens(implode(' ', array_slice($bahan, 0, 3)));

        $judulFrasa = [
            $kata->take(3)->implode(' ') => 'judul',
            $kata->take(2)->implode(' ') => 'judul',
        ];

        // Lauk dari daftar bahan kadang lebih menyebut hidangannya daripada
        // judul: "Sate Klopo Bondowoso" dicari sebagai "sate sapi".
        $lauk = $utama->first(fn($k) => isset(self::LAUK_CANON[$k]));

        if ($lauk && $kata->isNotEmpty()) {
            $judulFrasa[$kata->first() . ' ' . $lauk] = 'judul';
            $judulFrasa[$kata->take(2)->implode(' ') . ' ' . $lauk] = 'judul';
        }

        if ($wilayah) {
            $judulFrasa[$kata->take(2)->implode(' ') . ' ' . $this->bersihkan($wilayah)] = 'judul';
        }

        $inggris = $this->keInggris($kata->all());
        $verb = $this->kataMasak($kata->all());

        $inggrisFrasa = [
            $inggris->take(3)->implode(' ') => 'inggris',
            $inggris->take(2)->implode(' ') => 'inggris',
        ];

        foreach ($this->kategori($verb, $kata->all()) as $frasa) {
            $inggrisFrasa[$frasa] = 'kategori';
        }

        $bahanFrasa = [];

        if ($utama->isNotEmpty()) {
            $campur = $this->keInggris($utama->take(3)->all());

            $bahanFrasa = [
                $this->keInggris($utama->take(1)->all())->implode(' ') => 'bahan',
                trim($verb . ' ' . $campur->implode(' ')) => 'bahan',
                trim($verb . ' ' . $campur->take(2)->implode(' ')) => 'bahan',
                $campur->take(2)->implode(' ') => 'bahan',
            ];
        }

        $gabungan = [];

        foreach ([$judulFrasa, $inggrisFrasa, $bahanFrasa] as $tingkat) {
            foreach ($tingkat as $frasa => $asal) {
                $bersih = $this->bersihkan((string) $frasa);

                // Dua kata ke atas: satu kata mudah salah ("tempe" = kota Tempe,
                // "sosis" = kupu-kupu Taygetis sosis).
                if ($bersih !== '' && ! isset($gabungan[$bersih])
                    && str_word_count($bersih) >= 2 && Str::length($bersih) > 5) {
                    $gabungan[$bersih] = $asal;
                }
            }
        }

        foreach ($kata->filter(fn($k) => $this->namaKhas($k))->take(3)->all() as $khas) {
            $gabungan[$khas] ??= 'judul';
        }

        return $gabungan;
    }

    /**
     * Satu kata hanya boleh dicari sendirian kalau memang nama hidangan:
     * kata Inggris hasil terjemahan ("banana", "chicken") dan nama gaya/daerah
     * ("korean", "bali") justru memanggil foto yang tidak ada hubungannya.
     */
    private function namaKhas(string $kata): bool
    {
        $dalamFrasa = collect(self::TERJEMAH_FRASA)
            ->keys()
            ->some(fn($frasa) => in_array($kata, explode(' ', $frasa), true));

        return strlen($kata) >= 5
            && !isset(self::TERJEMAH_KATA[$kata])
            && !in_array($kata, array_values(self::TERJEMAH_KATA), true)
            && !in_array($kata, self::KATA_ASING, true)
            && !in_array($kata, self::KATA_BAHAN, true)
            && !in_array($kata, self::KATA_LOKOMOTIF, true)
            // "jeruk" dalam "daun jeruk" hanya menyebut bumbu, bukan hidangannya,
            // jadi tidak boleh dicari sendirian (hasilnya foto jeruk segar).
            && ! $dalamFrasa;
    }

    /**
     * Judul hasil AI jarang ada padanannya di Commons, jadi kategori hidangannya
     * yang dipakai: "tumis sayur campur" jadi "stir fried mixed vegetables".
     *
     * @param  array<int,string>  $kata
     * @return array<int,string>
     */
    private function kategori(string $verb, array $kata): array
    {
        $hasil = [];
        $ada = fn(array $grup) => (bool) array_intersect($grup, $kata);

        if ($ada(self::KELOMPOK_SAYUR)) {
            $hasil[] = trim($verb . ' mixed vegetables');
            $hasil[] = trim($verb . ' vegetables');
            $hasil[] = 'mixed vegetables';
        }

        foreach (self::KELOMPOK_LAUK as $nama => $grup) {
            if ($ada($grup)) {
                $hasil[] = trim($verb . ' ' . $nama);
                $hasil[] = $nama;
                break;
            }
        }

        return array_values(array_filter(array_unique($hasil), fn($f) => str_word_count($f) >= 2));
    }

    /**
     * @param  array<int,string>  $kata
     * @return \Illuminate\Support\Collection<int,string>
     */
    private function keInggris(array $kata): \Illuminate\Support\Collection
    {
        $hasil = collect();
        $batas = count($kata);
        $i = 0;

        while ($i < $batas) {
            $cocok = null;

            foreach ([3, 2] as $n) {
                $frasa = implode(' ', array_slice($kata, $i, $n));

                if ($n <= $batas - $i && isset(self::TERJEMAH_FRASA[$frasa])) {
                    $cocok = self::TERJEMAH_FRASA[$frasa];
                    $i += $n;
                    break;
                }
            }

            if ($cocok === null) {
                $tunggal = self::TERJEMAH_KATA[$kata[$i]] ?? $kata[$i];
                $i++;

                if (trim($tunggal) === '') {
                    continue;
                }

                $hasil->push(trim($tunggal));
                continue;
            }

            if (trim($cocok) !== '') {
                $hasil->push($cocok);
            }
        }

        return $hasil->unique()->values();
    }

    /**
     * @param  array<int,string>  $kata
     */
    private function kataMasak(array $kata): string
    {
        foreach ($kata as $k) {
            $inggris = self::TERJEMAH_KATA[$k] ?? null;

            if (in_array($inggris, ['stir fried', 'fried', 'boiled', 'grilled', 'baked', 'steamed'], true)) {
                return $inggris;
            }
        }

        return '';
    }

    /**
     * @return \Illuminate\Support\Collection<int,string>
     */
    private function tokens(string $teks): \Illuminate\Support\Collection
    {
        return collect(explode(' ', $this->bersihkan($teks)))
            ->reject(function ($kata) {
                $kata = trim($kata);

                return $kata === ''
                    || strlen($kata) < 3
                    || in_array($kata, self::KATA_SAMPING, true);
            })
            ->unique()
            ->values();
    }

    private function bersihkan(string $teks): string
    {
        return Str::of($teks)
            ->lower()
            ->replace('_', ' ')
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->trim()
            ->value();
    }

    /**
     * 3 = nama berkas memuat frasa pencarian utuh dan seluruh kata khasnya,
     * 2 = sebagian kata khas ada di nama berkas sehingga layak jadi cadangan.
     * Kata kerja masak seperti "stir fried" terlalu umum untuk membuktikan
     * kecocokan, jadi tidak dihitung sendiri.
     */
    private function skor(string $frasa, string $namaBerkas): int
    {
        if ($namaBerkas === '') {
            return 0;
        }

        $kata = collect(explode(' ', $frasa))
            ->reject(fn($k) => strlen($k) < 3)
            ->unique()
            ->values();

        $isi = $kata->reject(fn($k) => in_array($k, self::KATA_LOKOMOTIF, true))->values();

        if ($kata->isEmpty() || $isi->isEmpty()) {
            return 0;
        }

        $beres = ' ' . $namaBerkas . ' ';
        $ada = fn(string $k) => Str::contains($beres, ' ' . $k . ' ');

        if ($kata->count() === 1) {
            // Nama hidangan khas dicari sendirian, jadi kata itu harus membuka
            // nama berkasnya: "File:Cireng Bandung.jpg" ya, "Taygetis sosis" tidak.
            return $ada($frasa) && Str::startsWith($namaBerkas, 'file ' . $frasa) ? 2 : 0;
        }

        $kenaIsi = $isi->filter($ada)->count();

        if ($kenaIsi === 0) {
            return 0;
        }

        if ($kenaIsi === $isi->count() && Str::contains($beres, ' ' . $frasa . ' ')) {
            return 3;
        }

        if ($isi->count() === 1) {
            return strlen($isi->first()) >= 6 ? 2 : 0;
        }

        return $kenaIsi >= 2 ? 2 : 0;
    }

    /**
     * Golongan lauk yang disebut judul resep -> golongan yang boleh muncul di nama berkas.
     *
     * @param  array<string,int>  $kataResep
     * @return array<int,string>
     */
    private function ijinLauk(array $kataResep): array
    {
        return collect(array_keys($kataResep))
            ->map(fn($k) => self::LAUK_CANON[$k] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Nama berkas menyebut lauk yang berbeda dari lauk di judul resep.
     * Resep tanpa lauk (sayur, kue) dibiarkan karena fotonya sering bercampur lauk.
     *
     * @param  array<int,string>  $ijin
     */
    private function laukAsing(string $namaBerkas, array $ijin): bool
    {
        if ($ijin === []) {
            return false;
        }

        $beres = ' ' . $namaBerkas . ' ';

        return collect(self::LAUK_CANON)
            ->reject(fn($golongan, $k) => in_array($golongan, $ijin, true))
            ->keys()
            ->some(fn($k) => Str::contains($beres, ' ' . $k . ' ') || Str::contains($beres, ' ' . $k . 's '));
    }

    private function ditolak(string $url, string $namaBerkas, array $haram = []): bool
    {
        $acak = ' ' . $this->bersihkan($url . ' ' . $namaBerkas) . ' ';

        foreach (self::KATA_TOLAK as $kotor) {
            if (Str::contains($acak, ' ' . $kotor . ' ')) {
                return true;
            }
        }

        foreach ($haram as $kotor) {
            if (Str::contains($acak, ' ' . $kotor . ' ')) {
                return true;
            }
        }

        // Foto arsip (tahun 1800-1999 di nama berkas) bukan potret hidangan hari ini.
        // Nomor kamera (DSC 1981, IMG 2015) bukan tahun arsip.
        $arsip = Str::of($namaBerkas)
            ->replaceMatches('/\b\d+px\b/', ' ')
            ->replaceMatches('/\b(dsc|dscn|img|pict)\s*\d+\b/', ' ')
            ->value();

        if (preg_match('/\b1[89]\d{2}\b/', ' ' . $arsip . ' ')) {
            return true;
        }

        return false;
    }

    private function adaOlahan(string $namaBerkas): bool
    {
        $beres = ' ' . $namaBerkas . ' ';

        return collect(self::KATA_OLAHAN)
            ->contains(fn($k) => Str::contains($beres, ' ' . Str::lower($k) . ' '));
    }

    /**
     * Kata yang tidak boleh ada di nama berkas karena cara masaknya beda.
     *
     * @return array<int,string>
     */
    private function kataHaram(string $judul): array
    {
        $haram = [];

        foreach (self::BERTENTANGAN as $cara => $larangan) {
            if (Str::contains(' ' . $this->bersihkan($judul) . ' ', ' ' . $cara . ' ')) {
                $haram = array_merge($haram, $larangan);
            }
        }

        return array_values(array_unique($haram));
    }

    private function hasilCommons(string $kata): array
    {
        try {
            return Http::withHeaders(['User-Agent' => 'Resepify/1.0 (https://resepify.test; kontak developer@resepify.test)'])
                ->timeout(12)
                ->retry(1, 500, throw: false)
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
        } catch (\Throwable) {
            return [];
        }
    }
}
