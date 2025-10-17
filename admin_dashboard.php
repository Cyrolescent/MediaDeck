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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(rgba(0,0,0,1), rgba(33,20,127,1));
            min-height: 100vh;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #667eea;b
            font-size: 2em;
        }
        
        .header-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .user-info {
            color: #666;
            font-weight: 600;
        }
        
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-danger {
            background: #dc3545;
            padding: 6px 12px;
            font-size: 0.9em;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            gap: 10px;
            align-items: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            color: #232631ff;
            font-size: 2.5em;
            font-weight: bold;
        }
        
        .content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-admin {
            background: #ffd700;
            color: #333;
        }
        
        .badge-user {
            background: #667eea;
            color: white;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
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