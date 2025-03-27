<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] === "") {
    header("Location: login.php");
    exit();
}

$host    = 'localhost';
$db      = 'lument';
$user    = 'lument';
$pass    = 'eCb4hP6xNawZxiNL';
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
    die("Database connection failed: " . $e->getMessage());
}

// 提交新消息
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["message"])) {
    $user_name = $_SESSION["user"];
    $message   = trim($_POST["message"]);
    if ($message !== "") {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_name, message) VALUES (?, ?)");
        $stmt->execute([$user_name, $message]);
    }
    header("Location: chat.php");
    exit();
}

// 获取所有消息记录
$stmt = $pdo->query("SELECT * FROM chat_messages ORDER BY created_at ASC");
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>聊天室</title>
    <style>
        body {
            margin: 0;
            background-color: #F5F5F5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        /* 顶部导航栏，与 calculator.php 样式一致 */
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
        /* 聊天室主体 */
        .container {
            padding: 2rem;
        }
        .chat-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .chat-header {
            font-size: 1.5em;
            margin-bottom: 1rem;
            text-align: center;
        }
        .chat-box {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 1rem;
        }
        .message {
            margin: 0.5rem 0;
        }
        .message strong {
            color: #007AFF;
        }
        .timestamp {
            color: #888;
            font-size: 0.8em;
            margin-left: 8px;
        }
        form textarea {
            width: 100%;
            height: 80px;
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: vertical;
        }
        form input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #007AFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        form input[type="submit"]:hover {
            background-color: #005BB5;
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
                <li><a href="chat.php">聊天室</a></li>
                <li><a href="calculator.php">科学计算器</a></li>
            </ul>
        </nav>
        <div>
            <button class="logout-btn" onclick="location.href='app.php?action=logout'">退出登录</button>
        </div>
    </header>
    <div class="container">
        <div class="chat-container">
            <div class="chat-header">
                聊天室 - 当前用户：<?= htmlspecialchars($_SESSION["user"]) ?>
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
    </div>
</body>
</html>