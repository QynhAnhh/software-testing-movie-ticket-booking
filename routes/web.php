<?php

require_once __DIR__ . '/../app/controllers/HomeController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = '/movie-ticket-booking/public';
$route = str_replace($basePath, '', $uri);

if ($route === '' || $route === '/' || $route === '/index.php') {
    $controller = new HomeController();
    $controller->index();
    exit;
}

http_response_code(404);
echo '404 - Page Not Found';