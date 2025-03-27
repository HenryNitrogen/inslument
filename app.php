<?php
session_start();
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['user']) || $_SESSION['user'] === '') {
    header("Location: login.php");
    exit();
}

// 建立数据库连接（请根据你的数据库信息修改配置）
$dsn = "mysql:host=localhost;dbname=yourdbname;charset=utf8mb4";
$username = "yourusername";
$password = "yourpassword";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("数据库连接失败：" . $e->getMessage());
}

// 从数据库获取应用信息
$stmt = $pdo->query("SELECT * FROM applications");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>应用选择</title>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #F5F5F5;
        }
        .navbar {
            background-color: #007AFF;
            color: #fff;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar ul {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
        }
        .navbar li {
            margin-right: 1rem;
        }
        .navbar a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
        }
        .logout-btn {
            background-color: #FF3B30;
            border: none;
            border-radius: 5px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-weight: bold;
        }
        .container {
            padding: 2rem;
        }
        .app-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s;
        }
        .app-card:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo"><a href="app.php">应用选择</a></div>
        <nav>
            <ul>
                <li><a href="chat.php">聊天</a></li>
                <li><a href="calculator.php">计算器</a></li>
            </ul>
        </nav>
        <div>
            <button class="logout-btn" onclick="location.href='app.php?action=logout'">退出登录</button>
        </div>
    </header>
    <div class="container">
        <h2>欢迎, <?= htmlspecialchars($_SESSION['user']) ?></h2>
        <p>请选择您要使用的应用：</p>
        <?php foreach ($applications as $app): ?>
            <div class="app-card">
                <h3><?= htmlspecialchars($app['name']) ?></h3>
                <p><?= htmlspecialchars($app['description']) ?></p>
                <a href="<?= htmlspecialchars($app['link']) ?>">进入<?= htmlspecialchars($app['name']) ?></a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>