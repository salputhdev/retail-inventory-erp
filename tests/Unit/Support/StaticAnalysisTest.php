<?php

declare(strict_types=1);

/**
 * T1.01 kriteria 3: Larastan menolak penugasan float ke kolom qty/uang di model.
 * Menjalankan Larastan pada fixture kecil (konfigurasi terpisah), jadi test ini lambat (beberapa detik).
 *
 * @return array<string, list<string>> pesan error per nama file fixture
 */
function phpstanMessagesByFixture(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $root = dirname(__DIR__, 3);
    $output = (string) shell_exec(sprintf(
        'cd %s && ./vendor/bin/phpstan analyse -c tests/Fixtures/StaticAnalysis/phpstan.neon --error-format=json --no-progress --memory-limit=1G 2>&1',
        escapeshellarg($root),
    ));
    $start = strpos($output, '{');
    $json = $start === false ? null : json_decode(substr($output, $start), true);
    expect($json)->toBeArray('Output PHPStan tidak terbaca: '.substr($output, 0, 500));

    $byFile = [];
    // Format standar PHPStan: files.<path>.messages[]; format pembungkus (laravel/pao): error_details.<path>[].
    foreach ($json['files'] ?? [] as $path => $info) {
        foreach ($info['messages'] as $m) {
            $byFile[basename($path)][] = $m['message'];
        }
    }
    foreach ($json['error_details'] ?? [] as $path => $messages) {
        foreach ($messages as $m) {
            $byFile[basename($path)][] = $m['message'];
        }
    }

    return $cache = $byFile;
}

it('Larastan menolak penugasan float ke atribut Quantity dan Money', function () {
    $messages = phpstanMessagesByFixture()['FloatAssignment.php'] ?? [];

    expect($messages)->toHaveCount(2);
    foreach ($messages as $message) {
        expect($message)->toContain('does not accept float');
    }
});

it('Larastan menerima Quantity, string, dan int', function () {
    expect(phpstanMessagesByFixture())->not->toHaveKey('ValidAssignment.php');
});
