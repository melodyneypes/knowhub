<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit();
}

require '../../db.php';

$user_id = $_SESSION['user']['id'];

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['message'] = "All your notifications marked as read.";
    $stmt->close();
    header('Location: notifications.php');
    exit();
}

// Handle delete all notifications
if (isset($_GET['delete_all'])) {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['message'] = "All notifications deleted.";
    $stmt->close();
    header('Location: notifications.php');
    exit();
}

// Handle mark single notification as read
if (isset($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header('Location: notifications.php');
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

// Build the SQL query with filters
$sql = "SELECT * FROM notifications WHERE user_id = ?";
$params = [$user_id];
$param_types = "i";

// Add search condition
if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

// Add type filter
if (!empty($filter_type)) {
    $sql .= " AND type = ?";
    $params[] = $filter_type;
    $param_types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Count unread notifications for the current faculty
$unread_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_stmt->bind_result($unread_count);
$unread_stmt->fetch();
$unread_stmt->close();

// Get all notification types for the filter dropdown
$types_stmt = $conn->prepare("SELECT DISTINCT type FROM notifications WHERE user_id = ? AND type IS NOT NULL");
$types_stmt->bind_param("i", $user_id);
$types_stmt->execute();
$types_result = $types_stmt->get_result();
$notification_types = [];
while ($row = $types_result->fetch_assoc()) {
    $notification_types[] = $row['type'];
}
$types_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Notifications</title>
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
        .activity-icon {
            background-color: #f8d7da;
            color: #721c24;
        }
        .access-request-icon {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .submission-icon {
            background-color: #d4edda;
            color: #155724;
        }
        .comment-icon {
            background-color: #fff3cd;
            color: #856404;
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
                <a class="nav-link" style="color: red;" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<br><br><br>

        <!-- Main Content -->
        <div class="col-md-10 offset-md-1">
            <div class="card mb-4">
               <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Faculty Notifications</span>   
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
                    <?php endif; ?>
                    
                    <!-- Filter Section -->
                    <div class="mb-4 p-3 bg-light rounded">
                        <h5>Filter Notifications</h5>
                        <form method="GET" class="row">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" placeholder="Search in title or message" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="type">
                                    <option value="">All Types</option>
                                    <?php foreach ($notification_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filter_type === $type) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type))); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                            <div class="col-12 mt-2">
                                <a href="notifications.php" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                                <?php if (!empty($notifications)): ?>
                                    <a href="?mark_all_read=1" class="btn btn-outline-primary btn-sm">Mark All as Read</a>
                                    <a href="?delete_all=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete all notifications?')">Delete All</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?php if (!empty($notifications)): ?>
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
                                                <?php elseif (strpos($notification['type'], 'activity') !== false): ?>
                                                    <div class="notification-icon activity-icon">
                                                        <i class="bi bi-calendar"></i>
                                                    </div>
                                                <?php elseif (strpos($notification['type'], 'access_request') !== false): ?>
                                                    <div class="notification-icon access-request-icon">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                <?php elseif (strpos($notification['type'], 'submission') !== false): ?>
                                                    <div class="notification-icon submission-icon">
                                                        <i class="bi bi-upload"></i>
                                                    </div>
                                                <?php elseif (strpos($notification['type'], 'comment') !== false): ?>
                                                    <div class="notification-icon comment-icon">
                                                        <i class="bi bi-chat-square-text"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="notification-icon">
                                                        <i class="bi bi-bell"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                    <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-secondary">Mark as Read</a>
                                                </div>
                                                <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <div class="d-flex justify-content-between text-muted">
                                                    <small>
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
                                                    <?php if (!empty($notification['sender_id'])): ?>
                                                        <?php
                                                        $sender_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
                                                        $sender_stmt->bind_param("i", $notification['sender_id']);
                                                        $sender_stmt->execute();
                                                        $sender_result = $sender_stmt->get_result();
                                                        if ($sender_row = $sender_result->fetch_assoc()): ?>
                                                            <small>From: <?php echo htmlspecialchars($sender_row['name']); ?></small>
                                                        <?php endif;
                                                        $sender_stmt->close(); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-bell-slash" style="font-size: 3rem; color: #ccc;"></i>
                                    <h4 class="mt-3">No notifications found</h4>
                                    <p class="text-muted">
                                        <?php if (!empty($search) || !empty($filter_type)): ?>
                                            No notifications match your filter criteria. 
                                            <a href="notifications.php">Clear filters</a> to see all notifications.
                                        <?php else: ?>
                                            You don't have any notifications at the moment.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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