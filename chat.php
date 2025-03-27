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

// 读取消息记录
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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #F5F5F5;
            padding: 20px;
        }
        .nav {
            margin-bottom: 20px;
        }
        .nav a {
            color: #007AFF;
            text-decoration: none;
            margin-right: 15px;
        }
        .nav a:hover { text-decoration: underline; }
        .chat-header { font-size: 1.5em; margin-bottom: 20px; }
        .chat-box {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        .message { margin: 10px 0; }
        .message strong { color: #007AFF; }
        .timestamp { color: #888; font-size: 0.8em; margin-left: 10px; }
        form textarea {
            width: 100%;
            height: 80px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: vertical;
        }
        form input[type="submit"] {
            background-color: #007AFF;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 10px;
            cursor: pointer;
        }
        form input[type="submit"]:hover { background-color: #005BB5; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="app.php">首页</a>
        <a href="chat.php">聊天室</a>
        <a href="app.php?action=logout">退出登录</a>
    </div>
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
</body>
</html>