<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] === "") {
    header("Location: login.php");
    exit();
}

$result = "";
$expression = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $expression = $_POST["expression"] ?? "";
    if (isset($_POST["clear"])) {
        $expression = "";
    } elseif (isset($_POST["equal"])) {
        // 将 ^ 替换成 PHP 的幂运算符 **（PHP 7+支持）
        $expr = str_replace("^", "**", $expression);
        try {
            // 注意：eval()存在风险，此处仅用于演示环境
            $result = eval("return {$expr};");
        } catch (Throwable $e) {
            $result = "Error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>科学计算器</title>
    <style>
        body {
            margin: 0;
            background-color: #F5F5F5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        /* 导航栏 */
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
        /* 计算器区域 */
        .container {
            padding: 2rem;
            text-align: center;
        }
        .calc-container {
            width: 320px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 1rem;
        }
        .calc-display {
            width: 100%;
            height: 50px;
            font-size: 1.5rem;
            text-align: right;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 0.5rem;
        }
        .calc-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-gap: 0.5rem;
        }
        .calc-grid button {
            padding: 0.75rem;
            font-size: 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #E0E0E0;
        }
        .calc-grid button:hover {
            background-color: #ccc;
        }
        .calc-grid .operator {
            background-color: #007AFF;
            color: #fff;
        }
        .calc-grid .operator:hover {
            background-color: #005BB5;
        }
    </style>
    <script>
        // 使用 JavaScript 控制计算器按钮
        function appendToDisplay(value) {
            document.getElementById("expression").value += value;
        }
        function clearDisplay() {
            document.getElementById("expression").value = "";
        }
    </script>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <a href="app.php">应用选择</a>
        </div>
        <nav>
            <ul>
                <li><a href="chat.php">聊天室</a></li>
                <li><a href="calculator.php">计算器</a></li>
            </ul>
        </nav>
        <div>
            <button class="logout-btn" onclick="location.href='app.php?action=logout'">退出登录</button>
        </div>
    </header>
    <div class="container">
        <h2>欢迎, <?= htmlspecialchars($_SESSION["user"]) ?></h2>
        <div class="calc-container">
            <form method="post" action="">
                <input type="text" id="expression" name="expression" class="calc-display" value="<?= htmlspecialchars($expression) ?>" readonly>
                <div class="calc-grid">
                    <!-- 第一行：函数 -->
                    <button type="button" onclick="appendToDisplay('sin(')">sin</button>
                    <button type="button" onclick="appendToDisplay('cos(')">cos</button>
                    <button type="button" onclick="appendToDisplay('tan(')">tan</button>
                    <button type="button" onclick="appendToDisplay('log(')">log</button>
                    <button type="button" onclick="appendToDisplay('sqrt(')">√</button>
                    <!-- 第二行 -->
                    <button type="button" onclick="appendToDisplay('7')">7</button>
                    <button type="button" onclick="appendToDisplay('8')">8</button>
                    <button type="button" onclick="appendToDisplay('9')">9</button>
                    <button type="button" class="operator" onclick="appendToDisplay('/')">÷</button>
                    <button type="button" class="operator" onclick="appendToDisplay('(')">(</button>
                    <!-- 第三行 -->
                    <button type="button" onclick="appendToDisplay('4')">4</button>
                    <button type="button" onclick="appendToDisplay('5')">5</button>
                    <button type="button" onclick="appendToDisplay('6')">6</button>
                    <button type="button" class="operator" onclick="appendToDisplay('*')">×</button>
                    <button type="button" class="operator" onclick="appendToDisplay(')')">)</button>
                    <!-- 第四行 -->
                    <button type="button" onclick="appendToDisplay('1')">1</button>
                    <button type="button" onclick="appendToDisplay('2')">2</button>
                    <button type="button" onclick="appendToDisplay('3')">3</button>
                    <button type="button" class="operator" onclick="appendToDisplay('-')">-</button>
                    <button type="button" class="operator" onclick="appendToDisplay('^')">^</button>
                    <!-- 第五行 -->
                    <button type="button" onclick="appendToDisplay('0')">0</button>
                    <button type="button" onclick="appendToDisplay('.')">.</button>
                    <button type="button" onclick="clearDisplay()">C</button>
                    <button type="submit" name="equal" class="operator">=</button>
                    <button type="submit" name="submit" style="display:none;"></button>
                </div>
            </form>
            <?php if ($result !== ""): ?>
                <h3>结果：<?= htmlspecialchars($result) ?></h3>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>