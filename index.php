<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/dbconfig.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0">
    <title>MediaDeck - Home</title>
    <link rel="stylesheet" href="assets/css/home.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
    <main class="home-container">
        <div class="text-section">
            <h3 class="welcome">WELCOME TO</h3>
            <h1 class="logo-text">
                <span class="m">M</span><span class="e">E</span><span class="d">D</span><span class="i">I</span><span class="a">A</span><span class="d2">D</span><span class="e2">E</span><span class="c">C</span><span class="k">K</span>
            </h1>
            <p class="description">
                A smart companion for managing media — organizing your collection into a sleek catalog,
                making navigation effortless, and letting you add annotations so every piece of content keeps its story.
            </p>
            <a href="about.php" class="about-button">ABOUT</a>
        </div>

        <div class="image-section">
            <img src="assets/images/home-icons.png" alt="MediaDeck Icons" class="home-icons">
        </div>
    </main>
</body>
</html>
