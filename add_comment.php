<?php
// add_comment.php

// 1. НАЛАШТУВАННЯ БАЗИ ДАНИХ (Зміни дані на свої!)
$servername = "localhost";
$username_db = "root";      // Твій логін (на InfinityFree це щось типу if0_...)
$password_db = "";          // Твій пароль
$dbname = "kingdom_come_db"; // Назва твоєї бази даних

// Підключення
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Перевірка з'єднання
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. ОБРОБКА ДАНИХ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Очистка даних від шкідливих символів
    $username = $conn->real_escape_string($_POST['username']);
    $message = $conn->real_escape_string($_POST['message']);
    $date = date('Y-m-d H:i:s');

    // SQL запит на додавання
    $sql = "INSERT INTO comments (username, message, created_at) VALUES ('$username', '$message', '$date')";

    if ($conn->query($sql) === TRUE) {
        // Якщо успішно — формуємо HTML-блок для відповіді JavaScript-у
        echo '
        <div class="comment-box">
            <div class="comment-header">
                <span class="author">' . htmlspecialchars($username) . '</span>
                <span class="date">' . $date . '</span>
            </div>
            <div class="comment-body">
                ' . nl2br(htmlspecialchars($message)) . '
            </div>
        </div>';
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>