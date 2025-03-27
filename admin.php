<?php
session_start();

// 管理面板访问密码
$adminPassword = 'fuckvsa';

// 未登录时显示密码输入界面
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === $adminPassword) {
            $_SESSION['admin'] = true;
            header("Location: admin.php");
            exit();
        } else {
            $error = "密码错误！";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="zh">
    <head>
        <meta charset="UTF-8">
        <title>管理登录</title>
    </head>
    <body>
        <h2>请输入管理面板密码</h2>
        <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="post">
            <input type="password" name="password" placeholder="请输入密码" required>
            <button type="submit">登录</button>
        </form>
    </body>
    </html>
    <?php
    exit();
}

// 数据库配置信息
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
    die("数据库连接失败：" . $e->getMessage());
}

// 简单路由逻辑
$table = isset($_GET['table']) ? $_GET['table'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

// 公共函数：获取表的字段信息
function getTableColumns($pdo, $table) {
    $stmt = $pdo->prepare("DESCRIBE `$table`");
    $stmt->execute();
    return $stmt->fetchAll();
}

// 列出所有数据表
if (!$table) {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);
    ?>
    <!DOCTYPE html>
    <html lang="zh">
    <head>
        <meta charset="UTF-8">
        <title>管理面板 - 数据表列表</title>
    </head>
    <body>
        <h2>数据表列表</h2>
        <ul>
            <?php foreach ($tables as $t): 
                $tbl = $t[0]; ?>
                <li><a href="admin.php?table=<?= htmlspecialchars($tbl) ?>"><?= htmlspecialchars($tbl) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <p><a href="admin.php?logout=1">退出管理</a></p>
    </body>
    </html>
    <?php
    exit();
}

// 注: 为简单起见，仅允许由字母、数字和下划线组成的表名
if(!preg_match('/^\w+$/', $table)){
    die("非法的数据表名！");
}

// 处理退出登录操作
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit();
}

// 根据不同操作执行增删改查
if ($action === 'delete' && isset($_GET['id'])) {
    // 删除记录（假设主键字段为 id）
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: admin.php?table=" . htmlspecialchars($table));
    exit();
}

if ($action === 'edit' && isset($_GET['id'])) {
    // 先获取当前记录
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 处理更新提交
        $columns = getTableColumns($pdo, $table);
        $fields = [];
        $values = [];
        foreach ($columns as $col) {
            $colName = $col['Field'];
            if ($colName == 'id') continue; // 不更新主键
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
        // 显示编辑表单
        $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $record = $stmt->fetch();
        if (!$record) {
            die("记录不存在！");
        }
        $columns = getTableColumns($pdo, $table);
        ?>
        <!DOCTYPE html>
        <html lang="zh">
        <head>
            <meta charset="UTF-8">
            <title>编辑记录 - <?= htmlspecialchars($table) ?></title>
        </head>
        <body>
            <h2>编辑记录 (ID: <?= htmlspecialchars($_GET['id']) ?>)</h2>
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
                <button type="submit">保存</button>
            </form>
            <p><a href="admin.php?table=<?= htmlspecialchars($table) ?>">返回</a></p>
        </body>
        </html>
        <?php
        exit();
    }
}

if ($action === 'add') {
    $columns = getTableColumns($pdo, $table);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 插入记录（排除自增主键）
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
        // 显示新增表单
        ?>
        <!DOCTYPE html>
        <html lang="zh">
        <head>
            <meta charset="UTF-8">
            <title>新增记录 - <?= htmlspecialchars($table) ?></title>
        </head>
        <body>
            <h2>新增记录到表：<?= htmlspecialchars($table) ?></h2>
            <form method="post">
                <?php foreach ($columns as $col):
                    if (isset($col['Extra']) && strpos($col['Extra'], 'auto_increment') !== false) continue;
                    ?>
                    <div>
                        <label><?= htmlspecialchars($col['Field']) ?>:</label>
                        <input type="text" name="<?= htmlspecialchars($col['Field']) ?>" placeholder="<?= htmlspecialchars($col['Type']) ?>">
                    </div>
                <?php endforeach; ?>
                <button type="submit">新增</button>
            </form>
            <p><a href="admin.php?table=<?= htmlspecialchars($table) ?>">返回</a></p>
        </body>
        </html>
        <?php
        exit();
    }
}

// 默认情况下，显示指定数据表的所有记录
$stmt = $pdo->query("SELECT * FROM `$table`");
$records = $stmt->fetchAll();
$columns = getTableColumns($pdo, $table);
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>管理表：<?= htmlspecialchars($table) ?></title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 4px; text-align: left; }
    </style>
</head>
<body>
    <h2>当前管理的数据表：<?= htmlspecialchars($table) ?></h2>
    <p><a href="admin.php">返回表列表</a> | <a href="admin.php?table=<?= htmlspecialchars($table) ?>&action=add">新增记录</a> | <a href="admin.php?logout=1">退出管理</a></p>
    <table>
        <thead>
            <tr>
                <?php foreach ($columns as $col): ?>
                    <th><?= htmlspecialchars($col['Field']) ?></th>
                <?php endforeach; ?>
                <th>操作</th>
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
                            <a href="admin.php?table=<?= htmlspecialchars($table) ?>&action=edit&id=<?= htmlspecialchars($record['id']) ?>">编辑</a> | 
                            <a href="admin.php?table=<?= htmlspecialchars($table) ?>&action=delete&id=<?= htmlspecialchars($record['id']) ?>" onclick="return confirm('确定删除吗？')">删除</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr>
                    <td colspan="<?= count($columns)+1 ?>">没有记录</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>