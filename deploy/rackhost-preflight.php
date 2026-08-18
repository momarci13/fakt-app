<?php

declare(strict_types=1);

$requiredExtensions = ['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'session', 'simplexml', 'tokenizer', 'xml', 'zip'];
$errors = [];
$warnings = [];
$corePath = dirname(__DIR__);

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    $errors[] = 'PHP 8.3 vagy újabb szükséges; jelenlegi: '.PHP_VERSION;
}

foreach ($requiredExtensions as $extension) {
    if (! extension_loaded($extension)) {
        $errors[] = 'Hiányzó PHP-bővítmény: '.$extension;
    }
}

foreach ([$corePath.'/storage', $corePath.'/bootstrap/cache'] as $path) {
    if (! is_dir($path) || ! is_writable($path)) {
        $errors[] = 'Nem írható könyvtár: '.$path;
    }
}

if (str_contains(str_replace('\\', '/', $corePath), '/public_html/')) {
    $errors[] = 'A Laravel core nem lehet a public_html alatt.';
}

$envPath = $corePath.'/.env';
if (! is_file($envPath) || ! is_readable($envPath)) {
    $errors[] = 'A production .env fájl hiányzik vagy nem olvasható.';
} else {
    $env = [];
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim(trim($value), "'\"");
    }

    $requiredValues = [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://app.fakt.org.hu',
        'APP_TRUSTED_HOST' => 'app.fakt.org.hu',
        'SESSION_ENCRYPT' => 'true',
        'SESSION_SECURE_COOKIE' => 'true',
        'SESSION_SAME_SITE' => 'lax',
        'SECURITY_REQUIRE_PRIVILEGED_MFA' => 'true',
    ];
    foreach ($requiredValues as $key => $expected) {
        if (strtolower((string) ($env[$key] ?? '')) !== strtolower($expected)) {
            $errors[] = $key.' értéke legyen: '.$expected;
        }
    }

    if (! str_starts_with((string) ($env['APP_KEY'] ?? ''), 'base64:')) {
        $errors[] = 'Az APP_KEY hiányzik vagy nem Laravel base64 kulcs.';
    }
    foreach (['DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'MAIL_USERNAME', 'MAIL_PASSWORD'] as $key) {
        $value = (string) ($env[$key] ?? '');
        if ($value === '' || preg_match('/(?:YOUR_|CSERELD|PLACEHOLDER|CHANGE_ME)/i', $value) === 1) {
            $errors[] = $key.' nincs valódi production értékre állítva.';
        }
    }
    if ((int) ($env['BCRYPT_ROUNDS'] ?? 0) < 12) {
        $errors[] = 'BCRYPT_ROUNDS legalább 12 legyen.';
    }
    if (($env['DB_HOST'] ?? '') === 'localhost') {
        $warnings[] = 'DB_HOST=localhost socket hibát okozhat Rackhoston; használd a 127.0.0.1 értéket.';
    }
}

echo "FAKT Rackhost preflight\n";
echo 'PHP: '.PHP_VERSION."\n";
echo 'SAPI: '.PHP_SAPI."\n";

foreach ($warnings as $warning) {
    echo '[FIGYELEM] '.$warning."\n";
}

if ($errors !== []) {
    foreach ($errors as $error) {
        echo '[HIBA] '.$error."\n";
    }

    exit(1);
}

echo "[OK] A PHP-futtatókörnyezet megfelel. Külön ellenőrizd a cront, SSL-t, SMTP-t, MySQL-kvótát és mentést. SSH vagy szerveroldali Composer nem szükséges a cPanel kiadási csomaghoz.\n";
