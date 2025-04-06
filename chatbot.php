<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] === "") {
    header("Location: login.php");
    exit();
}

// Database connection
$host = 'localhost';
$db = 'lument';
$user = 'lument';
$pass = 'eCb4hP6xNawZxiNL';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Fetch applications for navigation
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY id");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// API configuration
$api_url = "https://api.vveai.com";
$api_key = "sk-ROaI6jhZeFQX3zng9dB99d5aD36941B89913D976E8B6B156";
$model = "gemini-2.0-flash";

// Get past conversations from session (initialize if needed)
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["message"]) && !empty($_POST["message"]) && isset($_POST["ajax_submit"])) {
    $user_message = trim($_POST["message"]);
    
    // Add user message to history
    $_SESSION['chat_history'][] = [
        'role' => 'user',
        'content' => $user_message
    ];
    
    // Prepare the conversation history for API call
    $messages = [];
    foreach ($_SESSION['chat_history'] as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    // Prepare API request
    $payload = json_encode([
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 1000
    ]);
    
    // Send the request to the API
    $ch = curl_init($api_url . '/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        $assistant_response = "Error communicating with the AI service: " . $error;
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error]);
        exit();
    } else {
        $response_data = json_decode($response, true);
        if (isset($response_data['choices'][0]['message']['content'])) {
            $assistant_response = $response_data['choices'][0]['message']['content'];
            
            // Add assistant's response to history
            $_SESSION['chat_history'][] = [
                'role' => 'assistant',
                'content' => $assistant_response
            ];
            
            // Store conversation in database for history
            try {
                $stmt = $pdo->prepare("INSERT INTO ai_conversations (user_name, user_message, ai_response) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user'], $user_message, $assistant_response]);
            } catch (PDOException $e) {
                // Table might not exist yet, admin would need to create it
            }
            
            // Return the AI response
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'response' => $assistant_response]);
            exit();
        } else {
            $assistant_response = "Error: Unexpected API response format.";
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unexpected API response format']);
            exit();
        }
    }
}

// Process if there's a regular form submission (for non-JavaScript browsers)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["message"]) && !empty($_POST["message"]) && isset($_POST["submit_message"])) {
    $user_message = trim($_POST["message"]);
    
    // Add user message to history
    $_SESSION['chat_history'][] = [
        'role' => 'user',
        'content' => $user_message
    ];
    
    // Prepare the conversation history for API call
    $messages = [];
    foreach ($_SESSION['chat_history'] as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    // Prepare API request
    $payload = json_encode([
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 1000
    ]);
    
    // Send the request to the API
    $ch = curl_init($api_url . '/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        $assistant_response = "Error communicating with the AI service: " . $error;
    } else {
        $response_data = json_decode($response, true);
        if (isset($response_data['choices'][0]['message']['content'])) {
            $assistant_response = $response_data['choices'][0]['message']['content'];
            
            // Add assistant's response to history
            $_SESSION['chat_history'][] = [
                'role' => 'assistant',
                'content' => $assistant_response
            ];
        } else {
            $assistant_response = "Error: Unexpected API response format.";
        }
    }
    
    // Store conversation in database for history
    try {
        $stmt = $pdo->prepare("INSERT INTO ai_conversations (user_name, user_message, ai_response) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user'], $user_message, $assistant_response]);
    } catch (PDOException $e) {
        // Table might not exist yet, admin would need to create it
    }
    
    // Redirect after form processing to prevent resubmission on refresh
    header("Location: chatbot.php");
    exit();
}

// Clear chat history if requested
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['chat_history'] = [];
    header("Location: chatbot.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI 聊天助手</title>
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
            display: flex;
            flex-direction: column;
        }
        .message {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 12px;
            max-width: 75%;
            word-wrap: break-word;
        }
        .user-message {
            background-color: #e1f0ff;
            align-self: flex-end;
            margin-left: auto;
        }
        .assistant-message {
            background-color: #f0f0f0;
            align-self: flex-start;
        }
        .typing-indicator {
            background-color: #f0f0f0;
            padding: 0.75rem;
            border-radius: 12px;
            align-self: flex-start;
            margin-bottom: 1rem;
            display: none;
        }
        .typing-indicator span {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #888;
            border-radius: 50%;
            animation: typing 1.4s infinite both;
            margin: 0 2px;
        }
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }
        @keyframes typing {
            0% { transform: scale(1); opacity: 0.7; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 0.7; }
        }
        .chat-input {
            border-top: 1px solid #ddd;
            padding: 1rem;
            display: flex;
            background-color: #fff;
        }
        .chat-input input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 20px;
            margin-right: 0.5rem;
            font-size: 1rem;
        }
        .chat-input button {
            background-color: #007AFF;
            color: #fff;
            border: none;
            border-radius: 20px;
            padding: 0 1.25rem;
            font-weight: bold;
            cursor: pointer;
        }
        .chat-input button:hover {
            background-color: #0066CC;
        }
        .clear-chat {
            margin-top: 1rem;
            text-align: center;
        }
        .clear-chat a {
            color: #FF3B30;
            text-decoration: none;
        }
        .clear-chat a:hover {
            text-decoration: underline;
        }
        pre {
            background-color: #f0f0f0;
            padding: 0.5rem;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        code {
            font-family: monospace;
            background-color: #f0f0f0;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
        }
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
        <h2>AI 聊天助手 (Gemini 2.0)</h2>
        <div class="chat-container">
            <div class="chat-header">
                <h3>与 AI 助手对话</h3>
            </div>
            <div class="chat-messages" id="chat-messages">
                <?php if (empty($_SESSION['chat_history'])): ?>
                    <div class="message assistant-message">
                        您好！我是 Gemini 2.0 AI 助手。有什么我可以帮您的吗？
                    </div>
                <?php else: ?>
                    <?php foreach ($_SESSION['chat_history'] as $message): ?>
                        <div class="message <?= $message['role'] === 'user' ? 'user-message' : 'assistant-message' ?>">
                            <?= nl2br(htmlspecialchars($message['content'])) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div id="typing-indicator" class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
            <form class="chat-input" method="post" id="chat-form">
                <input type="text" name="message" id="message-input" placeholder="发送消息..." autocomplete="off" required>
                <input type="hidden" name="submit_message" value="1">
                <button type="submit" id="submit-btn">发送</button>
            </form>
        </div>
        <div class="clear-chat">
            <a href="chatbot.php?action=clear">清除对话历史</a>
        </div>
    </div>

    <script>
        // Auto-scroll to the bottom of the chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Call when page loads
        window.onload = scrollToBottom;
        
        // Format Markdown in AI responses
        function formatMarkdown(text) {
            // Replace ```code``` blocks with <pre><code>code</code></pre>
            let formattedText = text.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
            
            // Replace `inline code` with <code>inline code</code>
            formattedText = formattedText.replace(/`([^`]+)`/g, '<code>$1</code>');
            
            // Replace line breaks with <br>
            formattedText = formattedText.replace(/\n/g, '<br>');
            
            return formattedText;
        }
        
        // Format existing AI responses
        document.addEventListener('DOMContentLoaded', function() {
            const assistantMessages = document.querySelectorAll('.assistant-message');
            assistantMessages.forEach(message => {
                let content = message.innerHTML;
                
                // Replace ```code``` blocks with <pre><code>code</code></pre>
                content = content.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
                
                // Replace `inline code` with <code>inline code</code>
                content = content.replace(/`([^`]+)`/g, '<code>$1</code>');
                
                message.innerHTML = content;
            });
            
            // Set up AJAX form submission
            const form = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const chatMessages = document.getElementById('chat-messages');
            const typingIndicator = document.getElementById('typing-indicator');
            const submitButton = document.getElementById('submit-btn');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const message = messageInput.value.trim();
                if (message === '') return;
                
                // Disable input and button while processing
                messageInput.disabled = true;
                submitButton.disabled = true;
                
                // Add user message to UI immediately
                const userMessageHtml = `
                    <div class="message user-message new-message">
                        ${message.replace(/\n/g, '<br>')}
                    </div>
                `;
                
                chatMessages.insertAdjacentHTML('beforeend', userMessageHtml);
                scrollToBottom();
                
                // Show typing indicator
                typingIndicator.style.display = 'block';
                scrollToBottom();
                
                // Clear input field
                messageInput.value = '';
                
                // Send data to server
                const formData = new FormData();
                formData.append('message', message);
                formData.append('ajax_submit', '1');
                
                fetch('chatbot.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Hide typing indicator
                    typingIndicator.style.display = 'none';
                    
                    if (data.success) {
                        // Format and add AI response to chat
                        const formattedResponse = formatMarkdown(data.response);
                        const aiMessageHtml = `
                            <div class="message assistant-message new-message">
                                ${formattedResponse}
                            </div>
                        `;
                        chatMessages.insertAdjacentHTML('beforeend', aiMessageHtml);
                    } else {
                        // Show error message
                        const errorMessageHtml = `
                            <div class="message assistant-message new-message">
                                Sorry, there was an error: ${data.error || 'Unknown error'}
                            </div>
                        `;
                        chatMessages.insertAdjacentHTML('beforeend', errorMessageHtml);
                    }
                    
                    // Re-enable input and button
                    messageInput.disabled = false;
                    submitButton.disabled = false;
                    messageInput.focus();
                    
                    // Scroll to bottom again to show AI response
                    scrollToBottom();
                })
                .catch(error => {
                    // Hide typing indicator
                    typingIndicator.style.display = 'none';
                    
                    // Show error message
                    const errorMessageHtml = `
                        <div class="message assistant-message new-message">
                            Sorry, there was an error communicating with the server. Please try again.
                        </div>
                    `;
                    chatMessages.insertAdjacentHTML('beforeend', errorMessageHtml);
                    
                    // Re-enable input and button
                    messageInput.disabled = false;
                    submitButton.disabled = false;
                    messageInput.focus();
                    
                    console.error('Error:', error);
                    scrollToBottom();
                });
            });
        });
    </script>
</body>
</html>