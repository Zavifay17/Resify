# Resify

## Instalasi cepat

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

`storage:link` wajib dijalankan: tanpanya folder `public/storage` hanya jadi
folder kosong sehingga foto hasil unggahan di Deteksi Foto tidak bisa dibuka.

## Masuk & Daftar dengan Akun Google

Sistem mencatat `google_id`, email, dan foto profil dari akun Google, jadi akun
yang sama bisa langsung masuk lagi tanpa kata sandi. Akun yang sebelumnya mendaftar
manual otomatis ditautkan saat email Google-nya sama.

### A. Buat kredensial di Google Cloud Console

1. Buka <https://console.cloud.google.com/> dan pada pemilih project di bar atas
   pilih **Resepify** (jangan project lain seperti Agrilens).
2. Menu kiri **Google Auth Platform → Branding**: isi *App name* `Resepify`,
   *User support email*, dan *Developer contact email* → **Save**.
3. **Google Auth Platform → Audience**: pilih *User type* **External** (gratis,
   siapa pun bisa login).
4. Selama *Publishing status* masih **Testing**, klik **Add users** dan masukkan
   alamat Gmail yang dipakai menguji. Tanpa langkah ini Google menolak dengan
   `access_denied`.
5. **Google Auth Platform → Clients → Create Clients** → *Application type*
   **Web application**, nama bebas (mis. `Resepify local`).
6. Isi **Authorized JavaScript origins** (Add URI): `http://localhost:8000`
   (tambahkan `http://127.0.0.1:8000` bila perlu).
7. Isi **Authorized redirect URIs** (Add URI):
   `http://localhost:8000/auth/google/callback` — harus **sama persis** dengan URI
   callback yang ditampilkan di halaman Pengaturan.
8. Klik **Create**, lalu salin **Client ID** dan **Client Secret** yang muncul
   (atau *Download client IDs*).

### B. Isi kredensialnya (dua cara)

**Cara 1 — dari aplikasi (paling cepat, tidak menyentuh `.env`):**
buka **Pengaturan → Login Google**, tempel *Client ID* dan *Client Secret*, klik
**Simpan konfigurasi API**. Nilai disimpan di tabel `system_settings` dan langsung
dipakai; tombol Google muncul seketika di halaman Masuk & Daftar.

**Cara 2 — lewat `.env`:**

```dotenv
GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxxxxxx
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

lalu jalankan `php artisan config:clear`.

Kedua cara aman: selama kredensial belum diisi, tombol Google otomatis disembunyikan
dan tidak ada teks konfigurasi yang muncul di halaman.

### C. Uji

1. Buka `/register` → klik **Daftar dengan Google** → pilih akun → izinkan akses.
2. Sistem membuat akun baru (`password` kosong, `google_id` terisi) dan langsung masuk.
3. Klik **Keluar**, buka `/login` → klik **Masuk dengan Google** → akun yang sama
   masuk lagi tanpa kata sandi.

Catatan teknis: kolom `google_id` dan `password` yang boleh kosong ditambahkan oleh
migration `2026_11_18_100000_add_google_id_to_users_table.php`. `Client Secret` yang
disimpan lewat Pengaturan tidak pernah dicerminkan ke Supabase (ada dalam daftar
`KUNCI_RAHASIA` pada `SupabaseService`).

## Skema Cloud Supabase & ERD

Projek Supabase memakai **9 tabel** yang mencerminkan struktur MySQL lokal supaya
*Schema Visualizer* menampilkan ERD yang utuh: `users`, `ingredients`,
`ingredient_requests`, `kitchen_inventories`, `recipes_cache`, `recipes`,
`recipe_substitutions`, `ai_logs`, dan `system_settings`. Antar tabel dihubungkan
foreign key asli (`not valid` → `validate constraint`), jadi relasi ikut tergambar
dan skripnya tetap aman dijalankan ulang.

Cara membangunnya (pilih salah satu, dari halaman **Pengaturan**):

1. **Manual lewat SQL Editor** — klik *Salin Script SQL Setup*, tempel di
   Supabase → *SQL Editor*, lalu **RUN**. Setelah itu klik **Sinkronkan data cloud**
   untuk mengisi tabel.
2. **Otomatis dari aplikasi** — isi *Personal Access Token* di
   [supabase.com/account/tokens](https://supabase.com/account/tokens) ke `.env`,
   lalu klik **Bangun semua tabel otomatis**.

   ```dotenv
   SUPABASE_ACCESS_TOKEN=sbp_xxxxxxxx
   ```

   Token ini hanya dibaca dari `.env` (lewat `config/services.php`), tidak pernah
   disimpan ke `system_settings`, tidak dicerminkan ke cloud, dan tidak ditampilkan
   di halaman mana pun.

Kolom `password`, `remember_token`, serta API key di `system_settings` sengaja
dikecualikan dari sinkronisasi. Foreign key dibuat `not valid` bila masih ada baris
yatim, sehingga **Sinkronkan data cloud** berikutnya otomatis melengkapi baris induk
(`users` dan `ingredients`) sebelum mengirim item inventaris.

## Aset visual

Latar foto bahan masakan pada halaman Masuk/Daftar adalah foto
[photo-1579150877125-53b49ce88315](https://images.unsplash.com/photo-1579150877125-53b49ce88315)
karya [Andre Benz (@trapnation)](https://unsplash.com/@trapnation) dari Unsplash
(lisensi bebas Unsplash), disimpan lokal di
`public/images/auth/bahan-dapur-unsplash.jpg`.

### Foto contoh sampel Deteksi Foto

Empat tombol "Atau Uji Contoh Sampel" memakai berkas lokal di
`public/images/samples/`, jadi foto yang ditampilkan dan foto yang dikirim ke AI
selalu gambar yang sama (tanpa dependensi internet saat demo):

| Berkas | Isi | Sumber | Lisensi |
| --- | --- | --- | --- |
| `kulkas-sayur.jpg` | Sayur & buah di dalam kulkas | Wikimedia Commons, *Fruits and Vegetables in Refrigerator* karya Alabama Extension | CC0 |
| `belanja-pasar.jpg` | Los pasar tradisional (timun, wortel, serai, rimpang) | Wikimedia Commons, *Pasar Rakyat Kedungmaling 075305* karya Abdul Hanif Arromy | CC BY 4.0 |
| `bumbu-dapur.jpg` | Rempah & bumbu dapur nusantara | Wikimedia Commons, *Ragam Rempah dan Bumbu Dapur* karya M Joko Apriyo Putro | CC BY-SA 4.0 |
| `bumbu-sachet.jpg` | Bumbu instan sachet (merek fiktif "Racik Nusa") | Hasil gambar AI (ImageGen), bukan merek dagang nyata | bebas dipakai |

Daftar berkasnya dijaga satu sumber di `VisionController::daftarSampel()`.