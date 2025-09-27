<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require 'db.php';

// Calculate storage statistics
$total_resources = 0;
$total_size = 0;
$resource_types = [];
$uploads_dir = __DIR__ . '/uploads';

// Function to get directory size
function getDirectorySize($path) {
    $size = 0;
    foreach (glob(rtrim($path, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : getDirectorySize($each);
    }
    return $size;
}

// Get overall stats
if (is_dir($uploads_dir)) {
    $total_size = getDirectorySize($uploads_dir);
}

// Get resource count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM resources");
$stmt->execute();
$result = $stmt->get_result();
$total_resources = $result->fetch_assoc()['count'];
$stmt->close();

// Get resource types distribution
$stmt = $conn->prepare("SELECT 
    CASE 
        WHEN file_path LIKE '%.pdf' THEN 'PDF'
        WHEN file_path LIKE '%.doc%' THEN 'Word Documents'
        WHEN file_path LIKE '%.xls%' THEN 'Excel Sheets'
        WHEN file_path LIKE '%.ppt%' THEN 'PowerPoint'
        WHEN file_path LIKE '%.txt' THEN 'Text Files'
        WHEN file_path LIKE '%.zip' THEN 'Archives'
        ELSE 'Other'
    END as type,
    COUNT(*) as count
    FROM resources 
    GROUP BY 
    CASE 
        WHEN file_path LIKE '%.pdf' THEN 'PDF'
        WHEN file_path LIKE '%.doc%' THEN 'Word Documents'
        WHEN file_path LIKE '%.xls%' THEN 'Excel Sheets'
        WHEN file_path LIKE '%.ppt%' THEN 'PowerPoint'
        WHEN file_path LIKE '%.txt' THEN 'Text Files'
        WHEN file_path LIKE '%.zip' THEN 'Archives'
        ELSE 'Other'
    END
    ORDER BY count DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $resource_types[] = $row;
}
$stmt->close();

// Get top subjects by resource count
$stmt = $conn->prepare("SELECT s.name, COUNT(r.id) as resource_count 
                       FROM subjects s 
                       LEFT JOIN resources r ON s.id = r.subject_id 
                       GROUP BY s.id, s.name 
                       ORDER BY resource_count DESC 
                       LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
$top_subjects = [];
while ($row = $result->fetch_assoc()) {
    $top_subjects[] = $row;
}
$stmt->close();

// Format bytes for readability
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Storage Health Check</title>
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
            <li class="nav-item">
                <a class="nav-link" href="admin_user_logs.php">User Logs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_instructors.php">Manage Instructors</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_subjects.php">Manage Subjects</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="review_alumni.php">Alumni Requests</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Storage Health Check</h2>
        <a href="dashboard-admin.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $total_resources; ?></h5>
                    <p class="card-text">Total Resources</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?php echo formatBytes($total_size); ?></h5>
                    <p class="card-text">Total Storage Used</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?php echo count($resource_types); ?></h5>
                    <p class="card-text">File Types</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>File Types Distribution</h5>
                </div>
                <div class="card-body">
                    <?php if (count($resource_types) > 0): ?>
                        <ul class="list-group">
                            <?php foreach ($resource_types as $type): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($type['type']); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $type['count']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No resources found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Top Subjects by Resources</h5>
                </div>
                <div class="card-body">
                    <?php if (count($top_subjects) > 0): ?>
                        <ul class="list-group">
                            <?php foreach ($top_subjects as $subject): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                    <span class="badge bg-success rounded-pill"><?php echo $subject['resource_count']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No subjects found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>