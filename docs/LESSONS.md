# Pelajaran Teknis (baca sebelum mengerjakan task)

Hal-hal yang sudah memakan waktu di M0–T1.02 dan jangan diulang. Tambahkan baris baru di akhir bagian yang sesuai; jangan menghapus.

## Git dan PR
- **Satu PR per task, langsung dari `main`.** PR bertumpuk (base = branch task lain) ter-merge ke branch usang, bukan ke `main` (terjadi di PR #6–#9; butuh PR #10 untuk memperbaikinya).
- Tiap task mengedit `TODO.md`, jadi PR yang berumur panjang konflik. Sebelum merge: gabungkan `main` ke branch (jangan force-push; WORKFLOW melarangnya).
- `main` terproteksi: 5 check CI wajib + 1 review. Karena kontributor tunggal tidak bisa menyetujui PR sendiri, Salman merge dengan **bypass admin** dan memilih **"Create a merge commit"** (bukan squash).
- Git tidak menyimpan folder kosong: `tests/Unit` hilang di CI sampai diberi `.gitkeep`.

## Lingkungan dan Docker
- Akun GitHub pribadi punya **billing terkunci**, jadi Actions gagal (`startup_failure`) walau repo publik. Repo aktif ada di organisasi **`salputhdev/retail-inventory-erp`**.
- Docker Desktop di macOS: tambahkan `/Applications/Docker.app/Contents/Resources/bin` ke PATH (credential helper). Jangan menutup Docker di tengah build.
- `php artisan serve` membuang env var yang juga ada di `.env`; di Compose pakai `php -S` langsung (lihat `docker-compose.yml`).
- Ekstensi `redis` gagal dikompilasi lewat PECL di PHP 8.5: pakai `install-php-extensions`.
- `COPY . .` membawa `bootstrap/cache/*.php` dari host ke image `--no-dev` (provider dev ikut): exclude di `.dockerignore`.
- Port 5432 host dipakai PostgreSQL Homebrew: container dev memakai 5433.
- **VPS Hostinger adalah host situs live `salmanrizwana.com`** (1 vCPU/4 GB). Jangan memasang stack di sana tanpa persetujuan eksplisit.

## Test dan analisis statis
- Test **wajib** lewat `make test` / `DB_DATABASE=retail_inventory_test`; `TestCase` menolak database tanpa akhiran `_test` karena `RefreshDatabase` menjalankan `migrate:fresh`. Lokal tanpa Docker: `DB_USERNAME=salman DB_PASSWORD= ./vendor/bin/pest`.
- `phpunit.xml` `force="true"` tidak mengalahkan env container (Laravel membaca `$_SERVER` lebih dulu).
- Test tidak boleh bergantung pada `public/build`: `TestCase::setUp` memanggil `withoutVite()`.
- Larastan tidak mengenal `$this->prop` di closure Pest: pakai variabel lokal dari helper (`$d = LocationFixtures::boot()`).
- Larastan membaca macro Builder sebagai tidak ada: panggil lewat `__call('nama', [])` di test.
- Output Pest/PHPStan di lingkungan ini dibungkus `laravel/pao` (JSON ber-format sendiri). Parser test menangani format standar dan format itu.
- Model fixture tanpa tabel butuh `@property-read`/`@property-write` terpisah agar Larastan menolak `float` tetapi menerima `string|int`.
- Jangan `@phpstan-ignore`; perbaiki akarnya (aturan proyek).

## Keamanan (ADR-003, R4)
- **PostgreSQL 16+ membaca `'1_0'::bigint` sebagai 10 dan `'0x0A'` sebagai 10**, sedangkan PHP `(int)"1_0"` = 1. Validasi `location_id` ketat (`LocationWriteGuard::normalize`) dan tulis ulang nilai yang sudah divalidasi.
- `Eloquent\Builder::__call` meneruskan metode yang tidak dikenal ke query mentah **tanpa global scope**; sebagian metode tulis ada di `passthru`. `LocationScopedBuilder` menolak metode tulis yang belum dijaga, dan `LocationBuilderBypassTest` gagal bila Laravel menambah metode baru.
- `upsert` dengan kunci konflik tanpa `location_id` menimpa baris lokasi lain: indeks unik tabel transaksional wajib memuat `location_id`.
- Worker queue tidak punya `Auth`: periksa akses saat dispatch dan periksa ulang saat job berjalan (`CapturesLocationActor` + `RunsInLocation`).
- Tabel transaksional baru: model `BelongsToLocation`, migration `LocationGuard::attach()`, indeks unik memuat `location_id`, tanpa `DB::table()->insert()` mentah.
- Pengaman tulis dipasang di `performInsert/performUpdate` dan builder, bukan event model (`saveQuietly`/`withoutEvents` melewati event).

## Autentikasi (T1.03)
- Fortify bawaan: throttle 1 menit; `limiters.login` terisi menghitung SEMUA request di route. Pakai `limiters.login => null` + `LoginRateLimiter` sendiri (hitung hanya gagal).
- "Ingat saya" melewati batas sesi absolut (cookie panjang memicu event `Login` lagi): dimatikan.
- Test HTTP Laravel tidak membawa cookie antar-request, dan objek session store dipakai ulang (atribut lama DIGABUNG saat load): gunakan `Tests\Support\Browser` (cookie + `flush()` store). Request JSON butuh `withCredentials()`.
- Handler sesi `array` membuang sesi setelah `session.lifetime`: naikkan di test agar yang diuji adalah middleware, bukan TTL.
- `$this` di closure Pest bertipe `TestCall` bagi Larastan, dan ekstensi Pest tidak membantu: jangan pakai `$this`; ambil test aktif lewat `Pest\TestSuite::getInstance()->test` (Browser) dan `Tests\Support\Clock` untuk waktu.
- Model dari factory tidak memuat default kolom database (`is_active`): isi eksplisit di factory atau `->fresh()`.
- `Http::fake()` pertama menang: pakai satu closure (`Tests\Support\Hibp`) agar test bisa menandai password bocor; `preventStrayRequests()` menjamin test tidak menyentuh internet.
- Pesan "lupa password" bawaan Fortify membocorkan email terdaftar: respons gagal diganti respons sukses, **termasuk bentuk JSON** (sukses bawaan = 200 JSON, gagal = redirect/422: beda bentuk = bocor).
- Hash palsu untuk anti-timing jangan memakai `static` + `Hash::make` per request: PHP-FPM mereset `static`, jadi hanya email tak dikenal yang membayar 2 bcrypt. Simpan sekali di cache (`DummyHash`).
- Fortify selalu mendaftarkan `/user/confirm-password` tanpa batas; pasang batas lewat `Fortify::confirmPasswordsUsing`.
- Batas login per `email+IP` tidak menghentikan botnet (1 tebakan per IP): kunci per akun (hash email ternormalisasi).
- `Hash::partialMock()` tidak punya container: `Hash::make()` asli rusak di dalamnya. Hangatkan hal yang butuh `make` sebelum mock. Hasher bcrypt membaca ronde saat dibuat: ubah lewat `Hash::driver('bcrypt')->setRounds()`, bukan hanya `config()`.
- Mutan yang selamat karena lapis lain menutupinya (mis. `TrimStrings`): uji komponen langsung (limiter dengan `Request::create`), jangan hanya lewat HTTP.

## Otorisasi (T1.04)
- `Gate::before` bawaan spatie mengizinkan `can('izin', $model)` tanpa memanggil Policy, jadi cek lokasi terlewati. Dimatikan (`register_permission_check_method => false`); penggantinya `PermissionGate` menolak izin yang diberi argumen.
- `Gate::before` berupa objek invokable gagal untuk guest (Gate memakai `ReflectionFunction`): daftarkan sebagai Closure, `(new PermissionGate)(...)`.
- Worker queue menyimpan izin spatie di memori sampai restart: kosongkan di `JobProcessing`. Spatie juga membuang cache di dalam transaksi; buang lagi setelah commit agar request lain tidak menyimpan data lama.
- `Permission::findOrCreate` memuat ulang seluruh tabel izin setiap kali cache dibuang: seeder 37 izin jadi O(n²) (matriks 185 test: 44 dtk → 10 dtk setelah diganti query biasa).
- `Role::findByName()` bertipe kontrak spatie (Larastan menolak dipakai sebagai model): pakai `Role::query()->where('name', ...)->firstOrFail()`.
- `Response::deny()` tanpa kode punya `status()` null (dirender 403); `denyAsNotFound()` = 404.
- Laravel menemukan `Domain\X\Policies\YPolicy` otomatis untuk `Domain\X\Models\Y`; registrasi eksplisit tetap dipasang, dan uji mutasinya perlu mematikan auto-discovery (`Gate::guessPolicyNamesUsing`).
- Closure di dalam baris dataset Pest tidak dievaluasi; jangan membuat model di dataset (model ter-boot sebelum aplikasi siap).
- Lokal tanpa Docker: `artisan db:seed` butuh `CACHE_STORE=array` (PHP host tidak punya ekstensi redis).

## Audit log (T1.07)
- Postgres menggagalkan SELURUH transaksi setelah satu statement gagal (`25P02 current transaction is aborted`): test yang mencoba dua pelanggaran trigger berturutan (update lalu delete) harus membungkus tiap percobaan dengan `DB::transaction()` sendiri (jadi savepoint), bukan satu transaksi test untuk semuanya.
- Redaksi data sensitif harus dijalankan **sebelum** `->create()`, bukan saat membaca: kalau hanya disamarkan di accessor, nilai asli tetap ada di kolom `jsonb` dan bisa dibaca lewat `DB::table()` mentah.
- Trigger `BEFORE UPDATE OR DELETE` (append-only) berlaku untuk SEMUA jalur, termasuk `DB::table()->update()/delete()` mentah — tidak bisa diakali lewat bypass Eloquent.
- `array $old === $array $new` di Pest gagal untuk array asosiatif yang urutannya beda (round-trip lewat `jsonb`/cast `array` bisa mengubah urutan key): pakai `toEqual()`, bukan `toBe()`, untuk membandingkan isi array tanpa peduli urutan.
- Mengubah perubahan+audit menjadi satu `DB::transaction()` (dari sebelumnya dua langkah) cukup diuji dengan fake `AuditTrail` yang melempar exception di tengah: kalau perubahan ikut batal, transaksinya benar atomic.

## Cara menguji pengaman (uji mutasi)
- Rusak pengaman satu per satu, pastikan test gagal, lalu pulihkan. **Pakai skrip `bash` dengan cadangan nyata dan `cmp`**: di zsh `$FILES` tidak dipecah per kata, cadangan gagal diam-diam, dan mutasi menumpuk di file produksi (terjadi sekali; dipulihkan manual).
- File yang belum ter-track git tidak bisa dipulihkan dengan `git checkout`.
- Mutan yang selamat belum tentu lubang: pertahanan berlapis saling menutupi (buktikan dengan mematikan kedua lapis).

## Alur kerja
- `todo-summary --check` harus hijau sebelum merge; task `done` hanya oleh Salman (atau AI setelah Salman mengonfirmasi).
- Review keamanan independen (`/review-security main`) hanya bisa dipanggil Salman; wajib untuk risiko R4 (auth, RBAC, scope lokasi, ledger).
- Perintah dengan `sleep` di foreground diblokir; jalankan lama di background. `curl` ke `localhost` bisa melaporkan 200 padahal isinya error PHP: verifikasi di browser.
- `npx` yang mengunduh kode dari internet diblokir pengaman; jangan disiasati.
