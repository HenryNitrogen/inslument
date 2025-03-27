<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] === "") {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION["user"];

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
    // 动态生成导航栏数据
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY id");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 如果提交了新消息，且已选定对话对象，则插入消息
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["message"]) && isset($_POST["conversation"])) {
    $conversationPartner = trim($_POST["conversation"]);
    $messageText = trim($_POST["message"]);
    if ($conversationPartner !== "" && $messageText !== "") {
        $stmt = $pdo->prepare("INSERT INTO private_messages (sender, receiver, message) VALUES (?, ?, ?)");
        $stmt->execute([$currentUser, $conversationPartner, $messageText]);
    }
    header("Location: private.php?conversation=" . urlencode($conversationPartner));
    exit();
}

// 如果选中了对话对象，则将该对话中发送给当前用户的未读消息标记为已读
$selectedConversation = isset($_GET["conversation"]) ? trim($_GET["conversation"]) : "";
if ($selectedConversation !== "") {
    $stmt = $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE sender = ? AND receiver = ? AND is_read = 0");
    $stmt->execute([$selectedConversation, $currentUser]);
}

// 获取搜索关键字（用于左侧筛选用户）
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

// 获取所有其他用户（根据 keys_table），并附带各自的未读消息数量和最后消息时间
$queryUsers = "SELECT user_name FROM keys_table WHERE user_name <> ?";
$params = [$currentUser];
if ($search !== "") {
    $queryUsers .= " AND user_name LIKE ?";
    $params[] = "%" . $search . "%";
}
$stmt = $pdo->prepare($queryUsers);
$stmt->execute($params);
$users = $stmt->fetchAll();

// 对每个用户，查询未读数和最近消息时间（若无消息则置为NULL）
$conversationList = [];
foreach ($users as $u) {
    $uname = $u["user_name"];
    // 未读消息数：当前用户作为接收方，对方作为发送方
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread, MAX(created_at) as last_time FROM private_messages 
                           WHERE ((sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?))");
    $stmt->execute([$uname, $currentUser, $currentUser, $uname]);
    $data = $stmt->fetch();
    $conversationList[] = [
        "user_name"    => $uname,
        "unread"       => $data["unread"] ? (int)$data["unread"] : 0,
        "last_time"    => $data["last_time"] // may be null
    ];
}

// 排序：按 last_time降序排列，空的排在后面
usort($conversationList, function($a, $b) {
    if ($a["last_time"] == $b["last_time"]) return 0;
    if ($a["last_time"] === null) return 1;
    if ($b["last_time"] === null) return -1;
    return strcmp($b["last_time"], $a["last_time"]);
});

// 如果选中了对话对象，则获取双方所有对话记录
$conversationHistory = [];
if ($selectedConversation !== "") {
    $stmt = $pdo->prepare("SELECT * FROM private_messages 
                    WHERE ((sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?))
                    ORDER BY created_at ASC");
    $stmt->execute([$currentUser, $selectedConversation, $selectedConversation, $currentUser]);
    $conversationHistory = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>私信页面</title>
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
        .private-container {
            display: flex;
        }
        .sidebar {
            width: 200px;
            background-color: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-right: 1rem;
        }
        .chat-area {
            flex: 1;
            background-color: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .user-item {
            padding: 0.5rem;
            cursor: pointer;
            border-bottom: 1px solid #ddd;
        }
        .user-item:hover {
            background-color: #f0f0f0;
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
        <div class="private-container">
            <!-- 左侧：用户列表 -->
            <div class="sidebar">
                <form method="get" action="private.php">
                    <input type="text" name="search" placeholder="搜索用户" value="<?= htmlspecialchars($search) ?>">
                </form>
                <?php foreach ($conversationList as $conv): 
                    $uname = $conv["user_name"];
                    $unread = $conv["unread"];
                    $active = ($selectedConversation === $uname) ? "style='background-color:#e0e0e0;'" : "";
                ?>
                    <div class="user-item" onclick="window.location.href='private.php?conversation=<?= urlencode($uname) ?>'" <?= $active ?>>
                        <span><?= htmlspecialchars($uname) ?></span>
                        <?php if ($unread > 0): ?>
                            <span class="unread-badge"><?= $unread ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- 右侧：聊天区域 -->
            <div class="chat-area">
                <?php if ($selectedConversation !== ""): ?>
                    <div class="chat-header">
                        私信 - 与 <?= htmlspecialchars($selectedConversation) ?> 对话
                    </div>
                    <div class="chat-history">
                        <?php foreach ($conversationHistory as $msg): ?>
                            <?php 
                                $sentByMe = ($msg["sender"] === $currentUser);
                                $class = $sentByMe ? "sent" : "received";
                            ?>
                            <div class="chat-message <?= $class ?>">
                                <div class="chat-bubble">
                                    <?= htmlspecialchars($msg["message"]) ?>
                                    <div class="chat-timestamp"><?= $msg["created_at"] ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chat-input">
                        <form method="post" action="private.php?conversation=<?= urlencode($selectedConversation) ?>">
                            <input type="hidden" name="conversation" value="<?= htmlspecialchars($selectedConversation) ?>">
                            <input type="text" name="message" placeholder="请输入消息..." required>
                            <input type="submit" value="发送">
                        </form>
                    </div>
                <?php else: ?>
                    <div class="chat-header" style="text-align:center;">请选择左侧用户开始对话</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>