<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Keep everything above vendor/autoload.php parseable and runnable on PHP 5.4+,
// so a wrong PHP handler reports itself instead of producing a blank HTTP 500.
// See deploy/cpanel-public-index.php for the full explanation.

define('LARAVEL_START', microtime(true));

if (PHP_VERSION_ID < 80300) {
    $detail = '['.date('Y-m-d H:i:s').'] A web PHP verzio '.PHP_VERSION.', de PHP 8.3 vagy ujabb kell.'
        .' | sapi='.PHP_SAPI
        .' | uri='.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-');

    if (@file_put_contents(dirname(__DIR__).'/storage/logs/bootstrap-error.log', $detail."\n", FILE_APPEND | LOCK_EX) === false) {
        @error_log($detail);
    }

    if (! headers_sent()) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');

        echo "A FAKT alkalmazas nem tud elindulni.\n\n";
        echo "A webkiszolgalo PHP ".PHP_VERSION." verzioval fut, az alkalmazas viszont PHP 8.3 vagy ujabb verziot igenyel.\n";
    }

    exit;
}

if (file_exists(__DIR__.'/../storage/framework/maintenance.php')) {
    require __DIR__.'/../storage/framework/maintenance.php';
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

/** @var Application $app */
$app->handleRequest(Request::capture());
