<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

// Get user role
$user_role = $_SESSION['user']['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Map</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Site Map</h1>
        
        <div class="row">
            <div class="col-md-4">
                <h3>Dashboard</h3>
                <ul class="list-group">
                    <li class="list-group-item"><a href="dashboard-<?php echo $user_role; ?>.php">Dashboard</a></li>
                </ul>
                
                <h3 class="mt-4">Resources</h3>
                <ul class="list-group">
                    <li class="list-group-item"><a href="browse.php">Browse Resources</a></li>
                    <?php if ($user_role !== 'student'): ?>
                    <li class="list-group-item"><a href="upload.php">Upload Resource</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="col-md-4">
                <h3>Forums</h3>
                <ul class="list-group">
                    <li class="list-group-item"><a href="threads.php">All Threads</a></li>
                    <li class="list-group-item"><a href="new_thread.php">Create New Thread</a></li>
                </ul>
                
                <h3 class="mt-4">Subjects</h3>
                <ul class="list-group">
                    <li class="list-group-item"><a href="subjects-handled.php">My Subjects</a></li>
                    <li class="list-group-item"><a href="specific-subject-not-handled.php">Subjects I Don't Handle</a></li>
                </ul>
            </div>
            
            <div class="col-md-4">
                <h3>Account</h3>
                <ul class="list-group">
                    <li class="list-group-item"><a href="user_profile.php?user_id=<?php echo $_SESSION['user']['id']; ?>">My Profile</a></li>
                    <li class="list-group-item"><a href="profile_settings.php">Settings</a></li>
                    <li class="list-group-item"><a href="notifications.php">Notifications</a></li>
                    <li class="list-group-item"><a href="logout.php">Logout</a></li>
                </ul>
                
                <?php if ($user_role === 'admin'): ?>
                <h3 class="mt-4">Admin</h3>
                <ul class="list-group">
                    <li class="list-group-item"><a href="manage_instructors.php">Manage Instructors</a></li>
                    <li class="list-group-item"><a href="manage_subjects.php">Manage Subjects</a></li>
                    <li class="list-group-item"><a href="admin_user_logs.php">User Logs</a></li>
                    <li class="list-group-item"><a href="review_alumni.php">Alumni Requests</a></li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>