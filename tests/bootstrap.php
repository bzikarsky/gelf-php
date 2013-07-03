<?php

$autoloadFile = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloadFile)) {
    die("Autoloader cannot be found. Please install dependencies first ('composer install --dev')\n");
}

require_once $autoloadFile;
