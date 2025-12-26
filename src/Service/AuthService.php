<?php
namespace App\Service;

class AuthService {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    public function register($username, $password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $this->db->beginTransaction();

            // 1. Створюємо юзера
            $stmt = $this->db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);
            $userId = $this->db->lastInsertId();

            // 2. Генеруємо випадковий номер картки (16 цифр)
            $cardNumber = '5375' . rand(100000000000, 999999999999);

            // 3. Створюємо рахунок
            $this->db->prepare("INSERT INTO accounts (user_id, balance, card_number) VALUES (?, 0, ?)")
                ->execute([$userId, $cardNumber]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
        return false;
    }

    public function logout() { session_destroy(); }
    public function getCurrentUser() { return $_SESSION['user_id'] ?? null; }
}