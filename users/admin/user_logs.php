<?php
// filepath: e:\CAP101-DANG FILES\archive-system\admin_user_logs.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require '../../db.php';

// Get filter parameters
$filter_date = $_GET['date'] ?? '';
$filter_user = $_GET['user'] ?? '';
$filter_role = $_GET['role'] ?? '';
$search_term = $_GET['search'] ?? '';

// Build the query with filters
$sql = "SELECT users.name, users.email, users.role, user_logs.action, user_logs.timestamp, user_logs.details 
        FROM user_logs 
        INNER JOIN users ON user_logs.user_id = users.id 
        WHERE 1=1";

$params = [];
$types = "";

// Add date filter
if (!empty($filter_date)) {
    $sql .= " AND DATE(user_logs.timestamp) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

// Add user name filter
if (!empty($filter_user)) {
    $sql .= " AND users.name LIKE ?";
    $params[] = "%".$filter_user."%";
    $types .= "s";
}

// Add role filter
if (!empty($filter_role)) {
    $sql .= " AND users.role = ?";
    $params[] = $filter_role;
    $types .= "s";
}

// Add search term filter
if (!empty($search_term)) {
    $sql .= " AND (users.name LIKE ? OR users.email LIKE ? OR user_logs.action LIKE ? OR user_logs.details LIKE ?)";
    $params = array_merge($params, ["%".$search_term."%", "%".$search_term."%", "%".$search_term."%", "%".$search_term."%"]);
    $types .= "ssss";
}

$sql .= " ORDER BY user_logs.timestamp DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$user_logs = [];
while ($row = $result->fetch_assoc()) {
    $user_logs[] = $row;
}
$stmt->close();

// Get unique roles for filter dropdown
$roles_stmt = $conn->prepare("SELECT DISTINCT role FROM users ORDER BY role");
$roles_stmt->execute();
$roles_result = $roles_stmt->get_result();
$roles = [];
while ($row = $roles_result->fetch_assoc()) {
    $roles[] = $row['role'];
}
$roles_stmt->close();

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
    <title>User Logs</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #fff; border-right: 1px solid #eee; }
        .sidebar .nav-link { color: #333; font-weight: 500; padding: 12px 20px; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #e9ecef; color: #126682d1; }
        .profile-img { max-width: 60px; margin: 20px auto 10px auto; display: block; border-radius: 50%; border: 2px solid #126682d1; }
        .main-content { padding: 40px 30px; }
        .badge { font-size: 0.9em; }
        .filters {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
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
        <h2 class="mb-4 fw-bold">User Logs</h2>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="col-md-3">
                    <label for="user" class="form-label">User Name</label>
                    <input type="text" class="form-control" id="user" name="user" placeholder="Search user" value="<?php echo htmlspecialchars($filter_user); ?>">
                </div>
                <div class="col-md-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo htmlspecialchars($role); ?>" <?php echo ($filter_role === $role) ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($role)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search logs" value="<?php echo htmlspecialchars($search_term); ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary me-2"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="user_logs.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="card p-4 shadow-sm">
            <?php if (!empty($user_logs)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Timestamp</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['name']); ?></td>
                                    <td><?php echo htmlspecialchars($log['email']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($log['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-journal-text" style="font-size: 3rem; color: #ccc;"></i>
                    <h4 class="mt-3">No logs found</h4>
                    <p class="text-muted">No user logs match your current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>