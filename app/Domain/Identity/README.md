# Modul Identity

User, lokasi, autentikasi (T1.03), otorisasi (T1.04), dan audit log (T1.07). Scope lokasi dijelaskan di ADR-003; bagian ini merangkum RBAC dan audit.

## RBAC (PRD §4, T1.04)

| Bagian | Letak | Aturan |
| ------ | ----- | ------ |
| Katalog izin | `Enums\Permission` | Satu izin per aksi (`resource.aksi`). Hanya izin di katalog yang bisa lolos Gate. |
| Role & izin default | `Enums\Role::defaultPermissions()` | Salinan matriks PRD §4.3. Diuji terhadap transkripsi PRD terpisah (`tests/Support/PrdRbacMatrix.php`). |
| Seeder | `Database\Seeders\RbacSeeder` | Aman diulang di produksi: membuat izin/role yang belum ada, tidak menimpa role yang sudah disesuaikan. |
| Gate | `Policies\PermissionGate` | Pengganti Gate bawaan spatie (dimatikan di `config/permission.php`). |
| Dasar Policy | `App\Support\Authorization\BasePolicy` | Lokasi dulu (404), baru izin (403). |
| Segregasi tugas | `App\Support\Authorization\AssertNotSameActor` | Penyetuju ≠ pembuat/penghitung; pembuat tidak diketahui = ditolak (R-02). |
| Ubah izin role | `Actions\SyncRolePermissions` | Hanya `role.manage`, diaudit sebelum/sesudah, role super-admin tidak bisa kehilangan `role.manage`. |

### Cara memakai
- Aksi tanpa data tertentu: `Gate::authorize(Permission::CatalogImport)`, middleware `can:catalog.import`, atau `@can('catalog.import')`.
- Aksi pada data tertentu: **selalu ability Policy** (`Gate::authorize('update', $doc)`), bukan nama izin. `can('catalog.manage', $produk)` melempar `LogicException` karena izin tidak memeriksa lokasi.
- Policy model ber-lokasi: turunkan dari `BasePolicy`, pakai `permitInLocation($user, Permission::X, $model->location_id)`, daftarkan dengan `Gate::policy()` di `AppServiceProvider`.
- Approval: panggil `AssertNotSameActor::enforce($approver, $doc->created_by)` (atau semua penghitung) di Action sebelum memposting.
- Pelaku aksi sensitif diambil dari `Support\Authorization\Actor::current()`, bukan parameter.

### Keputusan yang perlu diingat
- **Tanpa bypass Super Admin** (`Gate::before` yang mengizinkan semua): matriks melarang Super Admin memposting transaksi stok.
- 👁 di matriks = izin `.view`; 📝 adjustment = `adjustment.create` (draft) tanpa approve; Inventory Staff "cek stok" = `stock.check` saja.
- `audit_log.view` = entri di lokasi user (Store Manager); `audit_log.view_global` = juga entri tanpa lokasi (Super Admin, Auditor). Penegakan di penampil audit (T5.08).
- Role `pos-integrator` + `pos.push` ada demi kelengkapan matriks; token POS per sumber (R-05) dipetakan di T3.02.
- Izin baru: tambahkan ke `Permission`, ke `Role::defaultPermissions()`, dan ke transkripsi PRD di test. Untuk role yang sudah ada di produksi, berikan lewat migration/seeder task itu (seeder tidak menimpa role lama).
- Perubahan izin berlaku tanpa deploy: spatie membuang cache saat role diubah, `SyncRolePermissions` membuangnya lagi setelah commit, dan worker queue mengosongkan izin di memori setiap job.

## Audit log (US-1.25, L-01, T1.07)

| Bagian | Letak | Aturan |
| ------ | ----- | ------ |
| Kontrak | `Support\Audit\AuditTrail::record($action, $context, $actorId, $subject)` | Satu-satunya cara menulis audit. Dipakai semua aksi sensitif; tidak berubah sejak T1.03 selain `$subject` opsional. |
| Implementasi | `Support\Audit\DatabaseAuditTrail` | Menulis ke tabel `activity_log` (spatie/laravel-activitylog, model `Support\Audit\AuditLog`). |
| Append-only | `Support\Database\AppendOnlyGuard::attach('activity_log')` | Trigger Postgres menolak UPDATE/DELETE dari jalur mana pun (Eloquent, SQL mentah). Dipasang di migration, bukan di model. |
| Redaksi | `Support\Audit\Redactor::clean($context)` | Dijalankan **sebelum** disimpan (bukan saat dibaca): kunci berisi pass/token/secret/code/key/cookie disamarkan. |
| Lokasi (konteks) | `location_id` nullable | Diisi hanya bila tepat satu lokasi berlaku (`LocationContext::ids()`); ambigu atau tanpa konteks → `null`. |

### Cara memakai
- Aksi sensitif yang berdampak ke satu entitas: sertakan `$subject` (mis. `Role`, `Location`) supaya tercatat di `subject_type`/`subject_id`, bukan hanya di `properties`.
- Perubahan + audit untuk aksi yang sama **wajib satu `DB::transaction`** (ISS-027): bila audit gagal ditulis, perubahan ikut batal — tidak ada perubahan yang "lolos" tanpa jejak. Lihat `SyncRolePermissions`/`SetLocationCutover` sebagai pola.
- Jangan pernah membaca/menulis `activity_log` lewat jalur lain selain `AuditTrail`; tabel ini sengaja tidak boleh diubah aplikasi (termasuk oleh Super Admin).
- Perintah `activitylog:clean` sengaja tidak dipakai (append-only bertentangan dengan auto-delete); retensi/arsip ditangani operasional di luar aplikasi.

### Keputusan yang perlu diingat
- Menutup ISS-019: audit kini di tabel database, bukan channel log `LogAuditTrail` (dihapus).
- ISS-022 ditutup: `AuditAuthEvents::email()` menolak nilai yang bukan format email (`FILTER_VALIDATE_EMAIL`) → disimpan sebagai `[bukan-email]`, mencegah password yang salah ketik di kolom email tersimpan 365 hari.
- `log_name` selalu `'audit'` untuk entri dari kontrak ini; `event`/`description` diisi nama aksi yang sama (mis. `auth.login`, `rbac.role_permissions_changed`) supaya mudah difilter.
- Trigger database adalah lapis terakhir yang berlaku **selalu**, terlepas dari isi model `AuditLog` — jangan andalkan `$guarded`/`$fillable` sebagai satu-satunya pengaman.
