<?php

namespace App\Support;

final class UntrustedInput
{
    /** @var array<int, string> */
    private const SECRET_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'code',
        'recovery_code',
        'token',
    ];

    /** @var array<string, string> */
    private const THREAT_PATTERNS = [
        'script markup' => '/<\s*script\b|<\?\s*(?:php|=)|javascript\s*:|data\s*:\s*text\/html/iu',
        'destructive SQL' => '/\b(?:drop|truncate|alter)\s+(?:table|database|schema)\b/iu',
        'SQL union' => '/\bunion(?:\s+all)?\s+select\b/iu',
        'SQL metadata access' => '/\binformation_schema\b|\binto\s+(?:out|dump)file\b/iu',
        'SQL delay or file function' => '/\b(?:sleep|benchmark|load_file)\s*\(/iu',
        'SQL tautology' => '/(?:\x27|\x22)\s*(?:or|and)\s+\d+\s*=\s*\d+/iu',
        'command execution' => '/(?:^|[;&|]\s*)(?:bash|sh|cmd(?:\.exe)?|powershell|pwsh|curl|wget)\s+/iu',
        'path traversal' => '~(?:^|[/\\\\])\.\.(?:[/\\\\]|$)~u',
    ];

    /**
     * Returns a safe classification, never the hostile value itself.
     *
     * @param array<mixed> $input
     */
    public static function violation(array $input): ?string
    {
        $fieldCount = 0;

        return self::inspect($input, 0, $fieldCount, null);
    }

    public static function stringViolation(string $value, string $key = 'value'): ?string
    {
        $fieldCount = 0;

        return self::inspect($value, 0, $fieldCount, $key);
    }

    private static function inspect(mixed $value, int $depth, int &$fieldCount, ?string $key): ?string
    {
        if ($depth > (int) config('security.input.max_depth', 8)) {
            return 'structure-depth';
        }

        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $fieldCount++;
                if ($fieldCount > (int) config('security.input.max_fields', 500)) {
                    return 'field-count';
                }

                $keyString = (string) $childKey;
                if (! preg_match('/^(?:[A-Za-z0-9_.-]{1,80}|\d{1,10})$/D', $keyString)) {
                    return 'field-name';
                }

                if ($violation = self::inspect($childValue, $depth + 1, $fieldCount, $keyString)) {
                    return $violation;
                }
            }

            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $isSecret = $key !== null && in_array(strtolower($key), self::SECRET_KEYS, true);
        $maxBytes = $isSecret
            ? (int) config('security.input.max_secret_bytes', 4096)
            : (int) config('security.input.max_string_bytes', 50000);

        if (strlen($value) > $maxBytes) {
            return 'string-size';
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            return 'invalid-utf8';
        }

        if (str_contains($value, "\0") || preg_match('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value) === 1) {
            return 'control-character';
        }

        if ($isSecret) {
            return null;
        }

        // Reject invisible direction-changing characters that can disguise identifiers or log entries.
        if (preg_match('/[\x{061C}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2069}\x{FEFF}]/u', $value) === 1) {
            return 'invisible-character';
        }

        foreach (self::THREAT_PATTERNS as $classification => $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return $classification;
            }
        }

        return null;
    }
}
