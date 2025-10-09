<?php
$servername = "localhost";
$username   = "root";      
$password   = "";          
$dbname     = "mediadeck";

$conn = new mysqli($servername, $username, $password, $dbname);

// Test connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
