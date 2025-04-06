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
    // Fetch applications for both navigation and cards
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY id");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
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
            scroll-behavior: smooth;
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
        .app-card a {
            color: #007AFF;
            text-decoration: none;
            font-weight: 500;
        }
        .app-card a:hover {
            text-decoration: underline;
        }
        /* Google Custom Search styling */
        .search-container {
            margin: 0 1rem;
            flex-grow: 1;
            max-width: 400px;
        }
        /* Override some Google search styles */
        .gsc-control-cse {
            background-color: transparent !important;
            border: none !important;
            padding: 0 !important;
        }
        .gsc-search-button-v2 {
            padding: 6px !important;
            margin-left: 3px !important;
        }
        /* Search results container */
        .search-results-container {
            margin-top: 3rem;
            padding: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: opacity 0.3s, transform 0.3s;
            opacity: 0;
            transform: translateY(20px);
        }
        .search-results-container.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .search-results-container h2 {
            margin-top: 0;
            color: #007AFF;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
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
                    <li><a href="<?= htmlspecialchars($app['link']) ?>"><?= htmlspecialchars($app['NAME']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <!-- Search box in navbar - triggers the Google CSE -->
        <div class="search-container">
            <div class="gcse-searchbox"></div>
        </div>
        <div>
            <button class="logout-btn" onclick="location.href='app.php?action=logout'">退出登录</button>
        </div>
    </header>

    <div class="container">
        <h2>欢迎, <?= htmlspecialchars($_SESSION['user']) ?></h2>
        <p>请选择您要使用的应用：</p>
        <?php foreach ($applications as $app): ?>
            <div class="app-card">
                <h3><?= htmlspecialchars($app['NAME']) ?></h3>
                <p><?= htmlspecialchars($app['description']) ?></p>
                <a href="<?= htmlspecialchars($app['link']) ?>">进入<?= htmlspecialchars($app['NAME']) ?></a>
            </div>
        <?php endforeach; ?>
        
        <!-- Search results container at the bottom of the page -->
        <div id="search-results" class="search-results-container">
            <h2>搜索结果</h2>
            <div class="gcse-searchresults"></div>
        </div>
    </div>

    <!-- Google Custom Search Engine script -->
    <script async src="https://cse.google.com/cse.js?cx=46740d73640c342d2"></script>
    <script>
        // Wait for Google CSE to load
        window.__gcse = {
            callback: function() {
                // Listen for search events
                const searchBox = document.querySelector('.gsc-input');
                const searchButton = document.querySelector('.gsc-search-button');
                const resultsContainer = document.getElementById('search-results');
                
                // Initially hide the results container
                resultsContainer.style.display = 'none';
                
                // Function to handle search events
                function handleSearch() {
                    // Show results container with delay for Google CSE to populate results
                    setTimeout(function() {
                        resultsContainer.style.display = 'block';
                        
                        // Wait a bit more for the transition and scroll
                        setTimeout(function() {
                            resultsContainer.classList.add('visible');
                            
                            // Smooth scroll to results
                            resultsContainer.scrollIntoView({ 
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }, 100);
                    }, 300);
                }
                
                // Listen for the enter key in search box
                searchBox.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && this.value.trim() !== '') {
                        handleSearch();
                    }
                });
                
                // Listen for search button click
                searchButton.addEventListener('click', function() {
                    if (searchBox.value.trim() !== '') {
                        handleSearch();
                    }
                });
            }
        };
    </script>
</body>
</html>