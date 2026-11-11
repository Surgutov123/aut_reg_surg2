<?php
class Captcha {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Правильный порядок для твоих картинок:
        // 1 - верх лево, 2 - верх право, 3 - низ лево, 4 - низ право
        $correctOrder = [1, 2, 3, 4];
        
        // Перемешиваем для отображения
        $shuffled = $correctOrder;
        shuffle($shuffled);
        
        $_SESSION['captcha_shuffled'] = $shuffled;
        $_SESSION['captcha_answer'] = implode(',', $correctOrder);
    }

    public static function getShuffledImages() {
        return $_SESSION['captcha_shuffled'] ?? [1, 2, 3, 4];
    }

    public static function validate($userSequence) {
        if (empty($userSequence)) return false;
        $correct = explode(',', $_SESSION['captcha_answer'] ?? '');
        return $userSequence == $correct;
    }
}