<?php
// notifications.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';
$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// Mark all notifications as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = "All notifications marked as read.";
    header("Location: notifications.php");
    exit();
}

// Mark individual notification as read
if (isset($_GET['mark_read'])) {
    $notif_id = intval($_GET['mark_read']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = "Notification marked as read.";
    header("Location: notifications.php");
    exit();
}

// Fetch notifications for the user
$notifications = [];
$sql = "SELECT n.*, u.name as sender_name FROM notifications n 
        LEFT JOIN users u ON n.sender_id = u.id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Count unread notifications
$unread_count = 0;
foreach ($notifications as $notification) {
    if ($notification['is_read'] == 0) {
        $unread_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: #fff;
        }
        .sidebar .nav-link {
            color: #fff;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #495057;
            color: #ffc107;
        }
        .profile-img {
            max-width: 120px;
            margin: 20px auto 10px auto;
            display: block;
            border-radius: 50%;
            border: 3px solid #fff;
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
    </style>
</head>
<body>
 <div class="d-flex">
    <!-- Sidebar Navigation -->
    <div class="sidebar d-flex flex-column p-3" style="width: 250px;">
        <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3 mx-auto" style="max-width: 70px;">
        <h5 class="text-center mb-4"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h5>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard-admin.php"><i class="bi bi-house"></i> Home</a>
            <a class="nav-link" href="notifications.php"> <i class="bi bi-bell"></i>
                Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link" href="manage_instructors.php"><i class="bi bi-people"></i> Manage Subject Instructors</a>
            <a class="nav-link" href="threads-admin.php"><i class="bi bi-chat-dots"></i> Forums</a>
            <a class="nav-link" href="browse.php"><i class="bi bi-folder"></i> Resources</a>
            <a class="nav-link" href="admin_user_logs.php"><i class="bi bi-journal-text"></i> User Logs</a>
            <a class="nav-link" href="logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
            
        </nav>
    </div>
    
    <div class="container mt-4">
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
                                                <?php if ($notification['sender_name']): ?>
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

    <!-- Bootstrap JS and Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>