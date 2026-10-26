<?php
declare(strict_types=1);

namespace App\Core;

final class Env
{
    /**
     * Load KEY=VALUE pairs from a .env file. Variables already set in the real
     * environment take precedence, so server configuration can override the file.
     */
    public static function load(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = ltrim(substr($line, 7));
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) !== 1 || getenv($key) !== false) {
                continue;
            }
            $value = self::parseValue(trim($value));
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private static function parseValue(string $value): string
    {
        $quote = $value[0] ?? '';
        if (($quote === '"' || $quote === "'") && strlen($value) >= 2 && str_ends_with($value, $quote)) {
            return substr($value, 1, -1);
        }
        // Unquoted values may carry a trailing " # comment".
        return rtrim((string) preg_replace('/\s+#.*$/', '', $value));
    }
}
