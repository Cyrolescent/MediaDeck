<?php
require_once __DIR__ . '/config/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - MediaDeck</title>
    <link rel="stylesheet" href="assets/css/about.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="members-section">
            <img src="assets/images/team-members.png" alt="Project Members" class="members-image">
        </div>

        <div class="info-section">
            <h2>Program Project Info</h2>
            <p>
                Our main Scope for this Project is to create a management system that is efficient and easy to use allowing people especially digital media enthusiasts yo have their own personal and safe library of diverse Digital medias
            </p>
        </div>

        <div class="features-section">
            <h2>Key Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📁</div>
                    <h3>Media Organization</h3>
                    <p>Organize images, videos, audio, and documents in one place with easy categorization</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h3>Favorites & Ratings</h3>
                    <p>Mark your favorite items and rate them from 0-5 stars for quick access</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3>Smart Search</h3>
                    <p>Quickly find any media with powerful search and filter capabilities</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3>Notes & Tags</h3>
                    <p>Add notes and custom tags to keep track of important details</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">☁️</div>
                    <h3>Multi-Storage</h3>
                    <p>Support for both local uploads and online links</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>User Accounts</h3>
                    <p>Personal accounts with isolated media collections for privacy</p>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>