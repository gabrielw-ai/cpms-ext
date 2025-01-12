<?php
echo "Current directory: " . __DIR__ . "\n";
echo "Parent directory: " . dirname(__DIR__) . "\n";
echo "Checking for vendor/autoload.php in various locations:\n";

$paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    '../vendor/autoload.php',
    '../../vendor/autoload.php'
];

foreach ($paths as $path) {
    echo "Checking: " . $path . " - " . (file_exists($path) ? "EXISTS" : "NOT FOUND") . "\n";
} 