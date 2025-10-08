<?php
// filepath: e:\CAP101-DANG FILES\archive-system\dashboard-admin.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}
require '../../db.php';

// Recent Notifications (limit 3) for the logged-in admin
$notifications = [];
$stmt = $conn->prepare("SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->bind_param("i", $_SESSION['user']['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Unread notification count for the current admin
$stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $_SESSION['user']['id']);
$stmt->execute();
$stmt->bind_result($unread_count);
$stmt->fetch();
$stmt->close();

// Recent User Logs (limit 3)
$user_logs = [];
$stmt = $conn->prepare("SELECT users.name, user_logs.action, user_logs.timestamp, user_logs.details FROM user_logs INNER JOIN users ON user_logs.user_id = users.id ORDER BY user_logs.timestamp DESC LIMIT 3");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_logs[] = $row;
}
$stmt->close();

// Pending Alumni Requests (limit 1)
$pending_requests = [];
$stmt = $conn->prepare("SELECT CONCAT('New alumni registration awaiting approval for ', name, '.') AS name FROM guests_requests WHERE status = 'pending' LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_requests[] = $row;
}
$stmt->close();

// Upcoming Tasks (limit 3)
$upcoming_tasks = [];
$stmt = $conn->prepare("SELECT task, due FROM tasks WHERE assigned_to = ? ORDER BY due ASC LIMIT 3");
$stmt->bind_param("i", $_SESSION['user']['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $upcoming_tasks[] = $row;
}
$stmt->close();

// Storage health check
function getDirectorySize($path) {
    $bytestotal = 0;
    $path = realpath($path);
    if ($path !== false && $path != '' && file_exists($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
            $bytestotal += $object->getSize();
        }
    }
    return $bytestotal;
}

$total_storage = getDirectorySize('uploads/');
$total_resources = 0;
$total_users = 0;

$stmt = $conn->prepare("SELECT COUNT(*) FROM resources");
$stmt->execute();
$stmt->bind_result($total_resources);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$stmt->bind_result($total_users);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
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
        .card-header {
            font-weight: 600;
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
            <h1>Admin Dashboard</h1>
        </div>

        <h2 class="mb-2 fw-bold">Welcome, Admin!</h2>
        <p class="mb-4 text-muted">Here's a quick overview of your dashboard.</p>
        <div class="row g-4">
            <!-- Recent Notifications -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                       My Recent Notifications
                    </div>
                    <div class="card-body">
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div class="mb-2">
                                    <span><?php echo htmlspecialchars($notif['message']); ?></span><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($notif['created_at']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">No notifications yet.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Recent User Logs -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        Recent User Logs
                    </div>
                    <div class="card-body">
                        <?php if (!empty($user_logs)): ?>
                            <?php foreach ($user_logs as $log): ?>
                                <div class="mb-2">
                                    <span class="fw-bold"><?php echo htmlspecialchars($log['name']); ?></span>
                                    <?php echo htmlspecialchars($log['action']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($log['timestamp']); ?></small>
                                    <?php if (!empty($log['details'])): ?>
                                        <br><small><?php echo htmlspecialchars($log['details']); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">No user logs yet.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Quick Search -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        Quick Search
                    </div>
                    <div class="card-body">
                        <form method="get" action="search.php">
                            <input type="text" name="q" class="form-control mb-2" placeholder="Search users, subjects, resources...">
                            <button type="submit" class="btn btn-dark w-100">Search</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pending Alumni Requests -->
        <div class="card my-4 shadow-sm">
            <div class="card-body bg-info bg-opacity-10 d-flex align-items-center">
                <i class="bi bi-info-circle me-2 text-info"></i>
                <div class="flex-grow-1">
                    <strong>Pending Alumni Requests</strong><br>
                    <?php echo !empty($pending_requests) ? htmlspecialchars($pending_requests[0]['name']) : 'No pending requests.'; ?>
                </div>
                <a href="review_alumni.php" class="btn btn-info ms-3">Review Now</a>
            </div>
        </div>
        <!-- Storage Health Check -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Storage Health</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Total Storage Used:</strong> <?php echo round($total_storage / (1024*1024), 2); ?> MB</p>
                        <p><strong>Total Resources:</strong> <?php echo $total_resources; ?></p>
                        <p><strong>Total Users:</strong> <?php echo $total_users; ?></p>
                        <p><strong>Last Checked:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>