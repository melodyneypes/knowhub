<?php
// notifications.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';
$user_id = $_SESSION['user']['id'];
$is_admin = $_SESSION['user']['role'] === 'admin';

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    if ($is_admin) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1");
        $stmt->execute();
        $_SESSION['message'] = "All notifications marked as read.";
        $stmt->close();
    } else {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $_SESSION['message'] = "All your notifications marked as read.";
        $stmt->close();
    }
    header('Location: notifications.php');
    exit();
}

// Handle delete all notifications (admin only)
if (isset($_GET['delete_all']) && $is_admin) {
    $stmt = $conn->prepare("DELETE FROM notifications");
    $stmt->execute();
    $_SESSION['message'] = "All notifications deleted.";
    $stmt->close();
    header('Location: notifications.php');
    exit();
}

// Handle mark single notification as read
if (isset($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    if ($is_admin) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: notifications.php');
    exit();
}

// Fetch notifications - users only see their own notifications
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Count unread notifications for the current user
$unread_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_stmt->bind_result($unread_count);
$unread_stmt->fetch();
$unread_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #eee;
        }
        .sidebar .nav-link {
            color: #333;
            font-weight: 500;
            padding: 12px 20px;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #e9ecef;
            color: #126682d1;
        }
        .profile-img {
            max-width: 60px;
            margin: 20px auto 10px auto;
            display: block;
            border-radius: 50%;
            border: 2px solid #126682d1;
        }
        .main-content {
            padding: 40px 30px;
        }
        .notification-card {
            border-left: 4px solid #e9ecef;
            transition: all 0.3s;
        }
        .notification-card.unread {
            border-left-color: #007bff;
            background-color: #f8f9ff;
        }
        .notification-card:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .download-icon {
            background-color: #d4edda;
            color: #155724;
        }
        .reply-icon {
            background-color: #cce7ff;
            color: #004085;
        }
        .edit-request-icon {
            background-color: #fff3cd;
            color: #856404;
        }
        .forum-icon {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .badge {
            font-size: 0.9em;
        }
    </style>
</head>
<body>
 <div class="d-flex">
    <!-- Sidebar Navigation -->
    <div class="sidebar d-flex flex-column p-3" style="width: 240px;">
        <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="profile-img">
        <h5 class="text-center mb-4"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h5>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard-<?php echo $_SESSION['user']['role']; ?>.php"><i class="bi bi-house"></i> Dashboard</a>
            <a class="nav-link active" href="notifications.php"><i class="bi bi-bell"></i> Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <a class="nav-link" href="manage_instructors.php"><i class="bi bi-person-badge"></i> Manage Instructors</a>
                <a class="nav-link" href="threads-admin.php"><i class="bi bi-chat-dots"></i> Forums</a>
                <a class="nav-link" href="browse.php"><i class="bi bi-folder"></i> Resources</a>
                <a class="nav-link" href="admin_user_logs.php"><i class="bi bi-journal-text"></i> User Logs</a>
                <a class="nav-link" href="create_task.php"><i class="bi bi-plus-circle"></i> Create Task</a>
                <a class="nav-link" href="manage_subjects.php"><i class="bi bi-gear"></i> Manage Subjects</a>
            <?php else: ?>
                <a class="nav-link" href="threads.php"><i class="bi bi-chat-dots"></i> Forums</a>
                <a class="nav-link" href="browse.php"><i class="bi bi-folder"></i> Resources</a>
                <a class="nav-link" href="external.php"><i class="bi bi-link"></i> External Resources</a>
            <?php endif; ?>
            <a class="nav-link" href="logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
        <div class="mt-auto text-center">
            <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span><br>
            <span class="text-muted"><?php echo ucfirst($_SESSION['user']['role']); ?></span>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content flex-grow-1">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Notifications</h2>
                <div>
                    <?php if ($unread_count > 0): ?>
                        <a href="?mark_all_read=1" class="btn btn-sm btn-outline-primary">
                            Mark all as read (<?php echo $unread_count; ?> unread)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($notifications)): ?>
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">No notifications</h5>
                        <p class="card-text text-muted">You don't have any notifications at the moment.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-12">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="card mb-3 notification-card <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <?php if (strpos($notification['type'], 'download') !== false): ?>
                                                <div class="notification-icon download-icon">
                                                    <i class="bi bi-download"></i>
                                                </div>
                                            <?php elseif (strpos($notification['type'], 'reply') !== false): ?>
                                                <div class="notification-icon reply-icon">
                                                    <i class="bi bi-reply"></i>
                                                </div>
                                            <?php elseif (strpos($notification['type'], 'edit_request') !== false): ?>
                                                <div class="notification-icon edit-request-icon">
                                                    <i class="bi bi-pencil"></i>
                                                </div>
                                            <?php elseif (strpos($notification['type'], 'forum') !== false): ?>
                                                <div class="notification-icon forum-icon">
                                                    <i class="bi bi-chat"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="notification-icon">
                                                    <i class="bi bi-bell"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </h5>
                                            <p class="card-text">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <?php 
                                                    $time_diff = time() - strtotime($notification['created_at']);
                                                    if ($time_diff < 60) {
                                                        echo "Just now";
                                                    } elseif ($time_diff < 3600) {
                                                        echo floor($time_diff/60) . " minutes ago";
                                                    } elseif ($time_diff < 86400) {
                                                        echo floor($time_diff/3600) . " hours ago";
                                                    } else {
                                                        echo floor($time_diff/86400) . " days ago";
                                                    }
                                                    ?>
                                                    <?php if (!empty($notification['sender_name'])): ?>
                                                        by <?php echo htmlspecialchars($notification['sender_name']); ?>
                                                    <?php endif; ?>
                                                </small>
                                                <?php if ($notification['is_read'] == 0): ?>
                                                    <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        Mark as read
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>