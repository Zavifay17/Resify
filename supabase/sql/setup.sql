-- ============================================================================
-- RESEPIFY - Skema Supabase: Inventaris Dapur + Storage Foto Bahan
-- ============================================================================
-- Cara pakai (cukup satu kali):
--   1. Buka project Supabase Anda -> SQL Editor -> New query.
--   2. Salin SELURUH isi berkas ini ke editor, lalu klik RUN.
--   3. Selesai. Tidak ada perubahan kode/config yang diperlukan;
--      kolom di bawah sudah disamakan dengan payload SupabaseService.
--
-- Catatan: id baris diambil dari id MySQL lokal (client-generated) supaya
-- sinkronisasi ulang memperbarui baris yang sama, bukan menumpuk duplikat.
-- ============================================================================

-- 1. Tabel sinkronisasi inventaris dapur ---------------------------------------
create table if not exists public.kitchen_inventories (
    id               bigint primary key,
    user_id          integer not null,
    ingredient_id    integer,
    item_name        text not null,
    category         text,
    quantity         numeric(10, 2) not null default 1,
    unit             text not null default 'pcs',
    storage_location text,
    purchase_date    date,
    expiration_date  date,
    status           text not null default 'safe',
    notes            text,
    photo_path       text,
    synced_at        timestamptz
);

create index if not exists kitchen_inventories_user_id_idx on public.kitchen_inventories (user_id);
create index if not exists kitchen_inventories_expiration_date_idx on public.kitchen_inventories (expiration_date);
create index if not exists kitchen_inventories_status_idx on public.kitchen_inventories (status);

-- 2. Realtime untuk perubahan langsung ----------------------------------------
-- Kalau publikasi supabase_realtime belum ada, pernyataan ini dilewati aman.
do $$
begin
    begin
        alter publication supabase_realtime add table public.kitchen_inventories;
    exception when undefined_object then
        create publication supabase_realtime for table public.kitchen_inventories;
    when duplicate_object then
        raise notice 'Tabel kitchen_inventories sudah terdaftar di realtime.';
    end;
end $$;

-- 3. Row Level Security -------------------------------------------------------
-- Aplikasi menulis memakai service key (bypass RLS). Key publik/anon hanya boleh
-- membaca, jadi tidak bisa menyisipkan atau menguras data pengguna lain.
alter table public.kitchen_inventories enable row level security;

drop policy if exists "anon boleh membaca inventaris" on public.kitchen_inventories;
create policy "anon boleh membaca inventaris"
    on public.kitchen_inventories for select
    to anon
    using (true);

drop policy if exists "layanan boleh menulis inventaris" on public.kitchen_inventories;
create policy "layanan boleh menulis inventaris"
    on public.kitchen_inventories for all
    to service_role
    using (true) with check (true);

-- 4. Bucket Storage untuk foto bahan ------------------------------------------
-- Bucket dibuat public karena URL-nya dipakai langsung oleh tag <img> di web.
insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values ('ingredients', 'ingredients', true, 8388608, array['image/jpeg', 'image/png', 'image/webp'])
on conflict (id) do update
    set public = excluded.public,
        file_size_limit = excluded.file_size_limit,
        allowed_mime_types = excluded.allowed_mime_types;

drop policy if exists "unggah foto bahan" on storage.objects;
create policy "unggah foto bahan"
    on storage.objects for insert
    to service_role, authenticated
    with check (bucket_id = 'ingredients');

drop policy if exists "baca foto bahan" on storage.objects;
create policy "baca foto bahan"
    on storage.objects for select
    to anon, authenticated, service_role
    using (bucket_id = 'ingredients');

drop policy if exists "hapus foto bahan" on storage.objects;
create policy "hapus foto bahan"
    on storage.objects for delete
    to service_role
    using (bucket_id = 'ingredients');

-- 5. Pengecekan singkat --------------------------------------------------------
select 'tabel kitchen_inventories siap' as hasil, count(*) as jumlah_baris from public.kitchen_inventories;
select id, public, file_size_limit from storage.buckets where id = 'ingredients';
