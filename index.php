<?php
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аренда самокатов</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="navbar">
        <h2>🛴 Аренда самокатов</h2>
        <div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <span>Привет, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="dashboard.php">Личный кабинет</a>
                <a href="logout.php">Выйти</a>
            <?php else: ?>
                <a href="login.php">Вход</a>
                <a href="register.php">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <h1>Доступные самокаты</h1>
        
        <div class="scooter-grid">
            <?php
            $stmt = $pdo->query("SELECT * FROM scooters WHERE status = 'available'");
            while($scooter = $stmt->fetch(PDO::FETCH_ASSOC)):
            ?>
            <div class="scooter-card">
                <img src="<?php echo htmlspecialchars($scooter['image_url']); ?>" alt="<?php echo htmlspecialchars($scooter['model']); ?>">
                <div class="scooter-info">
                    <h3><?php echo htmlspecialchars($scooter['model']); ?></h3>
                    <p>Цена: <?php echo $scooter['price_per_hour']; ?> ₽/час</p>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form action="dashboard.php" method="POST">
                            <input type="hidden" name="scooter_id" value="<?php echo $scooter['id']; ?>">
                            <button type="submit" name="rent" class="btn">Арендовать</button>
                        </form>
                    <?php else: ?>
                        <a href="login.php" class="btn">Войдите для аренды</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>