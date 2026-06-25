<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Auth.php';

class AuthController extends BaseController
{
    private Auth $authModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->authModel = new Auth();
    }

    public function showLogin()
    {
        $this->view('auth/login');
    }

    public function showRegister()
    {
        $this->view('auth/register');
    }
}