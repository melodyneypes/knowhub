<?php
// filepath: e:\CAP101-DANG FILES\archive-system\dashboard-alumni.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'alumni') {
    header('Location: login.php');
    exit();
}
require 'db.php';

// Fetch alumni info
$alumni = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alumni Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-light bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" style="color:white;" href="#">KnowHub: A Digital Archive of BSIT Resources for PSU- Alaminos City Campus</a>
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link text-white" href="dashboard-alumni.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="browse.php">Browse Resources</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="threads.php">Forums</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="external.php">External Resources</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Alumni Profile</h2>
                </div>
                <div class="card-body text-center">
                    <img src="<?php echo $alumni['picture'] ?? 'assets/images/default-profile.png'; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                    <h3><?php echo htmlspecialchars($alumni['name']); ?></h3>
                    <p><?php echo htmlspecialchars($alumni['email']); ?></p>
                    <p>Batch Year: <?php echo htmlspecialchars($alumni['batch_year'] ?? 'N/A'); ?></p>
                    <a href="profile_settings.php" class="btn btn-secondary">Profile Settings</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Welcome, Alumni!</h2>
                </div>
                <div class="card-body">
                    <p>
                        You can browse archived resources, participate in forums, and connect with fellow alumni.<br>
                        Use the navigation above to get started.
                    </p>
                    <a href="browse.php" class="btn btn-primary me-2">Browse Resources</a>
                    <a href="forums.php" class="btn btn-outline-primary">Go to Forums</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>