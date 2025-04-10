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
    // Get application data for dynamically generating the navigation bar
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY id");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle new message submission
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

$selectedConversation = isset($_GET["conversation"]) ? trim($_GET["conversation"]) : "";
if ($selectedConversation !== "") {
    $stmt = $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE sender = ? AND receiver = ? AND is_read = 0");
    $stmt->execute([$selectedConversation, $currentUser]);
}

// Get search keyword
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

// Use aggregate query to get unread message count and last message time for all other users  
$query = "SELECT k.user_name, IFNULL(p.unread,0) AS unread, p.last_time
          FROM keys_table k
          LEFT JOIN (
              SELECT 
                  CASE WHEN sender = :cu1 THEN receiver ELSE sender END AS partner,
                  COUNT(CASE WHEN receiver = :cu2 AND is_read = 0 THEN 1 END) AS unread,
                  MAX(created_at) AS last_time
              FROM private_messages
              WHERE sender = :cu3 OR receiver = :cu4
              GROUP BY partner
          ) p ON k.user_name = p.partner
          WHERE k.user_name <> :cu5";
$params = [
    ':cu1' => $currentUser,
    ':cu2' => $currentUser,
    ':cu3' => $currentUser,
    ':cu4' => $currentUser,
    ':cu5' => $currentUser,
];
if ($search !== "") {
    $query .= " AND k.user_name LIKE :search";
    $params[':search'] = "%" . $search . "%";
}
$query .= " ORDER BY p.last_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$conversationList = $stmt->fetchAll();

// If a conversation partner is selected, get all messages between both parties
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Private Messages</title>
    <style>
        body {
            margin: 0;
            background-color: #F5F5F5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        /* Top navigation bar */
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
        .navbar li { margin-right: 1rem; }
        .navbar a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
        }
        .navbar a:hover { text-decoration: underline; }
        .logout-btn {
            background-color: #FF3B30;
            border: none;
            border-radius: 5px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-weight: bold;
        }
        .logout-btn:hover { background-color: #E02E20; }
        /* Private message main area */
        .container {
            padding: 1rem 2rem;
        }
        .private-container {
            display: flex;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            height: 80vh;
        }
        /* Left user list */
        .sidebar {
            width: 30%;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            padding: 1rem;
        }
        .sidebar input[type="text"] {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .user-item {
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-item:hover { background-color: #f0f0f0; }
        .active { background-color: #e0e0e0; }
        .unread-badge {
            background-color: #FF3B30;
            color: #fff;
            border-radius: 50%;
            padding: 0 6px;
            font-size: 0.8rem;
        }
        /* Right conversation area */
        .chat-area {
            width: 70%;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .chat-history {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background-color: #fafafa;
        }
        .chat-message {
            margin-bottom: 1rem;
            display: flex;
        }
        .chat-message.sent { justify-content: flex-end; }
        .chat-message.received { justify-content: flex-start; }
        .chat-bubble {
            max-width: 70%;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            background-color: #d9fdd3;
            position: relative;
        }
        .chat-message.sent .chat-bubble { background-color: #cce5ff; }
        .chat-timestamp {
            font-size: 0.75rem;
            color: #999;
            margin-top: 5px;
            text-align: right;
        }
        .chat-input {
            border-top: 1px solid #ddd;
            padding: 0.5rem;
        }
        .chat-input form { display: flex; }
        .chat-input input[type="text"] {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .chat-input input[type="submit"] {
            background-color: #007AFF;
            color: #fff;
            border: none;
            padding: 0 1rem;
            margin-left: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
        }
        .chat-input input[type="submit"]:hover { background-color: #005BB5; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <a href="app.php">App Selection</a>
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
            <button class="logout-btn" onclick="location.href='app.php?action=logout'">Logout</button>
        </div>
    </header>
    <div class="container">
        <div class="private-container">
            <!-- Left user list -->
            <div class="sidebar">
                <form method="get" action="private.php">
                    <input type="text" name="search" placeholder="Search users" value="<?= htmlspecialchars($search) ?>">
                </form>
                <?php foreach ($conversationList as $conv): 
                    $uname = $conv["user_name"];
                    $activeClass = ($selectedConversation === $uname) ? "active" : "";
                ?>
                    <div class="user-item <?= $activeClass ?>" onclick="window.location.href='private.php?conversation=<?= urlencode($uname) ?><?= $search ? '&search=' . urlencode($search) : '' ?>'">
                        <span><?= htmlspecialchars($uname) ?></span>
                        <?php if ($conv["unread"] > 0): ?>
                            <span class="unread-badge"><?= $conv["unread"] ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Right conversation area -->
            <div class="chat-area">
                <?php if ($selectedConversation !== ""): ?>
                    <div class="chat-header">
                        Private Messages - Conversation with <?= htmlspecialchars($selectedConversation) ?>
                    </div>
                    <div class="chat-history">
                        <?php foreach ($conversationHistory as $msg): 
                            $sentByMe = ($msg["sender"] === $currentUser);
                            $class = $sentByMe ? "sent" : "received";
                        ?>
                            <div class="chat-message <?= $class ?>">
                                <div class="chat-bubble">
                                    <?= htmlspecialchars($msg["message"]) ?>
                                    <div class="chat-timestamp"><?= htmlspecialchars($msg["created_at"]) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chat-input">
                        <form method="post" action="private.php?conversation=<?= urlencode($selectedConversation) ?>">
                            <input type="hidden" name="conversation" value="<?= htmlspecialchars($selectedConversation) ?>">
                            <input type="text" name="message" placeholder="Enter a message..." required>
                            <input type="submit" value="Send">
                        </form>
                    </div>
                <?php else: ?>
                    <div class="chat-header" style="text-align:center;">Please select a user from the left to start a conversation</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>