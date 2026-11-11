<?php
session_start();
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';

// Защита страницы
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'sysadmin'])) {
    header('Location: index.php');
    exit;
}

$auth = new Auth();

// Обработка разблокировки пользователя
if (isset($_POST['unblock_id'])) {
    $auth->unblockUser($_POST['unblock_id']);
    header('Location: admin_monitor.php');
    exit;
}

$users = $auth->getAllUsers();
$currentTab = $_GET['tab'] ?? 'users'; // По умолчанию открываем вкладку пользователей
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background-color: #f3f4f6; display: block; padding: 20px; }
        .admin-container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        
        /* Вкладки */
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; flex-wrap: wrap; }
        .tab { padding: 10px 20px; background: #e5e7eb; color: #4b5563; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .tab:hover { background: #d1d5db; }
        .tab.active { background: #4f46e5; color: white; }

        /* Таблица пользователей */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #f9fafb; font-weight: 600; color: #374151; }
        tr:hover { background-color: #f9fafb; }
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .badge-admin { background: #fef08a; color: #854d0e; }
        .badge-user { background: #e0f2fe; color: #0369a1; }
        .badge-blocked { background: #fee2e2; color: #b91c1c; }
        .badge-active { background: #dcfce7; color: #15803d; }
        .btn-sm { padding: 6px 12px; font-size: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; }
        .btn-sm:hover { background: #2563eb; }

        /* Сетка плиток мониторинга */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: #f9fafb; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; }
        .card-title { font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 8px; }
        .card-value { font-size: 24px; font-weight: 700; color: #111827; }
        .card-sub { font-size: 12px; color: #9ca3af; margin-top: 5px; }
        .text-blue { color: #2563eb; }
        .text-green { color: #10b981; }

        /* Прогресс-бары */
        .progress-wrapper { margin-bottom: 20px; }
        .progress-label { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; color: #374151; font-weight: 500; }
        .progress-bar { width: 100%; height: 12px; background: #e5e7eb; border-radius: 6px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 6px; transition: width 0.5s ease; }
        .fill-blue { background: #4f46e5; }
        .fill-green { background: #10b981; }
        .fill-orange { background: #f59e0b; }
    </style>
</head>
<body>

<div class="admin-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>🛡️ Админ-панель</h2>
        <div>
            <a href="index.php" class="tab" style="background:#e5e7eb;">← На главную</a>
            <a href="logout.php" class="tab" style="background:#ef4444; color:white;">Выйти</a>
        </div>
    </div>

    <!-- Вкладки -->
    <div class="tabs">
        <a href="admin_monitor.php?tab=users" class="tab <?= $currentTab === 'users' ? 'active' : '' ?>">Пользователи</a>
        <a href="admin_monitor.php?tab=monitor" class="tab <?= $currentTab === 'monitor' ? 'active' : '' ?>">Мониторинг</a>
        <a href="export_import.php" class="tab">Таблицы БД</a>
    </div>

    <!-- ВКЛАДКА 1: ПОЛЬЗОВАТЕЛИ -->
    <?php if ($currentTab === 'users'): ?>
        <h3>Список пользователей системы</h3>
        <table>
            <thead>
                <tr><th>ID</th><th>Логин</th><th>ФИО</th><th>Email</th><th>Роль</th><th>Попытки</th><th>Статус</th><th>Действие</th></tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['login']) ?></strong></td>
                    <td><?= htmlspecialchars($u['last_name'] . ' ' . $u['first_name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge <?= $u['role'] === 'user' ? 'badge-user' : 'badge-admin' ?>"><?= $u['role'] ?></span></td>
                    <td><?= $u['failed_attempts'] ?></td>
                    <td><span class="badge <?= $u['is_blocked'] === 'да' ? 'badge-blocked' : 'badge-active' ?>"><?= $u['is_blocked'] === 'да' ? 'Заблокирован' : 'Активен' ?></span></td>
                    <td>
                        <?php if($u['is_blocked'] === 'да'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="unblock_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-sm" style="background:#10b981;">Разблокировать</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#9ca3af;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- ВКЛАДКА 2: МОНИТОРИНГ -->
    <?php if ($currentTab === 'monitor'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Мониторинг сервера</h3>
            <a href="api_monitor.php" download="report.json" style="background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">Экспорт отчета (JSON)</a>
        </div>
        <p style="color: #6b7280; font-size: 14px; margin-bottom: 20px;">Данные обновляются автоматически каждые 3 секунды.</p>

        <div class="grid">
            <div class="card"><div class="card-title">Время запуска</div><div class="card-value text-blue" id="val-php-uptime">-</div><div class="card-sub" id="val-time">-</div></div>
            <div class="card"><div class="card-title">Время работы (Uptime)</div><div class="card-value" id="val-uptime">-</div><div class="card-sub">Сервер</div></div>
            <div class="card"><div class="card-title">Скорость ответа</div><div class="card-value text-green" id="val-site-speed">-</div><div class="card-sub">Сайт</div></div>
            <div class="card"><div class="card-title">Скорость работы БД</div><div class="card-value text-green" id="val-db-speed">-</div><div class="card-sub">MySQL</div></div>
            <div class="card"><div class="card-title">Загрузка диска</div><div class="card-value" id="val-disk-percent">-</div><div class="card-sub" id="val-disk-str">-</div></div>
            <div class="card"><div class="card-title">Загрузка ОЗУ</div><div class="card-value" id="val-ram-percent">-</div><div class="card-sub" id="val-ram-str">-</div></div>
        </div>

        <h3>База данных</h3>
        <div class="grid">
            <div class="card"><div class="card-title">Размер БД</div><div class="card-value" id="val-db-size">-</div></div>
            <div class="card"><div class="card-title">Таблиц</div><div class="card-value" id="val-db-tables">-</div></div>
            <div class="card"><div class="card-title">Записей (users)</div><div class="card-value" id="val-db-users">-</div></div>
        </div>

        <h3>Загрузка сервера</h3>
        <div class="progress-wrapper">
            <div class="progress-label"><span>Диск</span><span id="lbl-disk">0%</span></div>
            <div class="progress-bar"><div class="progress-fill fill-blue" id="bar-disk" style="width: 0%;"></div></div>
        </div>
        <div class="progress-wrapper">
            <div class="progress-label"><span>Оперативная память</span><span id="lbl-ram">0%</span></div>
            <div class="progress-bar"><div class="progress-fill fill-green" id="bar-ram" style="width: 0%;"></div></div>
        </div>
        <div class="progress-wrapper">
            <div class="progress-label"><span>Процессор</span><span id="lbl-cpu">0%</span></div>
            <div class="progress-bar"><div class="progress-fill fill-orange" id="bar-cpu" style="width: 0%;"></div></div>
        </div>

        <script>
            async function fetchData() {
                try {
                    const response = await fetch('api_monitor.php');
                    const data = await response.json();

                    if (data.error) return;

                    document.getElementById('val-php-uptime').textContent = data.php_uptime;
                    document.getElementById('val-time').textContent = data.time;
                    document.getElementById('val-uptime').textContent = data.uptime;
                    document.getElementById('val-site-speed').textContent = data.site_speed;
                    document.getElementById('val-db-speed').textContent = data.db_speed;
                    document.getElementById('val-disk-percent').textContent = data.disk_percent + '%';
                    document.getElementById('val-disk-str').textContent = data.disk_str;
                    document.getElementById('val-ram-percent').textContent = data.ram_percent + '%';
                    document.getElementById('val-ram-str').textContent = data.ram_str;
                    
                    document.getElementById('val-db-size').textContent = data.db_size;
                    document.getElementById('val-db-tables').textContent = data.db_tables;
                    document.getElementById('val-db-users').textContent = data.db_users;

                    document.getElementById('lbl-disk').textContent = data.disk_percent + '%';
                    document.getElementById('bar-disk').style.width = data.disk_percent + '%';

                    document.getElementById('lbl-ram').textContent = data.ram_percent + '%';
                    document.getElementById('bar-ram').style.width = data.ram_percent + '%';

                    let cpuPercent = Math.min(data.cpu_load * 10, 100).toFixed(1); 
                    document.getElementById('lbl-cpu').textContent = cpuPercent + '%';
                    document.getElementById('bar-cpu').style.width = cpuPercent + '%';

                } catch (error) {
                    console.error('Ошибка получения данных:', error);
                }
            }

            fetchData();
            setInterval(fetchData, 3000);
        </script>
    <?php endif; ?>
</div>

</body>
</html>