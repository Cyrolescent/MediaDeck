<?php require_once __DIR__ . '/config/dbconfig.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organize Collection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0px;
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
    
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <h1>Organize Your Media Collection</h1>

    <a href="add_media.php">➕ Add Media</a>
    <a href="edit_media.php">✏️ Edit Media</a>
    <a href="delete_media.php">🗑️ Delete Media</a>
</body>
</html>

