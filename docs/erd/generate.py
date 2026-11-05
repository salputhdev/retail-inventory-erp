#!/usr/bin/env python3
"""Generator ERD Fase 1. Jalankan: python3 docs/erd/generate.py docs/erd"""
import os, re, sys

OUT = sys.argv[1]
ID = ("id", "bigint identity", "PK")
TS = [("created_at", "timestamptz", ""), ("updated_at", "timestamptz", "")]
SD = ("deleted_at", "timestamptz null", "soft delete (master data)")
QTY, MONEY = "numeric(14,3)", "numeric(14,4)"

# (nama, kelompok, sumber, kolom, constraint, catatan)
# sumber: PRD = ada di PRD 6.1.6; Framework = tabel paket; Tambahan = diturunkan dari AC/Architecture
T = []
def t(name, group, src, cols, cons="", note=""):
    T.append(dict(name=name, group=group, src=src, cols=cols, cons=cons, note=note))

# ---------------- Identity ----------------
t("users", "Identity", "PRD", [ID,
  ("name", "varchar(150)", ""), ("email", "varchar(190)", "unique"), ("password", "varchar(255)", "hash"),
  ("email_verified_at", "timestamptz null", ""), ("two_factor_secret", "text null", "terenkripsi"),
  ("two_factor_recovery_codes", "text null", "terenkripsi"), ("two_factor_confirmed_at", "timestamptz null", ""),
  ("is_active", "boolean", "default true; nonaktif = sesi dicabut (US-1.24)"),
  ("last_login_at", "timestamptz null", ""), ("remember_token", "varchar(100) null", "")] + TS,
  "UNIQUE(email)")
t("roles", "Identity", "PRD", [ID, ("name", "varchar(80)", ""), ("guard_name", "varchar(40)", "")] + TS,
  "UNIQUE(name, guard_name)", "spatie/laravel-permission")
t("permissions", "Identity", "PRD", [ID, ("name", "varchar(120)", "permission per aksi, PRD 4.3"), ("guard_name", "varchar(40)", "")] + TS,
  "UNIQUE(name, guard_name)", "spatie/laravel-permission")
t("role_has_permissions", "Identity", "Framework", [("permission_id", "bigint", "FK permissions"), ("role_id", "bigint", "FK roles")],
  "PK(permission_id, role_id)", "spatie/laravel-permission (pivot)")
t("model_has_roles", "Identity", "PRD", [("role_id", "bigint", "FK roles"), ("model_type", "varchar(120)", ""), ("model_id", "bigint", "")],
  "PK(role_id, model_id, model_type)", "spatie/laravel-permission. Index (model_id, model_type)")
t("model_has_permissions", "Identity", "Framework", [("permission_id", "bigint", "FK permissions"), ("model_type", "varchar(120)", ""), ("model_id", "bigint", "")],
  "PK(permission_id, model_id, model_type)", "spatie/laravel-permission (izin langsung; dipakai minimal)")
t("location_user", "Identity", "PRD", [("user_id", "bigint", "FK users"), ("location_id", "bigint", "FK locations")] + TS,
  "PK(user_id, location_id)", "Dasar LocationScope (ADR-003). Index (location_id)")
t("personal_access_tokens", "Identity", "Framework", [ID, ("tokenable_type", "varchar(120)", ""), ("tokenable_id", "bigint", "pos_source / user"),
  ("name", "varchar(120)", ""), ("token", "char(64)", "hash; nilai mentah tampil sekali (IN-06)"), ("abilities", "text null", "mis. pos:write"),
  ("last_used_at", "timestamptz null", ""), ("expires_at", "timestamptz null", "")] + TS,
  "UNIQUE(token)", "Sanctum")
t("activity_log", "Identity", "PRD", [ID, ("log_name", "varchar(80) null", ""), ("description", "text", ""),
  ("subject_type", "varchar(120) null", ""), ("subject_id", "bigint null", ""), ("event", "varchar(60) null", ""),
  ("causer_type", "varchar(120) null", ""), ("causer_id", "bigint null", ""), ("location_id", "bigint null", "FK locations (konteks)"),
  ("ip_address", "inet null", "US-1.25"), ("properties", "jsonb null", "nilai sebelum/sesudah; tanpa data sensitif"),
  ("batch_uuid", "uuid null", ""), ("created_at", "timestamptz", "")],
  "", "Tanpa update/delete dari aplikasi (L-01): hak DB dan trigger. Index (subject_type, subject_id), (causer_type, causer_id), (created_at)")

# ---------------- Master ----------------
t("locations", "Master", "PRD", [ID, ("code", "varchar(20)", "unique"), ("name", "varchar(150)", ""),
  ("timezone", "varchar(60)", "IANA, mis. Asia/Jakarta (I-01)"), ("opening_time", "time null", ""), ("closing_time", "time null", ""),
  ("cutover_at", "timestamptz null", "MG-03; transaksi POS sebelumnya diabaikan"), ("is_active", "boolean", "default true"), SD] + TS,
  "UNIQUE(code)")
t("categories", "Master", "PRD", [ID, ("parent_id", "bigint null", "FK categories"), ("code", "varchar(30)", ""), ("name", "varchar(150)", ""), SD] + TS,
  "UNIQUE(code)")
t("brands", "Master", "PRD", [ID, ("name", "varchar(150)", ""), SD] + TS, "UNIQUE(name)")
t("suppliers", "Master", "PRD", [ID, ("code", "varchar(30)", ""), ("name", "varchar(150)", ""), ("contact_name", "varchar(150) null", ""),
  ("phone", "varchar(40) null", ""), ("email", "varchar(190) null", ""), ("address", "text null", ""), SD] + TS, "UNIQUE(code)")
t("units", "Master", "PRD", [ID, ("code", "varchar(20)", "mis. pcs, kg"), ("name", "varchar(60)", ""),
  ("allows_decimal", "boolean", "default false; qty desimal 3 digit hanya bila true")] + TS, "UNIQUE(code)",
  "Satu satuan dasar per SKU di Fase 1 (konversi Fase 2)")
t("products", "Master", "PRD", [ID, ("category_id", "bigint", "FK categories"), ("brand_id", "bigint null", "FK brands"),
  ("default_supplier_id", "bigint null", "FK suppliers"), ("name", "varchar(190)", ""), ("description", "text null", ""), SD] + TS,
  "", "Index (category_id), (brand_id), pencarian nama (trigram, T1.10)")
t("product_variants", "Master", "PRD", [ID, ("product_id", "bigint", "FK products"), ("unit_id", "bigint", "FK units"),
  ("sku", "varchar(60)", "unique"), ("name", "varchar(190)", "turunan atribut"),
  ("status", "varchar(20)", "active | discontinued (CHECK)"), ("standard_cost", "numeric(14,4) null", ""), ("sale_price", "numeric(14,4) null", ""), SD] + TS,
  "UNIQUE(sku); CHECK(status IN ('active','discontinued'))", "Index (product_id), (status)")
t("variant_attributes", "Master", "PRD", [ID, ("product_variant_id", "bigint", "FK product_variants"),
  ("name", "varchar(60)", "ukuran, warna, kemasan, ..."), ("value", "varchar(120)", "")] + TS,
  "UNIQUE(product_variant_id, name)")
t("barcodes", "Master", "PRD", [ID, ("product_variant_id", "bigint", "FK product_variants"), ("barcode", "varchar(64)", "unique"),
  ("symbology", "varchar(20)", "EAN13 | UPCA | CODE128 | OTHER (CHECK)"), ("is_primary", "boolean", "default false")] + TS,
  "UNIQUE(barcode)", "Pencarian scanner P-02. Partial unique (product_variant_id) WHERE is_primary")
t("variant_location_settings", "Master", "PRD", [ID, ("location_id", "bigint", "FK locations"),
  ("product_variant_id", "bigint", "FK product_variants"), ("reorder_point", QTY, "default 0"), ("min_qty", QTY, "default 0"), ("is_stocked", "boolean", "default true")] + TS,
  "UNIQUE(location_id, product_variant_id)", "US-1.04")

# ---------------- Inventory ----------------
t("reason_codes", "Inventory", "PRD", [ID, ("code", "varchar(30)", "unique"), ("name", "varchar(120)", ""),
  ("applies_to", "varchar(30)", "adjustment | supplier_return | count (CHECK)"), ("requires_attachment", "boolean", "default false"), ("is_active", "boolean", "default true")] + TS,
  "UNIQUE(code)")
t("stock_levels", "Inventory", "PRD", [ID, ("location_id", "bigint", "FK locations"), ("product_variant_id", "bigint", "FK product_variants"),
  ("on_hand", QTY, "cache; = SUM(ledger) (D-01)"), ("avg_cost", MONEY, "weighted average (ADR-008)"),
  ("last_movement_id", "bigint null", "FK stock_movements"), ("updated_at", "timestamptz", "")],
  "UNIQUE(location_id, product_variant_id)", "Dikunci `SELECT ... FOR UPDATE` saat posting")
t("stock_movements", "Inventory", "PRD", [ID, ("location_id", "bigint", "FK locations"), ("product_variant_id", "bigint", "FK product_variants"),
  ("type", "varchar(30)", "10 tipe PRD 6.1.4 (CHECK = enum PHP)"), ("quantity", QTY, "signed; CHECK (quantity <> 0)"),
  ("unit_cost", "numeric(14,4) null", "wajib untuk movement masuk"), ("balance_after", QTY, "saldo setelah movement"),
  ("occurred_at", "timestamptz", "waktu bisnis"), ("reference_type", "varchar(40)", "dokumen sumber"), ("reference_id", "bigint", ""),
  ("reason_code_id", "bigint null", "FK reason_codes"), ("reversed_movement_id", "bigint null", "FK stock_movements; unique"),
  ("created_by", "bigint null", "FK users (null untuk job)"), ("created_at", "timestamptz", "")],
  "UNIQUE(reversed_movement_id); CHECK(quantity <> 0)",
  "APPEND-ONLY: trigger BEFORE UPDATE OR DELETE menolak (ADR-002). Index (location_id, product_variant_id, occurred_at), (reference_type, reference_id). Partisi bulanan di Fase 7")
t("opening_balances", "Inventory", "PRD", [ID, ("location_id", "bigint", "FK locations"), ("number", "varchar(40)", "unique"),
  ("status", "varchar(20)", "draft | approved | posted | rejected (CHECK)"), ("cutover_at", "timestamptz", ""),
  ("source_filename", "varchar(190) null", ""), ("created_by", "bigint", "FK users"), ("approved_by", "bigint null", "FK users; <> created_by (R-02)"),
  ("approved_at", "timestamptz null", ""), ("posted_at", "timestamptz null", "")] + TS,
  "UNIQUE(number); partial UNIQUE(location_id) WHERE status = 'posted'", "Satu saldo awal per lokasi (MG-01)")
t("opening_balance_items", "Inventory", "PRD", [ID, ("opening_balance_id", "bigint", "FK opening_balances"), ("location_id", "bigint", "FK locations"),
  ("product_variant_id", "bigint", "FK product_variants"), ("quantity", QTY, ""), ("unit_cost", MONEY, "")] + TS,
  "UNIQUE(opening_balance_id, product_variant_id)")
t("goods_receipts", "Inventory", "PRD", [ID, ("location_id", "bigint", "FK locations"), ("number", "varchar(40)", "unique"),
  ("supplier_id", "bigint null", "FK suppliers"), ("status", "varchar(20)", "draft | posted | cancelled (CHECK)"),
  ("supplier_ref", "varchar(80) null", "no. surat jalan"), ("received_at", "timestamptz", ""),
  ("created_by", "bigint", "FK users"), ("posted_at", "timestamptz null", ""), ("notes", "text null", "")] + TS,
  "UNIQUE(number)", "Index (location_id, received_at)")
t("goods_receipt_items", "Inventory", "PRD", [ID, ("goods_receipt_id", "bigint", "FK goods_receipts"), ("location_id", "bigint", "FK locations"),
  ("product_variant_id", "bigint", "FK product_variants"), ("quantity", QTY, "> 0"), ("unit_cost", MONEY, "")] + TS)
t("transfers", "Inventory", "PRD", [ID, ("location_id", "bigint", "FK locations (asal)"), ("destination_location_id", "bigint", "FK locations; <> location_id"),
  ("number", "varchar(40)", "unique"), ("status", "varchar(20)", "draft | in_transit | received | cancelled (CHECK)"),
  ("shipped_at", "timestamptz null", ""), ("received_at", "timestamptz null", ""), ("created_by", "bigint", "FK users"), ("received_by", "bigint null", "FK users"), ("notes", "text null", "")] + TS,
  "UNIQUE(number); CHECK(location_id <> destination_location_id)", "Dapat dilihat kedua lokasi (US-1.06)")
t("transfer_items", "Inventory", "PRD", [ID, ("transfer_id", "bigint", "FK transfers"), ("location_id", "bigint", "FK locations (asal)"),
  ("product_variant_id", "bigint", "FK product_variants"), ("quantity_sent", QTY, "> 0"), ("quantity_received", "numeric(14,3) null", "selisih = sent - received"), ("unit_cost", MONEY, "avg_cost saat kirim")] + TS,
  "UNIQUE(transfer_id, product_variant_id)")
t("returns", "Inventory", "PRD", [ID, ("location_id", "bigint", "FK locations"), ("number", "varchar(40)", "unique"),
  ("type", "varchar(20)", "customer | supplier (CHECK)"), ("supplier_id", "bigint null", "FK suppliers; wajib bila supplier"),
  ("reason_code_id", "bigint null", "FK reason_codes; wajib bila supplier"), ("status", "varchar(20)", "draft | posted | cancelled (CHECK)"),
  ("returned_at", "timestamptz", ""), ("created_by", "bigint", "FK users"), ("posted_at", "timestamptz null", ""), ("notes", "text null", "")] + TS,
  "UNIQUE(number); CHECK(type <> 'supplier' OR (supplier_id IS NOT NULL AND reason_code_id IS NOT NULL))")
t("return_items", "Inventory", "PRD", [ID, ("return_id", "bigint", "FK returns"), ("location_id", "bigint", "FK locations"),
  ("product_variant_id", "bigint", "FK product_variants"), ("quantity", QTY, "> 0"), ("unit_cost", MONEY, "")] + TS)
t("stock_adjustments", "Inventory", "PRD", [ID, ("location_id", "bigint", "FK locations"), ("number", "varchar(40)", "unique"),
  ("reason_code_id", "bigint", "FK reason_codes"), ("status", "varchar(20)", "draft | submitted | approved | rejected | posted (CHECK)"),
  ("created_by", "bigint", "FK users"), ("approved_by", "bigint null", "FK users; <> created_by (R-02)"),
  ("approved_at", "timestamptz null", ""), ("rejection_note", "text null", ""), ("posted_at", "timestamptz null", ""), ("notes", "text null", "")] + TS,
  "UNIQUE(number)", "Index (location_id, status) untuk daftar menunggu approval")
t("stock_adjustment_items", "Inventory", "PRD", [ID, ("stock_adjustment_id", "bigint", "FK stock_adjustments"), ("location_id", "bigint", "FK locations"),
  ("product_variant_id", "bigint", "FK product_variants"), ("quantity", QTY, "signed, <> 0"), ("unit_cost", "numeric(14,4) null", "")] + TS)
t("document_sequences", "Inventory", "Tambahan", [ID, ("location_id", "bigint", "FK locations"), ("document_type", "varchar(10)", "GR, TR, RT, ADJ, OB, SC"),
  ("year", "smallint", ""), ("last_number", "integer", "dinaikkan dengan lock baris")] + TS,
  "UNIQUE(location_id, document_type, year)", "Nomor mis. GR-LOC01-2026-000123 unik dan berurutan meski paralel (T2.04)")
t("attachments", "Inventory", "Tambahan", [ID, ("attachable_type", "varchar(60)", ""), ("attachable_id", "bigint", ""),
  ("location_id", "bigint", "FK locations"), ("disk", "varchar(30)", ""), ("path", "varchar(255)", ""), ("original_name", "varchar(190)", ""),
  ("mime_type", "varchar(100)", ""), ("size_bytes", "bigint", ""), ("uploaded_by", "bigint", "FK users")] + TS,
  "", "Lampiran bukti penerimaan/adjustment (US-1.05); diakses lewat signed URL. Index (attachable_type, attachable_id)")

# ---------------- POS ----------------
t("pos_sources", "POS", "PRD", [ID, ("location_id", "bigint", "FK locations"), ("name", "varchar(120)", ""), ("vendor", "varchar(80) null", ""),
  ("channel", "varchar(20)", "file | api (CHECK)"), ("timezone", "varchar(60)", ""), ("is_active", "boolean", "default true")] + TS,
  "UNIQUE(location_id, name)", "Token API (Sanctum, ability pos:write) menempel ke sumber ini")
t("pos_column_mappings", "POS", "PRD", [ID, ("pos_source_id", "bigint", "FK pos_sources"),
  ("target_field", "varchar(40)", "field internal, mis. external_id, sku, quantity"), ("source_column", "varchar(120)", ""),
  ("transform", "jsonb null", "format tanggal, desimal, dll.")] + TS,
  "UNIQUE(pos_source_id, target_field)")
t("pos_import_batches", "POS", "PRD", [ID, ("pos_source_id", "bigint", "FK pos_sources"), ("location_id", "bigint", "FK locations"),
  ("status", "varchar(20)", "pending | processing | completed | failed (CHECK)"), ("file_hash", "char(64) null", "SHA-256 (IN-01)"),
  ("original_filename", "varchar(190) null", ""), ("storage_path", "varchar(255) null", "file asli di object storage"),
  ("payload", "jsonb null", "payload API mentah (IN-07)"), ("total_rows", "integer", "default 0"), ("posted_rows", "integer", "default 0"),
  ("duplicate_rows", "integer", "default 0"), ("discrepancy_rows", "integer", "default 0"), ("failed_rows", "integer", "default 0"),
  ("last_chunk_offset", "integer", "default 0; retry idempoten (IN-05)"), ("started_at", "timestamptz null", ""), ("finished_at", "timestamptz null", ""),
  ("created_by", "bigint null", "FK users")] + TS,
  "UNIQUE(pos_source_id, file_hash)", "Index (location_id, status, created_at)")
t("pos_transactions", "POS", "PRD", [ID, ("pos_source_id", "bigint", "FK pos_sources"), ("location_id", "bigint", "FK locations"),
  ("import_batch_id", "bigint", "FK pos_import_batches"), ("external_id", "varchar(80)", "id transaksi di POS"),
  ("type", "varchar(20)", "sale | customer_return (CHECK)"), ("occurred_at", "timestamptz", ""), ("total_amount", MONEY, ""),
  ("status", "varchar(20)", "posted | discrepancy | ignored (CHECK)"), ("raw", "jsonb null", "baris mentah")] + TS,
  "UNIQUE(pos_source_id, location_id, external_id)", "Idempotensi transaksi (IN-01). Index (location_id, occurred_at)")
t("pos_transaction_lines", "POS", "PRD", [ID, ("pos_transaction_id", "bigint", "FK pos_transactions"), ("location_id", "bigint", "FK locations"),
  ("line_no", "integer", ""), ("external_sku", "varchar(80)", "barcode/SKU dari POS"), ("product_variant_id", "bigint null", "FK product_variants; null bila belum terpetakan"),
  ("quantity", QTY, ""), ("unit_price", MONEY, ""), ("discount", MONEY, "default 0"), ("stock_movement_id", "bigint null", "FK stock_movements")] + TS,
  "UNIQUE(pos_transaction_id, line_no)", "Index (location_id, product_variant_id), (external_sku). ~5,5 jt baris/tahun")
t("sku_mappings", "POS", "PRD", [ID, ("pos_source_id", "bigint", "FK pos_sources"), ("external_sku", "varchar(80)", ""),
  ("product_variant_id", "bigint", "FK product_variants"), ("created_by", "bigint", "FK users")] + TS,
  "UNIQUE(pos_source_id, external_sku)", "Urutan resolusi SKU: barcode, SKU varian, lalu tabel ini (IN-03)")
t("pos_discrepancies", "POS", "PRD", [ID, ("location_id", "bigint", "FK locations"), ("import_batch_id", "bigint", "FK pos_import_batches"),
  ("pos_transaction_id", "bigint null", "FK pos_transactions"), ("type", "varchar(30)", "unmapped_sku | duplicate | negative_stock | price_mismatch | future_or_closed_hours | sequence_gap (CHECK)"),
  ("status", "varchar(20)", "open | resolved | ignored (CHECK)"), ("details", "jsonb", ""), ("resolved_by", "bigint null", "FK users"),
  ("resolved_at", "timestamptz null", ""), ("resolution_note", "text null", "")] + TS,
  "", "Index (location_id, status, type)")

# ---------------- Reconciliation ----------------
t("reconciliation_settings", "Reconciliation", "PRD", [ID, ("location_id", "bigint", "FK locations"), ("threshold_qty", QTY, "default 5"),
  ("threshold_pct", "numeric(7,3)", "default 5"), ("threshold_value", MONEY, "default 500000")] + TS,
  "UNIQUE(location_id)", "Exception bila melewati salah satu threshold (US-1.18)")
t("stock_counts", "Reconciliation", "PRD", [ID, ("location_id", "bigint", "FK locations"), ("number", "varchar(40)", "unique"),
  ("type", "varchar(20)", "full | cycle (CHECK)"),
  ("status", "varchar(20)", "draft | counting | submitted | under_review | approved | posted | cancelled (CHECK)"),
  ("snapshot_at", "timestamptz null", ""), ("submitted_at", "timestamptz null", ""), ("created_by", "bigint", "FK users"),
  ("approved_by", "bigint null", "FK users; <> created_by (R-02)"), ("approved_at", "timestamptz null", ""), ("posted_at", "timestamptz null", ""), ("notes", "text null", "")] + TS,
  "UNIQUE(number)", "State machine: PRD 6.1.5")
t("stock_count_lines", "Reconciliation", "PRD", [ID, ("stock_count_id", "bigint", "FK stock_counts"), ("location_id", "bigint", "FK locations"),
  ("product_variant_id", "bigint", "FK product_variants"), ("snapshot_qty", QTY, ""), ("expected_qty", QTY, "snapshot + movement sampai waktu hitung"),
  ("counted_qty", "numeric(14,3) null", ""), ("variance_qty", "numeric(14,3) null", "counted - expected"),
  ("variance_value", "numeric(14,4) null", "variance_qty x avg_cost"), ("is_exception", "boolean", "default false"),
  ("recount_requested", "boolean", "default false"), ("notes", "text null", "")] + TS,
  "UNIQUE(stock_count_id, product_variant_id)", "Index (stock_count_id, is_exception)")
t("stock_count_entries", "Reconciliation", "Tambahan", [ID, ("stock_count_line_id", "bigint", "FK stock_count_lines"), ("location_id", "bigint", "FK locations"),
  ("counted_by", "bigint", "FK users"), ("quantity", QTY, ">= 0"), ("counted_at", "timestamptz", "")] + TS,
  "", "Banyak penghitung per baris (US-1.16); counted_qty = hasil agregasi/recount")
t("count_assignments", "Reconciliation", "PRD", [ID, ("stock_count_id", "bigint", "FK stock_counts"), ("location_id", "bigint", "FK locations"),
  ("user_id", "bigint", "FK users"), ("zone", "varchar(60) null", "area/rak/kategori")] + TS,
  "UNIQUE(stock_count_id, user_id, zone)")

# ---------------- Reporting (materialized views) + framework ----------------
t("mv_daily_stock_summary", "Reporting", "PRD", [("location_id", "bigint", ""), ("product_variant_id", "bigint", ""), ("summary_date", "date", ""),
  ("on_hand", QTY, ""), ("stock_value", MONEY, "")], "UNIQUE INDEX(location_id, product_variant_id, summary_date)",
  "Materialized view; REFRESH CONCURRENTLY tiap 5 menit (P-07)")
t("mv_movement_by_type", "Reporting", "PRD", [("location_id", "bigint", ""), ("type", "varchar(30)", ""), ("movement_date", "date", ""),
  ("movement_count", "integer", ""), ("qty_in", QTY, ""), ("qty_out", QTY, ""), ("value", MONEY, "")],
  "UNIQUE INDEX(location_id, type, movement_date)", "Materialized view; refresh tiap 5 menit")
t("exports", "Reporting", "Tambahan", [ID, ("location_id", "bigint null", "FK locations"), ("user_id", "bigint", "FK users"), ("report", "varchar(60)", ""),
  ("format", "varchar(10)", "csv | xlsx | pdf (CHECK)"), ("status", "varchar(20)", "pending | ready | failed"), ("disk", "varchar(30) null", ""),
  ("path", "varchar(255) null", ""), ("expires_at", "timestamptz null", "dihapus `exports:prune` > 7 hari")] + TS,
  "", "Laporan berat via queue (P-06); notifikasi ReportReady")
t("notifications", "Reporting", "Framework", [("id", "uuid", "PK"), ("type", "varchar(190)", ""), ("notifiable_type", "varchar(120)", ""),
  ("notifiable_id", "bigint", ""), ("data", "jsonb", ""), ("read_at", "timestamptz null", "")] + TS,
  "", "Notifikasi in-app (approval, import selesai, ReportReady)")

FRAMEWORK = ["sessions (driver Redis, tabel tidak dipakai)", "cache / cache_locks", "jobs / job_batches / failed_jobs (queue di Redis; failed_jobs di DB)", "password_reset_tokens", "migrations"]

GROUPS = ["Identity", "Master", "Inventory", "POS", "Reconciliation", "Reporting"]
names = {x["name"] for x in T}

def fks(tb):
    out = []
    for c, ty, note in tb["cols"]:
        m = re.search(r"FK (\w+)", note)
        if m and m.group(1) in names:
            out.append((c, m.group(1)))
    return out

# ---- validasi: semua tabel PRD 6.1.6 ada
prd = ("users roles permissions model_has_roles location_user activity_log locations categories brands suppliers units products product_variants "
       "variant_attributes barcodes variant_location_settings stock_levels stock_movements opening_balances opening_balance_items goods_receipts "
       "goods_receipt_items transfers transfer_items returns return_items stock_adjustments stock_adjustment_items reason_codes pos_sources "
       "pos_column_mappings pos_import_batches pos_transactions pos_transaction_lines sku_mappings pos_discrepancies stock_counts stock_count_lines "
       "count_assignments reconciliation_settings mv_daily_stock_summary mv_movement_by_type").split()
missing = [n for n in prd if n not in names]
assert not missing, missing
for tb in T:
    for _, ref in fks(tb):
        assert ref in names, (tb["name"], ref)

# ---- tulis tables.md
os.makedirs(OUT, exist_ok=True)
md = ["# ERD Fase 1 — Daftar Tabel dan Kolom", "",
      "Dihasilkan dari satu sumber definisi (lihat README). Konvensi skema: Architecture §3.1. `location_id` wajib di setiap tabel transaksional (FR1.7).", ""]
for g in GROUPS:
    md += [f"## {g}", ""]
    for tb in [x for x in T if x["group"] == g]:
        tag = "" if tb["src"] == "PRD" else f" _(sumber: {tb['src']})_"
        md += [f"### `{tb['name']}`{tag}", ""]
        if tb["note"]:
            md += [tb["note"], ""]
        md += ["| Kolom | Tipe | Keterangan |", "| ----- | ---- | ---------- |"]
        for c, ty, note in tb["cols"]:
            md.append(f"| `{c}` | {ty} | {note} |")
        md.append("")
        if tb["cons"]:
            md += [f"**Constraint:** {tb['cons']}", ""]
        f = fks(tb)
        if f:
            md += ["**Foreign key:** " + ", ".join(f"`{c}` → `{r}`" for c, r in f) + ". `ON DELETE RESTRICT` (tanpa cascade ke ledger).", ""]
open(f"{OUT}/tables.md", "w").write("\n".join(md))

# ---- diagram per kelompok (mermaid)
def mermaid(group):
    ts = [x for x in T if x["group"] == group]
    members = {x["name"] for x in ts}
    lines = ["```mermaid", "erDiagram"]
    rels, ext = set(), set()
    for tb in ts:
        for c, ref in fks(tb):
            rels.add((ref, tb["name"], c))
            if ref not in members:
                ext.add(ref)
    for r in sorted(rels):
        lines.append(f'    {r[0]} ||--o{{ {r[1]} : "{r[2]}"')
    for tb in ts:
        lines.append(f"    {tb['name']} {{")
        shown = 0
        for c, ty, note in tb["cols"]:
            keyed = c == "id" or "FK" in note or "unique" in note or c in ("sku", "barcode", "number", "external_id")
            if keyed and shown < 9:
                kind = "PK" if c == "id" else ("FK" if "FK" in note else "UK")
                lines.append(f"        {re.sub(r'[^a-z0-9 ]','',ty.split()[0].split('(')[0])} {c} {kind}")
                shown += 1
        lines.append("    }")
    for e in sorted(ext):
        lines.append(f"    {e} {{\n        bigint id PK\n    }}")
    lines.append("```")
    return "\n".join(lines)

readme = ["# ERD Fase 1 (v1)", "",
  "| Item | Detail |", "| ---- | ------ |",
  "| Versi | 1.0 — 6 November 2026 (T0.06) |",
  "| Acuan | PRD §6.1.6, §6.1.4–6.1.5; Architecture §3 |",
  "| Detail kolom | [tables.md](tables.md) |", "",
  "## Konvensi", "",
  "- PK `bigint generated always as identity`; nomor dokumen manusiawi di kolom `number`.",
  "- Qty `numeric(14,3)`, uang/biaya `numeric(14,4)`, waktu `timestamptz` (UTC); `occurred_at` terpisah dari `created_at`.",
  "- `location_id` wajib di setiap tabel transaksional; FK selalu didefinisikan dengan `ON DELETE RESTRICT`.",
  "- Status = `varchar` + PHP enum + `CHECK`. Soft delete hanya untuk master data.",
  "- `stock_movements` append-only (trigger); `stock_levels` cache; payload eksternal `jsonb`.", "",
  "## Cakupan", "",
  f"Seluruh {len(prd)} tabel PRD §6.1.6 tercakup, ditambah tabel turunan. Total: **{len(T)} tabel/view** di bawah, ditambah tabel framework ({'; '.join(FRAMEWORK)}).", "",
  "| Sumber | Jumlah | Arti |", "| ------ | ------ | ---- |",
  f"| PRD | {sum(1 for x in T if x['src']=='PRD')} | Ada di daftar PRD §6.1.6 |",
  f"| Framework | {sum(1 for x in T if x['src']=='Framework')} | Dibutuhkan paket (spatie, Sanctum, notifikasi) |",
  f"| Tambahan | {sum(1 for x in T if x['src']=='Tambahan')} | Diturunkan dari AC/Architecture: " + ", ".join(f"`{x['name']}`" for x in T if x['src']=='Tambahan') + " |", "",
  "Tabel *Tambahan* belum ada di PRD §6.1.6; usulan memasukkannya ke PRD pada revisi berikut (perubahan PRD butuh persetujuan).", ""]
for g in GROUPS:
    readme += [f"## {g}", "", ", ".join(f"`{x['name']}`" for x in T if x["group"] == g), "", mermaid(g), ""]
open(f"{OUT}/README.md", "w").write("\n".join(readme))
print(len(T), "tabel;", len(prd), "tabel PRD tercakup")
