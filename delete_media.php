<?php
require_once __DIR__ . '/config/auth_check.php';  // ADD THIS LINE
require_once __DIR__ . '/config/dbconfig.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    // Dito checks kung yung file kay user siya ba
    if ($postId > 0) {
        $sel = $conn->prepare("SELECT file_path, storage_type FROM media WHERE id = ? AND user_id = ?");
        $sel->bind_param("ii", $postId, $current_user_id);
        $sel->execute();
        $res = $sel->get_result();
        $row = $res ? $res->fetch_assoc() : null;

        if ($row) {
            $del = $conn->prepare("DELETE FROM media WHERE id = ? AND user_id = ?");
            $del->bind_param("ii", $postId, $current_user_id);

            if ($del->execute()) {
                if ($row['storage_type'] === 'upload' && !empty($row['file_path'])) {  //deletes the file if uploaded
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


// getter ng file post ni user
$stmt = $conn->prepare("SELECT id, title FROM media WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $current_user_id);
$stmt->execute();
$res = $stmt->get_result();
$item = $res ? $res->fetch_assoc() : null;

if (!$item) {
    $error = "Media not found!";
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Delete Media</title>
<style>
    body { font-family: Arial; margin: 0; }
    .container { margin: 20px; }
    .btn { display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }
    .btn-delete { background: #f44336; color: white; }
    .btn-delete:hover { background: #da190b; }
    .btn-back { background: #888; color: white; }
    .btn-back:hover { background: #666; }
</style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Delete Media</h1>
        
        <?php if (!empty($error)): ?>
            <p style="color:red;"><?= $error ?></p>
            <a href="view_media.php" class="btn btn-back">Back to list</a>
        <?php else: ?>
            <p>Are you sure you want to delete: <strong><?= htmlspecialchars($item['title']) ?></strong></p>
            <p style="color: #d32f2f; font-size: 0.9em;">⚠️ This action cannot be undone. Any uploaded media will be permanently deleted.</p>

            <form method="post">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <button type="submit" class="btn btn-delete">Delete</button>
                <a href="view_media.php" class="btn btn-back">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>