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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $pdo->prepare("INSERT INTO anonymous_chat (message) VALUES (?)");
        $stmt->execute([$message]);
    }
    header("Location: anon_chat.php");
    exit();
}

// 获取所有聊天记录，最新的在上
$stmt = $pdo->query("SELECT * FROM anonymous_chat ORDER BY created_at DESC");
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>匿名聊天</title>
    <style>
        /* 模仿其他页面的 navbar 样式 */
        .navbar {
            background-color: #007AFF;
            color: #fff;
         
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
        .container {
            padding: 2rem;
        }
        .chat-box {
            border: 1px solid #ccc;
            padding: 1rem;
            height: 400px;
            overflow-y: auto;
            background: #f9f9f9;
            margin-bottom: 1rem;
        }
        .message {
            margin-bottom: 0.5rem;
            padding: 0.2rem;
            background: #fff;
            border-radius: 4px;
        }
        textarea {
            width: 100%;
            height: 100px;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            resize: vertical;
        }
        input[type="submit"] {
            background-color: #007AFF;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- navbar -->
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
    </header>

    <div class="container">
        <h2>匿名聊天</h2>
        <div class="chat-box">
            <?php foreach ($messages as $msg): ?>
                <div class="message">
                    <strong>匿名</strong>: <?= htmlspecialchars($msg['message']) ?>
                    <span style="font-size:0.8rem;color:#888;">(<?= $msg['created_at'] ?>)</span>
                </div>
            <?php endforeach; ?>
        </div>
        <form method="post" action="anon_chat.php">
            <textarea name="message" placeholder="请输入消息..." required></textarea>
            <input type="submit" value="发送">
        </form>
    </div>
</body>
</html>