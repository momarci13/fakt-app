<?php

declare(strict_types=1);

$requiredExtensions = ['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'intl', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'session', 'simplexml', 'tokenizer', 'xml', 'zip'];
$errors = [];

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    $errors[] = 'PHP 8.3 vagy újabb szükséges; jelenlegi: '.PHP_VERSION;
}

foreach ($requiredExtensions as $extension) {
    if (! extension_loaded($extension)) {
        $errors[] = 'Hiányzó PHP-bővítmény: '.$extension;
    }
}

foreach ([dirname(__DIR__).'/storage', dirname(__DIR__).'/bootstrap/cache'] as $path) {
    if (! is_dir($path) || ! is_writable($path)) {
        $errors[] = 'Nem írható könyvtár: '.$path;
    }
}

echo "FAKT Rackhost preflight\n";
echo 'PHP: '.PHP_VERSION."\n";
echo 'SAPI: '.PHP_SAPI."\n";

if ($errors !== []) {
    foreach ($errors as $error) {
        echo '[HIBA] '.$error."\n";
    }
    exit(1);
}

echo "[OK] A PHP futtatókörnyezet megfelel. Külön ellenőrizd az SSH/Composer, cron, SSL, SMTP, MySQL-kvóta és mentés elérhetőségét.\n";
