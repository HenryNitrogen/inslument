<?php
session_start();

// 数据库配置
$host = 'localhost';
$db   = 'lument';       
$user = 'lument';         
$pass = 'eCb4hP6xNawZxiNL';     
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 随机生成6位由小写字母和数字构成的密钥函数
function generateRandomKey($length = 6) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $key;
}

// 检查生成的 key 是否唯一
function getUniqueKey(PDO $pdo) {
    do {
        $key = generateRandomKey();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM keys_table WHERE user_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);
    return $key;
}

// 处理退出登录
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}

// 处理登录逻辑，如果未登录则显示登录页面
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    $error = "";
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($username === 'fuck' && $password === 'fuckvsa') {
            $_SESSION['admin'] = true;
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid credentials.";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Admin Login</title>
        <style>
            body {
                background-color: #F5F5F5;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .login-container {
                background-color: #fff;
                padding: 2em;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                width: 300px;
            }
            input[type="text"], input[type="password"] {
                width: 100%;
                padding: 10px;
                margin: 0.5em 0;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            input[type="submit"] {
                width: 100%;
                padding: 10px;
                background-color: #007AFF;
                color: #fff;
                border: none;
                border-radius: 5px;
                font-size: 1em;
                cursor: pointer;
            }
            .error {
                color: red;
                font-size: 0.9em;
            }
        </style>
    </head>
    <body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <label>Username:
                <input type="text" name="username" required>
            </label>
            <label>Password:
                <input type="password" name="password" required>
            </label>
            <input type="submit" name="login" value="Login">
        </form>
    </div>
    </body>
    </html>
    <?php
    exit();
}

// 如果已登录，处理主界面的逻辑
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_key'])) {
    $name = $_POST['name'] ?? '';
    // 验证：名字仅允许英文字母
    if (preg_match('/^[A-Za-z]+$/', $name)) {
        // 检查名字是否已存在
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM keys_table WHERE user_name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $message = "Name already exists.";
        } else {
            // 生成唯一密钥，由小写字母和数字组成
            $key = getUniqueKey($pdo);
            $stmt = $pdo->prepare("INSERT INTO keys_table (user_key, user_name) VALUES (?, ?)");
            if ($stmt->execute([$key, $name])) {
                $message = "Key created successfully! (Key: $key)";
            } else {
                $message = "Failed to create key.";
            }
        }
    } else {
        $message = "Invalid name format. Only alphabetic characters allowed.";
    }
}

// 获取当前所有密钥信息
$stmt = $pdo->query("SELECT * FROM keys_table");
$keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Home</title>
    <style>
        body {
            background-color: #F5F5F5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 2em auto;
            background-color: #fff;
            padding: 2em;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
        }
        form input[type="text"] {
            width: calc(50% - 20px);
            padding: 10px;
            margin: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        form input[type="submit"] {
            padding: 10px 20px;
            background-color: #007AFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2em;
        }
        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }
        .copy-btn {
            cursor: pointer;
            background-color: #007AFF;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s;
        }
        .copy-btn:hover {
            background-color: #005BB5;
            transform: scale(1.05);
        }
        .logout-btn {
            background-color: #FF3B30;
            color: #fff;
            padding: 7px 14px;
            border: none;
            border-radius: 5px;
            position: absolute;
            top: 20px;
            right: 20px;
            cursor: pointer;
        }
        .message {
            color: green;
        }
    </style>
</head>
<body>
<div class="container">
    <button class="logout-btn" onclick="window.location.href='?action=logout'">Logout</button>
    <h2>Create Key</h2>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <input type="text" name="name" placeholder="Alphabetic name" required>
        <input type="submit" name="create_key" value="Create">
    </form>

    <h2>Existing Keys</h2>
    <table>
        <thead>
            <tr>
                <th>Key</th>
                <th>Name</th>
                <th>Copy</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($keys as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['user_key']) ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td>
                        <button class="copy-btn" data-key="<?= htmlspecialchars($row['user_key']) ?>">Copy</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
    document.querySelectorAll('.copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var key = btn.getAttribute('data-key');
            navigator.clipboard.writeText(key).then(function() {
                var originalText = btn.textContent;
                btn.textContent = "Copied!";
                setTimeout(function() {
                    btn.textContent = originalText;
                }, 1000);
            });
        });
    });
</script>
</body>
</html>