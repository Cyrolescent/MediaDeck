<?php
require_once __DIR__ . '/../config/dbconfig.php';

// Fetch all media items
$sql    = "SELECT id, title, type, storage_type, file_path, rating, is_favorite, created_at FROM media ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Media</title>
<style>
    body { font-family: Arial; margin: 20px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #f2f2f2; }
    .fav { color: red; font-weight: bold; }
    a { color: blue; text-decoration: none; }
</style>
</head>
<body>

<h1>Media Collection</h1>
<p><a href="add_media.php">➕ Add New Media</a></p>

<table>
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Type</th>
        <th>Storage</th>
        <th>Link / Path</th>
        <th>Rating</th>
        <th>Favorite?</th>
        <th>Added</th>
    </tr>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= $row['type'] ?></td>
                <td><?= $row['storage_type'] ?></td>
                <td>
                    <?php if (!empty($row['file_path'])): ?>
                        <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank">Open</a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td><?= $row['rating'] ?> ⭐</td>
                <td><?= $row['is_favorite'] ? '<span class="fav">★</span>' : '—' ?></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="8">No media found.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
