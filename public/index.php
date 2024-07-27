<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

// Load environment variables
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__) . '/.env');

// Overload environment variables from .env.local if it exists
if (file_exists(dirname(__DIR__) . '/.env.local')) {
    $dotenv->overload(dirname(__DIR__) . '/.env.local');
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};