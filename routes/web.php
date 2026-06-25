<?php

require_once __DIR__ . '/../app/controllers/HomeController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = '/movie-ticket-booking/public/index.php';
$route = str_replace($basePath, '', $uri);

if ($route === '' || $route === '/') {
    $route = '/';
}

if ($route === '/') {
    $controller = new HomeController();
    $controller->index();
    exit;
}

if ($route === '/login') {
    $controller = new AuthController();
    $controller->showLogin();
    exit;
}

if ($route === '/register') {
    $controller = new AuthController();
    $controller->showRegister();
    exit;
}

http_response_code(404);
echo '404 - Page Not Found';