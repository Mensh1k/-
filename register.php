<?php
require_once 'config.php';

if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
//fufu

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    
    if($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if($stmt->rowCount() > 0) {
            $error = 'Пользователь с таким email или именем уже существует';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, phone) VALUES (?, ?, ?, ?)");
            
            if($stmt->execute([$username, $email, $hashed_password, $phone])) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
            } else {
                $error = 'Ошибка регистрации';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="navbar">
        <h2>🛴 Аренда самокатов</h2>
        <div>
            <a href="index.php">Главная</a>
            <a href="login.php">Вход</a>
        </div>
    </div>

    <div class="container">
        <div class="card" style="max-width: 500px; margin: 50px auto;">
            <h2>Регистрация</h2>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Имя пользователя:</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Телефон:</label>
                    <input type="tel" name="phone">
                </div>
                
                <div class="form-group">
                    <label>Пароль:</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Подтвердите пароль:</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn">Зарегистрироваться</button>
            </form>
            
            <p style="margin-top: 20px;">Уже есть аккаунт? <a href="login.php">Войдите</a></p>
        </div>
    </div>
</body>
</html>