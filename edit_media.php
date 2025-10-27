<?php
require_once __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/dbconfig.php';
require_once __DIR__ . '/config/tag_functions.php';

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

// Load existing tags
$mediaTagsData = getMediaTags($pdo, $id);
$selectedTagIds = array_merge(
    array_column($mediaTagsData['default'], 'id'),
    array_column($mediaTagsData['custom'], 'id')
);

$error = '';
$message = '';
$thumbnailFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $file_path = trim($_POST['file_path'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    if ($rating < 0) $rating = 0;
    if ($rating > 5) $rating = 5;
    $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
    $selected_tags = isset($_POST['selected_tags']) ? json_decode($_POST['selected_tags'], true) : [];
    
    $thumbnail_path = $media['thumbnail']; // Keep existing thumbnail by default

    if (empty($title)) {
        $error = "⚠ Title is required!";
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
            // Save tags (limit to 10)
            if (is_array($selected_tags)) {
                $tags_to_save = array_slice($selected_tags, 0, 10);
                saveMediaTags($conn, $id, $tags_to_save);
            }
            
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
    <style>
        .tags-container-new {
            margin-top: 10px;
        }
        
        .tag-search-box {
            position: relative;
        }
        
        .tag-search-input {
            width: 100%;
            padding: 10px 35px 10px 10px;
            border: 1px solid #555;
            border-radius: 8px;
            background: #2A1535;
            color: #f0f0f0;
            font-size: 14px;
        }
        
        .tag-search-input:focus {
            outline: none;
            border-color: #4A90E2;
        }
        
        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            pointer-events: none;
        }
        
        .tags-section-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .tags-section-wrapper-vertical {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 5px;
        }
        
        .available-tags, .selected-tags {
            background: #2A1535;
            border: 1px solid #555;
            border-radius: 8px;
            padding: 15px;
            min-height: 200px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .available-tags-compact, .selected-tags-compact {
            background: #2A1535;
            border: 1px solid #555;
            border-radius: 8px;
            padding: 10px;
            min-height: 120px;
            max-height: 150px;
            overflow-y: auto;
        }
        
        .section-header {
            font-weight: bold;
            color: #f0f0f0;
            margin-bottom: 10px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .tag-counter {
            color: #4A90E2;
            font-size: 12px;
        }
        
        .tag-counter.limit-reached {
            color: #ff6b6b;
        }
        
        .tag-item {
            display: inline-block;
            padding: 6px 12px;
            margin: 5px 5px 5px 0;
            border-radius: 15px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #555;
        }
        
        .tag-item.available {
            background: #3d2550;
            color: #f0f0f0;
        }
        
        .tag-item.available:hover {
            background: #4A90E2;
            border-color: #4A90E2;
        }
        
        .tag-item.selected {
            background: #4A90E2;
            color: white;
            border-color: #4A90E2;
        }
        
        .tag-item.selected:hover {
            background: #ff6b6b;
            border-color: #ff6b6b;
        }
        
        .tag-type-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            margin-left: 5px;
        }
        
        .no-tags-message {
            color: #888;
            text-align: center;
            padding: 20px;
            font-size: 13px;
        }
        
        .tags-divider {
            margin: 15px 0;
            border: none;
            border-top: 1px solid #555;
        }
        
        .tag-category {
            color: #888;
            font-size: 12px;
            margin-top: 15px;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .tag-category:first-child {
            margin-top: 0;
        }

        .media-info-box {
            background: linear-gradient(135deg, #1D0C2E 0%, #2A1535 100%);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            border: 1px solid #555;
        }

        .media-info-box h3 {
            margin: 0 0 10px 0;
            color: #4A90E2;
            font-size: 16px;
        }

        .media-info-box p {
            margin: 5px 0;
            color: #f0f0f0;
            font-size: 14px;
        }

        .media-info-box strong {
            color: #888;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="back-link">
            <a href="view_detail.php?id=<?= $id ?>">🏠 Back to Detail</a>
        </div>

        <div class="form-container">
            <div class="form-left">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>

                <!-- Media Info Box (Non-editable) -->
                <div class="media-info-box">
                    <h3>📋 Media Information</h3>
                    <p><strong>Type:</strong> <?= strtoupper($media['type']) ?></p>
                    <p><strong>Storage:</strong> <?= strtoupper($media['storage_type']) ?></p>
                    <p><strong>Date Added:</strong> <?= date('M d, Y', strtotime($media['created_at'])) ?></p>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="editMediaForm">
                    <!-- Hidden field for selected tags -->
                    <input type="hidden" id="selected_tags_input" name="selected_tags" value="[]">
                    
                    <!-- Title -->
                    <div class="form-group">
                        <h1 style="font-family: 'Montserrat', sans-serif;">Edit Your Media</h1>
                        <label for="title">Title:</label> <br>
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
                            <input type="text" value="<?= htmlspecialchars($media['file_path']) ?>" disabled style="background: #1a0d24; color: #888; cursor: not-allowed;">
                            <small style="color: #888; font-size: 0.85em;">Uploaded files cannot be changed</small>
                        </div><br>
                    <?php endif; ?>

                    <!-- Thumbnail Upload (only for audio/video + upload) -->
                    <?php if ($media['storage_type'] === 'upload' && in_array($media['type'], ['audio', 'video'])): ?>
                        <div class="form-group">
                            <label for="thumbnail_upload">Change Thumbnail (Optional):</label>
                            <input type="file" id="thumbnail_upload" name="thumbnail_upload" accept="image/jpeg,image/png,image/gif,image/webp"><br>
                            <small class="format-info">Formats: JPG, PNG, GIF, WebP</small>
                            <?php if (!empty($media['thumbnail'])): ?>
                                <div style="margin-top: 5px;">
                                    <small style="color: #888;">Current: <?= htmlspecialchars(basename($media['thumbnail'])) ?></small>
                                </div>
                            <?php endif; ?>
                        </div><br>
                    <?php endif; ?>

                    <!-- Rating -->
                    <div class="form-group">
                        <label for="rating">Rating (0-5) ⭐:</label>
                        <input type="number" id="rating" name="rating" min="0" max="5" value="<?= (int)$media['rating'] ?>">
                    </div> <br><br>

                    <!-- Favorite -->
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_favorite" name="is_favorite" <?= $media['is_favorite'] ? 'checked' : '' ?>>
                        <label for="is_favorite">Mark as Favorite ❤</label>
                    </div> <br>

                    <!-- Notes -->
                    <div class="form-group" style="margin-top: 45px;">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes" form="editMediaForm" rows="12"><?= htmlspecialchars($media['notes']) ?></textarea>
                    </div>
                </form>
            </div>

            <div class="form-right">
                <!-- Thumbnail Preview -->
                <div class="thumbnail-box">
                    <?php if ($media['storage_type'] === 'upload' && in_array($media['type'], ['audio', 'video'])): ?>
                        <div class="upload-text" id="uploadText">CHANGE THUMBNAIL</div>
                    <?php else: ?>
                        <div class="upload-text" id="uploadText">THUMBNAIL</div>
                    <?php endif; ?>
                    
                    <div class="thumbnail-placeholder" id="thumbnailPreview">
                        <?php if (!empty($media['thumbnail']) && file_exists(__DIR__ . '/' . $media['thumbnail'])): ?>
                            <img src="<?= htmlspecialchars($media['thumbnail']) ?>" alt="Thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php elseif ($media['type'] === 'image' && $media['storage_type'] === 'upload' && file_exists(__DIR__ . '/' . $media['file_path'])): ?>
                            <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php elseif ($media['type'] === 'image' && $media['storage_type'] === 'link'): ?>
                            <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.parentElement.innerHTML='📷';">
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
                </div>

                <!-- Tags Section -->
                <div class="form-group">
                    <label>Tags📖:</label>
                    
                    <!-- Search Box -->
                    <div class="tag-search-box">
                        <input type="text" class="tag-search-input" id="tagSearch" placeholder="🔍 Search tags...">
                    </div>
                    
                    <!-- Available and Selected Tags -->
                    <div class="tags-section-wrapper-vertical">
                        <!-- Available Tags -->
                        <div class="available-tags-compact">
                            <div class="section-header">Available</div>
                            <div id="availableTagsList">
                                <div class="no-tags-message">Loading tags...</div>
                            </div>
                        </div>
                        
                        <!-- Selected Tags -->
                        <div class="selected-tags-compact">
                            <div class="section-header">
                                <span>Selected</span>
                                <span class="tag-counter" id="tagCounter">0/10</span>
                            </div>
                            <div id="selectedTagsList">
                                <div class="no-tags-message">No tags selected</div>
                            </div>
                        </div>
                    </div>
                    
                    <small class="format-info">Click tags to add/remove. Max 10 tags.</small>
                </div>

                <!-- Submit Button at Bottom of Right Column -->
                <button type="submit" class="btn-submit" form="editMediaForm" style="margin-top: 10px; width: 100%;">SAVE CHANGES</button>
                <a href="view_detail.php?id=<?= $id ?>" class="btn-submit" style="background: #555; display: block; text-align: center; margin-top: 10px; text-decoration: none; width: 100%; box-sizing: border-box;">CANCEL</a>
            </div>
        </div>
    </div>

    <script>
        const mediaType = '<?= $media['type'] ?>';
        
        const allTagsData = <?php echo json_encode([
            'image' => ['default' => getDefaultTags($pdo, 'image'), 'custom' => getCustomTags($pdo, $current_user_id, 'image')],
            'video' => ['default' => getDefaultTags($pdo, 'video'), 'custom' => getCustomTags($pdo, $current_user_id, 'video')],
            'audio' => ['default' => getDefaultTags($pdo, 'audio'), 'custom' => getCustomTags($pdo, $current_user_id, 'audio')],
            'text' => ['default' => getDefaultTags($pdo, 'text'), 'custom' => getCustomTags($pdo, $current_user_id, 'text')],
            'universal_custom' => getCustomTags($pdo, $current_user_id, 'universal')
        ]); ?>;

        // Pre-load existing tags
        let selectedTags = <?php echo json_encode(array_map('intval', $selectedTagIds)); ?>;
        const MAX_TAGS = 10;

        function updateAvailableTags() {
            const searchTerm = document.getElementById('tagSearch').value.toLowerCase();
            const availableList = document.getElementById('availableTagsList');
            
            availableList.innerHTML = '';
            
            // Get tags for current type
            const defaultTags = allTagsData[mediaType]?.default || [];
            const customTags = allTagsData[mediaType]?.custom || [];
            const universalTags = allTagsData.universal_custom || [];
            
            // Filter out already selected tags
            const availableDefault = defaultTags.filter(tag => 
                !selectedTags.includes(tag.id) && 
                tag.name.toLowerCase().includes(searchTerm)
            );
            const availableCustom = [...customTags, ...universalTags].filter(tag => 
                !selectedTags.includes(tag.id) && 
                tag.name.toLowerCase().includes(searchTerm)
            );
            
            if (availableDefault.length === 0 && availableCustom.length === 0) {
                availableList.innerHTML = '<div class="no-tags-message">No tags found</div>';
                return;
            }
            
            // Display default tags
            if (availableDefault.length > 0) {
                const defaultCategory = document.createElement('div');
                defaultCategory.className = 'tag-category';
                defaultCategory.textContent = 'Default Tags';
                availableList.appendChild(defaultCategory);
                
                availableDefault.forEach(tag => {
                    const tagEl = createTagElement(tag, false);
                    availableList.appendChild(tagEl);
                });
            }
            
            // Display custom tags
            if (availableCustom.length > 0) {
                if (availableDefault.length > 0) {
                    const divider = document.createElement('hr');
                    divider.className = 'tags-divider';
                    availableList.appendChild(divider);
                }
                
                const customCategory = document.createElement('div');
                customCategory.className = 'tag-category';
                customCategory.textContent = 'Custom Tags';
                availableList.appendChild(customCategory);
                
                availableCustom.forEach(tag => {
                    const tagEl = createTagElement(tag, false);
                    availableList.appendChild(tagEl);
                });
            }
        }

        function updateSelectedTags() {
            const selectedList = document.getElementById('selectedTagsList');
            const counter = document.getElementById('tagCounter');
            
            counter.textContent = `${selectedTags.length}/10`;
            counter.classList.toggle('limit-reached', selectedTags.length >= MAX_TAGS);
            
            if (selectedTags.length === 0) {
                selectedList.innerHTML = '<div class="no-tags-message">No tags selected</div>';
                return;
            }
            
            selectedList.innerHTML = '';
            
            // Get all tags to find names
            const allTags = [
                ...(allTagsData[mediaType]?.default || []),
                ...(allTagsData[mediaType]?.custom || []),
                ...(allTagsData.universal_custom || [])
            ];
            
            selectedTags.forEach(tagId => {
                const tag = allTags.find(t => t.id == tagId);
                if (tag) {
                    const tagEl = createTagElement(tag, true);
                    selectedList.appendChild(tagEl);
                }
            });
            
            // Update hidden input
            document.getElementById('selected_tags_input').value = JSON.stringify(selectedTags);
        }

        function createTagElement(tag, isSelected) {
            const tagEl = document.createElement('span');
            tagEl.className = `tag-item ${isSelected ? 'selected' : 'available'}`;
            tagEl.textContent = tag.name;
            
            if (!isSelected && tag.media_type === 'universal') {
                const badge = document.createElement('span');
                badge.className = 'tag-type-badge';
                badge.textContent = 'ALL';
                tagEl.appendChild(badge);
            }
            
            tagEl.onclick = () => toggleTag(tag.id);
            
            return tagEl;
        }

        function toggleTag(tagId) {
            const index = selectedTags.indexOf(tagId);
            
            if (index > -1) {
                // Remove tag
                selectedTags.splice(index, 1);
            } else {
                // Add tag (if under limit)
                if (selectedTags.length < MAX_TAGS) {
                    selectedTags.push(tagId);
                } else {
                    alert('Maximum 10 tags allowed!');
                    return;
                }
            }
            
            updateAvailableTags();
            updateSelectedTags();
        }

        // Search functionality
        document.getElementById('tagSearch').addEventListener('input', function() {
            updateAvailableTags();
        });

        // Thumbnail preview for new uploads
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

        // Initialize tags on page load
        updateAvailableTags();
        updateSelectedTags();
    </script>
</body>
</html>