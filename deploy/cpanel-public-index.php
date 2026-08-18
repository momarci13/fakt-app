<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$configuredCorePath = getenv('FAKT_CORE_PATH') ?: dirname(__DIR__, 2).'/fakt-app-core';
$corePath = realpath(rtrim($configuredCorePath, '/\\'));

if ($corePath === false
    || ! is_file($corePath.'/artisan')
    || ! is_file($corePath.'/vendor/autoload.php')
    || str_starts_with($corePath, realpath(__DIR__) ?: __DIR__)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    echo 'Service temporarily unavailable.';
    exit;
}

if (file_exists($maintenance = $corePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $corePath.'/vendor/autoload.php';

$app = require_once $corePath.'/bootstrap/app.php';

/** @var Application $app */
$app->handleRequest(Request::capture());
