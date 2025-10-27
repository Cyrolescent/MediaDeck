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

// Get tags for this media
$mediaTagsData = getMediaTags($pdo, $id);

// Check if file is uploaded and exists
$isUploaded = ($media['storage_type'] === 'upload');
$fileExists = false;
$fullPath = '';
if ($isUploaded && !empty($media['file_path'])) {
    $fullPath = __DIR__ . '/' . $media['file_path'];
    $fileExists = file_exists($fullPath);
}

// Get file extension for document handling
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
    <style>
        /* Audio Player Styles */
        .audio-player-container {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .audio-thumbnail {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }

        .audio-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .audio-controls {
            flex: 1;
        }

        audio {
            width: 100%;
            margin-top: 10px;
        }

        /* Video Player Styles */
        .video-player-container {
            width: 100%;
            margin-bottom: 20px;
        }

        video {
            width: 100%;
            max-height: 500px;
            border-radius: 8px;
            background: #000;
        }

        /* Document Viewer Styles */
        .document-viewer {
            width: 100%;
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            max-height: 500px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            color: #333;
            line-height: 1.6;
        }

        .document-viewer.csv-table {
            font-family: Arial, sans-serif;
            overflow-x: auto;
        }

        .document-viewer.csv-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .document-viewer.csv-table th,
        .document-viewer.csv-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .document-viewer.csv-table th {
            background: #f5f5f5;
            font-weight: bold;
        }

        iframe.document-embed {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .btn-delete {
            background: #f44336;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn-delete:hover {
            background: #d32f2f;
            transform: scale(1.05);
        }

        .unsupported-format {
            padding: 20px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            color: #856404;
            margin-bottom: 20px;
        }

        /* Tags Section Styles */
        .tags-section {
            background: rgba(42, 21, 53, 0.8);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        .tags-section h2 {
            color: #f0f0f0;
            font-size: 24px;
            margin: 0 0 15px 0;
            text-align: center;
        }

        .tag-group {
            margin-bottom: 15px;
        }

        .tag-group:last-child {
            margin-bottom: 0;
        }

        .tag-group-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #f0f0f0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .tag-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: white;
            transition: transform 0.2s;
        }

        .tag-badge:hover {
            transform: scale(1.05);
        }

        .tag-badge-default {
            background: #4A90E2;
        }

        .tag-badge-custom {
            background: #764ba2;
        }

        .no-tags-message {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 14px;
            font-style: italic;
        }

        .tags-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="detail-header">
            <h1><?= htmlspecialchars($media['title']) ?></h1>
            <div class="header-actions">
                <a href="view_media.php" class="btn-back">← Back</a>
                <a href="edit_media.php?id=<?= $media['id'] ?>" class="btn-edit">✏️</a>
                <button onclick="confirmDelete()" class="btn-delete">🗑️</button>
            </div>
        </div>

        <div class="detail-body">
            <!-- MEDIA PREVIEW/PLAYER -->
            <?php if ($media['type'] === 'audio' && $isUploaded && $fileExists): ?>
                <!-- AUDIO PLAYER -->
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

            <?php elseif ($media['type'] === 'video' && $isUploaded && $fileExists): ?>
                <!-- VIDEO PLAYER -->
                <div class="video-player-container">
                    <video controls>
                        <source src="<?= htmlspecialchars($media['file_path']) ?>" type="video/<?= $fileExt ?>">
                        Your browser does not support the video element.
                    </video>
                </div>

            <?php elseif ($media['type'] === 'text' && $isUploaded && $fileExists): ?>
                <!-- DOCUMENT VIEWER -->
                <?php if (in_array($fileExt, $textFormats)): ?>
                    <?php
                    $content = file_get_contents($fullPath);
                    if ($fileExt === 'csv') {
                        // Parse CSV
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
                        // Display plain text
                        echo '<div class="document-viewer">' . htmlspecialchars($content) . '</div>';
                    }
                    ?>

                <?php elseif ($fileExt === 'pdf'): ?>
                    <!-- PDF Embed -->
                    <iframe class="document-embed" src="<?= htmlspecialchars($media['file_path']) ?>#toolbar=1&navpanes=0"></iframe>

                <?php elseif ($fileExt === 'html'): ?>
                    <!-- HTML Embed -->
                    <iframe class="document-embed" src="<?= htmlspecialchars($media['file_path']) ?>" sandbox="allow-same-origin"></iframe>

                <?php elseif (in_array($fileExt, $officeFormats)): ?>
                    <!-- Office Documents -->
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

                <?php else: ?>
                    <div class="unsupported-format">
                        Unsupported document format. <a href="<?= htmlspecialchars($media['file_path']) ?>" download style="color: #4A90E2;">Download to view</a>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- DEFAULT IMAGE/ICON PREVIEW -->
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

            <!-- RATINGS AND FAVORITE -->
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
                            <span style="color: #999;">No</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAGS SECTION -->
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
        </div>
    </div>

    <script>
        function confirmDelete() {
                window.location.href = 'delete_media.php?id=<?= $media['id'] ?>';
        }
    </script>
</body>
</html>