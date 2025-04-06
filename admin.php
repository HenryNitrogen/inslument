<?php
session_start();

// Admin panel access password
$adminPassword = 'fuckvsa';

// Show login form if not logged in
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === $adminPassword) {
            $_SESSION['admin'] = true;
            header("Location: admin.php");
            exit();
        } else {
            $error = "Wrong password!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Admin Login</title>
    </head>
    <body>
        <h2>Please enter admin panel password</h2>
        <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="post">
            <input type="password" name="password" placeholder="Enter password" required>
            <button type="submit">Login</button>
        </form>
    </body>
    </html>
    <?php
    exit();
}

// Database configuration
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
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Simple routing logic
$table = isset($_GET['table']) ? $_GET['table'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

// Common function: Get table column information
function getTableColumns($pdo, $table) {
    $stmt = $pdo->prepare("DESCRIBE `$table`");
    $stmt->execute();
    return $stmt->fetchAll();
}

// List all tables
if (!$table) {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Admin Panel - Table List</title>
    </head>
    <body>
        <h2>Table List</h2>
        <ul>
            <?php foreach ($tables as $t): 
                $tbl = $t[0]; ?>
                <li><a href="admin.php?table=<?= htmlspecialchars($tbl) ?>"><?= htmlspecialchars($tbl) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <p><a href="admin.php?logout=1">Logout</a></p>
    </body>
    </html>
    <?php
    exit();
}

// Note: For simplicity, only allow table names with letters, numbers and underscores
if(!preg_match('/^\w+$/', $table)){
    die("Invalid table name!");
}

// Handle logout
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit();
}

// Handle CRUD operations
if ($action === 'delete' && isset($_GET['id'])) {
    // Delete record (assuming primary key field is id)
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: admin.php?table=" . htmlspecialchars($table));
    exit();
}

if ($action === 'edit' && isset($_GET['id'])) {
    // First get current record
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Process update submission
        $columns = getTableColumns($pdo, $table);
        $fields = [];
        $values = [];
        foreach ($columns as $col) {
            $colName = $col['Field'];
            if ($colName == 'id') continue; // Don't update primary key
            $fields[] = "`$colName` = ?";
            $values[] = isset($_POST[$colName]) ? $_POST[$colName] : null;
        }
        $values[] = $_GET['id'];
        $sql = "UPDATE `$table` SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        header("Location: admin.php?table=" . htmlspecialchars($table));
        exit();
    } else {
        // Show edit form
        $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $record = $stmt->fetch();
        if (!$record) {
            die("Record not found!");
        }
        $columns = getTableColumns($pdo, $table);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Edit Record - <?= htmlspecialchars($table) ?></title>
        </head>
        <body>
            <h2>Edit Record (ID: <?= htmlspecialchars($_GET['id']) ?>)</h2>
            <form method="post">
                <?php foreach ($columns as $col): 
                    $colName = $col['Field'];
                    if ($colName == 'id') continue;
                    ?>
                    <div>
                        <label><?= htmlspecialchars($colName) ?>:</label>
                        <input type="text" name="<?= htmlspecialchars($colName) ?>" value="<?= htmlspecialchars($record[$colName]) ?>">
                    </div>
                <?php endforeach; ?>
                <button type="submit">Save</button>
            </form>
            <p><a href="admin.php?table=<?= htmlspecialchars($table) ?>">Back</a></p>
        </body>
        </html>
        <?php
        exit();
    }
}

if ($action === 'add') {
    $columns = getTableColumns($pdo, $table);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Insert record (exclude auto increment primary key)
        $fields = [];
        $placeholders = [];
        $values = [];
        foreach ($columns as $col) {
            if (isset($col['Extra']) && strpos($col['Extra'], 'auto_increment') !== false) continue;
            $fields[] = "`".$col['Field']."`";
            $placeholders[] = "?";
            $values[] = isset($_POST[$col['Field']]) ? $_POST[$col['Field']] : null;
        }
        $sql = "INSERT INTO `$table` (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        header("Location: admin.php?table=" . htmlspecialchars($table));
        exit();
    } else {
        // Show add form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Add Record - <?= htmlspecialchars($table) ?></title>
        </head>
        <body>
            <h2>Add Record to Table: <?= htmlspecialchars($table) ?></h2>
            <form method="post">
                <?php foreach ($columns as $col):
                    if (isset($col['Extra']) && strpos($col['Extra'], 'auto_increment') !== false) continue;
                    ?>
                    <div>
                        <label><?= htmlspecialchars($col['Field']) ?>:</label>
                        <input type="text" name="<?= htmlspecialchars($col['Field']) ?>" placeholder="<?= htmlspecialchars($col['Type']) ?>">
                    </div>
                <?php endforeach; ?>
                <button type="submit">Add</button>
            </form>
            <p><a href="admin.php?table=<?= htmlspecialchars($table) ?>">Back</a></p>
        </body>
        </html>
        <?php
        exit();
    }
}

// By default, show all records of the specified table
$stmt = $pdo->query("SELECT * FROM `$table`");
$records = $stmt->fetchAll();
$columns = getTableColumns($pdo, $table);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Table: <?= htmlspecialchars($table) ?></title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 4px; text-align: left; }
    </style>
</head>
<body>
    <h2>Currently Managing Table: <?= htmlspecialchars($table) ?></h2>
    <p><a href="admin.php">Return to Table List</a> | <a href="admin.php?table=<?= htmlspecialchars($table) ?>&action=add">Add Record</a> | <a href="admin.php?logout=1">Logout</a></p>
    <table>
        <thead>
            <tr>
                <?php foreach ($columns as $col): ?>
                    <th><?= htmlspecialchars($col['Field']) ?></th>
                <?php endforeach; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if($records): foreach($records as $record): ?>
                <tr>
                    <?php foreach ($columns as $col): 
                        $colName = $col['Field'];
                        ?>
                        <td><?= htmlspecialchars($record[$colName]) ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php if(isset($record['id'])): ?>
                            <a href="admin.php?table=<?= htmlspecialchars($table) ?>&action=edit&id=<?= htmlspecialchars($record['id']) ?>">Edit</a> | 
                            <a href="admin.php?table=<?= htmlspecialchars($table) ?>&action=delete&id=<?= htmlspecialchars($record['id']) ?>" onclick="return confirm('Are you sure you want to delete?')">Delete</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr>
                    <td colspan="<?= count($columns)+1 ?>">No records found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>