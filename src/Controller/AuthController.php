<?php
namespace App\Controller;
use App\Service\AuthService;
use App\Core\View;

class AuthController {
    private $authService;

    // Конструктор отримує готовий сервіс авторизації (DI)
    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    // Показати форму входу
    public function showLogin() {
        View::render('login');
    }

    // Показати форму реєстрації
    public function showRegister() {
        View::render('register');
    }

    // Обробка даних з форми входу
    public function login() {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if ($this->authService->login($username, $password)) {
            header('Location: /dashboard'); // Успіх -> в кабінет
        } else {
            View::render('login', ['error' => 'Невірний логін або пароль']);
        }
    }

    // Обробка реєстрації
    public function register() {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if ($this->authService->register($username, $password)) {
            header('Location: /login'); // Успіх -> на вхід
        } else {
            View::render('register', ['error' => 'Помилка реєстрації']);
        }
    }

    public function logout() {
        $this->authService->logout();
        header('Location: /login');
    }
}