<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;
use Tests\Support\Hibp;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test tidak boleh bergantung pada hasil build aset (public/build tidak ada di CI).
        $this->withoutVite();

        // Tidak ada request keluar di test; cek password bocor (HIBP) dipalsukan (S-03).
        Hibp::install();
    }

    /**
     * Pengaman: RefreshDatabase menjalankan migrate:fresh, jadi test tidak
     * boleh pernah menyentuh database selain database test.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $database = (string) config('database.connections.'.config('database.default').'.database');

        if (! str_ends_with($database, '_test')) {
            throw new RuntimeException(
                "Test ditolak: database '{$database}' bukan database test (harus berakhiran '_test'). Jalankan lewat `make test`."
            );
        }

        return $app;
    }
}
