<?php
namespace App\Core;

class Container {
    // Масив для збереження "рецептів" створення об'єктів
    private $services = [];

    // Метод, щоб навчити контейнер створювати сервіс
    public function set($name, callable $closure) {
        $this->services[$name] = $closure;
    }

    // Метод, щоб отримати готовий сервіс
    public function get($name) {
        if (!isset($this->services[$name])) {
            throw new \Exception("Сервіс не знайдено: " . $name);
        }
        // Виконуємо функцію створення і повертаємо об'єкт
        return $this->services[$name]($this);
    }
}