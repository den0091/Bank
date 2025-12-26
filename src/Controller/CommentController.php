<?php

namespace App\Controller;

use App\Service\CommentService;

class CommentController
{
    private $commentService;

    // Контейнер автоматично передасть сюди сервіс
    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Цей метод спрацьовує, коли йде запит на POST /api/comments/add
     */
    public function store()
    {
        // 1. Перевірка методу запиту (про всяк випадок)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            return;
        }

        // 2. Отримання та очистка даних
        $username = isset($_POST['username']) ? trim($_POST['username']) : 'Anonymous';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';

        // 3. Валідація
        if (empty($message)) {
            echo '<div style="color: red; padding: 10px;">Помилка: Повідомлення не може бути порожнім.</div>';
            return;
        }

        try {
            // 4. Виклик сервісу для збереження в БД
            $newComment = $this->commentService->add($username, $message);

            // 5. Формування HTML-відповіді для AJAX
            // Цей код JavaScript вставить на сторінку миттєво
            echo '
            <div class="comment-box" style="animation: fadeIn 0.5s;">
                <div class="comment-header">
                    <span class="author">' . htmlspecialchars($newComment['username']) . '</span>
                    <span class="date">' . $newComment['created_at'] . '</span>
                </div>
                <div class="comment-body">
                    ' . nl2br(htmlspecialchars($newComment['message'])) . '
                </div>
            </div>';

        } catch (\Exception $e) {
            // Логування помилки (опціонально) і вивід повідомлення
            http_response_code(500);
            echo "Сталася помилка сервера: " . $e->getMessage();
        }
    }

    /**
     * Метод для отримання списку (якщо треба буде GET /api/comments)
     */
    public function index()
    {
        $comments = $this->commentService->getAll();
        echo json_encode($comments);
    }
}