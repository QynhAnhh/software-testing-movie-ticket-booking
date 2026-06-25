<?php

class BaseController
{
    protected function view($viewPath, $data = [])
    {
        extract($data);

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/' . $viewPath . '.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}