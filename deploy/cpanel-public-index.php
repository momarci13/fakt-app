<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// ---------------------------------------------------------------------------
// Bootstrap guard.
//
// Everything above the autoloader must stay parseable and runnable on the
// OLDEST PHP this hosting account can still be switched to (PHP 5.4+). No PHP 8
// syntax and no PHP 8 functions here.
//
// Reason: cPanel decides per-subdomain which PHP the web server uses
// (MultiPHP Manager), while the cron jobs pin /usr/local/bin/ea-php83 by full
// path. If the subdomain is left on an older PHP, every artisan command stays
// green and every browser request dies inside vendor/autoload.php - before
// Laravel exists, so nothing is ever written to storage/logs/laravel-*.log and
// the browser only sees a blank HTTP 500.
//
// This guard turns that silent failure into a message that names its own cause,
// and records anything else that breaks before Laravel boots into
// storage/logs/bootstrap-error.log.
// ---------------------------------------------------------------------------

define('LARAVEL_START', microtime(true));

/**
 * Report a failure that happened before Laravel could take over.
 *
 * @param  string|null  $corePath     Laravel core path, when already resolved.
 * @param  string       $publicText   Shown to the visitor. Never include paths.
 * @param  string       $logDetail    Written to disk only.
 * @return void
 */
function fakt_bootstrap_failure($corePath, $publicText, $logDetail)
{
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-';

    $entry = '['.date('Y-m-d H:i:s').'] '.str_replace(array("\r", "\n"), ' ', $logDetail)
        .' | php='.PHP_VERSION
        .' | sapi='.PHP_SAPI
        .' | uri='.$uri;

    $written = false;

    if (is_string($corePath) && $corePath !== '' && is_dir($corePath.'/storage/logs')) {
        $written = @file_put_contents(
            $corePath.'/storage/logs/bootstrap-error.log',
            $entry."\n",
            FILE_APPEND | LOCK_EX
        );
    }

    if ($written === false) {
        @error_log($entry);
    }

    if (headers_sent()) {
        // The response already went out; only the log entry above is useful now.
        exit;
    }

    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    header('Retry-After: 3600');

    echo $publicText."\n";
    exit;
}

$configuredCorePath = getenv('FAKT_CORE_PATH');

if ($configuredCorePath === false || $configuredCorePath === '') {
    $configuredCorePath = dirname(dirname(__DIR__)).'/fakt-app-core';
}

$corePath = realpath(rtrim($configuredCorePath, '/\\'));

$publicPath = realpath(__DIR__);

if ($publicPath === false) {
    $publicPath = __DIR__;
}

if ($corePath === false
    || ! is_file($corePath.'/artisan')
    || ! is_file($corePath.'/vendor/autoload.php')
    || strpos($corePath, $publicPath) === 0) {
    fakt_bootstrap_failure(
        null,
        'Service temporarily unavailable.',
        'A Laravel core mappa nem talalhato, hianyos, vagy a publikus mappan belul van. Keresett utvonal: '.$configuredCorePath
    );
}

// Must be checked before vendor/autoload.php: Composer's own platform_check.php
// throws from inside the autoloader, and on PHP 7.x this file's own PHP 8 calls
// would fatal first. Keep this block free of PHP 8 syntax and functions.
if (PHP_VERSION_ID < 80300) {
    fakt_bootstrap_failure(
        $corePath,
        "A FAKT alkalmazas nem tud elindulni.\n\n"
            ."A webkiszolgalo PHP ".PHP_VERSION." verzioval futtatja ezt az aldomaint,\n"
            ."az alkalmazas viszont PHP 8.3 vagy ujabb verziot igenyel.\n\n"
            ."Javitas: cPanel -> MultiPHP Manager -> app.fakt.org.hu -> PHP 8.3 -> Apply.\n\n"
            ."Az utemezett cron feladatok az /usr/local/bin/ea-php83 binarisra hivatkoznak,\n"
            ."ezert hibatlanul futnak akkor is, ha ez a beallitas rossz.",
        'A web PHP verzio '.PHP_VERSION.', de PHP 8.3 vagy ujabb kell. Allitsd at a MultiPHP Manager-ben.'
    );
}

if (file_exists($maintenance = $corePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

try {
    require $corePath.'/vendor/autoload.php';

    $app = require_once $corePath.'/bootstrap/app.php';

    // Read the Vite manifest from the directory the browser is served from, not
    // the core's own public/ copy: if the two come from different releases the
    // page references asset hashes that 404 and renders blank.
    /** @var Application $app */
    $app->usePublicPath($publicPath);

    $app->handleRequest(Request::capture());
} catch (Throwable $e) {
    // Laravel's own handler deals with everything raised inside the HTTP kernel.
    // Reaching this point means the framework never got far enough to log.
    fakt_bootstrap_failure(
        $corePath,
        'Service temporarily unavailable.',
        'Bootstrap kivetel: '.get_class($e).': '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()
    );
}
