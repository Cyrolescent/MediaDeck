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

        .play-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #4A90E2;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin-bottom: 10px;
        }

        .play-button:hover {
            background: #357ABD;
            transform: scale(1.1);
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
            width: 60px;
            background: #f44336;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;

        .btn-delete:hover {
            background: #d32f2f;
        }

        .unsupported-format {
            padding: 20px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            color: #856404;
            margin-bottom: 20px;
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
                <button onclick="confirmDelete()" class="btn-delete header-actions">🗑️</button>
            </div>
        </div>

        <div class="detail-body">
            <!-- MEDIA PREVIEW/PLAYER -->
            <?php if ($media['type'] === 'audio' && $isUploaded && $fileExists): ?>
                <!-- AUDIO PLAYER -->
                <div class="audio-player-container">
                    <div class="audio-thumbnail">
                        <img src="assets/icons/audio-icon.png" alt="Audio" style="background: #ffffffff;">
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
                    <!-- Office Documents - Use Google Docs Viewer or Microsoft Office Online -->
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
            <?php endif; ?>

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

    <script>
        function confirmDelete() {
            if (confirm('Are you sure you want to delete "<?= addslashes($media['title']) ?>"?\n\nThis action cannot be undone!')) {
                window.location.href = 'delete_media.php?id=<?= $media['id'] ?>';
            }
        }

        function toggleAudio() {
            const audio = document.getElementById('audioPlayer');
            const btn = document.querySelector('.play-button');
            
            if (audio.paused) {
                audio.play();
                btn.textContent = '⏸';
            } else {
                audio.pause();
                btn.textContent = '▶';
            }
        }

        // Auto-update play button when audio ends
        const audio = document.getElementById('audioPlayer');
        if (audio) {
            audio.addEventListener('ended', function() {
                document.querySelector('.play-button').textContent = '▶';
            });
            audio.addEventListener('pause', function() {
                document.querySelector('.play-button').textContent = '▶';
            });
            audio.addEventListener('play', function() {
                document.querySelector('.play-button').textContent = '⏸';
            });
        }
    </script>
</body>
</html>