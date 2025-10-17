<?php
require_once __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/dbconfig.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - MediaDeck</title>
        <link rel="stylesheet" href="assets/css/settings.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="settings-header">
            <h1>⚙️ Settings</h1>
            <p>Manage your MediaDeck preferences and customization</p>
        </div>

        <div class="settings-content">
            <div class="settings-section">
                <h2>👤 Account Information</h2>
                <div class="user-info-box">
                    <h3><?php echo htmlspecialchars($current_username); ?></h3>
                    <p><strong>Status:</strong> <?php echo ucfirst($current_status); ?></p>
                    <p><strong>User ID:</strong> #<?php echo $current_user_id; ?></p>
                </div>
            </div>
            <div class="settings-section">
                <h2>🏷️ Custom Tags</h2>
                <p>Create and manage custom tags to better organize your media collection</p>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Manage Tags</h3>
                        <p>Create, edit, or delete custom tags for your media items</p>
                    </div>
                    <button class="btn" disabled>Coming Soon</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
