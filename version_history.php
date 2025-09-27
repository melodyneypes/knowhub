<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$resource_id = intval($_GET['resource_id'] ?? 0);

// Fetch resource details
$stmt = $conn->prepare("SELECT r.*, s.name as subject_name FROM resources r JOIN subjects s ON r.subject_id = s.id WHERE r.id = ?");
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$resource = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resource) {
    die("Resource not found.");
}

// Check user permissions
$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// For instructors, check if they teach this subject
$is_instructor = false;
if ($user_role == 'instructor') {
    $stmt = $conn->prepare("SELECT id FROM subject_instructors WHERE subject_id = ? AND instructor_id = ?");
    $stmt->bind_param("ii", $resource['subject_id'], $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $is_instructor = true;
    }
    $stmt->close();
}

// Only allow access to admins, instructors of this subject, or students enrolled in the subject
$can_access = ($user_role == 'admin') || $is_instructor;
if (!$can_access && $user_role == 'student') {
    $stmt = $conn->prepare("SELECT id FROM subject_students WHERE subject_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $resource['subject_id'], $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $can_access = true;
    }
    $stmt->close();
}

if (!$can_access) {
    die("Access denied.");
}

// Fetch version history
$stmt = $conn->prepare("SELECT rv.*, u.name FROM resource_versions rv JOIN users u ON rv.uploader_id = u.id WHERE rv.resource_id = ? ORDER BY rv.uploaded_at DESC");
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();
$versions = [];
while ($row = $result->fetch_assoc()) {
    $versions[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Version History - <?php echo htmlspecialchars($resource['title']); ?></title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="resources.php?subject_id=<?php echo $resource['subject_id']; ?>" class="btn btn-secondary mb-3">&larr; Back to Resources</a>
    <h2>Version History for "<?php echo htmlspecialchars($resource['title']); ?>"</h2>
    <p class="text-muted">Subject: <?php echo htmlspecialchars($resource['subject_name']); ?></p>
    
    <?php if ($versions): ?>
        <div class="list-group">
            <?php foreach ($versions as $index => $ver): ?>
                <div class="list-group-item <?php echo $index === 0 ? 'list-group-item-primary' : ''; ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><?php echo htmlspecialchars($ver['title']); ?></h5>
                        <small><?php echo date('F j, Y, g:i a', strtotime($ver['uploaded_at'])); ?></small>
                    </div>
                    <p class="mb-1"><?php echo htmlspecialchars($ver['description']); ?></p>
                    <small>Uploaded by: <?php echo htmlspecialchars($ver['name']); ?></small>
                    <div class="mt-2">
                        <a href="<?php echo htmlspecialchars($ver['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">Download</a>
                        <?php if ($index === 0): ?>
                            <span class="badge bg-success">Latest Version</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No previous versions found.</div>
    <?php endif; ?>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>