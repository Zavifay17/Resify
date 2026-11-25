# Resify

## Masuk & Daftar dengan Akun Google

Sistem mencatat `google_id`, email, dan foto profil dari akun Google, jadi akun
yang sama bisa langsung masuk lagi tanpa kata sandi.

1. Buka **Google Cloud Console** → *APIs & Services* → *Credentials* →
   **Create Credentials → OAuth client ID**, tipe **Web application**.
2. Isi **Authorized JavaScript origins**: `http://localhost:8000`.
3. Isi **Authorized redirect URIs**: `http://localhost:8000/auth/google/callback`
   (harus sama persis dengan `GOOGLE_REDIRECT_URI`).
4. Salin *Client ID* dan *Client Secret* ke `.env`:

   ```dotenv
   GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=xxxxxxxx
   GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
   ```

5. Jalankan `php artisan config:clear`, lalu buka halaman **Daftar**. Selama kedua
   kunci belum diisi, tombol Google otomatis disembunyikan agar halaman tetap aman.

Catatan teknis: kolom `google_id` dan `password` yang boleh kosong ditambahkan oleh
migration `2026_11_18_100000_add_google_id_to_users_table.php`.

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