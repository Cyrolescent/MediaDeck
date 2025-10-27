<?php
require_once __DIR__ . '/config/auth_check.php';  // ADD THIS LINE
require_once __DIR__ . '/config/dbconfig.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($postId > 0) {
        // Get file info before deleting (ONLY if belongs to current user)
        $sel = $conn->prepare("SELECT file_path, storage_type FROM media WHERE id = ? AND user_id = ?");
        $sel->bind_param("ii", $postId, $current_user_id);  // CHANGED
        $sel->execute();
        $res = $sel->get_result();
        $row = $res ? $res->fetch_assoc() : null;

        if ($row) {  // ADD THIS CHECK
            // Delete from database (ONLY if belongs to current user)
            $del = $conn->prepare("DELETE FROM media WHERE id = ? AND user_id = ?");
            $del->bind_param("ii", $postId, $current_user_id);  // CHANGED
            if ($del->execute()) {
                // Delete uploaded file if storage type is upload
                if ($row['storage_type'] === 'upload' && !empty($row['file_path'])) {
                    $file_to_delete = __DIR__ . '/' . $row['file_path'];
                    if (file_exists($file_to_delete)) {
                        unlink($file_to_delete);
                    }
                }
                header("Location: view_media.php?msg=deleted");
                exit;
            } else {
                $error = "Error deleting: " . $conn->error;
            }
        } else {
            $error = "Media not found or you don't have permission to delete it.";
        }
    } else {
        $error = "No media id provided.";
    }
}

if ($id <= 0) {
    // Show list of media (ONLY user's own media)
    $res = $conn->query("SELECT id, title FROM media WHERE user_id = $current_user_id ORDER BY created_at DESC");  // CHANGED
    ?>
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><title>Delete Media</title>
    <style>
        body { font-family: Arial; margin: 0px; }
        a { color: #4A90E2; text-decoration: none; }
    </style>
    </head>
    <body>
    <?php include 'includes/header.php'; ?>
    <h2>Select media to delete</h2>
    <?php if ($res && $res->num_rows > 0): ?>
        <ul>
        <?php while ($r = $res->fetch_assoc()): ?>
            <li><a href="delete_media.php?id=<?= $r['id'] ?>">Delete: <?= htmlspecialchars($r['title']) ?> (ID <?= $r['id'] ?>)</a></li>
        <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No media to delete. <a href='view_media.php'>Back to list</a></p>
    <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

// Fetch the media item to delete (ONLY if belongs to current user)
$stmt = $conn->prepare("SELECT id, title FROM media WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $current_user_id);  // CHANGED
$stmt->execute();
$res = $stmt->get_result();
$item = $res ? $res->fetch_assoc() : null;

if (!$item) {
    $error = "Media not found or you don't have permission to delete it.";
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Delete Media</title>
<style>
    body { font-family: Arial; margin: 0; }
    .container { margin: 20px; }
    .btn { display: inline-block; padding: 10px 20px; margin-right: 10px; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }
    .btn-danger { background: #f44336; color: white; }
    .btn-danger:hover { background: #da190b; }
    .btn-secondary { background: #888; color: white; }
    .btn-secondary:hover { background: #666; }
</style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Delete Media</h1>
        
        <?php if (!empty($error)): ?>
            <p style="color:red;"><?= $error ?></p>
            <a href="view_media.php" class="btn btn-secondary">Back to list</a>
        <?php else: ?>
            <p>Are you sure you want to delete: <strong><?= htmlspecialchars($item['title']) ?></strong></p>
            <p style="color: #d32f2f; font-size: 0.9em;">⚠️ This action cannot be undone. If this is an uploaded file, it will be permanently deleted.</p>

            <form method="post">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <button type="submit" class="btn btn-danger">Yes, delete permanently</button>
                <a href="view_media.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>