<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"] === "") {
    header("Location: login.php");
    exit();
}

// Database connection
$host    = 'localhost';
$db      = 'lument';
$user    = 'lument';
$pass    = 'eCb4hP6xNawZxiNL';
$charset = 'utf8mb4';
$dsn     = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Get application data
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY id");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Calculator business logic
$result = "";
$expression = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $expression = $_POST["expression"] ?? "";
    if (isset($_POST["clear"])) {
        $expression = "";
    } elseif (isset($_POST["equal"])) {
        // Replace ^ with PHP's power operator ** (PHP 7+ supported)
        $expr = str_replace("^", "**", $expression);
        try {
            // Note: eval() has security risks, used here only for demonstration
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
    <title>Scientific Calculator</title>
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
        /* Calculator styles below */
        .calc-container { max-width: 400px; margin: 0 auto; background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .calc-display { width: 100%; padding: 0.5rem; font-size: 1.2rem; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 1rem; }
        .calc-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
        .calc-grid button { padding: 0.8rem; font-size: 1rem; border: none; border-radius: 5px; cursor: pointer; background-color: #E0E0E0; }
        .calc-grid button.operator { background-color: #007AFF; color: #fff; }
        .calc-grid button:hover { background-color: #ccc; }
    </style>
    <script>
        // Use JavaScript to control calculator buttons
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
        <h2>Welcome, <?= htmlspecialchars($_SESSION["user"]) ?></h2>
        <div class="calc-container">
            <form method="post" action="">
                <input type="text" id="expression" name="expression" class="calc-display" value="<?= htmlspecialchars($expression) ?>" readonly>
                <div class="calc-grid">
                    <!-- First row: Functions -->
                    <button type="button" onclick="appendToDisplay('sin(')">sin</button>
                    <button type="button" onclick="appendToDisplay('cos(')">cos</button>
                    <button type="button" onclick="appendToDisplay('tan(')">tan</button>
                    <button type="button" onclick="appendToDisplay('log(')">log</button>
                    <!-- Second row: Numbers and operators -->
                    <button type="button" onclick="appendToDisplay('7')">7</button>
                    <button type="button" onclick="appendToDisplay('8')">8</button>
                    <button type="button" onclick="appendToDisplay('9')">9</button>
                    <button type="button" class="operator" onclick="appendToDisplay('/')">/</button>
                    <!-- Third row -->
                    <button type="button" onclick="appendToDisplay('4')">4</button>
                    <button type="button" onclick="appendToDisplay('5')">5</button>
                    <button type="button" onclick="appendToDisplay('6')">6</button>
                    <button type="button" class="operator" onclick="appendToDisplay('*')">*</button>
                    <!-- Fourth row -->
                    <button type="button" onclick="appendToDisplay('1')">1</button>
                    <button type="button" onclick="appendToDisplay('2')">2</button>
                    <button type="button" onclick="appendToDisplay('3')">3</button>
                    <button type="button" class="operator" onclick="appendToDisplay('-')">-</button>
                    <!-- Fifth row -->
                    <button type="button" onclick="appendToDisplay('0')">0</button>
                    <button type="button" onclick="appendToDisplay('.')">.</button>
                    <button type="submit" name="equal" class="operator">=</button>
                    <button type="button" class="operator" onclick="appendToDisplay('+')">+</button>
                    <!-- Clear button -->
                    <button type="submit" name="clear" style="grid-column: span 4;">Clear</button>
                </div>
            </form>
            <?php if ($result !== ""): ?>
                <p>Result: <?= htmlspecialchars($result) ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>