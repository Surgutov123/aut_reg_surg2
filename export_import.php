<?php
session_start();
require_once __DIR__ . '/classes/Database.php';

// Защита: только для админов
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'sysadmin'])) {
    die("Доступ запрещен");
}

$pdo = Database::getInstance();

// === ЭКСПОРТ ===
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sqlScript = "-- Дамп базы данных " . DB_NAME . "\n";
    $sqlScript .= "-- Дата: " . date('Y-m-d H:i:s') . "\n\n";
    $sqlScript .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sqlScript .= "SET time_zone = \"+00:00\";\n\n";

        foreach ($tables as $table) {
        // УДАЛЯЕМ старую таблицу перед созданием новой
        $sqlScript .= "\n\nDROP TABLE IF EXISTS `$table`;\n";
        
        // Структура таблицы
        $result = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $result->fetch(PDO::FETCH_NUM);
        $sqlScript .= "\n\n" . $row[1] . ";\n\n";

        // Данные таблицы
        $result = $pdo->query("SELECT * FROM `$table`");
        $columnCount = $result->columnCount();

        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $sqlScript .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $columnCount; $j++) {
                $row[$j] = $row[$j] === null ? 'NULL' : "'" . addslashes($row[$j]) . "'";
                if (isset($row[$j])) {
                    $sqlScript .= $row[$j];
                } else {
                    $sqlScript .= "''";
                }
                if ($j < ($columnCount - 1)) {
                    $sqlScript .= ',';
                }
            }
            $sqlScript .= ");\n";
        }
    }

    $sqlScript .= "\n";
    
    // Отдаем файл пользователю
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . DB_NAME . '_backup_' . date('Y-m-d_H-i') . '.sql"');
    echo $sqlScript;
    exit;
}

// === ИМПОРТ ===
$importMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $importMessage = "<div class='error-message'>Ошибка загрузки файла.</div>";
    } else {
        $sqlContent = file_get_contents($file['tmp_name']);
        
               if ($sqlContent === false) {
            $importMessage = "<div class='error-message'>Не удалось прочитать файл.</div>";
        } else {
            try {
                // 1. Отключаем проверку внешних ключей
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                
                // 2. Удаляем ВСЕ существующие таблицы перед импортом
                $tables = [];
                $result = $pdo->query("SHOW TABLES");
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
                foreach ($tables as $table) {
                    $pdo->exec("DROP TABLE IF EXISTS `$table`");
                }

                // 3. Выполняем дамп
                $pdo->exec($sqlContent);
                
                // 4. Включаем проверку обратно
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

                $importMessage = "<div style='background:#dcfce7; color:#15803d; padding:15px; border-radius:8px; margin-bottom:20px;'>✅ База данных успешно импортирована! Все старые таблицы удалены и заменены новыми.</div>";
            } catch (PDOException $e) {
                $importMessage = "<div class='error-message'>Ошибка импорта: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Таблицы БД — Экспорт/Импорт</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background-color: #f3f4f6; display: block; padding: 20px; }
        .admin-container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; }
        .tab { padding: 10px 20px; background: #e5e7eb; color: #4b5563; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
        .tab.active { background: #4f46e5; color: white; }
        
        .action-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px; background: #f9fafb; }
        .action-card h3 { margin-bottom: 15px; color: #111827; }
        .action-card p { color: #4b5563; font-size: 14px; margin-bottom: 20px; }
        
        .btn-export { display: inline-block; background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .btn-export:hover { background: #059669; }
        
        .file-input-wrapper { display: flex; gap: 15px; align-items: center; }
        .file-input-wrapper input[type="file"] { border: 1px solid #d1d5db; padding: 10px; border-radius: 8px; background: #fff; width: 100%; }
        .btn-import { background: #3b82f6; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-import:hover { background: #2563eb; }
    </style>
</head>
<body>
<div class="admin-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>🛡️ Админ-панель — Работа с БД</h2>
        <div>
            <a href="admin_monitor.php" class="tab" style="background:#e5e7eb;">← В панель</a>
            <a href="logout.php" class="tab" style="background:#ef4444; color:white;">Выйти</a>
        </div>
    </div>

    <div class="tabs">
        <a href="admin_monitor.php?tab=users" class="tab">Пользователи</a>
        <a href="admin_monitor.php?tab=monitor" class="tab">Мониторинг</a>
        <a href="export_import.php" class="tab active">Таблицы БД</a>
    </div>

    <?= $importMessage ?>

    <div class="action-card">
        <h3>📤 Экспорт базы данных</h3>
        <p>Скачать полный дамп базы данных (структура + данные) в формате .sql файла. Вы можете сохранить его как резервную копию.</p>
        <a href="?action=export" class="btn-export">Скачать базу данных (.sql)</a>
    </div>

    <div class="action-card">
        <h3>📥 Импорт базы данных</h3>
        <p>Загрузите ранее сохраненный .sql файл, чтобы восстановить данные. <strong style="color:#ef4444;">Внимание:</strong> это перезапишет текущие таблицы!</p>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="file-input-wrapper">
                <input type="file" name="sql_file" accept=".sql" required>
                <button type="submit" class="btn-import">Восстановить</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>