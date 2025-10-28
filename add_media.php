<?php
require_once __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/dbconfig.php';
require_once __DIR__ . '/config/tag_functions.php';

$message = '';
$error = '';

// Allowed file formats
$allowedFormats = [
    'image' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
    'audio' => ['mp3', 'wav', 'ogg'],
    'video' => ['mp4', 'webm', 'ogv'],
    'text' => ['html', 'htm', 'pdf', 'txt', 'rtf', 'xml']
];

$thumbnailFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title        = $_POST['title'] ?? '';
    $type         = $_POST['type'] ?? '';
    $storage_type = $_POST['storage_type'] ?? 'link';
    $notes        = $_POST['notes'] ?? '';
    $rating       = (int)($_POST['rating'] ?? 0);
    $is_favorite  = isset($_POST['is_favorite']) ? 1 : 0;
    $selected_tags = isset($_POST['selected_tags']) ? json_decode($_POST['selected_tags'], true) : [];
    $file_path    = '';
    $thumbnail_path = '';

    // Validate title
    if (empty($title)) {
        $error = "⚠ Title is required!";
    } else {
        // Handle file upload or link
        if ($storage_type === 'upload' && isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file_upload'];
            $filename = $file['name'];
            $tmp_name = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            $max_size = 100 * 1024 * 1024;

            if (!in_array($file_ext, $allowedFormats[$type])) {
                $error = "⚠ Invalid file type for " . strtoupper($type) . ". Allowed: " . implode(', ', $allowedFormats[$type]);
            } elseif ($file_size > $max_size) {
                $error = "⚠ File size exceeds 100MB limit!";
            } else {
                $new_filename = date('Y-m-d_H-i-s_') . uniqid() . '.' . $file_ext;
                $upload_path = __DIR__ . '/uploads/' . $new_filename;

                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $file_path = 'uploads/' . $new_filename;
                } else {
                    $error = "⚠ Failed to upload file!";
                }
            }

            // Handle thumbnail upload
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
            $file_path = $_POST['file_path'] ?? '';
            if (empty($file_path)) {
                $error = "⚠ Please provide a link!";
            }
        } else {
            $error = "⚠ Please select a file or provide a link!";
        }

        // Insert into database if no errors
        if (empty($error) && !empty($file_path)) {
            $stmt = $conn->prepare("
                INSERT INTO media (user_id, title, type, storage_type, file_path, thumbnail, notes, rating, is_favorite)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "issssssii",
                $current_user_id,
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
                
                // Save tags (limit to 10)
                if (!empty($selected_tags) && is_array($selected_tags)) {
                    $tags_to_save = array_slice($selected_tags, 0, 10);
                    saveMediaTags($conn, $media_id, $tags_to_save);
                }
                
                $message = "✅ Media added successfully!";
            } else {
                $error = "⚠ Error: " . $conn->error;
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
    <link rel="stylesheet" href="assets/css/responsive.css">
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
                    <!-- Hidden field for selected tags -->
                    <input type="hidden" id="selected_tags_input" name="selected_tags" value="[]">
                    
                    <!-- Title -->
                    <div class="form-group">
                        <h1 style= "font-family: 'Montserrat, sans-serif;"> Add Media to your Collection!<h1>
                        <label for="title">Title:</label> <br>
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
                        </select>
                    </div> <br>

                    <!-- Storage Method -->
                    <div class="form-group">
                        <label for="storage_type">Storage Method:</label>
                        <select id="storage_type" name="storage_type" required onchange="updateFormFields()">
                            <option value="link">Link</option>
                            <option value="upload">Upload</option>
                        </select>
                    </div> <br>

                    <!-- File/Link Path -->
                    <div class="form-group" id="linkSection">
                        <label for="file_path" id="pathLabel">File/Link Path:</label>
                        <input type="text" id="file_path" name="file_path" placeholder="e.g. https://example.com/file.jpg">
                    </div>

                    <!-- File Upload Section -->
                    <div class="form-group" id="uploadSection" style="display: none;">
                        <label for="file_upload" id="uploadLabel">Choose File:</label>
                        <input type="file" id="file_upload" name="file_upload" accept=""> <br>
                        <small id="fileFormatInfo" class="format-info"></small>
                    </div> <br>

                    <!-- Thumbnail Upload -->
                    <div class="form-group" id="thumbnailSection" style="display: none;">
                        <label for="thumbnail_upload">Upload Thumbnail:</label>
                        <input type="file" id="thumbnail_upload" name="thumbnail_upload" accept="image/jpeg,image/png,image/gif,image/webp"><br>
                        <small class="format-info">Formats: JPG, PNG, GIF, WebP</small>
                    </div> <br>

                    <!-- Rating -->
                    <div class="form-group">
                        <label for="rating">Rating (0-5) ⭐:</label>
                        <input type="number" id="rating" name="rating" min="0" max="5" value="0">
                    </div> <br> <br>

                    <!-- Favorite -->
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_favorite" name="is_favorite">
                        <label for="is_favorite">Mark as Favorite ❤</label>
                    </div> <br>

                    <!-- Notes (moved to left column) -->
                    <div class="form-group" style="margin-top: 45px;">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes" form="addMediaForm" rows="12"></textarea>
                    </div>
                </form>
            </div>

            <div class="form-right">
                <!-- Thumbnail Preview -->
                <div class="thumbnail-box">
                    <div class="upload-text" id="uploadText">THUMBNAIL</div>
                    <div class="thumbnail-placeholder" id="thumbnailPreview">
                        📷
                    </div>
                </div>

                <!-- Tags Section -->
                <div class="form-group">
                    <label>Tags🔖:</label>
                    
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
                                <div class="no-tags-message">Select a media type</div>
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
                <button type="submit" class="btn-submit" form="addMediaForm" style="margin-top: 10px; width: 100%;">ADD TO THE LIST</button>
            </div>
        </div>
    </div>

    <script>
        const formatInfo = {
            'image': {
                accept: 'image/jpeg,image/png,image/gif,image/svg+xml,image/webp',
                text: 'Formats: JPG, PNG, GIF, SVG, WebP'
            },
            'audio': {
                accept: 'audio/mpeg,audio/wav,audio/ogg',
                text: 'Formats: MP3, WAV, OGG'
            },
            'video': {
                accept: 'video/mp4,video/webm,video/ogg',
                text: 'Formats: MP4, WebM, OGV'
            },
            'text': {
                accept: '.html,.htm,.pdf,.txt,.rtf,.xml',
                text: 'Formats: HTML, PDF, TXT, RTF, XML'
            }
        };

        const allTagsData = <?php echo json_encode([
            'image' => ['default' => getDefaultTags($pdo, 'image'), 'custom' => getCustomTags($pdo, $current_user_id, 'image')],
            'video' => ['default' => getDefaultTags($pdo, 'video'), 'custom' => getCustomTags($pdo, $current_user_id, 'video')],
            'audio' => ['default' => getDefaultTags($pdo, 'audio'), 'custom' => getCustomTags($pdo, $current_user_id, 'audio')],
            'text' => ['default' => getDefaultTags($pdo, 'text'), 'custom' => getCustomTags($pdo, $current_user_id, 'text')],
            'universal_custom' => getCustomTags($pdo, $current_user_id, 'universal')
        ]); ?>;

        let selectedTags = [];
        const MAX_TAGS = 10;

        function updateFormFields() {
            const type = document.getElementById('type').value;
            const storageType = document.getElementById('storage_type').value;
            const linkSection = document.getElementById('linkSection');
            const uploadSection = document.getElementById('uploadSection');
            const thumbnailSection = document.getElementById('thumbnailSection');
            const fileUpload = document.getElementById('file_upload');
            const fileFormatInfo = document.getElementById('fileFormatInfo');
            const pathLabel = document.getElementById('pathLabel');
            const uploadText = document.getElementById('uploadText');
            const thumbnailPreview = document.getElementById('thumbnailPreview');

            pathLabel.textContent = storageType === 'upload' ? 'Upload:' : 'File/Link Path:';

            // Update thumbnail box based on media type
            if (type === 'text') {
                uploadText.textContent = 'UNAVAILABLE';
                thumbnailPreview.innerHTML = '📄';
                thumbnailPreview.style.opacity = '0.5';
            } else {
                uploadText.innerHTML = 'UPLOAD THUMBNAIL';
                thumbnailPreview.innerHTML = '📷';
                thumbnailPreview.style.opacity = '1';
            }

            if (storageType === 'upload') {
                linkSection.style.display = 'none';
                uploadSection.style.display = 'block';
                document.getElementById('file_path').removeAttribute('required');
                fileUpload.setAttribute('required', 'required');

                if (formatInfo[type]) {
                    fileUpload.accept = formatInfo[type].accept;
                    fileFormatInfo.textContent = formatInfo[type].text;
                }

                thumbnailSection.style.display = (type === 'audio' || type === 'video') ? 'block' : 'none';
            } else {
                linkSection.style.display = 'block';
                uploadSection.style.display = 'none';
                thumbnailSection.style.display = 'none';
                document.getElementById('file_path').setAttribute('required', 'required');
                fileUpload.removeAttribute('required');
            }
            
            updateAvailableTags();
        }

        function updateAvailableTags() {
            const type = document.getElementById('type').value;
            const searchTerm = document.getElementById('tagSearch').value.toLowerCase();
            const availableList = document.getElementById('availableTagsList');
            
            availableList.innerHTML = '';
            
            // Get tags for current type
            const defaultTags = allTagsData[type]?.default || [];
            const customTags = allTagsData[type]?.custom || [];
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
            const type = document.getElementById('type').value;
            const allTags = [
                ...(allTagsData[type]?.default || []),
                ...(allTagsData[type]?.custom || []),
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

        // Image file preview (for uploaded images)
        document.getElementById('file_upload')?.addEventListener('change', function(e) {
            const type = document.getElementById('type').value;
            const file = e.target.files[0];
            
            if (type === 'image' && file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('thumbnailPreview');
                    preview.innerHTML = `<img src="${event.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">`;
                    preview.style.opacity = '1';
                };
                reader.readAsDataURL(file);
            }
        });

        // Initialize
        updateFormFields();
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>