<?php
namespace App\Core;

class Router {
    private $routes = [];
    private $container;

    // Приймаємо контейнер, щоб потім створювати контролери автоматично
    public function __construct(Container $container) {
        $this->container = $container;
    }

    // Реєстрація POST запитів (форми)
    public function post($uri, $action) {
        $this->routes['POST'][$uri] = $action;
    }

    // Реєстрація GET запитів (звичайні посилання)
    public function get($uri, $action) {
        $this->routes['GET'][$uri] = $action;
    }

    // Головний метод: шукає маршрут і запускає його
    public function dispatch($uri, $method) {
        // Очищаємо URL від зайвих параметрів (?id=1 і т.д.)
        $uri = parse_url($uri, PHP_URL_PATH);

        if (isset($this->routes[$method][$uri])) {
            $action = $this->routes[$method][$uri];

            // Розбиваємо рядок "Controller@method" на дві змінні
            [$controllerName, $methodName] = explode('@', $action);

            // МАГІЯ DI: Просимо контейнер дати нам готовий контролер з усіма залежностями
            $controller = $this->container->get("App\\Controller\\" . $controllerName);

            // Запускаємо метод контролера
            return $controller->$methodName();
        }

        echo "404 - Сторінку не знайдено";
    }
}