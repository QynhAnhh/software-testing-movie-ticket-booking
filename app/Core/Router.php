<?php

namespace App\Core;

class Router
{
    public Request $request;
    public Response $response;
    protected array $routes = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get($path, $callback)
    {
        $this->routes['get'][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $this->routes['post'][$path] = $callback;
    }

    public function resolve()
    {
        $path = $this->request->getPath();
        $method = $this->request->method();
        
        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            $this->response->setStatusCode(404);
            return "404 - Page Not Found";
        }

        if (is_array($callback)) {
            // Instantiate the controller class
            $controller = new $callback[0]();
            $callback[0] = $controller;
        }

        return call_user_func($callback, $this->request);
    }
}
