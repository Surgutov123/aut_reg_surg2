<?php
class Captcha {
    // Инициализация: создаем перемешанный порядок и запоминаем правильный
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Правильный порядок (1, 2, 3, 4)
        $correctOrder = [1, 2, 3, 4];
        
        // Перемешиваем для отображения пользователю
        $shuffled = $correctOrder;
        shuffle($shuffled);
        
        // Сохраняем в сессию
        $_SESSION['captcha_shuffled'] = $shuffled; // Перемешанный для вывода
        $_SESSION['captcha_answer'] = implode(',', $correctOrder); // "1,2,3,4" — правильный ответ
    }

    // Возвращаем перемешанный массив для отрисовки
    public static function getShuffledImages() {
        return $_SESSION['captcha_shuffled'] ?? [1, 2, 3, 4];
    }

    // Проверка: пользователь кликнул по картинкам в правильном порядке
    public static function validate($userSequence) {
        if (empty($userSequence)) return false;
        
        $correct = explode(',', $_SESSION['captcha_answer'] ?? '');
        return $userSequence == $correct;
    }
}