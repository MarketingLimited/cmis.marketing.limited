<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Clear system environment variables that override .env file
// This ensures .env values are used even when system has conflicting vars
$envVarsToClear = [
    'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE', 'DB_HOST', 'DB_PORT', 'DB_CONNECTION',
    'CACHE_STORE', 'SESSION_DRIVER', 'QUEUE_CONNECTION',
];
foreach ($envVarsToClear as $var) {
    putenv($var);
    unset($_ENV[$var], $_SERVER[$var]);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
