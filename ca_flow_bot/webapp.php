<?php
require_once __DIR__ . '/src/Core/Database.php';
$db = Database::getInstance();

// суммa расходов
$stmt1 = $db->prepare("SELECT SUM(amount) as total FROM transactions WHERE type='expense'");
$stmt1->execute();
$expenseSum = $stmt1->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// количество активных планов
$stmt2 = $db->prepare("SELECT COUNT(*) as count FROM plans WHERE status = 0");
$stmt2->execute();
$plansCount = $stmt2->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

$stmt3 = $db->prepare("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 5");
$stmt3->execute();
$transactions = $stmt3->fetchAll(PDO::FETCH_ASSOC);
$stmt4 = $db->prepare("SELECT * FROM plans WHERE status = 0 ORDER BY created_at DESC LIMIT 5");
$stmt4->execute();
$plans = $stmt4->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Core Flow</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        :root {
            --bg: #0f172a; 
            --card: #1e293b;
            --accent: #38bdf8;
            --expense: #fb7185;
            --plan: #c084fc;
            --text: #f8fafc;
        }
        body { font-family: -apple-system, system-ui; background: var(--bg); color: var(--text); margin: 0; padding: 16px; }

        .grid-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        .tile {
            background: var(--card);
            padding: 20px 15px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .tile-icon { font-size: 24px; margin-bottom: 8px; display: block; }
        .tile-title { font-size: 13px; opacity: 0.7; margin-bottom: 4px; }
        .tile-value { font-size: 18px; font-weight: bold; }

        /* Полосы списков */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin: 20px 0 10px; }
        .section-title { font-size: 16px; font-weight: bold; color: var(--accent); }
        
        .row-card {
            background: var(--card);
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid transparent;
        }
        .row-info { display: flex; flex-direction: column; }
        .row-name { font-size: 15px; }
        .row-sub { font-size: 11px; opacity: 0.5; margin-top: 2px; }

        .expense-border { border-left-color: var(--expense); }
        .plan-border { border-left-color: var(--plan); }
    </style>
</head>
        <body>

        <div class="grid-header">
            <div class="tile" onclick="switchTab('finances')">
                <span class="tile-icon">💰</span>
                <div class="tile-title">Финансы</div>
                <div class="tile-value" style="color: var(--expense);"><?= number_format($expenseSum, 0, '.', ' ') ?> ₽</div>
            </div>
            <div class="tile" onclick="switchTab('plans')">
                <span class="tile-icon">📅</span>
                <div class="tile-title">Планы</div>
                <div class="tile-value"><?= $plansCount ?> задач</div>
            </div>
        </div>

        <button id="back-btn" onclick="switchTab('main')" style="display:none; background: var(--card); color: var(--accent); border: none; padding: 8px 12px; border-radius: 8px; margin-bottom: 15px; cursor: pointer;">← Назад</button>

        <div id="tab-main" class="tab-content">
            <div class="section-header">
                <span class="section-title">Последние расходы</span>
            </div>
            <?php foreach (array_slice($transactions, 0, 3) as $t): ?>
            <div class="row-card expense-border">
                <div class="row-info">
                    <span class="row-name"><?= htmlspecialchars($t['category']) ?></span>
                    <span class="row-sub"><?= date('d.m H:i', strtotime($t['created_at'])) ?></span>
                </div>
                <b style="color: var(--expense);">-<?= $t['amount'] ?> ₽</b>
            </div>
            <?php endforeach; ?>

            <div class="section-header">
                <span class="section-title">Планы</span>
            </div>
            <?php foreach (array_slice($plans, 0, 3) as $p): ?>
            <div class="row-card plan-border">
                <div class="row-info">
                    <span class="row-name"><?= htmlspecialchars($p['task']) ?></span>
                    <span class="row-sub"><?= htmlspecialchars($p['due_date']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="tab-finances" class="tab-content" style="display: none;">
            <div class="section-header">
                <span class="section-title">Вся история расходов</span>
            </div>
            <?php foreach ($transactions as $t): ?>
            <div class="row-card expense-border">
                <div class="row-info">
                    <span class="row-name"><?= htmlspecialchars($t['category']) ?></span>
                    <span class="row-sub"><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></span>
                </div>
                <b style="color: var(--expense);">-<?= $t['amount'] ?> ₽</b>
            </div>
            <?php endforeach; ?>
            <?php if(empty($transactions)): ?><p style="opacity:0.5;">Расходов пока нет</p><?php endif; ?>
        </div>

        <div id="tab-plans" class="tab-content" style="display: none;">
            <div class="section-header">
                <span class="section-title">Все активные задачи</span>
            </div>
            <?php foreach ($plans as $p): ?>
            <div class="row-card plan-border">
                <div class="row-info">
                    <span class="row-name"><?= htmlspecialchars($p['task']) ?></span>
                    <span class="row-sub">Срок: <?= htmlspecialchars($p['due_date']) ?></span>
                </div>
                <input type="checkbox" style="width:20px; height:20px;">
            </div>
            <?php endforeach; ?>
            <?php if(empty($plans)): ?><p style="opacity:0.5;">Планы отсутствуют</p><?php endif; ?>
        </div>

        <script>
            const tg = window.Telegram.WebApp;
            tg.ready();
            tg.expand();

            // переключение вкладок
            function switchTab(tabName) {
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.style.display = 'none';
                });
                document.getElementById('tab-' + tabName).style.display = 'block';
                const backBtn = document.getElementById('back-btn');
                if (tabName === 'main') {
                    backBtn.style.display = 'none';
                } else {
                    backBtn.style.display = 'block';
                }
            }
        </script>
    </body>
</html>