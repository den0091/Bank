<?php
namespace App\Service;
use PDO;

class Database {
    private $pdo;

    public function __construct(array $config) {
        // Формуємо рядок підключення DSN
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8";

        // Створюємо підключення
        $this->pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Показувати помилки
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Результат як асоціативний масив
        ]);
    }

    public function getConnection() {
        return $this->pdo;
    }
}