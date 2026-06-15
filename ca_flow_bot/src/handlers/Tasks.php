<?php

require_once __DIR__ . '/../Core/Database.php';

class Tasks {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function handleMessage($userId, $text) {
        $text = trim($text);

        $task = '';
        $dueDate = 'Не указано';

        if (strpos($text, '—') !== false) {
            $parts = explode('—', $text);
            $task = trim($parts[0]);
            $dueDate = trim($parts[1]);
        } else {
            $words = explode(' ', $text);
            $timeKeywords = ['завтра', 'сегодня', 'в', 'понедельник', 'вторник', 'среду', 'четверг', 'пятницу', 'субботу', 'воскресенье'];
            
            $splitIndex = -1;
            foreach ($words as $index => $word) {
                if (in_array(mb_strtolower($word), $timeKeywords)) {
                    $splitIndex = $index;
                    break;
                }
            }

            if ($splitIndex !== -1) {
                $taskWords = array_slice($words, 0, $splitIndex);
                $task = implode(' ', $taskWords);
                
                $dateWords = array_slice($words, $splitIndex);
                $dueDate = implode(' ', $dateWords);
            } else {
                $task = $text;
            }
        }

        if (empty($task)) {
            return "❌ Не удалось понять текст задачи. Напиши, например: Встреча завтра в 10";
        }

        $stmt = $this->db->prepare("
            INSERT INTO plans (user_id, task, due_date, status, created_at) 
            VALUES (?, ?, ?, 0, datetime('now'))
        ");
        
        $success = $stmt->execute([$userId, $task, $dueDate]);

        if ($success) {
            return "📅 Добавил в планы: \"{$task}\" (Срок: {$dueDate})";
        }

        return "❌ Ошибка при добавлении задачи в базу.";
    }
}