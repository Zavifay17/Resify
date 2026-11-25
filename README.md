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