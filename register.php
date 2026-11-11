<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/Captcha.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Captcha::init();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $captchaSequence = isset($_POST['captcha_sequence']) ? $_POST['captcha_sequence'] : '';
    $userSequence = explode(',', $captchaSequence);
    
    if (!Captcha::validate($userSequence)) {
        $error = "Капча не пройдена! Соберите картинку правильно.";
        Captcha::init();
    } else {
        $auth = new Auth();
        $result = $auth->register($_POST);
        
        if ($result === true) {
            header('Location: login.php');
            exit;
        } else {
            $error = $result;
            Captcha::init();
        }
    }
}

$shuffledImages = Captcha::getShuffledImages();
$imageString = implode(',', $shuffledImages);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="auth-card">
    <h2>Создать аккаунт</h2>
    
    <?php if($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <input type="text" name="login" placeholder="Логин" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Пароль" required>
        </div>
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
            <input type="text" name="phone" placeholder="Телефон" required>
        </div>
        <div class="form-group">
            <input type="text" name="last_name" placeholder="Фамилия" required>
        </div>
        <div class="form-group">
            <input type="text" name="first_name" placeholder="Имя" required>
        </div>
        <div class="form-group">
            <input type="text" name="middle_name" placeholder="Отчество">
        </div>

        <div class="captcha-section">
            <div class="captcha-header">
                <span>Соберите пазл</span>
                <span id="orderDisplay" class="captcha-status">0,0,0,0</span>
            </div>
            
            <input type="hidden" name="captcha_sequence" id="captcha_sequence" value="<?php echo $imageString; ?>">

            <div class="captcha-wrapper" id="captchaGrid">
                <?php foreach ($shuffledImages as $imgNum): ?>
                    <div class="captcha-cell" data-id="<?php echo $imgNum; ?>">
                        <img src="assets/captcha/<?php echo $imgNum; ?>.png" alt="Часть <?php echo $imgNum; ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn">Зарегистрироваться</button>
    </form>

    <div class="auth-footer">
        Уже есть аккаунт? <a href="login.php">Войти</a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var grid = document.getElementById('captchaGrid');
        var cells = grid.querySelectorAll('.captcha-cell');
        var hiddenInput = document.getElementById('captcha_sequence');
        var orderDisplay = document.getElementById('orderDisplay');

        function updateOrder() {
            var currentOrder = [];
            var allCells = grid.querySelectorAll('.captcha-cell');
            for (var i = 0; i < allCells.length; i++) {
                currentOrder.push(allCells[i].getAttribute('data-id'));
            }
            var orderStr = currentOrder.join(',');
            hiddenInput.value = orderStr;
            
            // Проверка на победу (1,2,3,4)
            if (orderStr === '1,2,3,4') {
                orderDisplay.textContent = 'Собрано!';
                orderDisplay.classList.add('success');
            } else {
                orderDisplay.textContent = 'Соберите пазл';
                orderDisplay.classList.remove('success');
            }
        }

        updateOrder();

        for (var i = 0; i < cells.length; i++) {
            var cell = cells[i];
            cell.setAttribute('draggable', 'true');

            cell.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', this.getAttribute('data-id'));
                this.classList.add('dragging');
            });

            cell.addEventListener('dragend', function() {
                this.classList.remove('dragging');
            });

            cell.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            cell.addEventListener('dragleave', function() {
                this.classList.remove('drag-over');
            });

            cell.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                var draggedId = e.dataTransfer.getData('text/plain');
                var targetId = this.getAttribute('data-id');

                if (draggedId === targetId) return;

                var draggedCell = grid.querySelector('[data-id="' + draggedId + '"]');
                var targetCell = this;

                var tempImgSrc = draggedCell.querySelector('img').src;
                draggedCell.querySelector('img').src = targetCell.querySelector('img').src;
                targetCell.querySelector('img').src = tempImgSrc;

                var tempId = draggedCell.getAttribute('data-id');
                draggedCell.setAttribute('data-id', targetId);
                targetCell.setAttribute('data-id', tempId);

                updateOrder();
            });
        }
    });
</script>

</body>
</html>