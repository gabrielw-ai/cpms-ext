<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
require_once 'routing.php';

// If accessing root URL or index.php, route to dashboard
if ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '/index.php') {
    header('Location: ' . Router::url('dashboard'));
    exit;
}

$router = new Router();
$router->route();