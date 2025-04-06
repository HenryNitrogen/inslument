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

// 处理AJAX提交的消息
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['ajax_submit'])) {
    $user_name = $_SESSION["user"];
    $message   = trim($_POST["message"]);
    
    if ($message !== "") {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (user_name, message) VALUES (?, ?)");
        $stmt->execute([$user_name, $message]);
        
        // 获取新消息的时间戳
        $timestamp = $pdo->query("SELECT created_at FROM chat_messages ORDER BY id DESC LIMIT 1")->fetchColumn();
        
        // 返回成功响应
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'timestamp' => $timestamp, 'user' => $user_name]);
        exit();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Empty message']);
    exit();
}

// 提交新消息（非AJAX）
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["message"]) && isset($_POST["submit_message"])) {
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
<html lang="zh">
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
            max-width: 75%;
            word-wrap: break-word;
            clear: both;
        }
        .message-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }
        .message-username {
            font-weight: bold;
        }
        .message-time {
            color: #888;
            font-size: 0.8rem;
        }
        .message-content {
            margin-top: 0.25rem;
        }
        .self-message {
            background-color: #e1f0ff;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        .other-message {
            background-color: #f0f0f0;
            margin-right: auto;
            border-bottom-left-radius: 4px;
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
        /* Animation for new messages */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .new-message {
            animation: fadeIn 0.3s ease-out;
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
        <h2>聊天室</h2>
        <div class="chat-container">
            <div class="chat-header">
                <h3>公共聊天区 - 当前用户：<?= htmlspecialchars($_SESSION["user"]) ?></h3>
            </div>
            <div class="chat-messages" id="chat-messages">
                <?php foreach ($messages as $msg): ?>
                    <?php $isSelf = ($msg["user_name"] === $_SESSION["user"]); ?>
                    <div class="message <?= $isSelf ? 'self-message' : 'other-message' ?>">
                        <div class="message-meta">
                            <span class="message-username"><?= htmlspecialchars($msg["user_name"]) ?></span>
                            <span class="message-time"><?= htmlspecialchars($msg["created_at"]) ?></span>
                        </div>
                        <div class="message-content"><?= htmlspecialchars($msg["message"]) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="chat-input">
                <form method="post" action="" id="chat-form">
                    <textarea name="message" placeholder="请输入消息..." required></textarea>
                    <input type="hidden" name="ajax_submit" value="1">
                    <button type="submit">发送</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        const form = document.getElementById('chat-form');
        const messagesContainer = document.getElementById('chat-messages');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                const newMessage = document.createElement('div');
                newMessage.className = 'message self-message new-message';
                newMessage.innerHTML = `
                    <div class="message-meta">
                        <span class="message-username">${result.user}</span>
                        <span class="message-time">${result.timestamp}</span>
                    </div>
                    <div class="message-content">${formData.get('message')}</div>
                `;
                messagesContainer.appendChild(newMessage);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                form.reset();
            } else {
                alert(result.error || '发送失败');
            }
        });
    </script>
</body>
</html>