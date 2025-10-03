<?php
// include the database connection file
require_once __DIR__ . '/config/dbconfig.php';

// If we reached this line, db_connect.php was included successfully.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mediadeck Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        .success { color: green; }
        .error   { color: red; }
    </style>
</head>
<body>
    <h1>Welcome to MediaDeck</h1>
    <a href="pages/add_media.php">Add Media</a><br>
    <a href="pages/view_media.php">View Media</a><br>
    <?php
    // Check the database connection
    if ($conn && !$conn->connect_error) {
        echo "<p class='success'>✅ Connected to MySQL database successfully!</p>";
    } else {
        echo "<p class='error'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    }
    ?>
</body>
</html>
