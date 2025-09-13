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

// Example: You can check if the logged-in user is an instructor for this subject/block
$is_instructor = false;
$user_id = $_SESSION['user']['id'];
foreach ($instructors as $block => $list) {
    foreach ($list as $inst) {
        if ($inst['instructor_id'] == $user_id) {
            $is_instructor = true;
        }
    }
}

// Handle class file upload
$file_upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['class_file'])) {
    $file = $_FILES['class_file'];
    if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
        $upload_dir = __DIR__ . '/uploads/class_files/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = basename($file['name']);
        $target = $upload_dir . $subject_id . '_' . time() . '_' . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $file_upload_message = "<div class='alert alert-success'>File uploaded successfully.</div>";
            // Optionally, save file info to database here
        } else {
            $file_upload_message = "<div class='alert alert-danger'>Failed to move uploaded file.</div>";
        }
    } else {
        $file_upload_message = "<div class='alert alert-danger'>Failed to upload file.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($subject_name); ?> - Subject Details</title>
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
            <h2><?php echo htmlspecialchars($subject_name); ?></h2>
        </div>
        <div class="card-body">
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
            
            <div class="mb-4">
                <h5>Class Lists:</h5>
                <div class="d-flex flex-wrap action-buttons">
                    <a href="classlist_block_a.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-primary">
                        Block A List
                    </a>
                    <a href="classlist_block_b.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-primary">
                        Block B List
                    </a>
                </div>
            </div>

           <div class="mb-4">
                <h5>Subject Resources:</h5>
                <div class="d-flex flex-wrap action-buttons">
                    <a href="activities.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-secondary">
                        Activities
                    </a>
                    <a href="resources.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-info">
                        Resources
                    </a>
                    <a href="quizzes.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-warning">
                        Quizzes
                    </a>
                    <a href="exams.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-danger">
                        Exams
                    </a>
                </div>
            </div>
        </div>
    </div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>