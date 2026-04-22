<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Обработка аренды
if(isset($_POST['rent']) && isset($_POST['scooter_id'])) {
    $scooter_id = $_POST['scooter_id'];
    
    // Проверяем баланс пользователя
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Проверяем доступность самоката
    $stmt = $pdo->prepare("SELECT * FROM scooters WHERE id = ? AND status = 'available'");
    $stmt->execute([$scooter_id]);
    $scooter = $stmt->fetch();
    
    if($scooter && $user['balance'] >= $scooter['price_per_hour']) {
        // Создаем аренду
        $stmt = $pdo->prepare("INSERT INTO rentals (user_id, scooter_id, start_time) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $scooter_id]);
        
        // Обновляем статус самоката
        $stmt = $pdo->prepare("UPDATE scooters SET status = 'rented' WHERE id = ?");
        $stmt->execute([$scooter_id]);
        
        $success = "Самокат успешно арендован!";
    } else {
        $error = "Недостаточно средств или самокат недоступен";
    }
}

// Обработка завершения аренды
if(isset($_POST['end_rental']) && isset($_POST['rental_id'])) {
    $rental_id = $_POST['rental_id'];
    
    // Получаем информацию об аренде
    $stmt = $pdo->prepare("SELECT r.*, s.price_per_hour FROM rentals r JOIN scooters s ON r.scooter_id = s.id WHERE r.id = ? AND r.user_id = ? AND r.status = 'active'");
    $stmt->execute([$rental_id, $user_id]);
    $rental = $stmt->fetch();
    
    if($rental) {
        $end_time = new DateTime();
        $start_time = new DateTime($rental['start_time']);
        $hours = ceil($end_time->diff($start_time)->h + ($end_time->diff($start_time)->i / 60));
        $hours = max(1, $hours);
        
        $total_price = $hours * $rental['price_per_hour'];
        
        // Обновляем аренду
        $stmt = $pdo->prepare("UPDATE rentals SET end_time = NOW(), total_price = ?, status = 'completed' WHERE id = ?");
        $stmt->execute([$total_price, $rental_id]);
        
        // Обновляем баланс пользователя
        $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$total_price, $user_id]);
        
        // Освобождаем самокат
        $stmt = $pdo->prepare("UPDATE scooters SET status = 'available' WHERE id = ?");
        $stmt->execute([$rental['scooter_id']]);
        
        $success = "Аренда завершена. Сумма к оплате: {$total_price} ₽";
    }
}

// Получаем активную аренду
$stmt = $pdo->prepare("SELECT r.*, s.model, s.price_per_hour FROM rentals r JOIN scooters s ON r.scooter_id = s.id WHERE r.user_id = ? AND r.status = 'active'");
$stmt->execute([$user_id]);
$active_rental = $stmt->fetch();

// Получаем историю аренды
$stmt = $pdo->prepare("SELECT r.*, s.model FROM rentals r JOIN scooters s ON r.scooter_id = s.id WHERE r.user_id = ? AND r.status = 'completed' ORDER BY r.end_time DESC LIMIT 10");
$stmt->execute([$user_id]);
$rental_history = $stmt->fetchAll();

// Получаем баланс пользователя
$stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_balance = $stmt->fetch()['balance'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="navbar">
        <h2>🛴 Аренда самокатов</h2>
        <div>
            <span>Привет, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="index.php">Главная</a>
            <?php if($_SESSION['is_admin']): ?>
                <a href="admin.php">Админ-панель</a>
            <?php endif; ?>
            <a href="logout.php">Выйти</a>
        </div>
    </div>

    <div class="container">
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Ваш баланс</h2>
            <p class="balance"><?php echo number_format($user_balance, 2); ?> ₽</p>
            <p><a href="#" onclick="alert('Пополнение баланса будет доступно позже')">Пополнить баланс</a></p>
        </div>
        
        <?php if($active_rental): ?>
        <div class="card">
            <h2>Активная аренда</h2>
            <p><strong>Самокат:</strong> <?php echo htmlspecialchars($active_rental['model']); ?></p>
            <p><strong>Время начала:</strong> <?php echo $active_rental['start_time']; ?></p>
            <p><strong>Цена:</strong> <?php echo $active_rental['price_per_hour']; ?> ₽/час</p>
            
            <form method="POST">
                <input type="hidden" name="rental_id" value="<?php echo $active_rental['id']; ?>">
                <button type="submit" name="end_rental" class="btn btn-danger">Завершить аренду</button>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>История аренды</h2>
            <?php if(count($rental_history) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Самокат</th>
                        <th>Начало</th>
                        <th>Конец</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rental_history as $rental): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rental['model']); ?></td>
                        <td><?php echo $rental['start_time']; ?></td>
                        <td><?php echo $rental['end_time']; ?></td>
                        <td><?php echo number_format($rental['total_price'], 2); ?> ₽</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>У вас пока нет аренды</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>