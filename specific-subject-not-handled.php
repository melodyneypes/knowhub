<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

// Get subject ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid subject ID.";
    exit();
}
$subject_id = intval($_GET['id']);

// Fetch subject details
$stmt = $conn->prepare("SELECT name, description FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$stmt->bind_result($subject_name, $subject_description);
if (!$stmt->fetch()) {
    echo "Subject not found.";
    exit();
}
$stmt->close();

// Fetch instructors for this subject and their blocks
$instructors = [];
$res = $conn->query(
    "SELECT u.name, si.block, u.id as instructor_id
     FROM subject_instructors si
     JOIN users u ON si.instructor_id = u.id
     WHERE si.subject_id = $subject_id"
);
while ($row = $res->fetch_assoc()) {
    $instructors[$row['block']][] = $row;
}

$user_id = $_SESSION['user']['id'];

// Check if user has already requested access to edit resources
$access_request_status = null;
$stmt = $conn->prepare("SELECT status FROM access_requests WHERE user_id = ? AND subject_id = ? AND request_type = 'resource_edit'");
$stmt->bind_param("ii", $user_id, $subject_id);
$stmt->execute();
$stmt->bind_result($access_request_status);
$stmt->fetch();
$stmt->close();

// Handle resource upload if user is approved
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resource']) && $access_request_status === 'approved') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['resource_file'];
        $upload_dir = __DIR__ . '/uploads/resources/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = basename($file['name']);
        $target = $upload_dir . $subject_id . '_' . time() . '_' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            // Save resource info to database
            $relative_path = 'uploads/resources/' . basename($target);
            $stmt = $conn->prepare("INSERT INTO resources (subject_id, title, description, file_path, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("isssi", $subject_id, $title, $description, $relative_path, $user_id);
            
            if ($stmt->execute()) {
                $upload_message = '<div class="alert alert-success">Resource uploaded successfully.</div>';
            } else {
                $upload_message = '<div class="alert alert-danger">Failed to save resource info to database.</div>';
            }
            $stmt->close();
        } else {
            $upload_message = '<div class="alert alert-danger">Failed to move uploaded file.</div>';
        }
    } else {
        $upload_message = '<div class="alert alert-danger">Failed to upload file.</div>';
    }
}

// Handle access request submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_access']) && $access_request_status !== 'approved') {
    $reason = trim($_POST['reason']);
    
    // Check if already requested
    if ($access_request_status) {
        $message = '<div class="alert alert-warning">You have already requested access for this subject.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO access_requests (user_id, subject_id, reason, status, request_type, created_at) VALUES (?, ?, ?, 'pending', 'resource_edit', NOW())");
        $stmt->bind_param("iis", $user_id, $subject_id, $reason);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Access request submitted successfully. Please wait for approval.</div>';
            $access_request_status = 'pending';
        } else {
            $message = '<div class="alert alert-danger">Error submitting request. Please try again.</div>';
        }
        $stmt->close();
    }
}

// Fetch resources for this subject
$resources = [];
$stmt = $conn->prepare(
    "SELECT id, title, description, file_path, created_at
     FROM resources 
     WHERE subject_id = ? 
     ORDER BY created_at DESC"
);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($subject_name); ?> - Resources</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        .action-buttons .btn {
            margin: 0.25rem;
            min-width: 120px;
        }
        .card-header {
            font-weight: bold;
        }
        .instructor-block {
            margin-bottom: 1rem;
        }
        .instructor-block:last-child {
            margin-bottom: 0;
        }
        .resource-item {
            border-left: 4px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .resource-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .resource-description {
            color: #6c757d;
            margin-bottom: 5px;
        }
        .resource-meta {
            font-size: 0.85rem;
            color: #999;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard-instructor.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="subjects-handled.php">My Handled Subjects</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="threads.php">Forums</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="external-instructor.php">External Resources</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" style="color: red;" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container mt-5">
    <a href="subjects-handled.php" class="btn btn-secondary mb-3">&larr; Back to Subjects</a>
    <div class="card mb-4">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($subject_name); ?> - Resources</h2>
        </div>
        <div class="card-body">
            <?php echo $message; ?>
            <?php echo $upload_message; ?>
            
            <p><?php echo nl2br(htmlspecialchars($subject_description)); ?></p>
            
            <div class="mb-4">
                <h5>Instructors:</h5>
                <?php foreach ($instructors as $block => $list): ?>
                    <div class="instructor-block">
                        <span class="badge bg-info text-dark me-2">Block <?php echo htmlspecialchars($block); ?></span>
                        <?php 
                        $instructor_names = [];
                        foreach ($list as $inst) {
                            $instructor_names[] = htmlspecialchars($inst['name']);
                        }
                        echo implode(', ', $instructor_names);
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($access_request_status === 'approved'): ?>
            <!-- Resource Upload Form for Approved Users -->
            <div class="mb-4">
                <h5>Add New Resource</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Resource Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="resource_file" class="form-label">Resource File</label>
                        <input class="form-control" type="file" id="resource_file" name="resource_file" required>
                    </div>
                    <button type="submit" name="upload_resource" class="btn btn-primary">Upload Resource</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="mb-4">
                <h5>Resources:</h5>
                <?php if (count($resources) > 0): ?>
                    <?php foreach ($resources as $resource): ?>
                        <div class="resource-item">
                            <div class="resource-title"><?php echo htmlspecialchars($resource['title']); ?></div>
                            <div class="resource-description"><?php echo htmlspecialchars($resource['description']); ?></div>
                            <div class="resource-meta">
                                Uploaded: <?php echo date('F j, Y \a\t g:i A', strtotime($resource['created_at'])); ?>
                            </div>
                            <div class="mt-2">
                                <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                    Download Resource
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">No resources available for this subject.</div>
                <?php endif; ?>
            </div>
            
            <?php if ($access_request_status !== 'approved'): ?>
            <div class="mb-4">
                <h5>Request Edit Access:</h5>
                <?php if ($access_request_status === 'pending'): ?>
                    <div class="alert alert-info">Your request to edit resources is pending approval.</div>
                <?php else: ?>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#requestModal">
                        Request to Edit Resources
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Request Access Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="requestModalLabel">Request Edit Access</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Requesting access to edit resources for: <strong><?php echo htmlspecialchars($subject_name); ?></strong></p>
          <div class="mb-3">
            <label for="reason" class="form-label">Reason for access:</label>
            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="request_access" class="btn btn-primary">Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>