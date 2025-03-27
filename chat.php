<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] === "") {
    header("Location: login.php");
    exit();
}

// 数据库连接配置
$host    = 'localhost';
$db      = 'lument';
$user    = 'lument';
$pass    = 'eCb4hP6xNawZxiNL';
$charset = 'utf8mb4';
$dsn     = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // 获取应用数据，用于动态生成导航栏
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY id");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// 此处假定聊天记录保存在 $messages 中
$messages = []; // ...existing code to fetch messages...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>聊天室</title>
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
            color: #fff;
        }
        .logout-btn:hover {
            background-color: #E02E20;
        }
        .container {
            padding: 2rem;
        }
        .chat-box {
            background-color: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .message {
            margin-bottom: 0.5rem;
        }
        .timestamp {
            font-size: 0.8rem;
            color: #888;
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
                    <li>
                        <a href="<?= htmlspecialchars($app['link']) ?>">
                            <?= htmlspecialchars($app['NAME']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div>
            <button class="logout-btn" onclick="location.href='app.php?action=logout'">退出登录</button>
        </div>
    </header>
    <div class="container">
        <div>
            <h2>聊天室 - 当前用户：<?= htmlspecialchars($_SESSION["user"]) ?></h2>
        </div>
        <div class="chat-box">
            <?php foreach ($messages as $msg): ?>
                <div class="message">
                    <strong><?= htmlspecialchars($msg["user_name"]) ?>:</strong>
                    <?= htmlspecialchars($msg["message"]) ?>
                    <span class="timestamp">(<?= $msg["created_at"] ?>)</span>
                </div>
            <?php endforeach; ?>
        </div>
        <form method="post" action="">
            <textarea name="message" placeholder="请输入消息..." required></textarea>
            <input type="submit" value="发送">
        </form>
    </div>
</body>
</html>