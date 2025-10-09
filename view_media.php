<?php
require_once __DIR__ . '/config/dbconfig.php';

$sql = "SELECT id, title, type, storage_type, file_path, rating, is_favorite, notes, created_at FROM media ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Media</title>

<style>
    body { font-family: Arial; margin: 0px; }
    table { border-collapse: collapse; width: 97%; margin: 20px; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: center; vertical-align: top; }
    th { background: #f2f2f2; }
    a { color: blue; text-decoration: none; }
    img { max-width: 150px; height: auto; border-radius: 5px; }
    .notes { max-width: 300px; white-space: pre-wrap; }
</style>

</head>
<body>
<?php include 'includes/header.php'; ?>
<h1 style="margin: 20px;">Media Collection</h1>

<table>
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Type</th>
        <th>Storage</th>
        <th>Preview / Link</th>
        <th>Annotation</th>
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
                        <?php
                        $file = strtolower($row['file_path']);
                        $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp)$/', $file);
                        ?>

                        <?php if ($isImage): ?>
                            <img src="<?= htmlspecialchars($row['file_path']) ?>" 
                                 alt="Media Preview" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                            <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" style="display:none;">Open</a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank">Open</a>
                        <?php endif; ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td class="notes"><?= nl2br(htmlspecialchars($row['notes'])) ?></td>
                <td><?= $row['rating'] ?> ⭐</td>
                <td><?= $row['is_favorite'] ? '<span class="fav">★</span>' : '—' ?></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="9">No media found.</td></tr>
    <?php endif; ?>
</table>
</body>
</html>
