<?php
session_start();
require_once __DIR__ . '/config/dbconfig.php';

if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    if ($delete_id !== $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND username != 'Admin'");
            $stmt->execute(['id' => $delete_id]);
            $message = 'User deleted successfully.';
        } catch (PDOException $e) {
            $error = 'Error deleting user.';
        }
    } else {
        $error = 'Cannot delete Admin account.';
    }
}

try {
    $stmt = $pdo->query("
        SELECT 
            u.id, 
            u.username, 
            u.status, 
            COUNT(m.id) as media_count,
            MAX(m.created_at) as last_upload
        FROM users u
        LEFT JOIN media m ON u.id = m.user_id
        GROUP BY u.id, u.username, u.status
        ORDER BY u.username = 'Admin' DESC, u.username ASC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total statistics
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT u.id) as total_users,
            COUNT(m.id) as total_media
        FROM users u
        LEFT JOIN media m ON u.id = m.user_id
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MediaDeck</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="assets/images/title.png" alt="MediaDeck Title" style="width: 185px; height: 85px;">
            <h1 style="margin-right: 400px;">Admin Dashboard</h1>
            <div class="header-right">
                <span class="user-info">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $stats['total_users']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Media Items</h3>
                <div class="number"><?php echo $stats['total_media']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Average per User</h3>
                <div class="number"><?php echo $stats['total_users'] > 0 ? round($stats['total_media'] / $stats['total_users'], 1) : 0; ?></div>
            </div>
        </div>
        
        <div class="content">
            <h2>User Management</h2>
            
            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (count($users) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Media Count</th>
                            <th>Last Upload</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['status'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                        <?php echo strtoupper($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['media_count']; ?></td>
                                <td><?php echo $user['last_upload'] ? date('M d, Y', strtotime($user['last_upload'])) : 'Never'; ?></td>
                                <td>
                                    <div class="actions">
                                        <?php if ($user['username'] !== 'Admin'): ?>
                                            <a href="?delete=<?php echo $user['id']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Delete user <?php echo htmlspecialchars($user['username']); ?> and all their media?');">
                                                Delete
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999;">Protected</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No users found.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>