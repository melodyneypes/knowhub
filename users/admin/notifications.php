<?php
// notifications.php - Admin exclusive notifications
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require '../../db.php';
$user_id = $_SESSION['user']['id'];
$is_admin = $_SESSION['user']['role'] === 'admin';

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
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

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

// Add status filter
if (!empty($filter_status)) {
    if ($filter_status === 'read') {
        $sql .= " AND is_read = 1";
    } elseif ($filter_status === 'unread') {
        $sql .= " AND is_read = 0";
    }
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

// Count unread notifications for the current admin
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
    <title>Admin Notifications</title>
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
            transition: all 0.3s;
        }
        .notification-card:not(.read) {
            background-color: #e9f7fe;
            border-left: 4px solid #0d6efd;
        }
        .notification-card.read {
            opacity: 0.7;
        }
        .badge {
            font-size: 0.9em;
        }
        .filter-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
        }
        .form-control, .form-select {
            margin-bottom: 15px;
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
            <a class="nav-link active" href="dashboard-admin.php"><i class="bi bi-house"></i> Dashboard</a>
            <a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i> Notifications
            </a>
            <a class="nav-link" href="manage_instructors.php"><i class="bi bi-person-badge"></i> Manage Instructors</a>
            <a class="nav-link" href="threads-admin.php"><i class="bi bi-chat-dots"></i> Forums</a>
            <a class="nav-link" href="../../browse.php"><i class="bi bi-folder"></i> Resources</a>
            <a class="nav-link" href="user_logs.php"><i class="bi bi-journal-text"></i> User Logs</a>
            <a class="nav-link" href="documents.php"><i class="bi bi-plus-circle"></i> Create Document</a>
            <a class="nav-link" href="manage_subjects.php"><i class="bi bi-gear"></i> Manage Subjects</a>
            <a class="nav-link" href="review_alumni.php"><i class="bi bi-gear"></i> Review Requesting Access</a>
            <a class="nav-link" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
        <div class="mt-auto text-center">
            <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span><br>
            <span class="text-muted">Administrator</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Admin Notifications</h2>
            <div>
                <?php if (!empty($notifications)): ?>
                    <a href="?mark_all_read=1" class="btn btn-sm btn-outline-primary">Mark All as Read</a>
                    <a href="?delete_all=1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete all notifications?')">Delete All</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5>Filter Notifications</h5>
            <form method="GET" class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search in title or message" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($notification_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filter_type === $type) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="read" <?php echo ($filter_status === 'read') ? 'selected' : ''; ?>>Read</option>
                        <option value="unread" <?php echo ($filter_status === 'unread') ? 'selected' : ''; ?>>Unread</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="notifications.php" class="btn btn-outline-secondary w-100 mt-2">Clear</a>
                </div>
            </form>
        </div>

        <div class="row">
            <div class="col-md-12">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="card mb-3 notification-card <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-primary">New</span>
                                        <?php endif; ?>
                                        <?php if (!empty($notification['type'])): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $notification['type']))); ?></span>
                                        <?php endif; ?>
                                    </h5>
                                    <div>
                                        <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-secondary">Mark as Read</a>
                                    </div>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <div class="d-flex justify-content-between text-muted">
                                    <small>
                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bell-slash" style="font-size: 3rem; color: #ccc;"></i>
                        <h4 class="mt-3">No notifications found</h4>
                        <p class="text-muted">
                            <?php if (!empty($search) || !empty($filter_type) || !empty($filter_status)): ?>
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

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>