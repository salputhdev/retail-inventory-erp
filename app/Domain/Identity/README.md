# Modul Identity

User, lokasi, autentikasi (T1.03), dan otorisasi (T1.04). Scope lokasi dijelaskan di ADR-003; bagian ini merangkum RBAC.

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
