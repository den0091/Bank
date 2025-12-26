<?php

namespace App\Service;

use App\Service\Database;

class CommentService
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Додає коментар у базу даних
     */
    public function add($username, $message)
    {
        $date = date('Y-m-d H:i:s');

        // SQL запит
        $sql = "INSERT INTO comments (username, message, created_at) VALUES (:username, :message, :created_at)";

        // Використовуємо метод query твого класу Database
        // Припускаю, що він підтримує параметри (якщо ні — адаптуй під свій клас)
        $this->db->query($sql, [
            ':username' => $username,
            ':message' => $message,
            ':created_at' => $date
        ]);

        // Повертаємо масив даних, щоб контролер міг їх відобразити
        return [
            'username' => $username,
            'message' => $message,
            'created_at' => $date
        ];
    }

    /**
     * Отримує всі коментарі
     */
    public function getAll()
    {
        // Припускаю, що твій клас Database має метод query(), який повертає об'єкт PDOStatement
        // або сам повертає масив. Адаптуй цей рядок під свій клас Database.php.
        $stmt = $this->db->query("SELECT * FROM comments ORDER BY id DESC");

        // Якщо твій метод query повертає PDOStatement:
        if ($stmt instanceof \PDOStatement) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Якщо твій метод query вже повертає масив (залежить від реалізації Database.php):
        return $stmt;
    }
}