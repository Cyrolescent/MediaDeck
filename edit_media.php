<?php
require_once __DIR__ . '/config/dbconfig.php';
require_once __DIR__ . '/config/auth_check.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// If no id provided, show a simple chooser list
if ($id <= 0) {
    $res = $conn->query("SELECT id, title FROM media WHERE user_id = $current_user_id ORDER BY created_at DESC");
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Edit Media</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 0; 
                background-color: #F3F4F6;
            }
            .container { 
                margin: 20px; 
            }
            h2 { 
                color: #333; 
                margin-top: 20px;
            }
            ul { 
                list-style: none; 
                padding: 0;
            }
            li { 
                margin: 10px 0;
            }
            a { 
                color: #4A90E2; 
                text-decoration: none;
                padding: 8px 12px;
                display: inline-block;
                border-radius: 4px;
                transition: background 0.2s;
            }
            a:hover { 
                background: #f0f0f0;
            }
            .btn-back {
                display: inline-block;
                margin-bottom: 15px;
                padding: 10px 20px;
                background: #888;
                color: white;
                border-radius: 5px;
                text-decoration: none;
            }
            .btn-back:hover {
                background: #666;
            }
        </style>
    </head>
    <body>
        <?php include 'includes/header.php'; ?>
        <div class="container">
            <h2>Select Media to Edit</h2>
            <?php if ($res && $res->num_rows > 0): ?>
                <ul>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <li>
                        <a href="edit_media.php?id=<?= $r['id'] ?>">
                            ✏️ Edit: <?= htmlspecialchars($r['title']) ?> (ID <?= $r['id'] ?>)
                        </a>
                    </li>
                <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No media found. <a href='add_media.php' class="btn-back">Add one</a></p>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Fetch the media item
$stmt = $conn->prepare("SELECT * FROM media WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$media = $result ? $result->fetch_assoc() : null;

if (!$media) {
    $error_msg = "Media not found for ID {$id}.";
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization/validation
    $title = trim($_POST['title'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    if ($rating < 0) $rating = 0;
    if ($rating > 5) $rating = 5;
    $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;

    if (empty($title)) {
        $error = "Title is required!";
    } else {
        $update = $conn->prepare("UPDATE media SET title = ?, notes = ?, rating = ?, is_favorite = ? WHERE id = ? AND user_id = ?");
        $update->bind_param("ssiiiii", $title, $notes, $rating, $is_favorite, $id, $current_user_id);

        if ($update->execute()) {
            $success = "✅ Media updated successfully.";
            // refresh $media values
            $media['title'] = $title;
            $media['notes'] = $notes;
            $media['rating'] = $rating;
            $media['is_favorite'] = $is_favorite;
        } else {
            $error = "❌ Update failed: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Media - MediaDeck</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            background-color: #F3F4F6;
        }
        .container { 
            margin: 20px;
            max-width: 600px;
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .form-group { 
            margin-bottom: 20px;
        }
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold;
            color: #333;
        }
        input[type=text], 
        textarea, 
        input[type=number] { 
            width: 100%; 
            padding: 10px; 
            margin-bottom: 10px; 
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        input[type=text]:focus,
        textarea:focus,
        input[type=number]:focus {
            outline: none;
            border-color: #4A90E2;
            box-shadow: 0 0 5px rgba(74, 144, 226, 0.3);
        }
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type=checkbox] {
            width: auto;
            margin: 0;
            padding: 0;
        }
        .button-group {
            margin-top: 25px;
            display: flex;
            gap: 10px;
        }
        button, a.btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        button {
            background: #4A90E2;
            color: white;
            flex: 1;
        }
        button:hover {
            background: #357ABD;
        }
        a.btn {
            background: #888;
            color: white;
            flex: 1;
            text-align: center;
        }
        a.btn:hover {
            background: #666;
        }
        .msg { 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px;
            font-weight: bold;
        }
        .msg.success { 
            background: #d4edda; 
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .msg.error { 
            background: #f8d7da; 
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .media-info { 
            background: #fff; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 25px;
            border-left: 4px solid #4A90E2;
        }
        .media-info p {
            margin: 8px 0;
            color: #555;
        }
        .media-info strong {
            color: #333;
        }
        .error-message {
            color: red;
            padding: 15px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-link {
            color: #4A90E2;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Edit Media</h1>

        <?php if (isset($error_msg)): ?>
            <div class="error-message">
                <strong>Error:</strong> <?= $error_msg ?>
            </div>
            <a href="view_media.php" class="back-link">← Back to Media List</a>
        <?php else: ?>
            <?php if ($error) echo "<div class='msg error'>$error</div>"; ?>
            <?php if ($success) echo "<div class='msg success'>$success</div>"; ?>

            <div class="media-info">
                <p><strong>Media ID:</strong> <?= $media['id'] ?></p>
                <p><strong>Type:</strong> <?= ucfirst($media['type']) ?></p>
                <p><strong>Storage:</strong> <?= ucfirst($media['storage_type']) ?></p>
                <p><strong>Date Added:</strong> <?= $media['created_at'] ?></p>
            </div>

            <form method="post">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($media['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes"><?= htmlspecialchars($media['notes']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="rating">Rating (0-5):</label>
                    <input type="number" id="rating" name="rating" min="0" max="5" value="<?= (int)$media['rating'] ?>">
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="favorite" name="is_favorite" <?= $media['is_favorite'] ? 'checked' : '' ?>> 
                    <label for="favorite" style="margin-bottom: 0;">Mark as Favorite ⭐</label>
                </div>

                <div class="button-group">
                    <button type="submit">Save Changes</button>
                    <a href="view_media.php" class="btn">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>