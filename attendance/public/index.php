<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Route the request
use App\Controllers\HomeController;

$controller = new HomeController();
$controller->index();
