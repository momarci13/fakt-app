<?php

return [
    'trusted_host' => env('APP_TRUSTED_HOST', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),

    'require_privileged_mfa' => (bool) env('SECURITY_REQUIRE_PRIVILEGED_MFA', env('APP_ENV') === 'production'),

    'input' => [
        'max_depth' => 8,
        'max_fields' => 500,
        'max_string_bytes' => 50_000,
        'max_secret_bytes' => 4_096,
    ],

    'uploads' => [
        'max_bytes' => 10 * 1024 * 1024,
        'max_archive_entries' => 2_000,
        'max_uncompressed_bytes' => 50 * 1024 * 1024,
    ],
];
