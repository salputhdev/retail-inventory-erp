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
- Pesan "lupa password" bawaan Fortify membocorkan email terdaftar: respons gagal diganti respons sukses.

## Cara menguji pengaman (uji mutasi)
- Rusak pengaman satu per satu, pastikan test gagal, lalu pulihkan. **Pakai skrip `bash` dengan cadangan nyata dan `cmp`**: di zsh `$FILES` tidak dipecah per kata, cadangan gagal diam-diam, dan mutasi menumpuk di file produksi (terjadi sekali; dipulihkan manual).
- File yang belum ter-track git tidak bisa dipulihkan dengan `git checkout`.
- Mutan yang selamat belum tentu lubang: pertahanan berlapis saling menutupi (buktikan dengan mematikan kedua lapis).

## Alur kerja
- `todo-summary --check` harus hijau sebelum merge; task `done` hanya oleh Salman (atau AI setelah Salman mengonfirmasi).
- Review keamanan independen (`/review-security main`) hanya bisa dipanggil Salman; wajib untuk risiko R4 (auth, RBAC, scope lokasi, ledger).
- Perintah dengan `sleep` di foreground diblokir; jalankan lama di background. `curl` ke `localhost` bisa melaporkan 200 padahal isinya error PHP: verifikasi di browser.
- `npx` yang mengunduh kode dari internet diblokir pengaman; jangan disiasati.
