<?php
session_start();

// Check if logged in, redirect to login page if not
if (!isset($_SESSION['user']) || $_SESSION['user'] === '') {
    header("Location: login.php");
    exit();
}

// Database connection configuration
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
    // Get application data for dynamically generating the navigation bar
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY id");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle AJAX submitted messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['ajax_submit'])) {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $pdo->prepare("INSERT INTO anonymous_chat (message) VALUES (?)");
        $stmt->execute([$message]);
        
        // Get the timestamp of the newly added message
        $timestamp = $pdo->query("SELECT created_at FROM anonymous_chat ORDER BY id DESC LIMIT 1")->fetchColumn();
        
        // Return success response with timestamp
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'timestamp' => $timestamp]);
        exit();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Empty message']);
    exit();
}

// Handle regular form submission (for browsers without JS support)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['submit_message'])) {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $pdo->prepare("INSERT INTO anonymous_chat (message) VALUES (?)");
        $stmt->execute([$message]);
    }
    header("Location: anon_chat.php");
    exit();
}

// Get all chat messages, ordered by time ascending (newest at bottom)
$stmt = $pdo->query("SELECT * FROM anonymous_chat ORDER BY created_at ASC");
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Anonymous Chat</title>
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
        <h2>Anonymous Chat</h2>
        <div class="chat-container">
            <div class="chat-header">
                <h3>Public Chat Area</h3>
            </div>
            <div class="chat-messages" id="chat-messages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message">
                        <div class="message-meta">
                            <span class="message-user">Anonymous</span>
                            <span class="message-time"><?= htmlspecialchars($msg['created_at']) ?></span>
                        </div>
                        <div class="message-content">
                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form class="chat-input" method="post" action="anon_chat.php" id="chat-form">
                <textarea name="message" id="message-input" placeholder="Enter message..." required></textarea>
                <input type="hidden" name="submit_message" value="1">
                <button type="submit">Send</button>
            </form>
        </div>
    </div>
    
    <script>
        // Auto-scroll to the bottom of chat immediately when loaded
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
            
            // Set up AJAX form submission for a smoother experience
            const form = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const chatMessages = document.getElementById('chat-messages');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const message = messageInput.value.trim();
                if (message === '') return;
                
                // Add message to UI immediately (optimistic UI)
                const now = new Date();
                const timeString = now.toISOString().replace('T', ' ').substr(0, 19);
                
                const messageHtml = `
                    <div class="message new-message">
                        <div class="message-meta">
                            <span class="message-user">Anonymous</span>
                            <span class="message-time">${timeString}</span>
                        </div>
                        <div class="message-content">
                            ${message.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                `;
                
                chatMessages.insertAdjacentHTML('beforeend', messageHtml);
                scrollToBottom();
                
                // Clear input field
                messageInput.value = '';
                
                // Send data to server via fetch
                const formData = new FormData();
                formData.append('message', message);
                formData.append('ajax_submit', '1');
                
                fetch('anon_chat.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the timestamp with the server's timestamp if needed
                        const lastMessage = chatMessages.lastElementChild;
                        const timeElement = lastMessage.querySelector('.message-time');
                        if (timeElement) {
                            timeElement.textContent = data.timestamp;
                        }
                    } else {
                        console.error('Error sending message:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
        
        function scrollToBottom() {
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>