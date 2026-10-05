<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config.php';

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Composer dependencies are missing. Run: composer install');
}
require_once $autoload;
