<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// InfinityFree: Laravel app root adalah satu level di atas htdocs/
define('APP_ROOT', dirname(__DIR__));

// Maintenance mode check
if (file_exists(APP_ROOT.'/storage/framework/maintenance.php')) {
    require APP_ROOT.'/storage/framework/maintenance.php';
}

require APP_ROOT.'/vendor/autoload.php';

$app = require_once APP_ROOT.'/bootstrap/app.php';

$app->bind('path.public', fn() => __DIR__);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
