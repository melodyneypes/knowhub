<?php
// filepath: e:\CAP101-DANG FILES\archive-system\alumni_requests.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}
require '../../db.php';

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    
    // Get the request details
    $stmt = $conn->prepare("SELECT name, email FROM alumni_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();
    
    if ($request) {
        if ($_POST['action'] === 'approve') {
            // Check if user already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $request['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // User exists, update their role to alumni
                $user = $result->fetch_assoc();
                $stmt = $conn->prepare("UPDATE users SET role = 'alumni' WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $stmt->close();
            } else {
                // User doesn't exist, create new user with alumni role
                $picture = 'assets/images/default-profile.png'; // Default picture for alumni
                $stmt = $conn->prepare("INSERT INTO users (email, name, picture, role) VALUES (?, ?, ?, 'alumni')");
                $stmt->bind_param("sss", $request['email'], $request['name'], $picture);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Update the request status
        $stmt = $conn->prepare("UPDATE alumni_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $action, $request_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch pending requests
$pending_requests = [];
$stmt = $conn->prepare("SELECT * FROM alumni_requests WHERE status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_requests[] = $row;
}
$stmt->close();

// Unread notification count for sidebar
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $_SESSION['user']['id']);
$stmt->execute();
$stmt->bind_result($unread_count);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Alumni Requests</title>
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
        <h2 class="mb-4 fw-bold">Pending Alumni Requests</h2>
        <?php if (count($pending_requests) > 0): ?>
            <?php foreach ($pending_requests as $req): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($req['name']); ?></h5>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($req['email'] ?? 'N/A'); ?></p>
                        <p><strong>Requested At:</strong> <?php echo htmlspecialchars($req['requested_at'] ?? 'N/A'); ?></p>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm ms-2">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No pending alumni requests.</div>
        <?php endif; ?>
    </div>
</div>
<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>