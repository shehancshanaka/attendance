<?php

use App\Controllers\HomeController;

return [
    '/' => [HomeController::class, 'index'],
    '/login' => [HomeController::class, 'login'],
];
