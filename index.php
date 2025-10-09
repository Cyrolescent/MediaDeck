<?php
require_once __DIR__ . '/config/dbconfig.php';

$connected = isset($conn) && !$conn->connect_error;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MediaDeck Home</title>
    <style>
        body { 
            text-align: center; 
            margin: 0; 
            background-color: #F3F4F6;
        }
        h2 { margin-top: 100px; color: #333; }
        .menu {
            margin-top: 40px;
        }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            margin: 10px;
            background: #4A90E2;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
        .btn:hover { background: #357ABD; }
    </style>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
<div style="text-align:center; margin-top:50px;">
<h2 style="font-size:2em;">Welcome to MediaDeck</h2>
</div>
</body>
</html>

