<?php
namespace App\Core;

class View {
    // Метод для виводу сторінки на екран
    public static function render($view, $data = []) {
        // Розпаковуємо масив даних у змінні (наприклад ['name' => 'Ivan'] стане $name = 'Ivan')
        extract($data);

        // Шлях до основного шаблону, який підключає всередину себе конкретну сторінку ($view)
        $content = __DIR__ . '/../../views/' . $view . '.php';
        require __DIR__ . '/../../views/layout.php';
    }
}