<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] === "") {
    header("Location: login.php");
    exit();
}

$result = "";
$num1 = "";
$num2 = "";
$operator = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $num1 = trim($_POST["num1"] ?? "");
    $num2 = trim($_POST["num2"] ?? "");
    $operator = $_POST["operator"] ?? "";

    if (is_numeric($num1) && is_numeric($num2)) {
        switch ($operator) {
            case '+':
                $result = $num1 + $num2;
                break;
            case '-':
                $result = $num1 - $num2;
                break;
            case '*':
                $result = $num1 * $num2;
                break;
            case '/':
                if (floatval($num2) != 0) {
                    $result = $num1 / $num2;
                } else {
                    $result = "除数不能为0";
                }
                break;
            default:
                $result = "无效的操作符";
        }
    } else {
        $result = "请输入有效的数字";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>计算器</title>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #F5F5F5;
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
        }
        .logout-btn:hover {
            background-color: #E02E20;
        }
        .container {
            padding: 2rem;
        }
        .calculator-box {
            background-color: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 0 auto;
        }
        input[type="number"],
        select {
            width: calc(50% - 12px);
            padding: 0.75rem;
            margin: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
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
            margin-top: 1rem;
        }
        input[type="submit"]:hover {
            background-color: #005BB5;
        }
        .result {
            margin-top: 1rem;
            font-size: 1.2rem;
            color: #333;
            text-align: center;
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
                <li><a href="calculator.php">计算器</a></li>
            </ul>
        </nav>
        <div>
            <button class="logout-btn" onclick="location.href='app.php?action=logout'">退出登录</button>
        </div>
    </header>
    <div class="container">
        <h2>欢迎, <?= htmlspecialchars($_SESSION["user"]) ?></h2>
        <div class="calculator-box">
            <h3 style="text-align:center;">计算器</h3>
            <form method="post" action="">
                <div style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap;">
                    <input type="number" name="num1" value="<?= htmlspecialchars($num1) ?>" placeholder="数字1" step="any" required>
                    <select name="operator" required>
                        <option value="+" <?= $operator === '+' ? 'selected' : '' ?>>+</option>
                        <option value="-" <?= $operator === '-' ? 'selected' : '' ?>>-</option>
                        <option value="*" <?= $operator === '*' ? 'selected' : '' ?>>×</option>
                        <option value="/" <?= $operator === '/' ? 'selected' : '' ?>>÷</option>
                    </select>
                    <input type="number" name="num2" value="<?= htmlspecialchars($num2) ?>" placeholder="数字2" step="any" required>
                </div>
                <input type="submit" value="计算">
            </form>
            <?php if ($result !== ""): ?>
                <div class="result">结果：<?= htmlspecialchars($result) ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>