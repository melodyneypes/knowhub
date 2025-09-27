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

// Fetch activities for this subject
$activities = [];
$stmt = $conn->prepare("SELECT id, title, description, file_path, created_at, due_date FROM activities WHERE subject_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}
$stmt->close();

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

// Fetch unread notifications for this subject
$unread_notifications = [];
$stmt = $conn->prepare(
    "SELECT id, user_id, message, is_read, created_at, title, type, sender_id 
     FROM notifications 
     WHERE user_id = ? AND id = ? AND is_read = 0 AND (type = 'activity' OR type = 'resource')
     ORDER BY created_at DESC"
);
$stmt->bind_param("ii", $_SESSION['user']['id'], $subject_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $unread_notifications[] = $row;
}
$stmt->close();

// Mark notifications as read when viewing the page
if (!empty($unread_notifications)) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND subject_id = ? AND is_read = 0 AND (type = 'activity' OR type = 'resource')");
    $stmt->bind_param("ii", $_SESSION['user']['id'], $subject_id);
    $stmt->execute();
    $stmt->close();
}

// Handle activity submission
$submission_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_activity'])) {
    $activity_id = intval($_POST['activity_id']);
    $student_id = $_SESSION['user']['id'];
    
    // Check if student has already submitted this activity
    $stmt = $conn->prepare("SELECT id FROM submissions WHERE activity_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $activity_id, $student_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $submission_message = '<div class="alert alert-warning">You have already submitted this activity.</div>';
    } else {
        $file_path = null;
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/submissions/';
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
                
                // Create notification for instructor
                $stmt = $conn->prepare("SELECT title FROM activities WHERE id = ?");
                $stmt->bind_param("i", $activity_id);
                $stmt->execute();
                $stmt->bind_result($activity_title);
                $stmt->fetch();
                $stmt->close();
                
                $notification_title = "New Submission";
                $notification_message = $_SESSION['user']['name'] . " has submitted activity: " . $activity_title;
                
                // Get instructor IDs for this subject
                $stmt = $conn->prepare("SELECT instructor_id FROM subject_instructors WHERE subject_id = ?");
                $stmt->bind_param("i", $subject_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $instructor_id = $row['instructor_id'];
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, id, message, is_read, title, type, sender_id) VALUES (?, ?, ?, 0, ?, 'submission', ?)");
                    $notif_stmt->bind_param("iissi", $instructor_id, $subject_id, $notification_message, $notification_title, $student_id);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
                $stmt->close();
            } else {
                $submission_message = '<div class="alert alert-danger">Failed to submit activity.</div>';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($subject_name); ?> - Student View</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-header {
            font-weight: bold;
        }
        .resource-item, .activity-item {
            border-left: 4px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .resource-title, .activity-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .resource-description, .activity-description {
            color: #6c757d;
            margin-bottom: 5px;
        }
        .resource-meta, .activity-meta {
            font-size: 0.85rem;
            color: #999;
        }
        .action-buttons .btn {
            margin: 0.25rem;
            min-width: 120px;
        }
        .notification-badge {
            background-color: #dc3545;
            color: white;
            padding: 0.25em 0.5em;
            border-radius: 50%;
            font-size: 0.75rem;
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
        }
        .notification-card {
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
        }
        .notification-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .notification-message {
            color: #6c757d;
            margin-bottom: 5px;
        }
        .notification-time {
            font-size: 0.85rem;
            color: #999;
        }
        .tab-content {
            border: 1px solid #dee2e6;
            border-top: none;
            padding: 20px;
        }
        .submission-history {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard-student.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="threads.php">Forums</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" style="color: red;" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container mt-5">
    <a href="dashboard-student.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
    
    <!-- Notifications Section -->
    <?php if (!empty($unread_notifications)): ?>
        <div class="alert alert-success mb-4">
            <h4>New Updates!</h4>
            <p>You have <?php echo count($unread_notifications); ?> new notification(s) for this subject.</p>
            <div class="mt-3">
                <?php foreach ($unread_notifications as $notification): ?>
                    <div class="notification-card">
                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                        <div class="notification-time">
                            Posted: <?php echo date('F j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($subject_name); ?></h2>
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($subject_description)); ?></p>
            
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" id="subjectTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activities" type="button" role="tab" aria-controls="activities" aria-selected="true">Activities</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resources-tab" data-bs-toggle="tab" data-bs-target="#resources" type="button" role="tab" aria-controls="resources" aria-selected="false">Resources</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="submissions-tab" data-bs-toggle="tab" data-bs-target="#submissions" type="button" role="tab" aria-controls="submissions" aria-selected="false">Submissions</button>
                </li>
            </ul>
            
            <!-- Tab panes -->
            <div class="tab-content" id="subjectTabsContent">
                <!-- Activities Tab -->
                <div class="tab-pane fade show active" id="activities" role="tabpanel" aria-labelledby="activities-tab">
                    <div class="mb-4">
                        <h4 class="mb-3">Activities</h4>
                        <?php if (count($activities) > 0): ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                    <div class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></div>
                                    <div class="activity-meta">
                                        Posted: <?php echo date('F j, Y \a\t g:i A', strtotime($activity['created_at'])); ?>
                                        <?php if (!empty($activity['due_date'])): ?>
                                            | Due: <?php echo date('F j, Y', strtotime($activity['due_date'])); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2">
                                        <?php if (!empty($activity['file_path']) && file_exists($activity['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($activity['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                                Download Activity
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-primary" disabled>No File</button>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#submitModal<?php echo $activity['id']; ?>">
                                            Submit
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Submission Modal -->
                                <div class="modal fade" id="submitModal<?php echo $activity['id']; ?>" tabindex="-1" aria-labelledby="submitModalLabel<?php echo $activity['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="submitModalLabel<?php echo $activity['id']; ?>">Submit Activity: <?php echo htmlspecialchars($activity['title']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php echo $submission_message; ?>
                                                    <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="submission_file" class="form-label">Upload your file</label>
                                                        <input class="form-control" type="file" id="submission_file" name="submission_file" required>
                                                        <div class="form-text">Allowed file types: PDF, DOC, DOCX, PPT, PPTX, ZIP, etc.</div>
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
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">No activities available for this subject.</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Resources Tab -->
                <div class="tab-pane fade" id="resources" role="tabpanel" aria-labelledby="resources-tab">
                    <div class="mb-4">
                        <h4 class="mb-3">Resources</h4>
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
                </div>
                
                <!-- Submissions Tab -->
                <div class="tab-pane fade" id="submissions" role="tabpanel" aria-labelledby="submissions-tab">
                    <div class="mb-4">
                        <h4 class="mb-3">Your Submissions</h4>
                        <?php
                        // Fetch student's submissions
                        $submissions = [];
                        $stmt = $conn->prepare(
                            "SELECT s.id, s.file_path, s.submitted_at, s.grade, s.feedback, a.title as activity_title
                             FROM submissions s
                             JOIN activities a ON s.activity_id = a.id
                             WHERE s.student_id = ? AND a.subject_id = ?
                             ORDER BY s.submitted_at DESC"
                        );
                        $stmt->bind_param("ii", $_SESSION['user']['id'], $subject_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $submissions[] = $row;
                        }
                        $stmt->close();
                        ?>
                        
                        <?php if (count($submissions) > 0): ?>
                            <?php foreach ($submissions as $submission): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($submission['activity_title']); ?></h5>
                                        <p class="card-text">
                                            <strong>Submitted:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($submission['submitted_at'])); ?><br>
                                            <?php if (!is_null($submission['grade'])): ?>
                                                <strong>Grade:</strong> <?php echo htmlspecialchars($submission['grade']); ?><br>
                                            <?php endif; ?>
                                            <?php if (!empty($submission['feedback'])): ?>
                                                <strong>Feedback:</strong> <?php echo htmlspecialchars($submission['feedback']); ?><br>
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($submission['file_path']) && file_exists($submission['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">Download Submission</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">You haven't submitted any activities for this subject yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>