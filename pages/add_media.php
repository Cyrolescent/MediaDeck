<?php
require_once __DIR__ . '/../config/dbconfig.php';

// If the form is submitted, handle the insert
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title        = $_POST['title'];
    $type         = $_POST['type'];
    $storage_type = $_POST['storage_type'];
    $file_path    = $_POST['file_path'];  // For now: just a link or relative path
    $notes        = $_POST['notes'];
    $rating       = (int)$_POST['rating'];
    $is_favorite  = isset($_POST['is_favorite']) ? 1 : 0;

    $stmt = $conn->prepare("
        INSERT INTO media (title, type, storage_type, file_path, notes, rating, is_favorite)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssssi",
        $title,
        $type,
        $storage_type,
        $file_path,
        $notes,
        $rating,
        $is_favorite
    );

    if ($stmt->execute()) {
        $message = "✅ Media added successfully!";
    } else {
        $message = "❌ Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Media</title>
<style>
    body { font-family: Arial; margin: 20px; }
    label { display: block; margin-top: 10px; }
    input[type=text], textarea, select { width: 300px; }
    button { margin-top: 15px; }
    .msg { margin-top: 15px; color: green; }
</style>
</head>
<body>

<h1>Add Media</h1>

<?php if (!empty($message)) echo "<p class='msg'>$message</p>"; ?>

<form method="POST" action="">
    <label>Title:
        <input type="text" name="title" required>
    </label>

    <label>Type:
        <select name="type" required>
            <option value="image">Image</option>
            <option value="video">Video</option>
            <option value="audio">Audio</option>
            <option value="text">Text</option>
        </select>
    </label>

    <label>Storage Type:
        <select name="storage_type" required>
            <option value="link">Link</option>
            <option value="upload">Upload (coming soon)</option>
        </select>
    </label>

    <label>File/Link Path:
        <input type="text" name="file_path" placeholder="e.g. uploads/pic.jpg or https://...">
    </label>

    <label>Notes:
        <textarea name="notes" rows="3"></textarea>
    </label>

    <label>Rating (0-5):
        <input type="number" name="rating" min="0" max="5" value="0">
    </label>

    <label>
        <input type="checkbox" name="is_favorite"> Mark as Favorite
    </label>

    <button type="submit">Add Media</button>
</form>

</body>
</html>
