<?php
session_start();

// If already logged in, redirect to protected page (e.g. home.php)
if (isset($_SESSION['user']) && $_SESSION['user'] !== '') {
    header("Location: app.php");
    exit();
}

// Database connection configuration
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
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $user_key = trim($_POST["user_key"] ?? "");

    if ($username && $user_key) {
        $stmt = $pdo->prepare("SELECT * FROM keys_table WHERE user_name = ? AND user_key = ?");
        $stmt->execute([$username, $user_key]);
        $record = $stmt->fetch();
        if ($record) {
            $_SESSION["user"] = $username;
            header("Location: app.php");
            exit();
        } else {
            $error = "Invalid username or key.";
        }
    } else {
        $error = "Please enter both username and key.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 300px;
        }
        input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            margin: 0.5rem 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 0.75rem;
            background-color: #007AFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        .error {
            color: red;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 style="text-align:center;">Login</h2>
        <?php if ($error) { ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php } ?>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="user_key" placeholder="Key" required>
            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>