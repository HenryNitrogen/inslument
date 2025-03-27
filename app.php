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

// Database configuration
$host = 'localhost';
$db = 'lument';
$user = 'lument';
$pass = 'eCb4hP6xNawZxiNL';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo 'Database connection failed: ' . $e->getMessage();
    exit();
}

// Fetch applications from the database
$stmt = $pdo->query("SELECT * FROM applications");
$applications = $stmt->fetchAll();
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
        .navbar a:hover {
            text-decoration: underline;
        }
        .logout-btn {
            background-color: #FF3B30;
            border: none;
            border-radius: 5px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-weight: bold;
        }
        .logout-btn:hover {
            background-color: #E02E20;
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
        .app-card h3 {
            margin: 0 0 0.5rem 0;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <a href="app.php">应用选择</a>
        </div>
        <nav>
            <ul>
                <?php foreach ($applications as $app): ?>
                <li><a href="<?= htmlspecialchars($app['link'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($app['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div>
            <button class="logout-btn" onclick="location.href='app.php?action=logout'">退出登录</button>
        </div>
    </header>

    <div class="container">
        <h2>欢迎, <?= htmlspecialchars($_SESSION['user'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>请选择您要使用的应用：</p>
        <?php foreach ($applications as $app): ?>
        <div class="app-card">
            <h3><?= htmlspecialchars($app['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($app['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <a href="<?= htmlspecialchars($app['link'] ?? '', ENT_QUOTES, 'UTF-8') ?>">进入<?= htmlspecialchars($app['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>