<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главная</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .welcome-box { text-align: center; padding: 50px 20px; background: #fff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 600px; margin: 40px auto; }
        .welcome-box h1 { margin-bottom: 20px; font-size: 28px; }
        .btn-group { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn { text-decoration: none; display: inline-block; width: auto; padding: 12px 24px; }
    </style>
</head>
<body>
    <div class="welcome-box">
        <h1>Добро пожаловать в систему</h1>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <p style="margin-bottom: 20px;">Вы вошли как <strong><?= htmlspecialchars($_SESSION['login']) ?></strong> (Роль: <?= $_SESSION['role'] ?>)</p>
            <div class="btn-group">
                <a href="profile.php" class="btn">Мой профиль</a>
                <?php if(in_array($_SESSION['role'], ['admin', 'sysadmin'])): ?>
                    <a href="admin_monitor.php" class="btn" style="background-color: #1f2937;">Админ-панель</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-secondary">Выйти</a>
            </div>
        <?php else: ?>
            <p style="margin-bottom: 20px; color: #6b7280;">Вы не авторизованы. Пожалуйста, войдите или зарегистрируйтесь.</p>
            <div class="btn-group">
                <a href="login.php" class="btn">Войти</a>
                <a href="register.php" class="btn btn-secondary">Регистрация</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>