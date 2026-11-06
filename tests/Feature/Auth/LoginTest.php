<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\Support\AuthHelpers;
use Tests\Support\Browser;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('menampilkan halaman login tanpa opsi "ingat saya"', function () {
    get('/login')->assertOk()->assertSee('Masuk')->assertDontSee('remember', false)->assertDontSee('Ingat saya');
});

it('login berhasil: masuk ke dashboard, mencatat last_login_at, dan menulis audit', function () {
    $audit = AuthHelpers::fakeAudit();
    $user = AuthHelpers::user();
    $browser = new Browser;

    $browser->login($user->email, AuthHelpers::PASSWORD)->assertRedirect('/dashboard');

    $browser->get('/dashboard')->assertOk()->assertSee($user->name);
    expect($user->fresh()->last_login_at)->not->toBeNull();
    expect($audit->actions())->toContain('auth.login');
    expect($audit->last('auth.login')['actor'])->toBe($user->id);
});

it('login gagal memberi pesan yang SAMA untuk email tak dikenal, password salah, dan user nonaktif (anti-enumerasi)', function () {
    $active = AuthHelpers::user();
    $inactive = AuthHelpers::user(['is_active' => false]);
    $messages = [];

    foreach ([
        ['email' => 'tidak-ada@example.test', 'password' => AuthHelpers::PASSWORD],
        ['email' => $active->email, 'password' => 'password-salah-sekali'],
        ['email' => $inactive->email, 'password' => AuthHelpers::PASSWORD], // password BENAR tapi nonaktif
    ] as $i => $credentials) {
        $browser = new Browser('203.0.113.'.(50 + $i));
        $response = $browser->post('/login', $credentials);
        $response->assertRedirect()->assertSessionHasErrors('email');
        $messages[] = session('errors')->first('email');
    }

    expect(array_unique($messages))->toHaveCount(1);
    expect($messages[0])->toBe(__('auth.failed'));
});

it('user nonaktif tidak bisa login walau password benar, dan tidak ada sesi terbentuk', function () {
    $inactive = AuthHelpers::user(['is_active' => false]);
    $browser = new Browser;

    $browser->login($inactive->email, AuthHelpers::PASSWORD);
    $browser->get('/dashboard')->assertRedirect('/login');
});

it('selalu mencocokkan hash BCRYPT sungguhan walau email tidak terdaftar (waktu respons sama)', function () {
    $real = AuthHelpers::user();
    $checkedHashes = [];
    Hash::partialMock()->shouldReceive('check')->andReturnUsing(function ($value, $hashed) use (&$checkedHashes) {
        $checkedHashes[] = $hashed;

        return false;
    });

    (new Browser)->post('/login', ['email' => 'hantu@example.test', 'password' => 'apa-saja-123456']);
    (new Browser('203.0.113.12'))->post('/login', ['email' => $real->email, 'password' => 'apa-saja-123456']);

    expect($checkedHashes)->toHaveCount(2);
    foreach ($checkedHashes as $hash) {
        // Hash palsu harus berbiaya sama dengan hash asli, bukan string kosong yang selesai seketika.
        expect(password_get_info($hash)['algoName'])->toBe('bcrypt');
        expect(password_get_info($hash)['options']['cost'])->toBe(password_get_info($real->password)['options']['cost']);
    }
});

it('email berhuruf campuran di database tetap bisa login dengan input huruf kecil', function () {
    // Data lama/impor bisa menyimpan huruf besar; Fortify mengecilkan input, jadi pencarian harus tidak peka huruf.
    $user = AuthHelpers::user(['email' => 'Rina.Wati@Example.Test']);
    $browser = new Browser;

    $browser->login('rina.wati@example.test', AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
    $browser->get('/dashboard')->assertOk();
    expect($user->id)->toBeInt();
});

it('email dicocokkan tanpa membedakan huruf besar/kecil', function () {
    $user = AuthHelpers::user(['email' => 'budi@example.test']);
    $browser = new Browser;

    $browser->login('BUDI@Example.Test', AuthHelpers::PASSWORD)->assertRedirect('/dashboard');
    $browser->get('/dashboard')->assertOk();
    expect($user->id)->toBeInt();
});

it('tidak ada "ingat saya": parameter remember diabaikan dan tidak ada cookie recaller', function () {
    $user = AuthHelpers::user();
    $tokenBefore = $user->remember_token;
    $browser = new Browser;

    $browser->get('/login');
    $response = $browser->post('/login', ['email' => $user->email, 'password' => AuthHelpers::PASSWORD, 'remember' => 'on']);

    foreach ($response->baseResponse->headers->getCookies() as $cookie) {
        expect($cookie->getName())->not->toStartWith('remember_web');
    }
    // Login dengan remember=true akan memutar remember_token; tidak boleh berubah.
    expect($user->fresh()->remember_token)->toBe($tokenBefore);
});

it('ID sesi diganti saat login (anti session fixation)', function () {
    $user = AuthHelpers::user();
    $browser = new Browser;

    $browser->get('/login');
    $before = $browser->sessionCookie();
    expect($before)->not->toBeNull();

    $browser->post('/login', ['email' => $user->email, 'password' => AuthHelpers::PASSWORD]);
    expect($browser->sessionCookie())->not->toBe($before);
});

it('logout mengakhiri sesi dan mencatat audit', function () {
    $audit = AuthHelpers::fakeAudit();
    $user = AuthHelpers::user();
    $browser = new Browser;
    $browser->login($user->email, AuthHelpers::PASSWORD);

    $browser->post('/logout')->assertRedirect('/');
    $browser->get('/dashboard')->assertRedirect('/login');
    expect($audit->actions())->toContain('auth.logout');
});

it('registrasi publik tidak ada', function () {
    get('/register')->assertNotFound();
    post('/register', ['name' => 'x', 'email' => 'x@example.test', 'password' => 'Kata-sandi-aman-2026', 'password_confirmation' => 'Kata-sandi-aman-2026'])
        ->assertStatus(404)->assertDontSee('dashboard');
    expect(User::query()->where('email', 'x@example.test')->exists())->toBeFalse();
});

it('dashboard mewajibkan login dan verifikasi email', function () {
    $verified = AuthHelpers::user();
    $unverified = AuthHelpers::user(['email_verified_at' => null]);

    (new Browser)->get('/dashboard')->assertRedirect('/login');

    $b = new Browser('203.0.113.60');
    $b->login($unverified->email, AuthHelpers::PASSWORD);
    $b->get('/dashboard')->assertRedirect('/email/verify');

    $c = new Browser('203.0.113.61');
    $c->login($verified->email, AuthHelpers::PASSWORD);
    $c->get('/dashboard')->assertOk();
    expect(Auth::check())->toBeTrue();
});
