<?php
// Отключаем проверки, чтобы увидеть суть
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'project_Surgutov2';

// Пробуем разные варианты подключения
$variants = [
    ['user' => 'root', 'pass' => ''],
    ['user' => 'root', 'pass' => 'root'],
    ['user' => 'root', 'pass' => '123456'],
    ['user' => 'root', 'pass' => 'password'],
    ['user' => 'student', 'pass' => 'student'],
    ['user' => 'admin', 'pass' => 'admin'],
];

$connected = false;

foreach ($variants as $v) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $v['user'], $v['pass']);
        echo "<h2 style='color:green'>Успех! Подключение работает.</h2>";
        echo "<p>Логин: <b>{$v['user']}</b>, Пароль: <b>" . ($v['pass'] ?: '(пустой)') . "</b></p>";
        echo "<p>Впиши эти данные в файл <b>config/db.php</b> и удали этот файл (fix_db.php).</p>";
        $connected = true;
        break;
    } catch (PDOException $e) {
        echo "<p style='color:red'>Не вышло с логином <b>{$v['user']}</b> и паролем <b>" . ($v['pass'] ?: '(пустой)') . "</b>: " . $e->getMessage() . "</p>";
    }
}

if (!$connected) {
    echo "<h2 style='color:orange'>Ни один из стандартных паролей не подошел.</h2>";
    echo "<p>Возможно, у тебя MySQL настроен на авторизацию через сокет. Попробуй подключиться без указания хоста:</p>";
    
    try {
        // Подключение через сокет (без host)
        $pdo = new PDO("mysql:dbname=$dbname;charset=utf8mb4", 'root', '');
        echo "<h2 style='color:green'>Успех через сокет! Логин: root, пароль пустой.</h2>";
        echo "<p>В файле config/db.php укажи DB_HOST как 'localhost', DB_USER как 'root', DB_PASS как ''.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>Сокет тоже не сработал: " . $e->getMessage() . "</p>";
    }
}
?>