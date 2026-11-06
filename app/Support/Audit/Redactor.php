<?php

declare(strict_types=1);

namespace App\Support\Audit;

/** Menyamarkan nilai sensitif sebelum masuk audit log (password, token, secret, kode 2FA, cookie). */
final class Redactor
{
    private const SENSITIVE = '/pass|token|secret|code|authorization|cookie|credential|key/i';

    private const MAX_STRING = 500;

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function clean(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE, $key) === 1) {
                $clean[$key] = '[disamarkan]';

                continue;
            }
            $clean[$key] = match (true) {
                is_array($value) => self::clean($value),
                is_string($value) => mb_substr($value, 0, self::MAX_STRING),
                is_scalar($value) || $value === null => $value,
                default => '['.get_debug_type($value).']',
            };
        }

        return $clean;
    }
}
