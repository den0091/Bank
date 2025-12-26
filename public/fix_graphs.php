<?php
// fix_graphs.php - Запустити один раз в браузері
require __DIR__ . '/../src/Service/Database.php';
$config = require __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "<h2>Налаштування графіків...</h2>";

    // 1. Створюємо таблицю історії цін (якщо немає)
    $pdo->exec("CREATE TABLE IF NOT EXISTS stock_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        stock_id INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (stock_id) REFERENCES stocks(id)
    )");
    echo "Таблиця stock_history перевірена.<br>";

    // 2. Очищаємо старі дані (щоб не було глюків)
    $pdo->exec("TRUNCATE TABLE stock_history");

    // 3. Генеруємо історію за останні 24 години
    $stocks = $pdo->query("SELECT * FROM stocks")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stocks as $stock) {
        $price = $stock['base_price'];
        // Генеруємо 20 точок для кожної акції
        for ($i = 20; $i >= 0; $i--) {
            $time = date('Y-m-d H:i:s', strtotime("-{$i} hours"));
            // Випадкова зміна ціни
            $change = (rand(-50, 50) / 1000);
            $price = $price * (1 + $change);

            $stmt = $pdo->prepare("INSERT INTO stock_history (stock_id, price, recorded_at) VALUES (?, ?, ?)");
            $stmt->execute([$stock['id'], $price, $time]);
        }
        echo "Згенеровано графік для {$stock['symbol']}<br>";
    }

    echo "<h1 style='color:green'>ГОТОВО! ГРАФІКИ МАЮТЬ ПРАЦЮВАТИ.</h1>";
    echo "<a href='/dashboard'>--> ПЕРЕЙТИ В КАБІНЕТ <--</a>";

} catch (Exception $e) {
    echo "Помилка: " . $e->getMessage();
}