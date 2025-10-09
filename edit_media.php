<?php
// pages/edit_media.php
require_once __DIR__ . '/config/dbconfig.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// If no id provided, show a simple chooser list
if ($id <= 0) {
    $res = $conn->query("SELECT id, title FROM media ORDER BY created_at DESC");
    echo "<h2>Select media to edit</h2>";
    if ($res && $res->num_rows > 0) {
        echo "<ul>";
        while ($r = $res->fetch_assoc()) {
            $safeTitle = htmlspecialchars($r['title']);
            echo "<li><a href=\"edit_media.php?id={$r['id']}\">Edit: {$safeTitle} (ID {$r['id']})</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No media found. <a href='add_media.php'>Add one</a></p>";
    }
    exit;
}

// Fetch the media item
$stmt = $conn->prepare("SELECT * FROM media WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$media = $result ? $result->fetch_assoc() : null;

if (!$media) {
    echo "<p>Media not found for ID {$id}. <a href='view_media.php'>Back to list</a></p>";
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization/validation
    $title = trim($_POST['title'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    if ($rating < 0) $rating = 0;
    if ($rating > 5) $rating = 5;
    $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;

    $update = $conn->prepare("UPDATE media SET title = ?, notes = ?, rating = ?, is_favorite = ? WHERE id = ?");
    $update->bind_param("ssiii", $title, $notes, $rating, $is_favorite, $id);

    if ($update->execute()) {
        $success = "✅ Media updated successfully.";
        // refresh $media values
        $media['title'] = $title;
        $media['notes'] = $notes;
        $media['rating'] = $rating;
        $media['is_favorite'] = $is_favorite;
    } else {
        $error = "❌ Update failed: " . $conn->error;
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit Media - MediaDeck</title>
</head>
<body>
    <h1>Edit Media (ID <?= $media['id'] ?>)</h1>

    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>

    <form method="post">
        <label>Title:<br>
            <input type="text" name="title" value="<?= htmlspecialchars($media['title']) ?>" required style="width:400px">
        </label><br><br>

        <label>Notes:<br>
            <textarea name="notes" rows="5" cols="60"><?= htmlspecialchars($media['notes']) ?></textarea>
        </label><br><br>

        <label>Rating (0-5):
            <input type="number" name="rating" min="0" max="5" value="<?= (int)$media['rating'] ?>">
        </label><br><br>

        <label>
            <input type="checkbox" name="is_favorite" <?= $media['is_favorite'] ? 'checked' : '' ?>> Favorite
        </label><br><br>

        <button type="submit">Save changes</button>
        <a href="view_media.php">Cancel</a>
    </form>
</body>
</html>
