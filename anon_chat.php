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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>匿名聊天</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }
        
        body {
            background-color: #f4f7f9;
            color: #333;
            line-height: 1.6;
        }
        
        /* Navbar Styles with Animation */
        .navbar {
            background: linear-gradient(135deg, #0062cc, #007AFF);
            color: #fff;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .navbar:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .navbar ul {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
        }
        
        .navbar li {
            margin-right: 1.5rem;
            position: relative;
        }
        
        .navbar a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            position: relative;
            padding: 0.5rem 0;
            transition: all 0.3s ease;
        }
        
        .navbar a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #fff;
            transition: width 0.3s ease;
        }
        
        .navbar a:hover:after {
            width: 100%;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
            animation: fadeIn 0.5s ease-in;
        }
        
        /* Chat Box Styling */
        .chat-box {
            border: 1px solid #dde1e7;
            border-radius: 12px;
            padding: 1rem;
            height: 450px;
            overflow-y: auto;
            background: #fff;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            scroll-behavior: smooth;
        }
        
        .message {
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f0f5ff;
            border-radius: 10px;
            border-left: 4px solid #007AFF;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            animation: slideIn 0.3s ease-out;
            transition: transform 0.2s ease;
        }
        
        .message:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.06);
        }
        
        /* Input Area Styling */
        .input-area {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        textarea {
            width: 100%;
            height: 100px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #dde1e7;
            border-radius: 8px;
            resize: vertical;
            transition: border 0.3s ease, box-shadow 0.3s ease;
            font-size: 1rem;
        }
        
        textarea:focus {
            outline: none;
            border-color: #007AFF;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.25);
        }
        
        input[type="submit"] {
            background-color: #007AFF;
            color: #fff;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 122, 255, 0.3);
        }
        
        input[type="submit"]:hover {
            background-color: #0062cc;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 122, 255, 0.4);
        }
        
        input[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(0, 122, 255, 0.3);
        }
        
        h2 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
            position: relative;
            display: inline-block;
        }
        
        h2:after {
            content: '';
            position: absolute;
            width: 50%;
            height: 3px;
            bottom: -8px;
            left: 0;
            background: linear-gradient(90deg, #007AFF, transparent);
            border-radius: 2px;
        }
        
        .timestamp {
            font-size: 0.8rem;
            color: #888;
            margin-top: 0.3rem;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { 
                opacity: 0; 
                transform: translateY(15px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 122, 255, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(0, 122, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 122, 255, 0); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
                flex-direction: column;
            }
            
            .navbar ul {
                margin-top: 1rem;
            }
            
            .container {
                padding: 1rem;
            }
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
        <div class="chat-box" id="chatBox">
            <?php foreach ($messages as $msg): ?>
                <div class="message">
                    <strong>匿名</strong>: <?= htmlspecialchars($msg['message']) ?>
                    <div class="timestamp"><?= $msg['created_at'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="input-area">
            <form method="post" action="anon_chat.php">
                <textarea name="message" placeholder="请输入消息..." required></textarea>
                <input type="submit" value="发送">
            </form>
        </div>
    </div>

    <script>
        // Auto-scroll to the bottom of chat on page load
        window.onload = function() {
            const chatBox = document.getElementById('chatBox');
            chatBox.scrollTop = chatBox.scrollHeight;
            
            // Add animation class to messages on load
            const messages = document.querySelectorAll('.message');
            messages.forEach((message, index) => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.animation = 'slideIn 0.3s ease-out forwards';
                }, index * 100);
            });
        };
    </script>
</body>
</html>