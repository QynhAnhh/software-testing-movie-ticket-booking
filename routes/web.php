<?php

use App\Controllers\HomeController;

/** @var \App\Core\Router $router */
$router = $app->getRouter();

// Định nghĩa các route ở đây
$router->get('/', [HomeController::class, 'index']);
// Thêm các route khác sau này...