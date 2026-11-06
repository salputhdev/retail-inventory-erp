<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Pest\TestSuite;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Klien HTTP sederhana yang MEMBAWA cookie antar-request (sesi), seperti browser sungguhan. Test HTTP Laravel
 * tidak melakukannya, padahal batas sesi (S-04) bergantung pada data sesi. Tiap request memakai guard baru,
 * meniru proses PHP baru per request.
 */
final class Browser
{
    /** @var array<string, string> cookie mentah (terenkripsi) */
    private array $cookies = [];

    private readonly TestCase $test;

    public function __construct(private readonly string $ip = '203.0.113.10')
    {
        // Test aktif diambil dari Pest, jadi test tidak perlu memakai $this (yang bertipe TestCall bagi PHPStan).
        $test = TestSuite::getInstance()->test;
        if (! $test instanceof TestCase) {
            throw new \LogicException('Browser hanya bisa dipakai di dalam test Pest berbasis Tests\TestCase.');
        }
        $this->test = $test;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TestResponse<Response>
     */
    public function post(string $uri, array $data = []): TestResponse
    {
        return $this->send('post', $uri, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TestResponse<Response>
     */
    public function postJson(string $uri, array $data = []): TestResponse
    {
        return $this->send('postJson', $uri, $data);
    }

    /** @return TestResponse<Response> */
    public function get(string $uri): TestResponse
    {
        return $this->send('get', $uri);
    }

    /** @return TestResponse<Response> */
    public function getJson(string $uri): TestResponse
    {
        return $this->send('getJson', $uri);
    }

    public function sessionCookie(): ?string
    {
        return $this->cookies[config('session.cookie')] ?? null;
    }

    /**
     * Login sungguhan lewat form; mengembalikan respons POST /login.
     *
     * @return TestResponse<Response>
     */
    public function login(string $email, string $password): TestResponse
    {
        $this->get('/login');

        return $this->post('/login', ['email' => $email, 'password' => $password]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TestResponse<Response>
     */
    private function send(string $method, string $uri, array $data = []): TestResponse
    {
        Auth::forgetGuards();
        app()->forgetScopedInstances();
        // Di produksi tiap request = proses baru. Di test, objek session store dipakai ulang dan loadSession()
        // MENGGABUNGKAN atribut lama, sehingga sesi satu Browser bocor ke Browser lain. Kosongkan atributnya
        // (data di handler tetap, jadi sesi berbasis cookie tetap terbaca).
        app('session.store')->flush();

        // Ganti (bukan tambah) cookie milik test: withUnencryptedCookies() menumpuk antar-request dan antar-Browser.
        (function (array $cookies): void {
            $this->unencryptedCookies = $cookies;
            $this->defaultCookies = [];
        })->call($this->test, $this->cookies);

        // withCredentials(): request JSON hanya mengirim cookie bila ini aktif (seperti fetch dengan credentials).
        $call = $this->test->withCredentials()->withServerVariables(['REMOTE_ADDR' => $this->ip]);
        $response = match ($method) {
            'post' => $call->post($uri, $data),
            'postJson' => $call->postJson($uri, $data),
            default => $call->{$method}($uri),
        };

        foreach ($response->baseResponse->headers->getCookies() as $cookie) {
            if ($cookie->isCleared() || $cookie->getValue() === '' || $cookie->getValue() === null) {
                unset($this->cookies[$cookie->getName()]);
            } else {
                $this->cookies[$cookie->getName()] = (string) $cookie->getValue();
            }
        }

        return $response;
    }
}
