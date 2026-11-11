<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/Auth.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$auth = new Auth();
$user = $auth->getUser($_SESSION['user_id']);

// Если пользователя разжаловали или заблокировали
if (!$user || $user['is_blocked'] === 'да') {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .profile-card { background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 500px; margin: 40px auto; }
        .profile-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
        .profile-row:last-child { border-bottom: none; }
        .profile-label { color: #6b7280; font-weight: 500; }
        .profile-value { font-weight: 600; color: #111827; text-align: right; }
        .admin-note { background: #e0f2fe; color: #0369a1; padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="profile-card">
        <h2 style="text-align:center; margin-bottom: 30px;">Ваш профиль</h2>
        
        <div class="profile-row">
            <span class="profile-label">ID</span>
            <span class="profile-value"><?= $user['id'] ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Логин</span>
            <span class="profile-value"><?= htmlspecialchars($user['login']) ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">ФИО</span>
            <span class="profile-value"><?= htmlspecialchars($user['last_name'] . ' ' . $user['first_name'] . ' ' . $user['middle_name']) ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Email</span>
            <span class="profile-value"><?= htmlspecialchars($user['email']) ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Роль</span>
            <span class="profile-value" style="text-transform: uppercase; color: #3b82f6;"><?= $user['role'] ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Дата регистрации</span>
            <span class="profile-value"><?= date('d.m.Y', strtotime($user['created_at'])) ?></span>
        </div>

        <?php if(in_array($user['role'], ['admin', 'sysadmin'])): ?>
            <div class="admin-note">
                У вас есть права администратора. <a href="admin.php" style="color:#0369a1; font-weight:bold;">Перейти в админ-панель</a>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="index.php" style="color: #3b82f6; text-decoration: none; margin-right: 15px;">На главную</a>
            <a href="logout.php" style="color: #ef4444; text-decoration: none;">Выйти</a>
        </div>
    </div>
</body>
</html>