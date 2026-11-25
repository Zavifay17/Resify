-- ============================================================================
-- RESEPIFY - Skema Cloud Supabase: 9 Tabel + Relasi (ERD) + Storage Foto
-- ============================================================================
-- Cara pakai (cukup satu kali):
--   1. Buka project Supabase Anda -> SQL Editor -> New query.
--   2. Salin SELURUH isi berkas ini ke editor, lalu klik RUN.
--   3. Atau lebih mudah: halaman Pengaturan -> tombol "Bangun Semua Tabel Otomatis".
--
-- Skrip ini IDEMPOTEN: boleh dijalankan berulang kali. Pembuatan foreign key
-- dibungkus penangkap kesalahan, jadi tidak pernah error walau masih ada baris
-- yatim (baris anak yang baris induknya belum ikut disinkronkan ke cloud).
-- Jalankan ulang skrip ini setelah data cloud terisi untuk menegakkan relasi.
--
-- Kolom disamakan dengan skema MySQL lokal (database/migrations) supaya
-- sinkronisasi memakai id yang sama (client-generated) dan tidak menumpuk
-- duplikat. Password & remember_token SENGAJA tidak dicerminkan ke cloud.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. users (identitas mahasiswa; kolom rahasia tidak dicerminkan ke cloud)
-- ---------------------------------------------------------------------------
create table if not exists public.users (
    id                bigint primary key,
    name              text not null,
    email             text not null unique,
    google_id         varchar(64),
    nim               varchar(20),
    campus            text not null default 'Universitas Bina Sarana Informatika',
    avatar            text,
    diet_preferences  jsonb,
    cooking_skill     text not null default 'Menengah',
    allergies         text,
    email_verified_at timestamptz,
    created_at        timestamptz,
    updated_at        timestamptz
);

create index if not exists users_email_idx on public.users (email);
create index if not exists users_google_id_idx on public.users (google_id);

-- ---------------------------------------------------------------------------
-- 2. ingredients (master bahan dapur)
-- ---------------------------------------------------------------------------
create table if not exists public.ingredients (
    id                       bigint primary key,
    name                     text not null unique,
    category                 text not null default 'Lainnya',
    region_origin            text,
    default_unit             text not null default 'gram',
    typical_shelf_life_days  integer not null default 7,
    calories_per_100g        integer,
    icon                     text,
    image_url                text,
    description              text,
    created_at               timestamptz,
    updated_at               timestamptz
);

create index if not exists ingredients_category_idx on public.ingredients (category);
create index if not exists ingredients_region_origin_idx on public.ingredients (region_origin);

-- ---------------------------------------------------------------------------
-- 3. ingredient_requests (riwayat deteksi foto / input bahan mentah)
-- ---------------------------------------------------------------------------
create table if not exists public.ingredient_requests (
    id                   bigint primary key,
    user_id              bigint,
    raw_input            text,
    image_path           text,
    supabase_image_url   text,
    source_type          text not null default 'manual',
    detected_ingredients jsonb not null default '[]'::jsonb,
    created_at           timestamptz,
    updated_at           timestamptz
);

create index if not exists ingredient_requests_user_id_idx on public.ingredient_requests (user_id);
create index if not exists ingredient_requests_source_type_idx on public.ingredient_requests (source_type);

-- ---------------------------------------------------------------------------
-- 4. kitchen_inventories (stok dapur; tabel lama cukup dilengkapi kolomnya)
-- ---------------------------------------------------------------------------
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

-- Bahan kedaluwarsa lebih dari 1 hari diarsipkan: tetap terlihat, tidak dipakai generator resep.
alter table public.kitchen_inventories add column if not exists archived_at timestamptz;

-- Tipe kunci disamakan dengan tabel induk (bigint) supaya relasi bisa dibuat.
alter table public.kitchen_inventories alter column user_id type bigint;
alter table public.kitchen_inventories alter column ingredient_id type bigint;

create index if not exists kitchen_inventories_user_id_idx on public.kitchen_inventories (user_id);
create index if not exists kitchen_inventories_expiration_date_idx on public.kitchen_inventories (expiration_date);
create index if not exists kitchen_inventories_status_idx on public.kitchen_inventories (status);
create index if not exists kitchen_inventories_archived_at_idx on public.kitchen_inventories (archived_at);
create index if not exists kitchen_inventories_ingredient_id_idx on public.kitchen_inventories (ingredient_id);

-- ---------------------------------------------------------------------------
-- 5. recipes_cache (hasil resep AI disimpan agar hemat kuota Gemini)
-- ---------------------------------------------------------------------------
create table if not exists public.recipes_cache (
    id                    bigint primary key,
    user_id               bigint,
    title                 text not null,
    slug                  text,
    description           text,
    cooking_time_minutes  integer not null default 30,
    difficulty            text not null default 'Mudah',
    servings              integer not null default 2,
    cuisine_type          text,
    region                text,
    image_url             text,
    meal_type             text not null default 'Makan Utama',
    ingredient_hash       varchar(64) not null,
    used_ingredients      jsonb not null default '[]'::jsonb,
    missing_ingredients   jsonb,
    instructions          jsonb not null default '[]'::jsonb,
    nutrition_facts       jsonb,
    chef_tips             text,
    tags                  jsonb,
    source                text not null default 'gemini',
    is_demo_fallback      boolean not null default false,
    is_favorite           boolean not null default false,
    created_at            timestamptz,
    updated_at            timestamptz
);

create index if not exists recipes_cache_user_id_idx on public.recipes_cache (user_id);
create index if not exists recipes_cache_ingredient_hash_idx on public.recipes_cache (ingredient_hash);
create index if not exists recipes_cache_cuisine_type_idx on public.recipes_cache (cuisine_type);
create index if not exists recipes_cache_region_idx on public.recipes_cache (region);

-- ---------------------------------------------------------------------------
-- 6. recipes (resep hasil request bahan tertentu)
-- ---------------------------------------------------------------------------
create table if not exists public.recipes (
    id                     bigint primary key,
    user_id                bigint,
    request_id             bigint,
    title                  text not null,
    slug                   text,
    variant_type           text not null default 'Autentik',
    cuisine_type           text not null default 'Nusantara',
    description            text,
    ingredient_needed      jsonb not null default '[]'::jsonb,
    missing_ingredients    jsonb,
    instructions           jsonb not null default '[]'::jsonb,
    estimated_time         integer not null default 30,
    difficulty             text not null default 'Mudah',
    servings               integer not null default 2,
    nutrition_facts        jsonb,
    substitute_suggestions jsonb,
    chef_tips              text,
    image_url              text,
    is_saved               boolean not null default false,
    created_at             timestamptz,
    updated_at             timestamptz
);

create index if not exists recipes_user_id_idx on public.recipes (user_id);
create index if not exists recipes_request_id_idx on public.recipes (request_id);
create index if not exists recipes_cuisine_type_idx on public.recipes (cuisine_type);

-- ---------------------------------------------------------------------------
-- 7. recipe_substitutions (catatan penggantian bahan)
-- ---------------------------------------------------------------------------
create table if not exists public.recipe_substitutions (
    id                    bigint primary key,
    user_id               bigint,
    recipe_id             bigint,
    original_ingredient   text not null,
    substitute_ingredient text not null,
    ratio_adjustment      text,
    culinary_notes        text,
    created_at            timestamptz,
    updated_at            timestamptz
);

create index if not exists recipe_substitutions_user_id_idx on public.recipe_substitutions (user_id);
create index if not exists recipe_substitutions_recipe_id_idx on public.recipe_substitutions (recipe_id);

-- ---------------------------------------------------------------------------
-- 8. ai_logs (jejak pemakaian Gemini untuk laporan & audit kuota)
-- ---------------------------------------------------------------------------
create table if not exists public.ai_logs (
    id                bigint primary key,
    user_id           bigint,
    action_type       text not null,
    model_used        text not null default 'gemini-3-flash-preview',
    prompt_summary    text,
    prompt_tokens     integer,
    response_tokens   integer,
    total_tokens      integer,
    execution_time_ms integer not null default 0,
    status            text not null default 'success',
    error_message     text,
    raw_response      text,
    created_at        timestamptz,
    updated_at        timestamptz
);

create index if not exists ai_logs_user_id_idx on public.ai_logs (user_id);
create index if not exists ai_logs_action_type_idx on public.ai_logs (action_type);
create index if not exists ai_logs_status_idx on public.ai_logs (status);
create index if not exists ai_logs_created_at_idx on public.ai_logs (created_at);

-- ---------------------------------------------------------------------------
-- 9. system_settings (pengaturan aplikasi non-rahasia)
-- ---------------------------------------------------------------------------
create table if not exists public.system_settings (
    id          bigint primary key,
    key         text not null unique,
    value       text,
    description text,
    created_at  timestamptz,
    updated_at  timestamptz
);

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

-- 3. Relasi (foreign key) supaya ERD Schema Visualizer terisi ------------------
-- Relasi dibuat "not valid" lebih dulu agar baris lama yang yatim tidak membuat
-- skrip gagal, lalu langsung divalidasi bila data induknya sudah lengkap.
do $$
declare
    rel record;
begin
    for rel in select * from (values
        ('kitchen_inventories'::text,  'fk_cloud_inventory_user'::text,         'user_id'::text,  'users'::text,           'cascade'::text),
        ('kitchen_inventories',        'fk_cloud_inventory_ingredient',         'ingredient_id',  'ingredients',           'set null'),
        ('ingredient_requests',        'fk_cloud_request_user',                 'user_id',        'users',                 'cascade'),
        ('recipes',                    'fk_cloud_recipe_user',                  'user_id',        'users',                 'cascade'),
        ('recipes',                    'fk_cloud_recipe_request',               'request_id',     'ingredient_requests',   'set null'),
        ('recipes_cache',              'fk_cloud_recipe_cache_user',            'user_id',        'users',                 'cascade'),
        ('recipe_substitutions',       'fk_cloud_substitution_user',            'user_id',        'users',                 'cascade'),
        ('recipe_substitutions',       'fk_cloud_substitution_recipe',          'recipe_id',      'recipes_cache',         'cascade'),
        ('ai_logs',                    'fk_cloud_ai_log_user',                  'user_id',        'users',                 'cascade')
    ) as t(tbl, con, col, ref_tbl, on_delete) loop
        begin
            execute format('alter table public.%I drop constraint if exists %I', rel.tbl, rel.con);
            execute format(
                'alter table public.%I add constraint %I foreign key (%I) references public.%I(id) on delete %s not valid',
                rel.tbl, rel.con, rel.col, rel.ref_tbl, upper(rel.on_delete)
            );
            begin
                execute format('alter table public.%I validate constraint %I', rel.tbl, rel.con);
                raise notice 'Relasi %.% -> % valid.', rel.tbl, rel.con, rel.ref_tbl;
            exception when foreign_key_violation then
                raise notice 'Relasi %.% dibuat tapi belum valid: klik "Sinkronkan Data Cloud" di halaman Pengaturan, lalu jalankan ulang skrip ini.',
                    rel.tbl, rel.con;
            end;
        exception when others then
            raise notice 'Relasi %.% dilewati: %', rel.tbl, rel.con, sqlerrm;
        end;
    end loop;
end $$;

-- 4. Row Level Security -------------------------------------------------------
-- Aplikasi menulis memakai service key (bypass RLS). Key publik/anon hanya boleh
-- membaca, jadi tidak bisa menyisipkan atau menghapus data.
do $$
declare
    t text;
begin
    foreach t in array array[
        'users', 'ingredients', 'ingredient_requests', 'kitchen_inventories',
        'recipes_cache', 'recipes', 'recipe_substitutions', 'ai_logs', 'system_settings'
    ] loop
        execute format('alter table public.%I enable row level security', t);

        execute format('drop policy if exists %I on public.%I', 'anon boleh membaca ' || t, t);
        execute format(
            'create policy %I on public.%I for select to anon using (true)',
            'anon boleh membaca ' || t, t
        );

        execute format('drop policy if exists %I on public.%I', 'layanan boleh menulis ' || t, t);
        execute format(
            'create policy %I on public.%I for all to service_role using (true) with check (true)',
            'layanan boleh menulis ' || t, t
        );
    end loop;
end $$;

-- 5. Bucket Storage untuk foto bahan ------------------------------------------
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

-- 6. Pengecekan singkat --------------------------------------------------------
-- Daftar tabel cloud beserta jumlah relasinya (inilah yang digambar ERD).
select
    t.table_name as tabel,
    (select count(*) from pg_constraint c
      where c.conrelid = ('public.' || t.table_name)::regclass and c.contype = 'f') as jumlah_relasi
from information_schema.tables t
where t.table_schema = 'public'
order by t.table_name;

select id, public, file_size_limit from storage.buckets where id = 'ingredients';
