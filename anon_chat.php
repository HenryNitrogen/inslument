<?php
session_start();

// 检测是否登录，如果没有登录则重定向到登录页面
if (!isset($_SESSION['user']) || $_SESSION['user'] === '') {
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
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // 获取应用数据，用于动态生成导航栏
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY id");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// 处理提交的消息
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['submit_message'])) {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $pdo->prepare("INSERT INTO anonymous_chat (message) VALUES (?)");
        $stmt->execute([$message]);
    }
    header("Location: anon_chat.php");
    exit();
}

// 获取所有聊天记录，按时间升序排列，最新的在下方
$stmt = $pdo->query("SELECT * FROM anonymous_chat ORDER BY created_at ASC");
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>匿名聊天</title>
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
            max-width: 800px;
            margin: 0 auto;
        }
        .chat-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 70vh;
        }
        .chat-header {
            background-color: #007AFF;
            color: #fff;
            padding: 0.8rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-header h3 {
            margin: 0;
        }
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background-color: #f9f9f9;
        }
        .message {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 12px;
            background-color: #f0f0f0;
            word-wrap: break-word;
        }
        .message-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        .message-user {
            font-weight: bold;
        }
        .message-time {
            color: #888;
            font-size: 0.8rem;
        }
        .message-content {
            margin-top: 0.5rem;
        }
        .chat-input {
            border-top: 1px solid #ddd;
            padding: 1rem;
            display: flex;
            background-color: #fff;
        }
        .chat-input textarea {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 12px;
            margin-right: 0.5rem;
            font-size: 1rem;
            resize: none;
            height: 60px;
            font-family: inherit;
        }
        .chat-input button {
            background-color: #007AFF;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 0 1.25rem;
            font-weight: bold;
            cursor: pointer;
        }
        .chat-input button:hover {
            background-color: #0066CC;
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
        <h2>匿名聊天</h2>
        <div class="chat-container">
            <div class="chat-header">
                <h3>公共聊天区</h3>
            </div>
            <div class="chat-messages" id="chat-messages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message">
                        <div class="message-meta">
                            <span class="message-user">匿名</span>
                            <span class="message-time"><?= htmlspecialchars($msg['created_at']) ?></span>
                        </div>
                        <div class="message-content">
                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form class="chat-input" method="post" action="anon_chat.php">
                <textarea name="message" placeholder="请输入消息..." required></textarea>
                <input type="hidden" name="submit_message" value="1">
                <button type="submit">发送</button>
            </form>
        </div>
    </div>
    
    <script>
        // Auto-scroll to the bottom of chat within 1 second after page loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const chatMessages = document.getElementById('chat-messages');
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 300); // Small delay to ensure all content is rendered
        });
    </script>
</body>
</html>