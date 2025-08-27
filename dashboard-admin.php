<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require 'db.php';

// Fetch recent notifications (latest 5)
$notifications = [];
$notif_stmt = $conn->prepare("SELECT message, created_at FROM notifications ORDER BY created_at DESC LIMIT 5");
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
while ($row = $notif_result->fetch_assoc()) {
    $notifications[] = $row;
}
$notif_stmt->close();

// Fetch recent user logs (latest 5)
$user_logs = [];
$log_stmt = $conn->prepare("SELECT users.name, user_logs.action, user_logs.details, user_logs.timestamp 
    FROM user_logs 
    JOIN users ON user_logs.user_id = users.id 
    ORDER BY user_logs.timestamp DESC LIMIT 5");
$log_stmt->execute();
$log_result = $log_stmt->get_result();
while ($row = $log_result->fetch_assoc()) {
    $user_logs[] = $row;
}
$log_stmt->close();

// Fetch pending alumni requests (latest 5)
$alumni_requests = [];
$req_stmt = $conn->prepare("SELECT id, name, email, batch_year, requested_at FROM alumni_requests ORDER BY requested_at DESC LIMIT 5");
$req_stmt->execute();
$req_result = $req_stmt->get_result();
while ($row = $req_result->fetch_assoc()) {
    $alumni_requests[] = $row;
}
$req_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
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
            <a class="nav-link" href="admin_notifications.php"><i class="bi bi-bell"></i> Notifications</a>
            <a class="nav-link" href="manage_instructors.php"><i class="bi bi-people"></i> Manage Subject Instructors</a>
            <a class="nav-link" href="threads-admin.php"><i class="bi bi-chat-dots"></i> Forums</a>
            <a class="nav-link" href="browse.php"><i class="bi bi-folder"></i> Resources</a>
            <a class="nav-link" href="admin_user_logs.php"><i class="bi bi-journal-text"></i> User Logs</a>
            <a class="nav-link" href="logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    <!-- Main Content -->
    <div class="main-content flex-1 col-md-10">
        <div class="container-fluid py-4">
            <h2 class="mb-4">Welcome, Admin!</h2>
            <div class="row">
                <!-- Recent Notifications -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <strong>Recent Notifications</strong>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <li class="list-group-item">
                                        <span class="fw-bold"><?php echo htmlspecialchars($notif['message']); ?></span>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($notif['created_at']); ?></small>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">No notifications yet.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <!-- Recent User Logs -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <strong>Recent User Logs</strong>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($user_logs)): ?>
                                <?php foreach ($user_logs as $log): ?>
                                    <li class="list-group-item">
                                        <span class="fw-bold"><?php echo htmlspecialchars($log['name']); ?></span>
                                        <?php echo htmlspecialchars($log['action']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['timestamp']); ?></small>
                                        <?php if (!empty($log['details'])): ?>
                                            <br><small><?php echo htmlspecialchars($log['details']); ?></small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">No user logs yet.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <!-- Quick Search -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <strong>Quick Search</strong>
                        </div>
                        <div class="card-body">
                            <form method="get" action="search.php">
                                <input type="text" name="q" class="form-control mb-2" placeholder="Search users, subjects, resources...">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- Pending Alumni Requests -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <strong>Pending Alumni Requests</strong>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($alumni_requests)): ?>
                                <?php foreach ($alumni_requests as $req): ?>
                                    <li class="list-group-item">
                                        <span class="fw-bold"><?php echo htmlspecialchars($req['name']); ?></span>
                                        <br>
                                        <small><?php echo htmlspecialchars($req['email']); ?> | Batch <?php echo htmlspecialchars($req['batch_year']); ?></small>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($req['requested_at']); ?></small>
                                        <br>
                                        <a href="admin_alumni_requests.php?approve_id=<?php echo $req['id']; ?>" class="btn btn-success btn-sm mt-2">Approve</a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">No pending alumni requests.</li>
                            <?php endif; ?>
                        </ul>
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
