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

// 读取用户列表（排除当前用户）
$stmt = $pdo->prepare("SELECT user_name FROM keys_table WHERE user_name <> ?");
$stmt->execute([$_SESSION["user"]]);
$users = $stmt->fetchAll();

// 处理发送消息
$messageSent = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $receiver = trim($_POST["receiver"] ?? "");
    $message  = trim($_POST["message"] ?? "");
    if ($receiver && $message) {
        $stmt = $pdo->prepare("INSERT INTO private_messages (sender, receiver, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$_SESSION["user"], $receiver, $message])) {
            $messageSent = "消息已发送给 " . htmlspecialchars($receiver);
        } else {
            $messageSent = "发送消息失败";
        }
    } else {
        $messageSent = "请选择接收人和输入消息";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>私信</title>
    <style>
        body {
            margin: 0;
            background-color: #F5F5F5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        /* 顶部导航栏 */
        .navbar {
            background-color: #007AFF;
            color: #fff;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar ul {
            list-style: none;
            margin: 0;
            padding: 0;
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
        /* 主体内容 */
        .container {
            padding: 2rem;
        }
        .private-container {
            max-width: 500px;
            margin: 0 auto;
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .private-container h2 {
            text-align: center;
        }
        .private-container select, 
        .private-container textarea {
            width: 100%;
            padding: 0.75rem;
            margin: 0.5rem 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        .private-container input[type="submit"] {
            width: 100%;
            padding: 0.75rem;
            background-color: #007AFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        .private-container input[type="submit"]:hover {
            background-color: #005BB5;
        }
        .message-sent {
            text-align: center;
            color: green;
            margin: 1rem 0;
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
                <li><a href="private.php">私信</a></li>
            </ul>
        </nav>
        <div>
            <button class="logout-btn" onclick="location.href='app.php?action=logout'">退出登录</button>
        </div>
    </header>
    <div class="container">
        <div class="private-container">
            <h2>私信</h2>
            <p>当前用户：<?= htmlspecialchars($_SESSION["user"]) ?></p>
            <?php if ($messageSent): ?>
                <div class="message-sent"><?= $messageSent ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <label for="receiver">选择接收人：</label>
                <select name="receiver" id="receiver" required>
                    <option value="">--请选择用户--</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user["user_name"]) ?>"><?= htmlspecialchars($user["user_name"]) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="message">消息内容：</label>
                <textarea name="message" id="message" placeholder="请输入消息..." required></textarea>
                <input type="submit" value="发送私信">
            </form>
        </div>
    </div>
</body>
</html>