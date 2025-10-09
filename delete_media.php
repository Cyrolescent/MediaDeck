<?php
require_once __DIR__ . '/config/dbconfig.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($postId > 0) {
        $sel = $conn->prepare("SELECT file_path, storage_type FROM media WHERE id = ?");
        $sel->bind_param("i", $postId);
        $sel->execute();
        $res = $sel->get_result();
        $row = $res ? $res->fetch_assoc() : null;

        $del = $conn->prepare("DELETE FROM media WHERE id = ?");
        $del->bind_param("i", $postId);
        if ($del->execute()) {
            header("Location: view_media.php?msg=deleted");
            exit;
        } else {
            $error = "Error deleting: " . $conn->error;
        }
    } else {
        $error = "No media id provided.";
    }
}

if ($id <= 0) {
    $res = $conn->query("SELECT id, title FROM media ORDER BY created_at DESC");
    echo "<h2>Select media to delete</h2>";
    if ($res && $res->num_rows > 0) {
        echo "<ul>";
        while ($r = $res->fetch_assoc()) {
            echo "<li><a href=\"delete_media.php?id={$r['id']}\">Delete: " . htmlspecialchars($r['title']) . " (ID {$r['id']})</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No media to delete. <a href='view_media.php'>Back to list</a></p>";
    }
    exit;
}

//SHOW POSSIBLES MEDIAS
$stmt = $conn->prepare("SELECT id, title FROM media WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$item = $res ? $res->fetch_assoc() : null;

if (!$item) {
    echo "<p>Media not found. <a href='view_media.php'>Back to list</a></p>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Delete Media</title></head>
<body>
    <h1>Delete Media</h1>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <p>Are you sure you want to delete: <strong><?= htmlspecialchars($item['title']) ?></strong> (ID <?= $item['id'] ?>)?</p>

    <form method="post">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        <button type="submit">Yes, delete</button>
        <a href="view_media.php">Cancel</a>
    </form>
</body>
</html>
