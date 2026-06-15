<?php

require_once __DIR__ . '/../Core/Database.php';

class Finance {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function handleMessage($userId, $text) {
        $text = trim($text);
        
        $type = 'expense';
        if ($text[0] === '+') {
            $type = 'income';
            $text = trim(substr($text, 1));
        }

        $words = explode(' ', $text);
        $count = count($words);
        
        if ($count < 2) {
            return "Неверный формат. Напиши, например: Кофе 300 или +50000 зп";
        }

        $amountStr = $words[$count - 1];
        $amount = (int)$amountStr;

        if ($amount <= 0) {
            return "Сумма должна быть числом больше нуля!";
        }

        $categoryWords = [];
        for ($i = 0; $i < $count - 1; $i++) {
            $categoryWords[] = $words[$i];
        }
        $category = implode(' ', $categoryWords);

        $stmt = $this->db->prepare("
            INSERT INTO transactions (user_id, type, category, amount, created_at) 
            VALUES (?, ?, ?, ?, datetime('now'))
        ");
        
        $success = $stmt->execute([$userId, $type, $category, $amount]);

        if ($success) {
            if ($type === 'income') {
                return "✅ Записал доход: {$category} на сумму {$amount} руб.";
            }
            return "✅ Записал расход: {$category} на сумму {$amount} руб.";
        }

        return "❌ Ошибка при сохранении в базу данных.";
    }
}