<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

$user_id = $_SESSION['user']['id'];

// Mark notifications as read when visiting the page
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Fetch notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard-<?php echo $_SESSION['user']['role']; ?>.php">Home</a>
            </li>
            <?php if ($_SESSION['user']['role'] === 'instructor'): ?>
            <li class="nav-item">
                <a class="nav-link" href="subjects-handled.php">My Handled Subjects</a>
            </li>
            <?php elseif ($_SESSION['user']['role'] === 'student'): ?>
            <li class="nav-item">
                <a class="nav-link" href="student-subject.php">My Subjects</a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="browse.php">Browse</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="threads.php">Forums</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container mt-5">
    <h2>Notifications</h2>
    
    <?php if (count($notifications) > 0): ?>
        <div class="list-group">
            <?php foreach ($notifications as $notification): ?>
                <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'list-group-item-primary'; ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h5>
                        <small><?php echo date('M j, Y g:i a', strtotime($notification['created_at'])); ?></small>
                    </div>
                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                    <?php if (!$notification['is_read']): ?>
                        <small class="text-muted">New</small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No notifications found.</div>
    <?php endif; ?>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>