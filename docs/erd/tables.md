# ERD Fase 1 — Daftar Tabel dan Kolom

Dihasilkan dari satu sumber definisi (lihat README). Konvensi skema: Architecture §3.1. `location_id` wajib di setiap tabel transaksional (FR1.7).

## Identity

### `users`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `name` | varchar(150) |  |
| `email` | varchar(190) | unique |
| `password` | varchar(255) | hash |
| `email_verified_at` | timestamptz null |  |
| `two_factor_secret` | text null | terenkripsi |
| `two_factor_recovery_codes` | text null | terenkripsi |
| `two_factor_confirmed_at` | timestamptz null |  |
| `is_active` | boolean | default true; nonaktif = sesi dicabut (US-1.24) |
| `last_login_at` | timestamptz null |  |
| `remember_token` | varchar(100) null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(email)

### `roles`

spatie/laravel-permission

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `name` | varchar(80) |  |
| `guard_name` | varchar(40) |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(name, guard_name)

### `permissions`

spatie/laravel-permission

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `name` | varchar(120) | permission per aksi, PRD 4.3 |
| `guard_name` | varchar(40) |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(name, guard_name)

### `role_has_permissions` _(sumber: Framework)_

spatie/laravel-permission (pivot)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `permission_id` | bigint | FK permissions |
| `role_id` | bigint | FK roles |

**Constraint:** PK(permission_id, role_id)

**Foreign key:** `permission_id` → `permissions`, `role_id` → `roles`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `model_has_roles`

spatie/laravel-permission. Index (model_id, model_type)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `role_id` | bigint | FK roles |
| `model_type` | varchar(120) |  |
| `model_id` | bigint |  |

**Constraint:** PK(role_id, model_id, model_type)

**Foreign key:** `role_id` → `roles`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `model_has_permissions` _(sumber: Framework)_

spatie/laravel-permission (izin langsung; dipakai minimal)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `permission_id` | bigint | FK permissions |
| `model_type` | varchar(120) |  |
| `model_id` | bigint |  |

**Constraint:** PK(permission_id, model_id, model_type)

**Foreign key:** `permission_id` → `permissions`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `location_user`

Dasar LocationScope (ADR-003). Index (location_id)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `user_id` | bigint | FK users |
| `location_id` | bigint | FK locations |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** PK(user_id, location_id)

**Foreign key:** `user_id` → `users`, `location_id` → `locations`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `personal_access_tokens` _(sumber: Framework)_

Sanctum

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `tokenable_type` | varchar(120) |  |
| `tokenable_id` | bigint | pos_source / user |
| `name` | varchar(120) |  |
| `token` | char(64) | hash; nilai mentah tampil sekali (IN-06) |
| `abilities` | text null | mis. pos:write |
| `last_used_at` | timestamptz null |  |
| `expires_at` | timestamptz null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(token)

### `activity_log`

Tanpa update/delete dari aplikasi (L-01): hak DB dan trigger. Index (subject_type, subject_id), (causer_type, causer_id), (created_at)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `log_name` | varchar(80) null |  |
| `description` | text |  |
| `subject_type` | varchar(120) null |  |
| `subject_id` | bigint null |  |
| `event` | varchar(60) null |  |
| `causer_type` | varchar(120) null |  |
| `causer_id` | bigint null |  |
| `location_id` | bigint null | FK locations (konteks) |
| `ip_address` | inet null | US-1.25 |
| `properties` | jsonb null | nilai sebelum/sesudah; tanpa data sensitif |
| `batch_uuid` | uuid null |  |
| `created_at` | timestamptz |  |

**Foreign key:** `location_id` → `locations`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

## Master

### `locations`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `code` | varchar(20) | unique |
| `name` | varchar(150) |  |
| `timezone` | varchar(60) | IANA, mis. Asia/Jakarta (I-01) |
| `opening_time` | time null |  |
| `closing_time` | time null |  |
| `cutover_at` | timestamptz null | MG-03; transaksi POS sebelumnya diabaikan |
| `is_active` | boolean | default true |
| `deleted_at` | timestamptz null | soft delete (master data) |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(code)

### `categories`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `parent_id` | bigint null | FK categories |
| `code` | varchar(30) |  |
| `name` | varchar(150) |  |
| `deleted_at` | timestamptz null | soft delete (master data) |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(code)

**Foreign key:** `parent_id` → `categories`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `brands`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `name` | varchar(150) |  |
| `deleted_at` | timestamptz null | soft delete (master data) |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(name)

### `suppliers`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `code` | varchar(30) |  |
| `name` | varchar(150) |  |
| `contact_name` | varchar(150) null |  |
| `phone` | varchar(40) null |  |
| `email` | varchar(190) null |  |
| `address` | text null |  |
| `deleted_at` | timestamptz null | soft delete (master data) |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(code)

### `units`

Satu satuan dasar per SKU di Fase 1 (konversi Fase 2)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `code` | varchar(20) | mis. pcs, kg |
| `name` | varchar(60) |  |
| `allows_decimal` | boolean | default false; qty desimal 3 digit hanya bila true |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(code)

### `products`

Index (category_id), (brand_id), pencarian nama (trigram, T1.10)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `category_id` | bigint | FK categories |
| `brand_id` | bigint null | FK brands |
| `default_supplier_id` | bigint null | FK suppliers |
| `name` | varchar(190) |  |
| `description` | text null |  |
| `deleted_at` | timestamptz null | soft delete (master data) |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Foreign key:** `category_id` → `categories`, `brand_id` → `brands`, `default_supplier_id` → `suppliers`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `product_variants`

Index (product_id), (status)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `product_id` | bigint | FK products |
| `unit_id` | bigint | FK units |
| `sku` | varchar(60) | unique |
| `name` | varchar(190) | turunan atribut |
| `status` | varchar(20) | active | discontinued (CHECK) |
| `standard_cost` | numeric(14,4) null |  |
| `sale_price` | numeric(14,4) null |  |
| `deleted_at` | timestamptz null | soft delete (master data) |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(sku); CHECK(status IN ('active','discontinued'))

**Foreign key:** `product_id` → `products`, `unit_id` → `units`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `variant_attributes`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `product_variant_id` | bigint | FK product_variants |
| `name` | varchar(60) | ukuran, warna, kemasan, ... |
| `value` | varchar(120) |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(product_variant_id, name)

**Foreign key:** `product_variant_id` → `product_variants`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `barcodes`

Pencarian scanner P-02. Partial unique (product_variant_id) WHERE is_primary

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `product_variant_id` | bigint | FK product_variants |
| `barcode` | varchar(64) | unique |
| `symbology` | varchar(20) | EAN13 | UPCA | CODE128 | OTHER (CHECK) |
| `is_primary` | boolean | default false |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(barcode)

**Foreign key:** `product_variant_id` → `product_variants`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `variant_location_settings`

US-1.04

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `product_variant_id` | bigint | FK product_variants |
| `reorder_point` | numeric(14,3) | default 0 |
| `min_qty` | numeric(14,3) | default 0 |
| `is_stocked` | boolean | default true |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(location_id, product_variant_id)

**Foreign key:** `location_id` → `locations`, `product_variant_id` → `product_variants`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

## Inventory

### `reason_codes`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `code` | varchar(30) | unique |
| `name` | varchar(120) |  |
| `applies_to` | varchar(30) | adjustment | supplier_return | count (CHECK) |
| `requires_attachment` | boolean | default false |
| `is_active` | boolean | default true |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(code)

### `stock_levels`

Dikunci `SELECT ... FOR UPDATE` saat posting

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `product_variant_id` | bigint | FK product_variants |
| `on_hand` | numeric(14,3) | cache; = SUM(ledger) (D-01) |
| `avg_cost` | numeric(14,4) | weighted average (ADR-008) |
| `last_movement_id` | bigint null | FK stock_movements |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(location_id, product_variant_id)

**Foreign key:** `location_id` → `locations`, `product_variant_id` → `product_variants`, `last_movement_id` → `stock_movements`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `stock_movements`

APPEND-ONLY: trigger BEFORE UPDATE OR DELETE menolak (ADR-002). Index (location_id, product_variant_id, occurred_at), (reference_type, reference_id). Partisi bulanan di Fase 7

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `product_variant_id` | bigint | FK product_variants |
| `type` | varchar(30) | 10 tipe PRD 6.1.4 (CHECK = enum PHP) |
| `quantity` | numeric(14,3) | signed; CHECK (quantity <> 0) |
| `unit_cost` | numeric(14,4) null | wajib untuk movement masuk |
| `balance_after` | numeric(14,3) | saldo setelah movement |
| `occurred_at` | timestamptz | waktu bisnis |
| `reference_type` | varchar(40) | dokumen sumber |
| `reference_id` | bigint |  |
| `reason_code_id` | bigint null | FK reason_codes |
| `reversed_movement_id` | bigint null | FK stock_movements; unique |
| `created_by` | bigint null | FK users (null untuk job) |
| `created_at` | timestamptz |  |

**Constraint:** UNIQUE(reversed_movement_id); CHECK(quantity <> 0)

**Foreign key:** `location_id` → `locations`, `product_variant_id` → `product_variants`, `reason_code_id` → `reason_codes`, `reversed_movement_id` → `stock_movements`, `created_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `opening_balances`

Satu saldo awal per lokasi (MG-01)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `number` | varchar(40) | unique |
| `status` | varchar(20) | draft | approved | posted | rejected (CHECK) |
| `cutover_at` | timestamptz |  |
| `source_filename` | varchar(190) null |  |
| `created_by` | bigint | FK users |
| `approved_by` | bigint null | FK users; <> created_by (R-02) |
| `approved_at` | timestamptz null |  |
| `posted_at` | timestamptz null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(number); partial UNIQUE(location_id) WHERE status = 'posted'

**Foreign key:** `location_id` → `locations`, `created_by` → `users`, `approved_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `opening_balance_items`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `opening_balance_id` | bigint | FK opening_balances |
| `location_id` | bigint | FK locations |
| `product_variant_id` | bigint | FK product_variants |
| `quantity` | numeric(14,3) |  |
| `unit_cost` | numeric(14,4) |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(opening_balance_id, product_variant_id)

**Foreign key:** `opening_balance_id` → `opening_balances`, `location_id` → `locations`, `product_variant_id` → `product_variants`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `goods_receipts`

Index (location_id, received_at)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `number` | varchar(40) | unique |
| `supplier_id` | bigint null | FK suppliers |
| `status` | varchar(20) | draft | posted | cancelled (CHECK) |
| `supplier_ref` | varchar(80) null | no. surat jalan |
| `received_at` | timestamptz |  |
| `created_by` | bigint | FK users |
| `posted_at` | timestamptz null |  |
| `notes` | text null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(number)

**Foreign key:** `location_id` → `locations`, `supplier_id` → `suppliers`, `created_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `goods_receipt_items`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `goods_receipt_id` | bigint | FK goods_receipts |
| `location_id` | bigint | FK locations |
| `product_variant_id` | bigint | FK product_variants |
| `quantity` | numeric(14,3) | > 0 |
| `unit_cost` | numeric(14,4) |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Foreign key:** `goods_receipt_id` → `goods_receipts`, `location_id` → `locations`, `product_variant_id` → `product_variants`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `transfers`

Dapat dilihat kedua lokasi (US-1.06)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations (asal) |
| `destination_location_id` | bigint | FK locations; <> location_id |
| `number` | varchar(40) | unique |
| `status` | varchar(20) | draft | in_transit | received | cancelled (CHECK) |
| `shipped_at` | timestamptz null |  |
| `received_at` | timestamptz null |  |
| `created_by` | bigint | FK users |
| `received_by` | bigint null | FK users |
| `notes` | text null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(number); CHECK(location_id <> destination_location_id)

**Foreign key:** `location_id` → `locations`, `destination_location_id` → `locations`, `created_by` → `users`, `received_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `transfer_items`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `transfer_id` | bigint | FK transfers |
| `location_id` | bigint | FK locations (asal) |
| `product_variant_id` | bigint | FK product_variants |
| `quantity_sent` | numeric(14,3) | > 0 |
| `quantity_received` | numeric(14,3) null | selisih = sent - received |
| `unit_cost` | numeric(14,4) | avg_cost saat kirim |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(transfer_id, product_variant_id)

**Foreign key:** `transfer_id` → `transfers`, `location_id` → `locations`, `product_variant_id` → `product_variants`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `returns`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `number` | varchar(40) | unique |
| `type` | varchar(20) | customer | supplier (CHECK) |
| `supplier_id` | bigint null | FK suppliers; wajib bila supplier |
| `reason_code_id` | bigint null | FK reason_codes; wajib bila supplier |
| `status` | varchar(20) | draft | posted | cancelled (CHECK) |
| `returned_at` | timestamptz |  |
| `created_by` | bigint | FK users |
| `posted_at` | timestamptz null |  |
| `notes` | text null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(number); CHECK(type <> 'supplier' OR (supplier_id IS NOT NULL AND reason_code_id IS NOT NULL))

**Foreign key:** `location_id` → `locations`, `supplier_id` → `suppliers`, `reason_code_id` → `reason_codes`, `created_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `return_items`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `return_id` | bigint | FK returns |
| `location_id` | bigint | FK locations |
| `product_variant_id` | bigint | FK product_variants |
| `quantity` | numeric(14,3) | > 0 |
| `unit_cost` | numeric(14,4) |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Foreign key:** `return_id` → `returns`, `location_id` → `locations`, `product_variant_id` → `product_variants`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `stock_adjustments`

Index (location_id, status) untuk daftar menunggu approval

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `number` | varchar(40) | unique |
| `reason_code_id` | bigint | FK reason_codes |
| `status` | varchar(20) | draft | submitted | approved | rejected | posted (CHECK) |
| `created_by` | bigint | FK users |
| `approved_by` | bigint null | FK users; <> created_by (R-02) |
| `approved_at` | timestamptz null |  |
| `rejection_note` | text null |  |
| `posted_at` | timestamptz null |  |
| `notes` | text null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(number)

**Foreign key:** `location_id` → `locations`, `reason_code_id` → `reason_codes`, `created_by` → `users`, `approved_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `stock_adjustment_items`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `stock_adjustment_id` | bigint | FK stock_adjustments |
| `location_id` | bigint | FK locations |
| `product_variant_id` | bigint | FK product_variants |
| `quantity` | numeric(14,3) | signed, <> 0 |
| `unit_cost` | numeric(14,4) null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Foreign key:** `stock_adjustment_id` → `stock_adjustments`, `location_id` → `locations`, `product_variant_id` → `product_variants`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `document_sequences` _(sumber: Tambahan)_

Nomor mis. GR-LOC01-2026-000123 unik dan berurutan meski paralel (T2.04)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `document_type` | varchar(10) | GR, TR, RT, ADJ, OB, SC |
| `year` | smallint |  |
| `last_number` | integer | dinaikkan dengan lock baris |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(location_id, document_type, year)

**Foreign key:** `location_id` → `locations`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `attachments` _(sumber: Tambahan)_

Lampiran bukti penerimaan/adjustment (US-1.05); diakses lewat signed URL. Index (attachable_type, attachable_id)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `attachable_type` | varchar(60) |  |
| `attachable_id` | bigint |  |
| `location_id` | bigint | FK locations |
| `disk` | varchar(30) |  |
| `path` | varchar(255) |  |
| `original_name` | varchar(190) |  |
| `mime_type` | varchar(100) |  |
| `size_bytes` | bigint |  |
| `uploaded_by` | bigint | FK users |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Foreign key:** `location_id` → `locations`, `uploaded_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

## POS

### `pos_sources`

Token API (Sanctum, ability pos:write) menempel ke sumber ini

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `name` | varchar(120) |  |
| `vendor` | varchar(80) null |  |
| `channel` | varchar(20) | file | api (CHECK) |
| `timezone` | varchar(60) |  |
| `is_active` | boolean | default true |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(location_id, name)

**Foreign key:** `location_id` → `locations`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `pos_column_mappings`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `pos_source_id` | bigint | FK pos_sources |
| `target_field` | varchar(40) | field internal, mis. external_id, sku, quantity |
| `source_column` | varchar(120) |  |
| `transform` | jsonb null | format tanggal, desimal, dll. |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(pos_source_id, target_field)

**Foreign key:** `pos_source_id` → `pos_sources`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `pos_import_batches`

Index (location_id, status, created_at)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `pos_source_id` | bigint | FK pos_sources |
| `location_id` | bigint | FK locations |
| `status` | varchar(20) | pending | processing | completed | failed (CHECK) |
| `file_hash` | char(64) null | SHA-256 (IN-01) |
| `original_filename` | varchar(190) null |  |
| `storage_path` | varchar(255) null | file asli di object storage |
| `payload` | jsonb null | payload API mentah (IN-07) |
| `total_rows` | integer | default 0 |
| `posted_rows` | integer | default 0 |
| `duplicate_rows` | integer | default 0 |
| `discrepancy_rows` | integer | default 0 |
| `failed_rows` | integer | default 0 |
| `last_chunk_offset` | integer | default 0; retry idempoten (IN-05) |
| `started_at` | timestamptz null |  |
| `finished_at` | timestamptz null |  |
| `created_by` | bigint null | FK users |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(pos_source_id, file_hash)

**Foreign key:** `pos_source_id` → `pos_sources`, `location_id` → `locations`, `created_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `pos_transactions`

Idempotensi transaksi (IN-01). Index (location_id, occurred_at)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `pos_source_id` | bigint | FK pos_sources |
| `location_id` | bigint | FK locations |
| `import_batch_id` | bigint | FK pos_import_batches |
| `external_id` | varchar(80) | id transaksi di POS |
| `type` | varchar(20) | sale | customer_return (CHECK) |
| `occurred_at` | timestamptz |  |
| `total_amount` | numeric(14,4) |  |
| `status` | varchar(20) | posted | discrepancy | ignored (CHECK) |
| `raw` | jsonb null | baris mentah |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(pos_source_id, location_id, external_id)

**Foreign key:** `pos_source_id` → `pos_sources`, `location_id` → `locations`, `import_batch_id` → `pos_import_batches`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `pos_transaction_lines`

Index (location_id, product_variant_id), (external_sku). ~5,5 jt baris/tahun

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `pos_transaction_id` | bigint | FK pos_transactions |
| `location_id` | bigint | FK locations |
| `line_no` | integer |  |
| `external_sku` | varchar(80) | barcode/SKU dari POS |
| `product_variant_id` | bigint null | FK product_variants; null bila belum terpetakan |
| `quantity` | numeric(14,3) |  |
| `unit_price` | numeric(14,4) |  |
| `discount` | numeric(14,4) | default 0 |
| `stock_movement_id` | bigint null | FK stock_movements |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(pos_transaction_id, line_no)

**Foreign key:** `pos_transaction_id` → `pos_transactions`, `location_id` → `locations`, `product_variant_id` → `product_variants`, `stock_movement_id` → `stock_movements`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `sku_mappings`

Urutan resolusi SKU: barcode, SKU varian, lalu tabel ini (IN-03)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `pos_source_id` | bigint | FK pos_sources |
| `external_sku` | varchar(80) |  |
| `product_variant_id` | bigint | FK product_variants |
| `created_by` | bigint | FK users |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(pos_source_id, external_sku)

**Foreign key:** `pos_source_id` → `pos_sources`, `product_variant_id` → `product_variants`, `created_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `pos_discrepancies`

Index (location_id, status, type)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `import_batch_id` | bigint | FK pos_import_batches |
| `pos_transaction_id` | bigint null | FK pos_transactions |
| `type` | varchar(30) | unmapped_sku | duplicate | negative_stock | price_mismatch | future_or_closed_hours | sequence_gap (CHECK) |
| `status` | varchar(20) | open | resolved | ignored (CHECK) |
| `details` | jsonb |  |
| `resolved_by` | bigint null | FK users |
| `resolved_at` | timestamptz null |  |
| `resolution_note` | text null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Foreign key:** `location_id` → `locations`, `import_batch_id` → `pos_import_batches`, `pos_transaction_id` → `pos_transactions`, `resolved_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

## Reconciliation

### `reconciliation_settings`

Exception bila melewati salah satu threshold (US-1.18)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `threshold_qty` | numeric(14,3) | default 5 |
| `threshold_pct` | numeric(7,3) | default 5 |
| `threshold_value` | numeric(14,4) | default 500000 |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(location_id)

**Foreign key:** `location_id` → `locations`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `stock_counts`

State machine: PRD 6.1.5

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint | FK locations |
| `number` | varchar(40) | unique |
| `type` | varchar(20) | full | cycle (CHECK) |
| `status` | varchar(20) | draft | counting | submitted | under_review | approved | posted | cancelled (CHECK) |
| `snapshot_at` | timestamptz null |  |
| `submitted_at` | timestamptz null |  |
| `created_by` | bigint | FK users |
| `approved_by` | bigint null | FK users; <> created_by (R-02) |
| `approved_at` | timestamptz null |  |
| `posted_at` | timestamptz null |  |
| `notes` | text null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(number)

**Foreign key:** `location_id` → `locations`, `created_by` → `users`, `approved_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `stock_count_lines`

Index (stock_count_id, is_exception)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `stock_count_id` | bigint | FK stock_counts |
| `location_id` | bigint | FK locations |
| `product_variant_id` | bigint | FK product_variants |
| `snapshot_qty` | numeric(14,3) |  |
| `expected_qty` | numeric(14,3) | snapshot + movement sampai waktu hitung |
| `counted_qty` | numeric(14,3) null |  |
| `variance_qty` | numeric(14,3) null | counted - expected |
| `variance_value` | numeric(14,4) null | variance_qty x avg_cost |
| `is_exception` | boolean | default false |
| `recount_requested` | boolean | default false |
| `notes` | text null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(stock_count_id, product_variant_id)

**Foreign key:** `stock_count_id` → `stock_counts`, `location_id` → `locations`, `product_variant_id` → `product_variants`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `stock_count_entries` _(sumber: Tambahan)_

Banyak penghitung per baris (US-1.16); counted_qty = hasil agregasi/recount

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `stock_count_line_id` | bigint | FK stock_count_lines |
| `location_id` | bigint | FK locations |
| `counted_by` | bigint | FK users |
| `quantity` | numeric(14,3) | >= 0 |
| `counted_at` | timestamptz |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Foreign key:** `stock_count_line_id` → `stock_count_lines`, `location_id` → `locations`, `counted_by` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `count_assignments`

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `stock_count_id` | bigint | FK stock_counts |
| `location_id` | bigint | FK locations |
| `user_id` | bigint | FK users |
| `zone` | varchar(60) null | area/rak/kategori |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Constraint:** UNIQUE(stock_count_id, user_id, zone)

**Foreign key:** `stock_count_id` → `stock_counts`, `location_id` → `locations`, `user_id` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

## Reporting

### `mv_daily_stock_summary`

Materialized view; REFRESH CONCURRENTLY tiap 5 menit (P-07)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `location_id` | bigint |  |
| `product_variant_id` | bigint |  |
| `summary_date` | date |  |
| `on_hand` | numeric(14,3) |  |
| `stock_value` | numeric(14,4) |  |

**Constraint:** UNIQUE INDEX(location_id, product_variant_id, summary_date)

### `mv_movement_by_type`

Materialized view; refresh tiap 5 menit

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `location_id` | bigint |  |
| `type` | varchar(30) |  |
| `movement_date` | date |  |
| `movement_count` | integer |  |
| `qty_in` | numeric(14,3) |  |
| `qty_out` | numeric(14,3) |  |
| `value` | numeric(14,4) |  |

**Constraint:** UNIQUE INDEX(location_id, type, movement_date)

### `exports` _(sumber: Tambahan)_

Laporan berat via queue (P-06); notifikasi ReportReady

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | bigint identity | PK |
| `location_id` | bigint null | FK locations |
| `user_id` | bigint | FK users |
| `report` | varchar(60) |  |
| `format` | varchar(10) | csv | xlsx | pdf (CHECK) |
| `status` | varchar(20) | pending | ready | failed |
| `disk` | varchar(30) null |  |
| `path` | varchar(255) null |  |
| `expires_at` | timestamptz null | dihapus `exports:prune` > 7 hari |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |

**Foreign key:** `location_id` → `locations`, `user_id` → `users`. `ON DELETE RESTRICT` (tanpa cascade ke ledger).

### `notifications` _(sumber: Framework)_

Notifikasi in-app (approval, import selesai, ReportReady)

| Kolom | Tipe | Keterangan |
| ----- | ---- | ---------- |
| `id` | uuid | PK |
| `type` | varchar(190) |  |
| `notifiable_type` | varchar(120) |  |
| `notifiable_id` | bigint |  |
| `data` | jsonb |  |
| `read_at` | timestamptz null |  |
| `created_at` | timestamptz |  |
| `updated_at` | timestamptz |  |
