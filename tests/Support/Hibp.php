<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Palsu untuk layanan HaveIBeenPwned (k-anonymity range API) agar test TIDAK menyentuh internet.
 * Password yang didaftarkan lewat leak() dianggap bocor; selain itu aman.
 */
final class Hibp
{
    /** @var list<string> */
    private static array $leaked = [];

    public static function install(): void
    {
        self::$leaked = [];
        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => str_contains($request->url(), 'api.pwnedpasswords.com/range/')
            ? Http::response(self::body(substr($request->url(), -5)), 200)
            : Http::response('', 500));
    }

    public static function leak(string $password): void
    {
        self::$leaked[] = $password;
    }

    private static function body(string $prefix): string
    {
        $lines = ['0000000000000000000000000000000000A:1']; // baris pengisi agar respons tidak kosong
        foreach (self::$leaked as $password) {
            $hash = strtoupper(sha1($password));
            if (str_starts_with($hash, strtoupper($prefix))) {
                $lines[] = substr($hash, 5).':4242';
            }
        }

        return implode("\r\n", $lines);
    }
}
