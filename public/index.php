<?php
// public/index.php

// 1. ЗАПУСК СЕСІЇ
// Це потрібно для роботи входу/виходу користувачів
session_start();

// 2. АВТОЗАВАНТАЖЕННЯ КЛАСІВ
// Цей блок автоматично підключає файли класів, коли ми їх викликаємо
spl_autoload_register(function ($class) {
    // Базовий простір імен нашого проекту
    $prefix = 'App\\';
    // Папка, де лежать класи (виходимо з public і йдемо в src)
    $base_dir = __DIR__ . '/../src/';

    // Перевіряємо, чи клас належить до нашого namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    // Отримуємо відносне ім'я класу
    $relative_class = substr($class, $len);

    // Складаємо повний шлях до файлу
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Якщо файл існує — підключаємо його
    if (file_exists($file)) {
        require $file;
    }
});

// 3. ІМПОРТ КЛАСІВ (use)
use App\Core\Container;
use App\Core\Router;
use App\Service\Database;
use App\Service\AuthService;
use App\Service\BankService;
use App\Service\CommentService; // <--- Додали наш новий сервіс

// 4. НАЛАШТУВАННЯ КОНТЕЙНЕРА (Dependency Injection)
// Тут ми вчимо програму, як створювати складні об'єкти
$container = new Container();

// -- Сервіс Бази Даних --
$container->set(Database::class, function() {
    // Підключаємо файл конфігурації (перевір, що він існує)
    $config = require __DIR__ . '/../config/database.php';
    return new Database($config);
});

// -- Сервіс Авторизації --
$container->set(AuthService::class, function($c) {
    return new AuthService($c->get(Database::class));
});

// -- Банківський Сервіс --
$container->set(BankService::class, function($c) {
    return new BankService($c->get(Database::class));
});

// -- Сервіс Коментарів (НОВЕ) --
$container->set(CommentService::class, function($c) {
    return new CommentService($c->get(Database::class));
});


// -- Контролери --

// AuthController (Вхід/Реєстрація)
$container->set('App\Controller\AuthController', function($c) {
    return new \App\Controller\AuthController($c->get(AuthService::class));
});

// BankController (Банківські операції)
$container->set('App\Controller\BankController', function($c) {
    return new \App\Controller\BankController(
        $c->get(BankService::class),
        $c->get(AuthService::class)
    );
});

// CommentController (Коментарі - НОВЕ)
$container->set('App\Controller\CommentController', function($c) {
    return new \App\Controller\CommentController($c->get(CommentService::class));
});


// 5. МАРШРУТИЗАЦІЯ (Router)
// Тут ми прописуємо всі URL-адреси сайту
$router = new Router($container);

// --- Маршрути Авторизації ---
$router->get('/', 'AuthController@showLogin');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');

// --- Маршрути Банку ---
$router->get('/dashboard', 'BankController@index');
$router->post('/loan', 'BankController@loan');
$router->post('/deposit', 'BankController@deposit');
$router->post('/trade', 'BankController@trade');
$router->post('/transfer', 'BankController@transfer');
$router->post('/pay-loan', 'BankController@payLoan');
$router->post('/exchange', 'BankController@exchange');

// --- Маршрути Коментарів (НОВЕ) ---
// Цей маршрут використовується AJAX-запитом для додавання коментаря
$router->post('/api/comments/add', 'CommentController@store');

// Опціонально: маршрут для отримання всіх коментарів (JSON)
$router->get('/api/comments', 'CommentController@index');


// 6. ЗАПУСК ОБРОБКИ ЗАПИТУ
// Router дивиться на поточну адресу і метод (GET/POST) і викликає потрібний контролер
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);