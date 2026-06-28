<?php

namespace App\Core;

class Controller
{
    public function view(string $viewPath, array $data = [])
    {
        extract($data);
        
        $baseDir = dirname(__DIR__) . '/Views/';
        
        // Output buffering
        ob_start();
        
        // Tạm thời require header & footer cứng để không phá vỡ UI hiện có
        // Sau này sẽ refactor lại thành hệ thống Layout chuẩn
        require_once $baseDir . 'layouts/header.php';
        require_once $baseDir . $viewPath . '.php';
        require_once $baseDir . 'layouts/footer.php';
        
        return ob_get_clean();
    }
    
    public function redirect(string $url)
    {
        header("Location: " . $url);
        exit;
    }

    public function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

