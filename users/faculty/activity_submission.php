<?php
session_start();
require '../../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: ../../login.php');
    exit();
}

$subject_id = $_GET['id'] ?? null;
$student_id = $_SESSION['user']['id'];

if (!$subject_id || !is_numeric($subject_id)) {
    die("Invalid subject ID.");
}

// Check if student is enrolled in this subject
$stmt = $conn->prepare("SELECT id FROM subject_students WHERE subject_id = ? AND student_id = ?");
$stmt->bind_param("ii", $subject_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("You are not enrolled in this subject.");
}
$stmt->close();

// Handle activity submission
$submission_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_activity'])) {
    $activity_id = $_POST['activity_id'];
    $file_path = null;
    
    // Check if student has already submitted this activity
    $stmt = $conn->prepare("SELECT id FROM submissions WHERE activity_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $activity_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $submission_message = '<div class="alert alert-warning">You have already submitted this activity.</div>';
    } else {
        // Handle file upload
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/submissions/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = basename($_FILES['submission_file']['name']);
            $file_path = $upload_dir . $activity_id . '_' . $student_id . '_' . time() . '_' . $filename;
            
            if (!move_uploaded_file($_FILES['submission_file']['tmp_name'], $file_path)) {
                $file_path = null;
                $submission_message = '<div class="alert alert-danger">Failed to upload file.</div>';
            }
        }
        
        if (empty($submission_message)) {
            $stmt = $conn->prepare("INSERT INTO submissions (activity_id, student_id, file_path, submitted_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $activity_id, $student_id, $file_path);
            
            if ($stmt->execute()) {
                $submission_message = '<div class="alert alert-success">Activity submitted successfully!</div>';
                
                // Create notification for instructor using faculty notification system
                $stmt = $conn->prepare("SELECT title FROM activities WHERE id = ?");
                $stmt->bind_param("i", $activity_id);
                $stmt->execute();
                $stmt->bind_result($activity_title);
                $stmt->fetch();
                $stmt->close();
                
                // Get instructor IDs for this subject
                $stmt = $conn->prepare("SELECT instructor_id FROM subject_instructors WHERE subject_id = ?");
                $stmt->bind_param("i", $subject_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                // Include faculty notification functions
                require_once '../faculty_notify.php';
                
                // Notify each instructor about the submission
                while ($row = $result->fetch_assoc()) {
                    $instructor_id = $row['instructor_id'];
                    notify_faculty_activity_submission($instructor_id, $_SESSION['user']['name'], $activity_title, $student_id);
                }
                $stmt->close();
            } else {
                $submission_message = '<div class="alert alert-danger">Failed to submit activity.</div>';
            }
            $stmt->close();
        }
    }
}

// Fetch subject details
$stmt = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$stmt->bind_result($subject_name);
$stmt->fetch();
$stmt->close();

// Fetch activities for this subject
$activities = [];
$stmt = $conn->prepare("SELECT id, title, description, due_date FROM activities WHERE subject_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Check if student has submitted this activity
    $submitted_stmt = $conn->prepare("SELECT id, submitted_at FROM submissions WHERE activity_id = ? AND student_id = ?");
    $submitted_stmt->bind_param("ii", $row['id'], $student_id);
    $submitted_stmt->execute();
    $submitted_result = $submitted_stmt->get_result();
    if ($submitted_row = $submitted_result->fetch_assoc()) {
        $row['submitted'] = true;
        $row['submitted_at'] = $submitted_row['submitted_at'];
    } else {
        $row['submitted'] = false;
    }
    $submitted_stmt->close();
    
    $activities[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Submission - <?php echo htmlspecialchars($subject_name); ?></title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .activity-card {
            transition: all 0.3s;
        }
        .activity-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .submitted-badge {
            background-color: #28a745;
        }
        .pending-badge {
            background-color: #ffc107;
            color: #212529;
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
                <a class="nav-link" style="color: red;" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container mt-5">
    <a href="student-subject.php?id=<?php echo $subject_id; ?>" class="btn btn-secondary mb-3">&larr; Back to Subject</a>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Activity Submission - <?php echo htmlspecialchars($subject_name); ?></h2>
                </div>
                <div class="card-body">
                    <?php if ($submission_message): ?>
                        <?php echo $submission_message; ?>
                    <?php endif; ?>
                    
                    <p class="text-muted">Submit your activities for this subject. You can only submit each activity once.</p>
                    
                    <?php if (count($activities) > 0): ?>
                        <div class="row">
                            <?php foreach ($activities as $activity): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card activity-card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0"><?php echo htmlspecialchars($activity['title']); ?></h5>
                                            <?php if ($activity['submitted']): ?>
                                                <span class="badge submitted-badge">Submitted</span>
                                            <?php else: ?>
                                                <span class="badge pending-badge">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text"><?php echo htmlspecialchars($activity['description'] ?? 'No description provided'); ?></p>
                                            
                                            <?php if (!empty($activity['due_date'])): ?>
                                                <p class="card-text">
                                                    <strong>Due Date:</strong> 
                                                    <?php echo date('F j, Y', strtotime($activity['due_date'])); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if ($activity['submitted']): ?>
                                                <p class="card-text text-success">
                                                    <strong>Submitted on:</strong> 
                                                    <?php echo date('F j, Y g:i A', strtotime($activity['submitted_at'])); ?>
                                                </p>
                                            <?php else: ?>
                                                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#submitModal<?php echo $activity['id']; ?>">
                                                    Submit Activity
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!$activity['submitted']): ?>
                                    <!-- Submit Modal -->
                                    <div class="modal fade" id="submitModal<?php echo $activity['id']; ?>" tabindex="-1" aria-labelledby="submitModalLabel<?php echo $activity['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="submitModalLabel<?php echo $activity['id']; ?>">Submit Activity: <?php echo htmlspecialchars($activity['title']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" enctype="multipart/form-data">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                                                        <div class="mb-3">
                                                            <label for="submission_file<?php echo $activity['id']; ?>" class="form-label">Upload File</label>
                                                            <input type="file" class="form-control" id="submission_file<?php echo $activity['id']; ?>" name="submission_file" required>
                                                            <div class="form-text">Please upload your completed activity file.</div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="submit_activity" class="btn btn-primary">Submit Activity</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No activities available for this subject yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>