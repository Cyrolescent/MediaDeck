<?php
require_once __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/dbconfig.php';
require_once __DIR__ . '/config/tag_functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: view_media.php');
    exit;
}

// Getter ng media post
$stmt = $conn->prepare("SELECT * FROM media WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$media = $result ? $result->fetch_assoc() : null;

//incase lang kung walang laman error
if (!$media) {
    header('Location: view_media.php');
    exit;
}

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
    
    $thumbnail_path = $media['thumbnail'];

    if (empty($title)) {
        $error = "⚠ Title is required!";
    } else { 
        if ($media['storage_type'] === 'upload' && in_array($media['type'], ['audio', 'video']) && 
            isset($_FILES['thumbnail_upload']) && $_FILES['thumbnail_upload']['error'] === UPLOAD_ERR_OK) {
            
            $thumb_file = $_FILES['thumbnail_upload'];
            $thumb_ext = strtolower(pathinfo($thumb_file['name'], PATHINFO_EXTENSION));
            
            if (in_array($thumb_ext, $thumbnailFormats)) {
                $thumb_filename = 'thumb_' . date('Y-m-d_H-i-s_') . uniqid() . '.' . $thumb_ext;
                $thumb_path = __DIR__ . '/uploads/' . $thumb_filename;
                
                if (move_uploaded_file($thumb_file['tmp_name'], $thumb_path)) {
                    if (!empty($media['thumbnail']) && file_exists(__DIR__ . '/' . $media['thumbnail'])) {
                        unlink(__DIR__ . '/' . $media['thumbnail']);
                    }
                    $thumbnail_path = 'uploads/' . $thumb_filename;
                }
            }
        }

        // UPDATE FUNCTION PO
        if ($media['storage_type'] === 'link') {
            $update = $conn->prepare("UPDATE media SET title = ?, file_path = ?, notes = ?, rating = ?, is_favorite = ?, thumbnail = ? WHERE id = ? AND user_id = ?");
            $update->bind_param("sssiisii", $title, $file_path, $notes, $rating, $is_favorite, $thumbnail_path, $id, $current_user_id);
        } else { // IF UPLOAD SIYA
            $update = $conn->prepare("UPDATE media SET title = ?, notes = ?, rating = ?, is_favorite = ?, thumbnail = ? WHERE id = ? AND user_id = ?");
            $update->bind_param("ssiisii", $title, $notes, $rating, $is_favorite, $thumbnail_path, $id, $current_user_id);
        }

        // LIMITER 10 tags lang dapat ang post
        if ($update->execute()) {
            if (is_array($selected_tags)) {
                $tags_to_save = array_slice($selected_tags, 0, 10);
                saveMediaTags($conn, $id, $tags_to_save);
            }
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
    <link rel="stylesheet" href="assets/css/edit.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
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

                <!-- INFO BOX NG MEDIA HINDI NAEEDIT -->
                <div class="media-info-box">
                    <h3>📋 Media Information</h3>
                    <p><strong>Type:</strong> <?= strtoupper($media['type']) ?></p>
                    <p><strong>Storage:</strong> <?= strtoupper($media['storage_type']) ?></p>
                    <p><strong>Date Added:</strong> <?= date('M d, Y', strtotime($media['created_at'])) ?></p>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="editMediaForm">
                    <input type="hidden" id="selected_tags_input" name="selected_tags" value="[]">
                    
                    <!-- TITLE LOOK -->
                    <div class="form-group">
                        <h1 style="font-family: 'Montserrat', sans-serif;">Edit Your Media</h1>
                        <label for="title">Title:</label> <br>
                        <input type="text" id="title" name="title" required value="<?= htmlspecialchars($media['title']) ?>">
                    </div> <br>

                    <!--FILE PATH DITO -->
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

                    <!-- Thumbnail Upload for audio and video lang-->
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
        
        // GET THE TAGS FROM LIST 
        const allTagsData = <?php echo json_encode([
            'image' => ['default' => getDefaultTags($pdo, 'image'), 'custom' => getCustomTags($pdo, $current_user_id, 'image')],
            'video' => ['default' => getDefaultTags($pdo, 'video'), 'custom' => getCustomTags($pdo, $current_user_id, 'video')],
            'audio' => ['default' => getDefaultTags($pdo, 'audio'), 'custom' => getCustomTags($pdo, $current_user_id, 'audio')],
            'text' => ['default' => getDefaultTags($pdo, 'text'), 'custom' => getCustomTags($pdo, $current_user_id, 'text')],
            'universal_custom' => getCustomTags($pdo, $current_user_id, 'universal')
        ]); ?>;

        // Pre
        let selectedTags = <?php echo json_encode(array_map('intval', $selectedTagIds)); ?>;
        const MAX_TAGS = 10;

        function updateAvailableTags() {
            const searchTerm = document.getElementById('tagSearch').value.toLowerCase();
            const availableList = document.getElementById('availableTagsList');
            
            availableList.innerHTML = '';
            
            const defaultTags = allTagsData[mediaType]?.default || [];
            const customTags = allTagsData[mediaType]?.custom || [];
            const universalTags = allTagsData.universal_custom || [];
            
            // Show all the not selcted tags sa sections
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
            
            // CUSTOM TAGS DITO
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

        // uPDATE the tags heres
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

        // Tag on and off functions dito
        function toggleTag(tagId) {
            const index = selectedTags.indexOf(tagId);
            
            if (index > -1) {
                selectedTags.splice(index, 1);
            } else {
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

        // Tag SEARCHER dito
        document.getElementById('tagSearch').addEventListener('input', function() {
            updateAvailableTags();
        });

        // Thumbnail viewer dito
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
        updateAvailableTags();
        updateSelectedTags();
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>