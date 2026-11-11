<?php
require_once __DIR__ . '/Database.php';

class Auth {
    private $db;
    private $maxAttempts = 5; // Максимум попыток

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function register($data): bool|string {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$data['login']]);
        if ($stmt->fetch()) return "Логин уже занят";

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) return "Неверный email";

        $stmt = $this->db->prepare("INSERT INTO users 
            (login, password_hash, phone, email, first_name, last_name, middle_name, role, is_blocked, failed_attempts) 
            VALUES (:login, :hash, :phone, :email, :fname, :lname, :mname, 'user', 'нет', 0)");

        try {
            $stmt->execute([
                ':login' => $data['login'],
                ':hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':phone' => $data['phone'],
                ':email' => $data['email'],
                ':fname' => $data['first_name'],
                ':lname' => $data['last_name'],
                ':mname' => $data['middle_name']
            ]);
            return true;
        } catch (PDOException $e) {
            return "Ошибка БД: " . $e->getMessage();
        }
    }

    public function login($login, $password): bool|string {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if (!$user) return "Неверный логин или пароль";
        
        // Проверка на блокировку
        if ($user['is_blocked'] === 'да') {
            return "Аккаунт заблокирован из-за множественных неверных попыток входа. Обратитесь к администратору.";
        }

        if (password_verify($password, $user['password_hash'])) {
            // Успех: сбрасываем счетчик и пускаем
            $this->db->query("UPDATE users SET failed_attempts = 0 WHERE id = {$user['id']}");
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login'] = $user['login'];
            return true;
        } else {
            // Неудача: увеличиваем счетчик
            $newAttempts = $user['failed_attempts'] + 1;
            $this->db->query("UPDATE users SET failed_attempts = {$newAttempts} WHERE id = {$user['id']}");
            
            // Блокируем, если превысили лимит
            if ($newAttempts >= $this->maxAttempts) {
                $this->db->query("UPDATE users SET is_blocked = 'да' WHERE id = {$user['id']}");
                return "Вы превысили лимит попыток. Аккаунт заблокирован.";
            }
            
            return "Неверный логин или пароль. Осталось попыток: " . ($this->maxAttempts - $newAttempts);
        }
    }

    public function getUser($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Метод для админки: получить всех пользователей
    public function getAllUsers() {
        return $this->db->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
    }

    // Метод для админки: разблокировать
    public function unblockUser($id) {
        $stmt = $this->db->prepare("UPDATE users SET is_blocked = 'нет', failed_attempts = 0 WHERE id = ?");
        $stmt->execute([$id]);
    }
}