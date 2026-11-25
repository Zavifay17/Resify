# Resify

## Masuk & Daftar dengan Akun Google

Sistem mencatat `google_id`, email, dan foto profil dari akun Google, jadi akun
yang sama bisa langsung masuk lagi tanpa kata sandi. Akun yang sebelumnya mendaftar
manual otomatis ditautkan saat email Google-nya sama.

### A. Buat kredensial di Google Cloud Console

1. Buka <https://console.cloud.google.com/> dan pilih (atau buat) project, misalnya
   **Resepify**.
2. **APIs & Services → Library** → cari **Google Identity Platforms** → **Enable**
   (opsional; OAuth bawaan Google sudah bisa dipakai langsung).
3. **APIs & Services → OAuth consent screen**:
   - *User Type*: **External** (gratis, siapa pun bisa login).
   - Isi *App name* (mis. `Resepify`), *User support email*, dan *Developer contact email*.
   - Pada *Scopes*, tambahkan `openid`, `email`, `profile`.
   - Klik **Create**.
4. Selama *Publishing status* masih **Testing**, buka **Audience** →
   **Test users** → **Add users** → masukkan alamat Gmail yang dipakai untuk uji.
   Tanpa langkah ini Google menampilkan error `access_denied`.
5. **APIs & Services → Credentials** → **Create Credentials → OAuth client ID**:
   - *Application type*: **Web application**.
   - *Authorized JavaScript origins*: `http://localhost:8000`
     (dan `http://127.0.0.1:8000` bila perlu).
   - *Authorized redirect URIs*: `http://localhost:8000/auth/google/callback`
     — harus **sama persis** dengan URI callback yang ditampilkan di halaman Pengaturan.
   - Klik **Create**, lalu salin **Client ID** dan **Client Secret**.

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