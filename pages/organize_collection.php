<?php
// Connect to database
require_once __DIR__ . '/../config/dbconfig.php';

// Optional: check if connected successfully
if (!$conn) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organize Collection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            text-align: center;
        }
        a {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            text-decoration: none;
            background: #007bff;
            color: white;
            border-radius: 5px;
        }
        a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Organize Your Media Collection</h1>

    <a href="add_media.php">➕ Add Media</a>
    <a href="edit_media.php">✏️ Edit Media</a>
    <a href="delete_media.php">🗑️ Delete Media</a>
    <a href="../index.php">🏠 Back to Home</a>

</body>
</html>

