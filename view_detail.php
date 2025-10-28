<?php
require_once __DIR__ . '/config/dbconfig.php';
require_once __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/tag_functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: view_media.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM media WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$media = $result ? $result->fetch_assoc() : null;

if (!$media) {
    header("Location: view_media.php");
    exit;
}

$mediaTagsData = getMediaTags($pdo, $id);                   // Get the Tags
$isUploaded = ($media['storage_type'] === 'upload');        // Check if file exist / available siya
$fileExists = false;
$fullPath = '';
if ($isUploaded && !empty($media['file_path'])) {
    $fullPath = __DIR__ . '/' . $media['file_path'];
    $fileExists = file_exists($fullPath);
}

$fileExt = strtolower(pathinfo($media['file_path'], PATHINFO_EXTENSION));
$textFormats = ['txt', 'csv', 'rtf'];
$embedFormats = ['pdf', 'html'];
$officeFormats = ['docx', 'pptx', 'xlsx', 'odt'];


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
                <a href="view_media.php" class="btn-back">Back</a>
                <a href="edit_media.php?id=<?= $media['id'] ?>" class="btn-edit">✏️</a>
                <button onclick="confirmDelete()" class="btn-delete">🗑️</button>
            </div>
        </div>

        <!-- MEDIA PREVIEW/PLAYER -->
        <div class="detail-body">

            <!-- AUDIO Player po dito -->
            <?php if ($media['type'] === 'audio' && $isUploaded && $fileExists): ?>
                <div class="audio-player-container">
                    <div class="audio-thumbnail">
                        <?php if (!empty($media['thumbnail']) && file_exists(__DIR__ . '/' . $media['thumbnail'])): ?>
                            <img src="<?= htmlspecialchars($media['thumbnail']) ?>" alt="Audio Thumbnail">
                        <?php else: ?>
                            <img src="assets/icons/audio-icon.png" alt="Audio" style="background: #ffffff;">
                        <?php endif; ?>
                    </div>
                    <div class="audio-controls">
                        <audio id="audioPlayer" controls>
                            <source src="<?= htmlspecialchars($media['file_path']) ?>" type="audio/<?= $fileExt ?>">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                </div>
            
            <!-- TApos Video PLayer -->
            <?php elseif ($media['type'] === 'video' && $isUploaded && $fileExists): ?>
                <div class="video-player-container">
                    <video controls>
                        <source src="<?= htmlspecialchars($media['file_path']) ?>" type="video/<?= $fileExt ?>">
                        Your browser does not support the video element.
                    </video>
                </div>

            <!-- Text Viewer -->
            <?php elseif ($media['type'] === 'text' && $isUploaded && $fileExists): ?>
                <?php if (in_array($fileExt, $textFormats)): ?>
                    <?php
                    $content = file_get_contents($fullPath);

                    // CSV Viewer Testing files, Wala na ito
                    if ($fileExt === 'csv') {
                        $lines = array_map('str_getcsv', file($fullPath));
                        echo '<div class="document-viewer csv-table"><table>';
                        foreach ($lines as $index => $line) {
                            echo $index === 0 ? '<thead><tr>' : '<tr>';
                            foreach ($line as $cell) {
                                echo $index === 0 ? '<th>' . htmlspecialchars($cell) . '</th>' : '<td>' . htmlspecialchars($cell) . '</td>';
                            }
                            echo $index === 0 ? '</tr></thead><tbody>' : '</tr>';
                        }
                        echo '</tbody></table></div>';
                    } else {
                        echo '<div class="document-viewer">' . htmlspecialchars($content) . '</div>';
                    }
                    ?>

                    <!-- PDF Viewer Iframe-->
                <?php elseif ($fileExt === 'pdf'): ?>
                    <iframe class="document-embed" src="<?= htmlspecialchars($media['file_path']) ?>#toolbar=1&navpanes=0"></iframe>

                    <!-- HTML Viewer Iframe-->
                <?php elseif ($fileExt === 'html'): ?>
                    <iframe class="document-embed" src="<?= htmlspecialchars($media['file_path']) ?>" sandbox="allow-same-origin"></iframe>

                    <!-- Office Documents WALA ITO TANGGAL NA ANG PPTX DOCX ETC-->
                <?php elseif (in_array($fileExt, $officeFormats)): ?>
                    <div class="unsupported-format">
                        <strong>Office Document Preview</strong><br>
                        This format (<?= strtoupper($fileExt) ?>) requires external viewer.<br>
                        <a href="https://view.officeapps.live.com/op/embed.aspx?src=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . '/' . $media['file_path']) ?>" target="_blank" style="color: #4A90E2;">
                            📄 Open with Microsoft Office Viewer
                        </a>
                        or
                        <a href="<?= htmlspecialchars($media['file_path']) ?>" download style="color: #4A90E2;">
                            ⬇️ Download File
                        </a>
                    </div>

                    <!-- Error Unsupported FILES sayer -->
                <?php else: ?>
                    <div class="unsupported-format">
                        Unsupported document format. <a href="<?= htmlspecialchars($media['file_path']) ?>" download style="color: #4A90E2;">Download to view</a>
                    </div>
                <?php endif; ?>

            <?php else: ?>

                <!-- DEFAULT IMAGE/ICON PREVIEW FOR IMAGE MEDIAS DITO -->
                <div class="media-preview">
                    <?php
                    $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $media['file_path']);
                    $isExternal = preg_match('/^https?:\/\//i', $media['file_path']);
                    
                    if ($media['type'] === 'image' && $isImage):
                        if ($isExternal):?>
                        <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="<?= htmlspecialchars($media['title']) ?>" crossorigin="anonymous">
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="<?= htmlspecialchars($media['title']) ?>">
                    <?php endif; ?>
                    <?php elseif ($media['type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.parentElement.innerHTML='📷';">
                    <?php elseif ($media['type'] === 'video'): ?>
                        <?php if (!empty($media['thumbnail']) && file_exists(__DIR__ . '/' . $media['thumbnail'])): ?>
                            <img src="<?= htmlspecialchars($media['thumbnail']) ?>" alt="Video Thumbnail" style="max-width: 400px; border-radius: 8px;">
                        <?php else: ?>
                            <img src="assets/icons/video-icon.png" alt="Video" style="max-width: 200px;">
                        <?php endif; ?>
                    <?php elseif ($media['type'] === 'audio'): ?>
                        <img src="assets/icons/audio-icon.png" alt="Audio" style="max-width: 200px;">
                    <?php elseif ($media['type'] === 'text'): ?>
                        <img src="assets/icons/document-icon.png" alt="Document" style="max-width: 200px;">
                    <?php endif; ?>
                </div>
            <?php endif; ?>



            <!-- NOTES  LAHAT NG NOTES NG MEDIA RITO-->
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

            <!-- RATINGS AND FAVORITE  dito-->
            <div class="info-section">
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
                            <span style="color: #999;">❌ No</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <!-- TAGS SECTIONs LAHAT DITO TAGS LALABAS-->
            <div class="tags-section">
                <h2>Tags 🏷️</h2>
                
                <?php if (empty($mediaTagsData['default']) && empty($mediaTagsData['custom'])): ?>
                    <div class="no-tags-message">No tags assigned</div>
                <?php else: ?>
                    
                    <?php if (!empty($mediaTagsData['default'])): ?>
                        <div class="tag-group">
                            <div class="tag-group-title">Default Tags</div>
                            <div class="tag-badges">
                                <?php foreach ($mediaTagsData['default'] as $tag): ?>
                                    <span class="tag-badge tag-badge-default">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($mediaTagsData['default']) && !empty($mediaTagsData['custom'])): ?>
                        <div class="tags-divider"></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($mediaTagsData['custom'])): ?>
                        <div class="tag-group">
                            <div class="tag-group-title">Custom Tags</div>
                            <div class="tag-badges">
                                <?php foreach ($mediaTagsData['custom'] as $tag): ?>
                                    <span class="tag-badge tag-badge-custom">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>


            <!-- STORAGE INFO, infos ng strg type, file at date uploaded po-->
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
        </div>
    </div>


    <!-- script for delete media button -->
    <script>
        function confirmDelete() {
                window.location.href = 'delete_media.php?id=<?= $media['id'] ?>';
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>