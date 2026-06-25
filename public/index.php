<?php

$routes = require_once __DIR__ . '/../routes/web.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = '/movie-ticket-booking/public';

$route = str_replace($basePath, '', $uri);

if ($route === '') {
    $route = '/';
}

if (!array_key_exists($route, $routes)) {
    http_response_code(404);
    echo '404 - Page Not Found';
    exit;
}

$controllerName = $routes[$route]['controller'];
$methodName = $routes[$route]['method'];

$controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

if (!file_exists($controllerFile)) {
    die('Controller not found: ' . $controllerName);
}

require_once $controllerFile;

$controller = new $controllerName();
$controller->$methodName();