<?php
require_once __DIR__ . '/config/dbconfig.php';
require_once __DIR__ . '/config/auth_check.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: view_media.php');
    exit;
}

// Fetch the media item
$stmt = $conn->prepare("SELECT * FROM media WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$media = $result ? $result->fetch_assoc() : null;

if (!$media) {
    header('Location: view_media.php');
    exit;
}

$error = '';
$success = '';
$thumbnailFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $file_path = trim($_POST['file_path'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    if ($rating < 0) $rating = 0;
    if ($rating > 5) $rating = 5;
    $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
    
    $thumbnail_path = $media['thumbnail']; // Keep existing thumbnail by default

    if (empty($title)) {
        $error = "Title is required!";
    } else {
        // Handle thumbnail upload (only for audio/video with upload storage)
        if ($media['storage_type'] === 'upload' && in_array($media['type'], ['audio', 'video']) && 
            isset($_FILES['thumbnail_upload']) && $_FILES['thumbnail_upload']['error'] === UPLOAD_ERR_OK) {
            
            $thumb_file = $_FILES['thumbnail_upload'];
            $thumb_ext = strtolower(pathinfo($thumb_file['name'], PATHINFO_EXTENSION));
            
            if (in_array($thumb_ext, $thumbnailFormats)) {
                $thumb_filename = 'thumb_' . date('Y-m-d_H-i-s_') . uniqid() . '.' . $thumb_ext;
                $thumb_path = __DIR__ . '/uploads/' . $thumb_filename;
                
                if (move_uploaded_file($thumb_file['tmp_name'], $thumb_path)) {
                    // Delete old thumbnail if exists
                    if (!empty($media['thumbnail']) && file_exists(__DIR__ . '/' . $media['thumbnail'])) {
                        unlink(__DIR__ . '/' . $media['thumbnail']);
                    }
                    $thumbnail_path = 'uploads/' . $thumb_filename;
                }
            }
        }

        // Update media
        if ($media['storage_type'] === 'link') {
            $update = $conn->prepare("UPDATE media SET title = ?, file_path = ?, notes = ?, rating = ?, is_favorite = ?, thumbnail = ? WHERE id = ? AND user_id = ?");
            $update->bind_param("sssiisii", $title, $file_path, $notes, $rating, $is_favorite, $thumbnail_path, $id, $current_user_id);
        } else {
            // For uploads, don't change file_path
            $update = $conn->prepare("UPDATE media SET title = ?, notes = ?, rating = ?, is_favorite = ?, thumbnail = ? WHERE id = ? AND user_id = ?");
            $update->bind_param("ssiisii", $title, $notes, $rating, $is_favorite, $thumbnail_path, $id, $current_user_id);
        }

        if ($update->execute()) {
            // Redirect to detail page
            header("Location: view_detail.php?id=" . $id);
            exit;
        } else {
            $error = "⚠ Update failed: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Media - MediaDeck</title>
    <link rel="stylesheet" href="assets/css/add_media.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="back-link">
            <a href="view_detail.php?id=<?= $id ?>">🏠 Back to Detail</a>
        </div>

        <div class="form-container">
            <div class="form-left">
                <!-- Media Info Box (Non-editable) -->
                <div style="background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                    <h3 style="margin: 0 0 10px 0; color: #667eea;">Media Information</h3>
                    <p style="margin: 5px 0; color: #555;"><strong>Type:</strong> <?= strtoupper($media['type']) ?></p>
                    <p style="margin: 5px 0; color: #555;"><strong>Storage:</strong> <?= strtoupper($media['storage_type']) ?></p>
                    <p style="margin: 5px 0; color: #555;"><strong>Date Added:</strong> <?= date('M d, Y', strtotime($media['created_at'])) ?></p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" id="editMediaForm">
                    <!-- Title -->
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" required value="<?= htmlspecialchars($media['title']) ?>">
                    </div> <br>

                    <!-- File/Link Path (only editable for links) -->
                    <?php if ($media['storage_type'] === 'link'): ?>
                        <div class="form-group">
                            <label for="file_path">Link Path:</label>
                            <input type="text" id="file_path" name="file_path" placeholder="e.g. https://example.com/file.jpg" value="<?= htmlspecialchars($media['file_path']) ?>">
                        </div><br>
                    <?php else: ?>
                        <div class="form-group">
                            <label>File Path:</label>
                            <input type="text" value="<?= htmlspecialchars($media['file_path']) ?>" disabled style="background: #f0f0f0; color: #999;">
                            <small style="color: #999; font-size: 0.85em;">Uploaded files cannot be changed</small>
                        </div><br>
                    <?php endif; ?>

                    <!-- Thumbnail Upload (only for audio/video + upload) -->
                    <?php if ($media['storage_type'] === 'upload' && in_array($media['type'], ['audio', 'video'])): ?>
                        <div class="form-group">
                            <label for="thumbnail_upload">Change Thumbnail (Optional):</label>
                            <input type="file" id="thumbnail_upload" name="thumbnail_upload" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="format-info">Formats: JPG, PNG, GIF, WebP</small>
                            <?php if (!empty($media['thumbnail'])): ?>
                                <div style="margin-top: 10px;">
                                    <small style="color: #666;">Current thumbnail: <?= htmlspecialchars(basename($media['thumbnail'])) ?></small>
                                </div>
                            <?php endif; ?>
                        </div><br>
                    <?php endif; ?>

                    <!-- Rating -->
                    <div class="form-group">
                        <label for="rating">Rating (0-5):</label>
                        <input type="number" id="rating" name="rating" min="0" max="5" value="<?= (int)$media['rating'] ?>"><br>
                    </div>

                    <!-- Favorite -->
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_favorite" name="is_favorite" <?= $media['is_favorite'] ? 'checked' : '' ?>>
                        <label for="is_favorite">Mark as Favorite ⭐</label>
                    </div><br><br>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">SAVE CHANGES</button>
                    <a href="view_detail.php?id=<?= $id ?>" class="btn-submit" style="background: #888; display: inline-block; text-align: center; margin-top: 10px; text-decoration: none;">CANCEL</a>
                </form>
            </div>

            <div class="form-right">
                <!-- Thumbnail Preview -->
                <div class="thumbnail-box">
                    <div class="thumbnail-placeholder" id="thumbnailPreview">
                        <?php if (!empty($media['thumbnail']) && file_exists(__DIR__ . '/' . $media['thumbnail'])): ?>
                            <img src="<?= htmlspecialchars($media['thumbnail']) ?>" alt="Thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php elseif ($media['type'] === 'image' && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $media['file_path'])): ?>
                            <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php elseif ($media['type'] === 'audio'): ?>
                            <img src="assets/icons/audio-icon.png" alt="Audio" style="width: 80%; height: 80%; object-fit: contain;">
                        <?php elseif ($media['type'] === 'video'): ?>
                            <img src="assets/icons/video-icon.png" alt="Video" style="width: 80%; height: 80%; object-fit: contain;">
                        <?php elseif ($media['type'] === 'text'): ?>
                            <img src="assets/icons/document-icon.png" alt="Document" style="width: 80%; height: 80%; object-fit: contain;">
                        <?php else: ?>
                            📷
                        <?php endif; ?>
                    </div>
                    <?php if ($media['storage_type'] === 'upload' && in_array($media['type'], ['audio', 'video'])): ?>
                        <div class="upload-text">CHANGE<br>THUMBNAIL</div>
                    <?php else: ?>
                        <div class="upload-text">PREVIEW</div>
                    <?php endif; ?>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" form="editMediaForm" rows="8"><?= htmlspecialchars($media['notes']) ?></textarea>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Thumbnail preview
        document.getElementById('thumbnail_upload')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('thumbnailPreview');
                    preview.innerHTML = `<img src="${event.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">`;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>