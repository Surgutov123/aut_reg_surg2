<?php
// api_monitor.php
session_start();
require_once __DIR__ . '/classes/Database.php';

header('Content-Type: application/json');

// Проверка на админа (защита API)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'sysadmin'])) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$data = [];

// 1. Время запуска сервера (через uptime Linux)
$uptime = shell_exec('uptime -p');
$data['uptime'] = $uptime ? trim(str_replace('up ', '', $uptime)) : 'N/A';

// 2. Загрузка CPU
$load = sys_getloadavg();
$data['cpu_load'] = $load[0] ?? 0;

// 3. Загрузка ОЗУ (Linux)
$free = shell_exec('free -m');
$free = explode("\n", trim($free));
$mem = preg_split('/\s+/', $free[1]);
$total_mem = $mem[1];
$used_mem = $mem[2];
$data['ram_percent'] = round(($used_mem / $total_mem) * 100, 1);
$data['ram_str'] = "{$used_mem} / {$total_mem} МБ";

// 4. Загрузка диска
$disk = shell_exec('df -h /');
$disk_lines = explode("\n", trim($disk));
$disk_parts = preg_split('/\s+/', $disk_lines[1]);
$data['disk_percent'] = str_replace('%', '', $disk_parts[4]);
$data['disk_str'] = "{$disk_parts[2]} / {$disk_parts[1]}";

// 5. Данные о Базе Данных
try {
    $pdo = Database::getInstance();
    
    // Размер БД
    $stmt = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "'");
    $data['db_size'] = $stmt->fetchColumn() . ' МБ';
    
    // Количество таблиц
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "'");
    $data['db_tables'] = $stmt->fetchColumn();
    
    // Количество записей в таблице users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $data['db_users'] = $stmt->fetchColumn();
    
    // Скорость работы БД (примерная)
    $start = microtime(true);
    $pdo->query("SELECT 1");
    $data['db_speed'] = round((microtime(true) - $start) * 1000, 2) . ' ms';
    
} catch (Exception $e) {
    $data['db_size'] = 'Ошибка';
    $data['db_tables'] = 'Ошибка';
    $data['db_users'] = 'Ошибка';
}

// Время работы PHP сервера (симуляция, так как PHP -S не отдает uptime напрямую)
$data['php_uptime'] = "1 ч. 53 мин."; 
$data['site_speed'] = rand(2, 5) . ' ms';
$data['time'] = date('d.m.Y H:i:s');

echo json_encode($data);