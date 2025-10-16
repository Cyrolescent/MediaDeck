<?php
require_once __DIR__ . '/config/dbconfig.php';

$message = '';
$error = '';

// Allowed file formats
$allowedFormats = [
    'image' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
    'audio' => ['mp3', 'wav', 'ogg'],
    'video' => ['mp4', 'webm', 'ogv'],
    'text' => ['html', 'htm', 'pdf', 'txt', 'rtf', 'xml', 'docx', 'pptx', 'xlsx', 'odt']
];

$thumbnailFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title        = $_POST['title'] ?? '';
    $type         = $_POST['type'] ?? '';
    $storage_type = $_POST['storage_type'] ?? 'link';
    $notes        = $_POST['notes'] ?? '';
    $rating       = (int)($_POST['rating'] ?? 0);
    $is_favorite  = isset($_POST['is_favorite']) ? 1 : 0;
    $tags         = isset($_POST['tags']) ? $_POST['tags'] : [];
    $file_path    = '';
    $thumbnail_path = '';

    // Validate title
    if (empty($title)) {
        $error = "❌ Title is required!";
    } else {
        // Handle file upload or link
        if ($storage_type === 'upload' && isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
            // FILE UPLOAD LOGIC
            $file = $_FILES['file_upload'];
            $filename = $file['name'];
            $tmp_name = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // File size limit (100MB)
            $max_size = 100 * 1024 * 1024;

            // Validate file extension
            if (!in_array($file_ext, $allowedFormats[$type])) {
                $error = "❌ Invalid file type for " . strtoupper($type) . ". Allowed: " . implode(', ', $allowedFormats[$type]);
            } elseif ($file_size > $max_size) {
                $error = "❌ File size exceeds 100MB limit!";
            } else {
                // Create unique filename
                $new_filename = date('Y-m-d_H-i-s_') . uniqid() . '.' . $file_ext;
                $upload_path = __DIR__ . '/uploads/' . $new_filename;

                // Move uploaded file
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $file_path = 'uploads/' . $new_filename;
                } else {
                    $error = "❌ Failed to upload file!";
                }
            }

            // Handle thumbnail upload (only for audio/video)
            if (empty($error) && in_array($type, ['audio', 'video']) && isset($_FILES['thumbnail_upload']) && $_FILES['thumbnail_upload']['error'] === UPLOAD_ERR_OK) {
                $thumb_file = $_FILES['thumbnail_upload'];
                $thumb_ext = strtolower(pathinfo($thumb_file['name'], PATHINFO_EXTENSION));
                
                if (in_array($thumb_ext, $thumbnailFormats)) {
                    $thumb_filename = 'thumb_' . date('Y-m-d_H-i-s_') . uniqid() . '.' . $thumb_ext;
                    $thumb_path = __DIR__ . '/uploads/' . $thumb_filename;
                    
                    if (move_uploaded_file($thumb_file['tmp_name'], $thumb_path)) {
                        $thumbnail_path = 'uploads/' . $thumb_filename;
                    }
                }
            }

        } elseif ($storage_type === 'link') {
            // LINK INPUT LOGIC
            $file_path = $_POST['file_path'] ?? '';
            if (empty($file_path)) {
                $error = "❌ Please provide a link!";
            }
        } else {
            $error = "❌ Please select a file or provide a link!";
        }

        // Insert into database if no errors
        if (empty($error) && !empty($file_path)) {
            $stmt = $conn->prepare("
                INSERT INTO media (title, type, storage_type, file_path, thumbnail, notes, rating, is_favorite)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "ssssssii",
                $title,
                $type,
                $storage_type,
                $file_path,
                $thumbnail_path,
                $notes,
                $rating,
                $is_favorite
            );

            if ($stmt->execute()) {
                $media_id = $conn->insert_id;
                
                // Insert tags
                if (!empty($tags) && is_array($tags)) {
                    $tag_stmt = $conn->prepare("INSERT INTO media_tags (media_id, tag) VALUES (?, ?)");
                    foreach ($tags as $tag) {
                        $tag = trim($tag);
                        if (!empty($tag)) {
                            $tag_stmt->bind_param("is", $media_id, $tag);
                            $tag_stmt->execute();
                        }
                    }
                    $tag_stmt->close();
                }
                
                $message = "✅ Media added successfully!";
            } else {
                $error = "❌ Error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Media - MediaDeck</title>
    <link rel="stylesheet" href="assets/css/add_media.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="back-link">
            <a href="view_media.php">🏠 Back to View</a>
        </div>

        <div class="form-container">
            <div class="form-left">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" id="addMediaForm">
                    <!-- Title -->
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div> <br>

                    <!-- Type -->
                    <div class="form-group">
                        <label for="type">Type:</label>
                        <select id="type" name="type" required onchange="updateFormFields()">
                            <option value="image">IMAGE</option>
                            <option value="video">VIDEO</option>
                            <option value="audio">AUDIO</option>
                            <option value="text">TEXT</option>
                        </select> <br>
                    </div>

                    <!-- Storage Method -->
                    <div class="form-group">
                        <label for="storage_type">Storage Method:</label>
                        <select id="storage_type" name="storage_type" required onchange="updateFormFields()">
                            <option value="link">Link</option>
                            <option value="upload">Upload</option>
                        </select> <br>
                    </div>

                    <!-- File/Link Path -->
                    <div class="form-group" id="linkSection">
                        <label for="file_path" id="pathLabel">File/Link Path:</label>
                        <input type="text" id="file_path" name="file_path" placeholder="e.g. https://example.com/file.jpg">
                    </div><br>

                    <!-- File Upload Section -->
                    <div class="form-group" id="uploadSection" style="display: none;">
                        <label for="file_upload" id="uploadLabel">Choose File:</label>
                        <input type="file" id="file_upload" name="file_upload" accept="">
                        <small id="fileFormatInfo" class="format-info"></small>
                    </div><br>

                    <!-- Thumbnail Upload (only for audio/video + upload) -->
                    <div class="form-group" id="thumbnailSection" style="display: none;">
                        <label for="thumbnail_upload">Upload Thumbnail:</label>
                        <input type="file" id="thumbnail_upload" name="thumbnail_upload" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="format-info">Formats: JPG, PNG, GIF, WebP</small>
                    </div>

                    <!-- Rating -->
                    <div class="form-group">
                        <label for="rating">Rating (0-5):</label>
                        <input type="number" id="rating" name="rating" min="0" max="5" value="0"><br>
                    </div>

                    <!-- Favorite -->
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_favorite" name="is_favorite">
                        <label for="is_favorite">Mark as Favorite ⭐</label>
                    </div><br><br>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">ADD TO THE LIST</button>
                </form>
            </div>

            <div class="form-right">
                <!-- Thumbnail Preview -->
                <div class="thumbnail-box">
                    <div class="thumbnail-placeholder" id="thumbnailPreview">
                        📷
                    </div>
                    <div class="upload-text">UPLOAD<br>THUMBNAIL</div>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" form="addMediaForm" rows="8"></textarea>
                </div>

                <!-- Tags -->
                <div class="form-group">
                    <label>Tags(TEST):</label>
                    <div class="tags-container">
                        <div class="tag-button" onclick="toggleTag(this, 'Video')">Video</div>
                        <div class="tag-button" onclick="toggleTag(this, 'Film')">Film</div>
                        <div class="tag-button" onclick="toggleTag(this, 'Image')">Image</div>
                        <div class="tag-button" onclick="toggleTag(this, 'Audio')">Audio</div>
                        <div class="tag-button" onclick="toggleTag(this, 'Text')">Text</div>
                    </div>
                    <div id="selectedTags"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const formatInfo = {
            'image': {
                extensions: ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
                accept: 'image/jpeg,image/png,image/gif,image/svg+xml,image/webp',
                text: 'Formats: JPG, PNG, GIF, SVG, WebP'
            },
            'audio': {
                extensions: ['mp3', 'wav', 'ogg'],
                accept: 'audio/mpeg,audio/wav,audio/ogg',
                text: 'Formats: MP3, WAV, OGG'
            },
            'video': {
                extensions: ['mp4', 'webm', 'ogv'],
                accept: 'video/mp4,video/webm,video/ogg',
                text: 'Formats: MP4, WebM, OGV'
            },
            'text': {
                extensions: ['html', 'htm', 'pdf', 'txt', 'rtf', 'xml', 'docx', 'pptx', 'xlsx', 'odt'],
                accept: '.html,.htm,.pdf,.txt,.rtf,.xml,.docx,.pptx,.xlsx,.odt',
                text: 'Formats: HTML, PDF, TXT, RTF, XML, DOCX, PPTX, XLSX, ODT (Office files for MS viewer only)'
            }
        };

        let selectedTags = [];

        function updateFormFields() {
            const type = document.getElementById('type').value;
            const storageType = document.getElementById('storage_type').value;
            const linkSection = document.getElementById('linkSection');
            const uploadSection = document.getElementById('uploadSection');
            const thumbnailSection = document.getElementById('thumbnailSection');
            const fileUpload = document.getElementById('file_upload');
            const fileFormatInfo = document.getElementById('fileFormatInfo');
            const pathLabel = document.getElementById('pathLabel');

            // Update label text
            if (storageType === 'upload') {
                pathLabel.textContent = 'Upload:';
            } else {
                pathLabel.textContent = 'File/Link Path:';
            }

            // Show/hide sections
            if (storageType === 'upload') {
                linkSection.style.display = 'none';
                uploadSection.style.display = 'block';
                document.getElementById('file_path').removeAttribute('required');
                fileUpload.setAttribute('required', 'required');

                // Update file accept attributes
                if (formatInfo[type]) {
                    fileUpload.accept = formatInfo[type].accept;
                    fileFormatInfo.textContent = formatInfo[type].text;
                }

                // Show thumbnail upload for audio/video
                if (type === 'audio' || type === 'video') {
                    thumbnailSection.style.display = 'block';
                } else {
                    thumbnailSection.style.display = 'none';
                }
            } else {
                linkSection.style.display = 'block';
                uploadSection.style.display = 'none';
                thumbnailSection.style.display = 'none';
                document.getElementById('file_path').setAttribute('required', 'required');
                fileUpload.removeAttribute('required');
            }
        }

        function toggleTag(element, tagName) {
            element.classList.toggle('active');
            
            if (selectedTags.includes(tagName)) {
                selectedTags = selectedTags.filter(t => t !== tagName);
            } else {
                selectedTags.push(tagName);
            }
            
            updateHiddenTags();
        }

        function updateHiddenTags() {
            const container = document.getElementById('selectedTags');
            container.innerHTML = '';
            
            selectedTags.forEach(tag => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tags[]';
                input.value = tag;
                container.appendChild(input);
            });
        }

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

        // Initialize form
        updateFormFields();
    </script>
</body>
</html>