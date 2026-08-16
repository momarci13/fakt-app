<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$corePath = getenv('FAKT_CORE_PATH') ?: dirname(__DIR__, 2).'/fakt-app-core';
$corePath = rtrim($corePath, '/\\');

if (file_exists($maintenance = $corePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $corePath.'/vendor/autoload.php';

$app = require_once $corePath.'/bootstrap/app.php';

/** @var Application $app */
$app->handleRequest(Request::capture());
