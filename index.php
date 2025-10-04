<?php
// index.php
require_once __DIR__ . '/config/dbconfig.php';

// simple connection check
$connected = isset($conn) && !$conn->connect_error;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MediaDeck Home</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .menu { margin-top: 30px; }
        .btn {
            display:inline-block;
            padding:14px 28px;
            margin:10px;
            background:#4A90E2;
            color:#fff;
            text-decoration:none;
            border-radius:8px;
        }
        .btn:hover { background:#357ABD; }
        .success { color: green; margin-top:20px; }
        .error   { color: red; margin-top:20px; }
    </style>
</head>
<body>
    <h1>Welcome to MediaDeck</h1>

    <div class="menu">
        <a class="btn" href="pages/view_media.php">View Collection</a>
        <a class="btn" href="pages/organize_collection.php">Organize Collection</a>
        <a class="btn" href="pages/settings.php">Settings</a>
    </div>

    <?php if ($connected): ?>
        <p class="success">✅ Connected to MySQL database successfully.</p>
    <?php else: ?>
        <!-- In development you can show the error; in production show a generic message. -->
        <p class="error">❌ Database connection failed. Please check your configuration.</p>
        <!-- Optional debug line (remove on production): -->
        <!-- <p class="error"><?= htmlspecialchars($conn->connect_error ?? 'Unknown error') ?></p> -->
    <?php endif; ?>
</body>
</html>

