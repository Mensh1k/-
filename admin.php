<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit();
}

// Добавление самоката
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_scooter'])) {
    $model = $_POST['model'];
    $price = $_POST['price_per_hour'];
    $image_url = $_POST['image_url'];
    
    $stmt = $pdo->prepare("INSERT INTO scooters (model, price_per_hour, image_url) VALUES (?, ?, ?)");
    $stmt->execute([$model, $price, $image_url]);
    $success = "Самокат добавлен";
}

// Удаление самоката
if(isset($_GET['delete_scooter'])) {
    $stmt = $pdo->prepare("DELETE FROM scooters WHERE id = ?");
    $stmt->execute([$_GET['delete_scooter']]);
    $success = "Самокат удален";
}

// Получение статистики
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_scooters = $pdo->query("SELECT COUNT(*) FROM scooters")->fetchColumn();
$active_rentals = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_price) FROM rentals WHERE status = 'completed'")->fetchColumn();

$scooters = $pdo->query("SELECT * FROM scooters")->fetchAll();
$users = $pdo->query("SELECT id, username, email, balance FROM users WHERE is_admin = FALSE")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="navbar">
        <h2>🛴 Админ-панель</h2>
        <div>
            <a href="index.php">Главная</a>
            <a href="dashboard.php">Личный кабинет</a>
            <a href="logout.php">Выйти</a>
        </div>
    </div>

    <div class="container">
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Статистика</h2>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div>
                    <h3><?php echo $total_users; ?></h3>
                    <p>Пользователей</p>
                </div>
                <div>
                    <h3><?php echo $total_scooters; ?></h3>
                    <p>Самокатов</p>
                </div>
                <div>
                    <h3><?php echo $active_rentals; ?></h3>
                    <p>Активных аренд</p>
                </div>
                <div>
                    <h3><?php echo number_format($total_revenue ?: 0, 2); ?> ₽</h3>
                    <p>Выручка</p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>Добавить самокат</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Модель:</label>
                    <input type="text" name="model" required>
                </div>
                <div class="form-group">
                    <label>Цена за час (₽):</label>
                    <input type="number" step="0.01" name="price_per_hour" required>
                </div>
                <div class="form-group">
                    <label>URL изображения:</label>
                    <input type="url" name="image_url" placeholder="https://via.placeholder.com/300x200">
                </div>
                <button type="submit" name="add_scooter" class="btn">Добавить</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Список самокатов</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Модель</th>
                        <th>Цена/час</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($scooters as $scooter): ?>
                    <tr>
                        <td><?php echo $scooter['id']; ?></td>
                        <td><?php echo htmlspecialchars($scooter['model']); ?></td>
                        <td><?php echo $scooter['price_per_hour']; ?> ₽</td>
                        <td><?php echo $scooter['status']; ?></td>
                        <td>
                            <a href="?delete_scooter=<?php echo $scooter['id']; ?>" class="btn btn-danger" onclick="return confirm('Удалить?')">Удалить</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2>Пользователи</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Баланс</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo number_format($user['balance'], 2); ?> ₽</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>