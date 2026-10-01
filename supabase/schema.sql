-- =====================================================================
-- Skema database MBKM (jalankan di Supabase: SQL Editor > New query)
-- =====================================================================

-- ---------- Tipe & fungsi umum ----------
do $$ begin
  create type public.user_type as enum ('admin', 'dosen', 'mahasiswa');
exception when duplicate_object then null; end $$;

create or replace function public.set_updated_at() returns trigger
language plpgsql set search_path = public as $$
begin new.updated_at = now(); return new; end $$;

-- ---------- Master role ----------
-- allowed_user_types: tipe akun yang boleh memegang role tsb
create table if not exists public.roles (
  id                 smallint generated always as identity primary key,
  code               text not null unique check (code ~ '^[a-z0-9_]+$'),
  name               text not null,
  description        text,
  allowed_user_types text[] not null default '{dosen}'
                     check (allowed_user_types <@ array['admin','dosen','mahasiswa']
                            and cardinality(allowed_user_types) > 0),
  is_system          boolean not null default false,  -- role bawaan: tidak bisa dihapus
  created_at         timestamptz not null default now()
);

insert into public.roles (code, name, description, allowed_user_types, is_system) values
  ('admin',          'Admin',          'Mengelola user dan role',              '{admin,dosen}', true),
  ('mahasiswa',      'Mahasiswa',      'Peserta program MBKM',                 '{mahasiswa}',   true),
  ('tim_mbkm',       'Tim MBKM',       'Tim pengelola program MBKM',           '{dosen}',       true),
  ('gpm',            'GPM',            'Gugus Penjaminan Mutu',                '{dosen}',       true),
  ('penguji_semhas', 'Penguji Semhas', 'Penguji seminar hasil',                '{dosen}',       true),
  ('dpl',            'DPL',            'Dosen Pembimbing Lapangan',            '{dosen}',       true),
  ('wakil_dekan_1',  'Wakil Dekan 1',  'Wakil Dekan bidang akademik',          '{dosen}',       true)
on conflict (code) do nothing;

-- ---------- Akun (identitas umum) ----------
create table if not exists public.users (
  id            uuid primary key default gen_random_uuid(),
  nama          text not null check (length(trim(nama)) >= 2),
  alamat        text,
  email         text not null,
  password_hash text not null,
  user_type     public.user_type not null,
  is_active     boolean not null default true,
  created_at    timestamptz not null default now(),
  updated_at    timestamptz not null default now()
);
create unique index if not exists users_email_key on public.users (lower(email));
create index if not exists users_user_type_idx on public.users (user_type);

drop trigger if exists trg_users_updated_at on public.users;
create trigger trg_users_updated_at before update on public.users
  for each row execute function public.set_updated_at();

-- ---------- Profil khusus ----------
create table if not exists public.mahasiswa_profiles (
  user_id  uuid primary key references public.users(id) on delete cascade,
  nim      text not null unique,
  prodi    text,
  angkatan smallint check (angkatan between 1990 and 2100)
);

create table if not exists public.dosen_profiles (
  user_id uuid primary key references public.users(id) on delete cascade,
  nip     text not null unique
);

-- Profil hanya boleh menempel ke akun bertipe yang sesuai
create or replace function public.check_profile_user_type() returns trigger
language plpgsql set search_path = public as $$
declare v_expected text; v_actual text;
begin
  v_expected := case tg_table_name
                  when 'mahasiswa_profiles' then 'mahasiswa'
                  when 'dosen_profiles'     then 'dosen' end;
  select user_type::text into v_actual from public.users where id = new.user_id;
  if v_actual is distinct from v_expected then
    raise exception 'Profil % hanya untuk akun bertipe %', tg_table_name, v_expected;
  end if;
  return new;
end $$;

drop trigger if exists trg_mhs_profile_check on public.mahasiswa_profiles;
create trigger trg_mhs_profile_check before insert or update on public.mahasiswa_profiles
  for each row execute function public.check_profile_user_type();
drop trigger if exists trg_dosen_profile_check on public.dosen_profiles;
create trigger trg_dosen_profile_check before insert or update on public.dosen_profiles
  for each row execute function public.check_profile_user_type();

-- ---------- Pivot user <-> role (satu user bisa banyak role) ----------
create table if not exists public.user_roles (
  user_id     uuid     not null references public.users(id) on delete cascade,
  role_id     smallint not null references public.roles(id) on delete restrict,
  assigned_by uuid              references public.users(id) on delete set null,
  assigned_at timestamptz not null default now(),
  primary key (user_id, role_id)
);
create index if not exists user_roles_role_id_idx on public.user_roles (role_id);

-- Role hanya boleh diberikan ke tipe akun yang diizinkan
create or replace function public.check_role_user_type() returns trigger
language plpgsql set search_path = public as $$
declare v_type text; v_allowed text[]; v_name text;
begin
  select user_type::text into v_type from public.users where id = new.user_id;
  select allowed_user_types, name into v_allowed, v_name from public.roles where id = new.role_id;
  if not (v_type = any(v_allowed)) then
    raise exception 'Role "%" tidak dapat diberikan ke akun bertipe %', v_name, v_type;
  end if;
  return new;
end $$;

drop trigger if exists trg_user_roles_check on public.user_roles;
create trigger trg_user_roles_check before insert or update on public.user_roles
  for each row execute function public.check_role_user_type();

-- Ganti seluruh role user sekaligus (atomic) - dipakai form checkbox role
create or replace function public.set_user_roles(
  p_user_id uuid, p_role_ids smallint[], p_assigned_by uuid
) returns void language plpgsql set search_path = public as $$
begin
  delete from public.user_roles
   where user_id = p_user_id and role_id <> all (coalesce(p_role_ids, '{}'));
  insert into public.user_roles (user_id, role_id, assigned_by)
  select p_user_id, r, p_assigned_by from unnest(coalesce(p_role_ids, '{}')) r
  on conflict (user_id, role_id) do nothing;
end $$;

-- ---------- Keamanan ----------
-- API memakai service_role (bypass RLS). RLS tanpa policy = anon/authenticated
-- tidak bisa akses langsung lewat REST Supabase.
alter table public.roles              enable row level security;
alter table public.users              enable row level security;
alter table public.mahasiswa_profiles enable row level security;
alter table public.dosen_profiles     enable row level security;
alter table public.user_roles         enable row level security;

revoke all on public.roles, public.users, public.mahasiswa_profiles,
              public.dosen_profiles, public.user_roles from anon, authenticated;
revoke execute on function public.set_user_roles(uuid, smallint[], uuid) from public, anon, authenticated;

-- =====================================================================
-- Akademik: Program Studi, CPL, Mata Kuliah, CPMK
-- =====================================================================

-- ---------- Master program studi (isi dropdown "Program Studi") ----------
create table if not exists public.program_studi (
  id   smallint generated always as identity primary key,
  kode text not null unique,
  nama text not null unique
);

insert into public.program_studi (kode, nama) values
  ('TI', 'Teknologi Informasi'),
  ('SI', 'Sistem Informasi'),
  ('IF', 'Informatika')
on conflict (kode) do nothing;

-- ---------- CPL (Capaian Pembelajaran Lulusan) ----------
create table if not exists public.cpl (
  id               uuid primary key default gen_random_uuid(),
  program_studi_id smallint not null references public.program_studi(id) on delete restrict,
  kode             text not null check (length(trim(kode)) > 0),
  deskripsi        text not null check (length(trim(deskripsi)) > 0),
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now(),
  unique (program_studi_id, kode)
);

drop trigger if exists trg_cpl_updated_at on public.cpl;
create trigger trg_cpl_updated_at before update on public.cpl
  for each row execute function public.set_updated_at();

-- ---------- Mata kuliah ----------
create table if not exists public.mata_kuliah (
  id               uuid primary key default gen_random_uuid(),
  program_studi_id smallint not null references public.program_studi(id) on delete restrict,
  kode             text not null check (length(trim(kode)) > 0),
  nama             text not null check (length(trim(nama)) >= 2),
  cpl_id           uuid not null references public.cpl(id) on delete restrict,
  sks              smallint not null check (sks between 1 and 6),
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now(),
  unique (program_studi_id, kode)
);
create index if not exists mata_kuliah_cpl_id_idx on public.mata_kuliah (cpl_id);

drop trigger if exists trg_mata_kuliah_updated_at on public.mata_kuliah;
create trigger trg_mata_kuliah_updated_at before update on public.mata_kuliah
  for each row execute function public.set_updated_at();

-- ---------- CPMK (deskripsi bebas, jumlahnya tidak dibatasi per mata kuliah) ----------
create table if not exists public.cpmk (
  id             uuid primary key default gen_random_uuid(),
  mata_kuliah_id uuid not null references public.mata_kuliah(id) on delete cascade,
  urutan         smallint not null,
  deskripsi      text not null check (length(trim(deskripsi)) > 0),
  unique (mata_kuliah_id, urutan)
);

-- CPL yang dipilih harus milik program studi yang sama dengan mata kuliahnya
create or replace function public.check_mk_cpl_prodi() returns trigger
language plpgsql set search_path = public as $$
declare v_prodi smallint;
begin
  select program_studi_id into v_prodi from public.cpl where id = new.cpl_id;
  if v_prodi is distinct from new.program_studi_id then
    raise exception 'CPL tidak sesuai dengan program studi mata kuliah';
  end if;
  return new;
end $$;

drop trigger if exists trg_mk_cpl_check on public.mata_kuliah;
create trigger trg_mk_cpl_check before insert or update on public.mata_kuliah
  for each row execute function public.check_mk_cpl_prodi();

-- Program studi CPL tidak boleh diubah kalau sudah dipakai mata kuliah
create or replace function public.check_cpl_prodi_change() returns trigger
language plpgsql set search_path = public as $$
begin
  if new.program_studi_id is distinct from old.program_studi_id
     and exists (select 1 from public.mata_kuliah where cpl_id = old.id) then
    raise exception 'Program studi CPL tidak dapat diubah karena sudah dipakai mata kuliah';
  end if;
  return new;
end $$;

drop trigger if exists trg_cpl_prodi_change on public.cpl;
create trigger trg_cpl_prodi_change before update on public.cpl
  for each row execute function public.check_cpl_prodi_change();

-- Ganti seluruh CPMK sebuah mata kuliah sekaligus (atomic), urutan mengikuti array
create or replace function public.set_cpmk(
  p_mata_kuliah_id uuid, p_deskripsi text[]
) returns void language plpgsql set search_path = public as $$
begin
  delete from public.cpmk where mata_kuliah_id = p_mata_kuliah_id;
  insert into public.cpmk (mata_kuliah_id, urutan, deskripsi)
  select p_mata_kuliah_id, t.ord::smallint, t.d
    from unnest(coalesce(p_deskripsi, '{}')) with ordinality as t(d, ord);
end $$;

-- ---------- Keamanan (sama seperti tabel lain) ----------
alter table public.program_studi enable row level security;
alter table public.cpl           enable row level security;
alter table public.mata_kuliah   enable row level security;
alter table public.cpmk          enable row level security;

revoke all on public.program_studi, public.cpl, public.mata_kuliah, public.cpmk
  from anon, authenticated;
revoke execute on function public.set_cpmk(uuid, text[]) from public, anon, authenticated;

-- =====================================================================
-- Pemaketan / Paket Konversi (Role / Skill MBKM)
-- =====================================================================

-- ---------- Paket Konversi ----------
create table if not exists public.paket_konversi (
  id         uuid primary key default gen_random_uuid(),
  nama       text not null check (length(trim(nama)) >= 2), -- Nama Plotting / Paket (cth: UI/UX DESIGN)
  deskripsi  text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

drop trigger if exists trg_paket_konversi_updated_at on public.paket_konversi;
create trigger trg_paket_konversi_updated_at before update on public.paket_konversi
  for each row execute function public.set_updated_at();

-- ---------- Pivot Paket Konversi <-> Mata Kuliah ----------
create table if not exists public.paket_konversi_item (
  paket_konversi_id uuid not null references public.paket_konversi(id) on delete cascade,
  mata_kuliah_id    uuid not null references public.mata_kuliah(id) on delete restrict,
  created_at        timestamptz not null default now(),
  primary key (paket_konversi_id, mata_kuliah_id)
);
create index if not exists paket_konversi_item_mk_idx on public.paket_konversi_item (mata_kuliah_id);

-- Ganti seluruh item mata kuliah paket konversi sekaligus (atomic)
create or replace function public.set_paket_konversi_items(
  p_paket_id uuid, p_mata_kuliah_ids uuid[]
) returns void language plpgsql set search_path = public as $$
begin
  delete from public.paket_konversi_item
   where paket_konversi_id = p_paket_id
     and mata_kuliah_id <> all (coalesce(p_mata_kuliah_ids, '{}'));
  insert into public.paket_konversi_item (paket_konversi_id, mata_kuliah_id)
  select p_paket_id, m from unnest(coalesce(p_mata_kuliah_ids, '{}')) m
  on conflict (paket_konversi_id, mata_kuliah_id) do nothing;
end $$;

-- ---------- Keamanan ----------
alter table public.paket_konversi      enable row level security;
alter table public.paket_konversi_item enable row level security;

revoke all on public.paket_konversi, public.paket_konversi_item from anon, authenticated;
revoke execute on function public.set_paket_konversi_items(uuid, uuid[]) from public, anon, authenticated;

