<?php
require_once __DIR__ . '/config/dbconfig.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: view_media.php");
    exit;
}

// Fetch the media item
$stmt = $conn->prepare("SELECT * FROM media WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$media = $result ? $result->fetch_assoc() : null;

if (!$media) {
    header("Location: view_media.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($media['title']) ?> - MediaDeck</title>
    <link rel="stylesheet" href="assets/css/detail.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="detail-header">
            <h1><?= htmlspecialchars($media['title']) ?></h1>
            <div class="header-actions">
                <a href="view_media.php" class="btn-back">← Back</a>
                <a href="edit_media.php?id=<?= $media['id'] ?>" class="btn-edit">✏️ Edit</a>
            </div>
        </div>

        <div class="detail-body">
            <!-- MEDIA PREVIEW -->
            <div class="media-preview">
                <?php
                $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $media['file_path']);
                $isExternal = preg_match('/^https?:\/\//i', $media['file_path']);
                
                if ($media['type'] === 'image' && $isImage):
                    if ($isExternal):
                ?>
                    <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="<?= htmlspecialchars($media['title']) ?>" crossorigin="anonymous">
                <?php else: ?>
                    <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="<?= htmlspecialchars($media['title']) ?>">
                <?php endif; ?>
                <?php elseif ($media['type'] === 'image'): ?>
                    <img src="assets/icons/image-icon.png" alt="Image" style="max-width: 200px;">
                <?php elseif ($media['type'] === 'video'): ?>
                    <img src="assets/icons/video-icon.png" alt="Video" style="max-width: 200px;">
                <?php elseif ($media['type'] === 'audio'): ?>
                    <img src="assets/icons/audio-icon.png" alt="Audio" style="max-width: 200px;">
                <?php elseif ($media['type'] === 'text'): ?>
                    <img src="assets/icons/document-icon.png" alt="Document" style="max-width: 200px;">
                <?php endif; ?>
            </div>

            <!-- BASIC INFO -->
            <div class="info-section">
                <h2>Basic Information</h2>
                
                <div class="info-row">
                    <div class="info-label">Media ID:</div>
                    <div class="info-value"><?= $media['id'] ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">Title:</div>
                    <div class="info-value"><?= htmlspecialchars($media['title']) ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">Type:</div>
                    <div class="info-value">
                        <?php
                        $typeClass = '';
                        switch($media['type']) {
                            case 'image': $typeClass = 'badge-image'; break;
                            case 'video': $typeClass = 'badge-video'; break;
                            case 'audio': $typeClass = 'badge-audio'; break;
                            case 'text': $typeClass = 'badge-document'; break;
                        }
                        ?>
                        <span class="badge <?= $typeClass ?>"><?= strtoupper($media['type']) ?></span>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Rating:</div>
                    <div class="info-value">
                        <span class="rating-stars"><?= str_repeat('⭐', $media['rating']) ?></span>
                        <span style="color: #999;"> (<?= $media['rating'] ?>/5)</span>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Favorite:</div>
                    <div class="info-value">
                        <?php if ($media['is_favorite']): ?>
                            <span class="favorite-indicator">❤️ Yes</span>
                        <?php else: ?>
                            <span style="color: #999;">No</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- STORAGE INFO -->
            <div class="info-section">
                <h2>Storage Information</h2>
                
                <div class="info-row">
                    <div class="info-label">Storage Type:</div>
                    <div class="info-value">
                        <span class="badge <?= $media['storage_type'] === 'upload' ? 'badge-upload' : 'badge-link' ?>">
                            <?= strtoupper($media['storage_type']) ?>
                        </span>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">File Path:</div>
                    <div class="info-value">
                        <?php if ($media['storage_type'] === 'upload'): ?>
                            <span style="color: #999;">Uploaded in storage</span>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($media['file_path']) ?>" target="_blank" title="Click to open">
                                <?= htmlspecialchars($media['file_path']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Date Added:</div>
                    <div class="info-value"><?= $media['created_at'] ?></div>
                </div>
            </div>

            <!-- NOTES -->
            <div class="info-section">
                <h2>Notes / Description</h2>
                <div style="padding: 15px; background: #f9f9f9; border-radius: 8px; min-height: 60px;">
                    <?php if (!empty($media['notes'])): ?>
                        <?= nl2br(htmlspecialchars($media['notes'])) ?>
                    <?php else: ?>
                        <span style="color: #999; font-style: italic;">No notes available</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>