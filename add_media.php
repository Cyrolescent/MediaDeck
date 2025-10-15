<?php
require_once __DIR__ . '/config/dbconfig.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title        = $_POST['title'] ?? '';
    $type         = $_POST['type'] ?? '';
    $storage_type = $_POST['storage_type'] ?? 'link';
    $notes        = $_POST['notes'] ?? '';
    $rating       = (int)($_POST['rating'] ?? 0);
    $is_favorite  = isset($_POST['is_favorite']) ? 1 : 0;
    $file_path    = '';

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
            $file_error = $file['error'];

            // Allowed file types and size limit (50MB)
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'audio/mpeg', 'audio/wav', 'text/plain'];
            $max_size = 50 * 1024 * 1024; // 50MB

            // Get file MIME type
            $file_mime = mime_content_type($tmp_name);

            // Validate file
            if ($file_size > $max_size) {
                $error = "❌ File size exceeds 50MB limit!";
            } elseif (!in_array($file_mime, $allowed_types)) {
                $error = "❌ File type not allowed! Allowed: Images, Videos, Audio, Text";
            } else {
                // Create unique filename with timestamp
                $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
                $new_filename = date('Y-m-d_H-i-s_') . uniqid() . '.' . $file_ext;
                $upload_path = __DIR__ . '/uploads/' . $new_filename;

                // Move uploaded file
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $file_path = 'uploads/' . $new_filename;
                } else {
                    $error = "❌ Failed to upload file!";
                }
            }
        } elseif ($storage_type === 'link') {
            // LINK INPUT LOGIC
            $file_path = $_POST['file_path'] ?? '';
            if (empty($file_path)) {
                $error = "❌ Please provide a link or upload a file!";
            }
        } else {
            $error = "❌ Please select upload or link!";
        }

        // Insert into database if no errors
        if (empty($error) && !empty($file_path)) {
            $stmt = $conn->prepare("
                INSERT INTO media (title, type, storage_type, file_path, notes, rating, is_favorite)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssssii",
                $title,
                $type,
                $storage_type,
                $file_path,
                $notes,
                $rating,
                $is_favorite
            );

            if ($stmt->execute()) {
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
<title>Add Media</title>
<style>
    body { font-family: Arial;}
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; font-weight: bold; }
    input[type=text], input[type=file], textarea, select { width: 100%; padding: 8px; margin-bottom: 10px; }
    button { padding: 10px 20px; background: #4A90E2; color: white; border: none; border-radius: 5px; cursor: pointer; }
    button:hover { background: #357ABD; }
    .msg { margin: 15px 0; padding: 10px; border-radius: 5px; }
    .msg.success { background: #d4edda; color: #155724; }
    .msg.error { background: #f8d7da; color: #721c24; }
    .toggle-section { display: none; }
    .toggle-section.active { display: block; }
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<h1>Add Media</h1>

<?php if (!empty($message)) echo "<p class='msg success'>$message</p>"; ?>
<?php if (!empty($error)) echo "<p class='msg error'>$error</p>"; ?>

<form method="POST" action="" enctype="multipart/form-data">
    <div class="form-group">
        <label>Title: *</label>
        <input type="text" name="title" required>
    </div>

    <div class="form-group">
        <label>Type: *</label>
        <select name="type" required>
            <option value="image">Image</option>
            <option value="video">Video</option>
            <option value="audio">Audio</option>
            <option value="text">Text</option>
        </select>
    </div>

    <div class="form-group">
        <label>Storage Method: *</label>
        <select name="storage_type" id="storage_type" onchange="toggleUploadLink()" required>
            <option value="link">From Link (URL)</option>
            <option value="upload">Upload File</option>
        </select>
    </div>

    <!-- LINK INPUT SECTION -->
    <div id="link-section" class="toggle-section active">
        <div class="form-group">
            <label>File/Link Path:</label>
            <input type="text" name="file_path" placeholder="e.g. https://example.com/image.jpg">
        </div>
    </div>

    <!-- FILE UPLOAD SECTION -->
    <div id="upload-section" class="toggle-section">
        <div class="form-group">
            <label>Choose File: (Max 50MB)</label>
            <input type="file" name="file_upload" id="file_upload" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.mp3,.wav,.txt">
            <small>Supported: Images (jpg, png, gif, webp), Videos (mp4, mov), Audio (mp3, wav), Text (.txt)</small>
        </div>
    </div>

    <div class="form-group">
        <label>Notes:</label>
        <textarea name="notes" rows="3"></textarea>
    </div>

    <div class="form-group">
        <label>Rating (0-5):</label>
        <input type="number" name="rating" min="0" max="5" value="0">
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="is_favorite"> Mark as Favorite
        </label>
    </div>

    <button type="submit">Add Media</button>
</form>

<script>
function toggleUploadLink() {
    const storageType = document.getElementById('storage_type').value;
    const linkSection = document.getElementById('link-section');
    const uploadSection = document.getElementById('upload-section');

    if (storageType === 'upload') {
        linkSection.classList.remove('active');
        uploadSection.classList.add('active');
        document.querySelector('input[name="file_upload"]').setAttribute('required', 'required');
        document.querySelector('input[name="file_path"]').removeAttribute('required');
    } else {
        uploadSection.classList.remove('active');
        linkSection.classList.add('active');
        document.querySelector('input[name="file_path"]').setAttribute('required', 'required');
        document.querySelector('input[name="file_upload"]').removeAttribute('required');
    }
}
</script>

</body>
</html>