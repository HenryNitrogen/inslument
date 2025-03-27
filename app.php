<?php
session_start();
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['user']) || $_SESSION['user'] === '') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>应用选择</title>
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
        .container {
            padding: 2rem;
        }
        .app-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s;
        }
        .app-card:hover {
            transform: scale(1.02);
        }
        .app-card h3 {
            margin: 0 0 0.5rem 0;
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
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <a href="app.php">应用选择</a>
        </div>
        <nav>
            <ul>
                <li><a href="app1.php">应用1</a></li>
                <li><a href="app2.php">应用2</a></li>
                <li><a href="chat.php">聊天</a></li>
            </ul>
        </nav>
        <div>
            <button class="logout-btn" onclick="location.href='app.php?action=logout'">退出登录</button>
        </div>
    </header>

    <div class="container">
        <h2>欢迎, <?= htmlspecialchars($_SESSION['user']) ?></h2>
        <p>请选择您要使用的应用：</p>
        <div class="app-card">
            <h3>应用1</h3>
            <p>这是应用1的简介。</p>
            <a href="app1.php">进入应用1</a>
        </div>
        <div class="app-card">
            <h3>应用2</h3>
            <p>这是应用2的简介。</p>
            <a href="app2.php">进入应用2</a>
        </div>
        <div class="app-card">
            <h3>聊天</h3>
            <p>进入聊天页面，与其他用户实时交流。</p>
            <a href="chat.php">进入聊天</a>
        </div>
    </div>
</body>
</html>