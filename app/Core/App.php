<?php

namespace App\Core;

class App
{
    protected Router $router;
    protected Request $request;
    protected Response $response;
    public static App $app;

    public function __construct()
    {
        self::$app = $this;
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
    }

    public function run()
    {
        try {
            echo $this->router->resolve();
        } catch (\Exception $e) {
            $this->response->setStatusCode($e->getCode() ?: 500);
            echo $e->getMessage(); // Có thể thay bằng view render trang lỗi sau
        }
    }

    public function getRouter()
    {
        return $this->router;
    }
}
