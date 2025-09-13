<?php
session_start();
error_log("Session user: " . print_r($_SESSION['user'], true)); // Log session user for debugging

// Check if the user is logged in (session variable exists)
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

require 'db.php';

$user_id = $_SESSION['user']['id'];

// Fetch notifications for the instructor
$notifications = [];
$notif_stmt = $conn->prepare(
    "SELECT n.*, u.name as sender_name 
     FROM notifications n 
     LEFT JOIN users u ON n.sender_id = u.id 
     WHERE n.user_id = ? 
     ORDER BY n.created_at DESC 
     LIMIT 5"
);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
while ($row = $notif_result->fetch_assoc()) {
    $notifications[] = $row;
}
$notif_stmt->close();

// Count unread notifications
$unread_count_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_count_stmt->bind_param("i", $user_id);
$unread_count_stmt->execute();
$unread_count_result = $unread_count_stmt->get_result();
$unread_count_row = $unread_count_result->fetch_assoc();
$unread_count = $unread_count_row['unread_count'];
$unread_count_stmt->close();

$subjects = [];
$stmt = $conn->prepare(
    "SELECT s.id, s.name AS title, s.description, si.block
     FROM subject_instructors si
     JOIN subjects s ON si.subject_id = s.id
     WHERE si.instructor_id = ?
     ORDER BY s.year_level, s.semester, s.name, si.block"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 0.7rem;
        }
        .notification-card {
            border-left: 3px solid #e9ecef;
        }
        .notification-card.unread {
            border-left-color: #007bff;
            background-color: #f8f9ff;
        }
        .notification-icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.8rem;
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
<nav class="navbar navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard-instructor.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="subjects-handled.php">My Handled Subjects</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="threads.php">Forums</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="external-instructor.php">External Resources</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" style="color: red;" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>
    <div class="container mt-5">
        <div class="row">
            <!-- User Profile Section (left column) -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Instructor Profile</h2>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                        <h3><?php echo $_SESSION['user']['name']; ?></h3>
                        <p><?php echo $_SESSION['user']['email']; ?></p>
                        <a href="profile_settings.php" class="btn btn-secondary">Profile Settings</a>
                    </div>
                </div>
            </div>
            
            <!-- Subjects Section (right column) -->
            <div class="col-md-8">
                <div class="card">
                   <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Recent Notifications</span>   <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <p class="text-muted mb-0">No notifications yet.</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-card <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?> mb-3 p-2">
                                    <div class="d-flex">
                                        <div class="me-2">
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
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted">
                                                <?php 
                                                $time_diff = time() - strtotime($notification['created_at']);
                                                if ($time_diff < 60) {
                                                    echo "Just now";
                                                } elseif ($time_diff < 3600) {
                                                    echo floor($time_diff/60) . " min ago";
                                                } elseif ($time_diff < 86400) {
                                                    echo floor($time_diff/3600) . " hrs ago";
                                                } else {
                                                    echo floor($time_diff/86400) . " days ago";
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS and Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>