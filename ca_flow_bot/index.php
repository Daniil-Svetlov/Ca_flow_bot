<?php
ob_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Core/Bot.php';
require_once __DIR__ . '/config.php';

// инициализация БД
if (class_exists('Database')) {
    $db = Database::getInstance();
} elseif (class_exists('Core\\Database')) {
    $db = \Core\Database::getInstance();
} else {
    die("Ошибка: Класс Database не найден.");
}

try {
    $db->prepare("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, chat_id INTEGER UNIQUE, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)")->execute();
    $db->prepare("CREATE TABLE IF NOT EXISTS transactions (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, category TEXT, amount REAL, type TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)")->execute();
    $db->prepare("CREATE TABLE IF NOT EXISTS plans (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, task TEXT, due_date TEXT, status INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)")->execute();
} catch (Exception $e) {
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $text = trim($update['message']['text'] ?? '');

    // Регистрация пользователя
    $db->prepare("INSERT OR IGNORE INTO users (chat_id) VALUES (?)")->execute([$chatId]);

    if ($text === '/start') {
        $message = "Привет в Core Flow! 🚀\n\nПримеры команд:\n💰 *Кофе 300* — запишу расход\n📅 *Встреча завтра 10* — запишу план";
    } 
    
    // Логика для расходов 
    elseif (preg_match('/^(.+)\s+(\d+)$|^\s*(\d+)\s+(.+)$/u', $text, $matches)) {
        $amount = !empty($matches[2]) ? $matches[2] : $matches[3];
        $category = trim(!empty($matches[1]) ? $matches[1] : $matches[4]);

        $stmt = $db->prepare("INSERT INTO transactions (user_id, category, amount, type) VALUES (?, ?, ?, 'expense')");
        $stmt->execute([$chatId, $category, $amount]);

        $message = "✅ Записал расход: *$category* на сумму *$amount* руб.";
    }

    // Логика для планов
    elseif (preg_match('/(.+)\s+(сегодня|завтра|послезавтра|в|в субботу|в воскресенье)\s*(\d+)?/ui', $text, $matches)) {
        $task = trim($matches[1]);
        $timePart = $matches[2] . ($matches[3] ? " " . $matches[3] : "");

        $stmt = $db->prepare("INSERT INTO plans (user_id, task, due_date) VALUES (?, ?, ?)");
        $stmt->execute([$chatId, $task, $timePart]);

        $message = "📅 Добавил в планы: *$task* ($timePart)";
    }

    else {
        $message = "Ты написал: *$text*\nЧтобы записать данные, используй формат: 'Продукт Цена' или 'Дело Время'.";
    }

    Bot::send('sendMessage', [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ]);
}