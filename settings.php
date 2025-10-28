<?php
require_once __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/dbconfig.php';

$error = '';

// TAG DELETION THING
if (isset($_GET['delete_tag']) && is_numeric($_GET['delete_tag'])) {
    $tag_id = (int)$_GET['delete_tag'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM tags WHERE id = :id AND user_id = :user_id AND is_default = 0");
        $stmt->execute(['id' => $tag_id, 'user_id' => $current_user_id]);
    } catch (PDOException $e) {
        $error = 'Error deleting tag.';
    }
    
    header('Location: settings.php');
    exit;
}

// TAG CREATION FUNCTION PO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tag'])) {
    $tag_name = trim($_POST['tag_name'] ?? '');
    $tag_media_type = $_POST['tag_media_type'] ?? 'universal';
    
    if (empty($tag_name)) {
        $error = 'Tag name is required.';
    } elseif (strlen($tag_name) < 2) {
        $error = 'Tag name must be at least 2 characters.';
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM tags WHERE name = :name AND user_id = :user_id");
            $check->execute(['name' => $tag_name, 'user_id' => $current_user_id]);
            
            if ($check->fetch()) {
                $error = 'You already have a tag with this name.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO tags (name, media_type, user_id, is_default) VALUES (:name, :media_type, :user_id, 0)");
                $stmt->execute([
                    'name' => $tag_name,
                    'media_type' => $tag_media_type,
                    'user_id' => $current_user_id
                ]);
                
                header('Location: settings.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Error creating tag.';
        }
    }
}

// Pagination for tags
$tags_per_page = 15;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $tags_per_page;

// Get total count
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tags WHERE user_id = :user_id");
    $count_stmt->execute(['user_id' => $current_user_id]);
    $total_tags = $count_stmt->fetchColumn();
    $total_pages = ceil($total_tags / $tags_per_page);
} catch (PDOException $e) {
    $total_tags = 0;
    $total_pages = 0;
}

// Get paginated tags
try {
    $stmt = $pdo->prepare("SELECT * FROM tags WHERE user_id = :user_id ORDER BY media_type, name LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $tags_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $custom_tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $custom_tags = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - MediaDeck</title>
    <link rel="stylesheet" href="assets/css/settings.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="settings-header">
            <h1>⚙️ Settings</h1>
            <p>Manage your MediaDeck preferences and customization</p>
        </div>

        <div class="settings-content">
            <!-- Account Information -->
            <div class="settings-section">
                <h2>👤 Account Information</h2>
                <div class="user-info-box">
                    <h3><?php echo htmlspecialchars($current_username); ?></h3>
                    <p><strong>Status:</strong> <?php echo ucfirst($current_status); ?></p>
                    <p><strong>User ID:</strong> #<?php echo $current_user_id; ?></p>
                </div>
            </div>

            <!-- Custom Tags -->
            <div class="settings-section">
                <h2>🏷️ Custom Tags</h2>
                <p>Create and manage your personal tags to organize your media collection</p>
                
                <?php if ($error): ?>
                    <div class="message-box message-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Create New Tag Form -->
                <div class="tag-form">
                    <h3>Create New Tag</h3>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="tag_name">Tag Name</label>
                                <input type="text" id="tag_name" name="tag_name" required placeholder="e.g., My Project">
                            </div>
                            <div class="form-group form-group-select">
                                <label for="tag_media_type">Media Type</label>
                                <select id="tag_media_type" name="tag_media_type">
                                    <option value="universal">All Types</option>
                                    <option value="image">Images Only</option>
                                    <option value="video">Videos Only</option>
                                    <option value="audio">Audio Only</option>
                                    <option value="text">Documents Only</option>
                                </select>
                            </div>
                            <div class="form-group-btn">
                                <button type="submit" name="create_tag" class="btn">Create Tag</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Custom Tags Table -->
                <h3>Your Custom Tags (<?php echo $total_tags; ?>)</h3>
                
                <?php if ($total_tags > 0): ?>
                    <table class="tags-table">
                        <thead>
                            <tr>
                                <th>Tag Name</th>
                                <th>Media Type</th>
                                <th class="center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($custom_tags as $tag): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($tag['name']); ?></strong></td>
                                    <td>
                                        <span class="tag-badge">
                                            <?php echo strtoupper($tag['media_type']); ?>
                                        </span>
                                    </td>
                                    <td class="center">
                                        <a href="?delete_tag=<?php echo $tag['id']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Delete tag \'<?php echo htmlspecialchars($tag['name']); ?>\'?\n\nThis will remove it from all media items.');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?php echo $current_page - 1; ?>">← Previous</a>
                            <?php else: ?>
                                <span class="disabled">← Previous</span>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?php echo $current_page + 1; ?>">Next →</a>
                            <?php else: ?>
                                <span class="disabled">Next →</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-tags">
                        <p>📝 No custom tags yet</p>
                        <p>Create your first custom tag above to get started organizing your media!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeOptions = document.querySelectorAll('.theme-option');
            const savedTheme = localStorage.getItem('mediadeck_theme') || 'light';
            
            themeOptions.forEach(opt => {
                if (opt.dataset.theme === savedTheme) {
                    opt.classList.add('active');
                } else {
                    opt.classList.remove('active');
                }
                
                opt.addEventListener('click', function() {
                    const theme = this.dataset.theme;
                    
                    themeOptions.forEach(o => o.classList.remove('active'));
                    this.classList.add('active');
                    
                    localStorage.setItem('mediadeck_theme', theme);
                    
                    alert('Theme preference saved: ' + theme + '\n\nFull theme switching will be available in the next update!');
                });
            });
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>