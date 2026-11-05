# ERD Fase 1 (v1)

| Item | Detail |
| ---- | ------ |
| Versi | 1.0 — 6 November 2026 (T0.06) |
| Acuan | PRD §6.1.6, §6.1.4–6.1.5; Architecture §3 |
| Detail kolom | [tables.md](tables.md) |

## Konvensi

- PK `bigint generated always as identity`; nomor dokumen manusiawi di kolom `number`.
- Qty `numeric(14,3)`, uang/biaya `numeric(14,4)`, waktu `timestamptz` (UTC); `occurred_at` terpisah dari `created_at`.
- `location_id` wajib di setiap tabel transaksional; FK selalu didefinisikan dengan `ON DELETE RESTRICT`.
- Status = `varchar` + PHP enum + `CHECK`. Soft delete hanya untuk master data.
- `stock_movements` append-only (trigger); `stock_levels` cache; payload eksternal `jsonb`.

## Cakupan

Seluruh 42 tabel PRD §6.1.6 tercakup, ditambah tabel turunan. Total: **50 tabel/view** di bawah, ditambah tabel framework (sessions (driver Redis, tabel tidak dipakai); cache / cache_locks; jobs / job_batches / failed_jobs (queue di Redis; failed_jobs di DB); password_reset_tokens; migrations).

| Sumber | Jumlah | Arti |
| ------ | ------ | ---- |
| PRD | 42 | Ada di daftar PRD §6.1.6 |
| Framework | 4 | Dibutuhkan paket (spatie, Sanctum, notifikasi) |
| Tambahan | 4 | Diturunkan dari AC/Architecture: `document_sequences`, `attachments`, `stock_count_entries`, `exports` |

Tabel *Tambahan* belum ada di PRD §6.1.6; usulan memasukkannya ke PRD pada revisi berikut (perubahan PRD butuh persetujuan).

## Identity

`users`, `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`, `location_user`, `personal_access_tokens`, `activity_log`

```mermaid
erDiagram
    locations ||--o{ activity_log : "location_id"
    locations ||--o{ location_user : "location_id"
    permissions ||--o{ model_has_permissions : "permission_id"
    permissions ||--o{ role_has_permissions : "permission_id"
    roles ||--o{ model_has_roles : "role_id"
    roles ||--o{ role_has_permissions : "role_id"
    users ||--o{ location_user : "user_id"
    users {
        bigint id PK
        varchar email UK
    }
    roles {
        bigint id PK
    }
    permissions {
        bigint id PK
    }
    role_has_permissions {
        bigint permission_id FK
        bigint role_id FK
    }
    model_has_roles {
        bigint role_id FK
    }
    model_has_permissions {
        bigint permission_id FK
    }
    location_user {
        bigint user_id FK
        bigint location_id FK
    }
    personal_access_tokens {
        bigint id PK
    }
    activity_log {
        bigint id PK
        bigint location_id FK
    }
    locations {
        bigint id PK
    }
```

## Master

`locations`, `categories`, `brands`, `suppliers`, `units`, `products`, `product_variants`, `variant_attributes`, `barcodes`, `variant_location_settings`

```mermaid
erDiagram
    brands ||--o{ products : "brand_id"
    categories ||--o{ categories : "parent_id"
    categories ||--o{ products : "category_id"
    locations ||--o{ variant_location_settings : "location_id"
    product_variants ||--o{ barcodes : "product_variant_id"
    product_variants ||--o{ variant_attributes : "product_variant_id"
    product_variants ||--o{ variant_location_settings : "product_variant_id"
    products ||--o{ product_variants : "product_id"
    suppliers ||--o{ products : "default_supplier_id"
    units ||--o{ product_variants : "unit_id"
    locations {
        bigint id PK
        varchar code UK
    }
    categories {
        bigint id PK
        bigint parent_id FK
    }
    brands {
        bigint id PK
    }
    suppliers {
        bigint id PK
    }
    units {
        bigint id PK
    }
    products {
        bigint id PK
        bigint category_id FK
        bigint brand_id FK
        bigint default_supplier_id FK
    }
    product_variants {
        bigint id PK
        bigint product_id FK
        bigint unit_id FK
        varchar sku UK
    }
    variant_attributes {
        bigint id PK
        bigint product_variant_id FK
    }
    barcodes {
        bigint id PK
        bigint product_variant_id FK
        varchar barcode UK
    }
    variant_location_settings {
        bigint id PK
        bigint location_id FK
        bigint product_variant_id FK
    }
```

## Inventory

`reason_codes`, `stock_levels`, `stock_movements`, `opening_balances`, `opening_balance_items`, `goods_receipts`, `goods_receipt_items`, `transfers`, `transfer_items`, `returns`, `return_items`, `stock_adjustments`, `stock_adjustment_items`, `document_sequences`, `attachments`

```mermaid
erDiagram
    goods_receipts ||--o{ goods_receipt_items : "goods_receipt_id"
    locations ||--o{ attachments : "location_id"
    locations ||--o{ document_sequences : "location_id"
    locations ||--o{ goods_receipt_items : "location_id"
    locations ||--o{ goods_receipts : "location_id"
    locations ||--o{ opening_balance_items : "location_id"
    locations ||--o{ opening_balances : "location_id"
    locations ||--o{ return_items : "location_id"
    locations ||--o{ returns : "location_id"
    locations ||--o{ stock_adjustment_items : "location_id"
    locations ||--o{ stock_adjustments : "location_id"
    locations ||--o{ stock_levels : "location_id"
    locations ||--o{ stock_movements : "location_id"
    locations ||--o{ transfer_items : "location_id"
    locations ||--o{ transfers : "destination_location_id"
    locations ||--o{ transfers : "location_id"
    opening_balances ||--o{ opening_balance_items : "opening_balance_id"
    product_variants ||--o{ goods_receipt_items : "product_variant_id"
    product_variants ||--o{ opening_balance_items : "product_variant_id"
    product_variants ||--o{ return_items : "product_variant_id"
    product_variants ||--o{ stock_adjustment_items : "product_variant_id"
    product_variants ||--o{ stock_levels : "product_variant_id"
    product_variants ||--o{ stock_movements : "product_variant_id"
    product_variants ||--o{ transfer_items : "product_variant_id"
    reason_codes ||--o{ returns : "reason_code_id"
    reason_codes ||--o{ stock_adjustments : "reason_code_id"
    reason_codes ||--o{ stock_movements : "reason_code_id"
    returns ||--o{ return_items : "return_id"
    stock_adjustments ||--o{ stock_adjustment_items : "stock_adjustment_id"
    stock_movements ||--o{ stock_levels : "last_movement_id"
    stock_movements ||--o{ stock_movements : "reversed_movement_id"
    suppliers ||--o{ goods_receipts : "supplier_id"
    suppliers ||--o{ returns : "supplier_id"
    transfers ||--o{ transfer_items : "transfer_id"
    users ||--o{ attachments : "uploaded_by"
    users ||--o{ goods_receipts : "created_by"
    users ||--o{ opening_balances : "approved_by"
    users ||--o{ opening_balances : "created_by"
    users ||--o{ returns : "created_by"
    users ||--o{ stock_adjustments : "approved_by"
    users ||--o{ stock_adjustments : "created_by"
    users ||--o{ stock_movements : "created_by"
    users ||--o{ transfers : "created_by"
    users ||--o{ transfers : "received_by"
    reason_codes {
        bigint id PK
        varchar code UK
    }
    stock_levels {
        bigint id PK
        bigint location_id FK
        bigint product_variant_id FK
        bigint last_movement_id FK
    }
    stock_movements {
        bigint id PK
        bigint location_id FK
        bigint product_variant_id FK
        bigint reason_code_id FK
        bigint reversed_movement_id FK
        bigint created_by FK
    }
    opening_balances {
        bigint id PK
        bigint location_id FK
        varchar number UK
        bigint created_by FK
        bigint approved_by FK
    }
    opening_balance_items {
        bigint id PK
        bigint opening_balance_id FK
        bigint location_id FK
        bigint product_variant_id FK
    }
    goods_receipts {
        bigint id PK
        bigint location_id FK
        varchar number UK
        bigint supplier_id FK
        bigint created_by FK
    }
    goods_receipt_items {
        bigint id PK
        bigint goods_receipt_id FK
        bigint location_id FK
        bigint product_variant_id FK
    }
    transfers {
        bigint id PK
        bigint location_id FK
        bigint destination_location_id FK
        varchar number UK
        bigint created_by FK
        bigint received_by FK
    }
    transfer_items {
        bigint id PK
        bigint transfer_id FK
        bigint location_id FK
        bigint product_variant_id FK
    }
    returns {
        bigint id PK
        bigint location_id FK
        varchar number UK
        bigint supplier_id FK
        bigint reason_code_id FK
        bigint created_by FK
    }
    return_items {
        bigint id PK
        bigint return_id FK
        bigint location_id FK
        bigint product_variant_id FK
    }
    stock_adjustments {
        bigint id PK
        bigint location_id FK
        varchar number UK
        bigint reason_code_id FK
        bigint created_by FK
        bigint approved_by FK
    }
    stock_adjustment_items {
        bigint id PK
        bigint stock_adjustment_id FK
        bigint location_id FK
        bigint product_variant_id FK
    }
    document_sequences {
        bigint id PK
        bigint location_id FK
    }
    attachments {
        bigint id PK
        bigint location_id FK
        bigint uploaded_by FK
    }
    locations {
        bigint id PK
    }
    product_variants {
        bigint id PK
    }
    suppliers {
        bigint id PK
    }
    users {
        bigint id PK
    }
```

## POS

`pos_sources`, `pos_column_mappings`, `pos_import_batches`, `pos_transactions`, `pos_transaction_lines`, `sku_mappings`, `pos_discrepancies`

```mermaid
erDiagram
    locations ||--o{ pos_discrepancies : "location_id"
    locations ||--o{ pos_import_batches : "location_id"
    locations ||--o{ pos_sources : "location_id"
    locations ||--o{ pos_transaction_lines : "location_id"
    locations ||--o{ pos_transactions : "location_id"
    pos_import_batches ||--o{ pos_discrepancies : "import_batch_id"
    pos_import_batches ||--o{ pos_transactions : "import_batch_id"
    pos_sources ||--o{ pos_column_mappings : "pos_source_id"
    pos_sources ||--o{ pos_import_batches : "pos_source_id"
    pos_sources ||--o{ pos_transactions : "pos_source_id"
    pos_sources ||--o{ sku_mappings : "pos_source_id"
    pos_transactions ||--o{ pos_discrepancies : "pos_transaction_id"
    pos_transactions ||--o{ pos_transaction_lines : "pos_transaction_id"
    product_variants ||--o{ pos_transaction_lines : "product_variant_id"
    product_variants ||--o{ sku_mappings : "product_variant_id"
    stock_movements ||--o{ pos_transaction_lines : "stock_movement_id"
    users ||--o{ pos_discrepancies : "resolved_by"
    users ||--o{ pos_import_batches : "created_by"
    users ||--o{ sku_mappings : "created_by"
    pos_sources {
        bigint id PK
        bigint location_id FK
    }
    pos_column_mappings {
        bigint id PK
        bigint pos_source_id FK
    }
    pos_import_batches {
        bigint id PK
        bigint pos_source_id FK
        bigint location_id FK
        bigint created_by FK
    }
    pos_transactions {
        bigint id PK
        bigint pos_source_id FK
        bigint location_id FK
        bigint import_batch_id FK
        varchar external_id UK
    }
    pos_transaction_lines {
        bigint id PK
        bigint pos_transaction_id FK
        bigint location_id FK
        bigint product_variant_id FK
        bigint stock_movement_id FK
    }
    sku_mappings {
        bigint id PK
        bigint pos_source_id FK
        bigint product_variant_id FK
        bigint created_by FK
    }
    pos_discrepancies {
        bigint id PK
        bigint location_id FK
        bigint import_batch_id FK
        bigint pos_transaction_id FK
        bigint resolved_by FK
    }
    locations {
        bigint id PK
    }
    product_variants {
        bigint id PK
    }
    stock_movements {
        bigint id PK
    }
    users {
        bigint id PK
    }
```

## Reconciliation

`reconciliation_settings`, `stock_counts`, `stock_count_lines`, `stock_count_entries`, `count_assignments`

```mermaid
erDiagram
    locations ||--o{ count_assignments : "location_id"
    locations ||--o{ reconciliation_settings : "location_id"
    locations ||--o{ stock_count_entries : "location_id"
    locations ||--o{ stock_count_lines : "location_id"
    locations ||--o{ stock_counts : "location_id"
    product_variants ||--o{ stock_count_lines : "product_variant_id"
    stock_count_lines ||--o{ stock_count_entries : "stock_count_line_id"
    stock_counts ||--o{ count_assignments : "stock_count_id"
    stock_counts ||--o{ stock_count_lines : "stock_count_id"
    users ||--o{ count_assignments : "user_id"
    users ||--o{ stock_count_entries : "counted_by"
    users ||--o{ stock_counts : "approved_by"
    users ||--o{ stock_counts : "created_by"
    reconciliation_settings {
        bigint id PK
        bigint location_id FK
    }
    stock_counts {
        bigint id PK
        bigint location_id FK
        varchar number UK
        bigint created_by FK
        bigint approved_by FK
    }
    stock_count_lines {
        bigint id PK
        bigint stock_count_id FK
        bigint location_id FK
        bigint product_variant_id FK
    }
    stock_count_entries {
        bigint id PK
        bigint stock_count_line_id FK
        bigint location_id FK
        bigint counted_by FK
    }
    count_assignments {
        bigint id PK
        bigint stock_count_id FK
        bigint location_id FK
        bigint user_id FK
    }
    locations {
        bigint id PK
    }
    product_variants {
        bigint id PK
    }
    users {
        bigint id PK
    }
```

## Reporting

`mv_daily_stock_summary`, `mv_movement_by_type`, `exports`, `notifications`

```mermaid
erDiagram
    locations ||--o{ exports : "location_id"
    users ||--o{ exports : "user_id"
    mv_daily_stock_summary {
    }
    mv_movement_by_type {
    }
    exports {
        bigint id PK
        bigint location_id FK
        bigint user_id FK
    }
    notifications {
        uuid id PK
    }
    locations {
        bigint id PK
    }
    users {
        bigint id PK
    }
```
