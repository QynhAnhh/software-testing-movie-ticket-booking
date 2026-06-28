<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        // Sử dụng view() từ Core\Controller
        // Do header/footer đang được nhúng cứng trong Controller::view()
        // Nên ta chỉ cần render file nội dung 'home/index'
        return $this->view('home/index');
    }
}