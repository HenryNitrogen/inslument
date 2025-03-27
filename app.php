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
$host    = 'localhost';
$db      = 'lument';
$user    = 'lument';
$pass    = 'eCb4hP6xNawZxiNL';
$charset = 'utf8mb4';
$dsn     = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Retrieve all applications from database
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
        }
        .logout-btn {
            background-color: #FF3B30;
            color: #fff;
            padding: 7px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .container {
            padding: 1rem;
        }
        .app-card {
            border: 1px solid #ccc;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            background-color: #fff;
        }
        .app-card h3 {
            margin: 0 0 0.5rem 0;
        }
        .app-card a {
            background-color: #007AFF;
            color: #fff;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
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
                    <li><a href="<?= htmlspecialchars($app['link']) ?>"><?= htmlspecialchars($app['name']) ?></a></li>
                <?php endforeach; ?>
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
                <a href="<?= htmlspecialchars($app['link']) ?>">进入应用</a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>