<?php
// activities.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

// Get subject ID from URL
if (!isset($_GET['subject_id']) || !is_numeric($_GET['subject_id'])) {
    echo "Invalid subject ID.";
    exit();
}
$subject_id = intval($_GET['subject_id']);

// Fetch subject details
$stmt = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$stmt->bind_result($subject_name);
if (!$stmt->fetch()) {
    echo "Subject not found.";
    exit();
}
$stmt->close();

// Check if user is instructor for this subject
$is_instructor = false;
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT id FROM subject_instructors WHERE subject_id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $subject_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $is_instructor = true;
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

// Handle activity deletion (for instructors)
if ($is_instructor && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_activity'])) {
    $activity_id = intval($_POST['activity_id']);
    
    // First get file path to delete the file
    $stmt = $conn->prepare("SELECT file_path FROM activities WHERE id = ? AND subject_id = ?");
    $stmt->bind_param("ii", $activity_id, $subject_id);
    $stmt->execute();
    $stmt->bind_result($file_path);
    
    if ($stmt->fetch()) {
        // Delete file from server
        if (file_exists($file_path) && !empty($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $stmt->close();
        $delete_stmt = $conn->prepare("DELETE FROM activities WHERE id = ? AND subject_id = ?");
        $delete_stmt->bind_param("ii", $activity_id, $subject_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Redirect to refresh the page
        header("Location: activities.php?subject_id=$subject_id");
        exit();
    }
    $stmt->close();
}

// Handle activity upload (for instructors)
$upload_message = '';
if ($is_instructor && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['activity_file'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : NULL;
    
    if (empty($title)) {
        $upload_message = "<div class='alert alert-danger'>Title is required.</div>";
    } else {
        $file_path = NULL;
        if (isset($_FILES['activity_file']) && $_FILES['activity_file']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['activity_file']['tmp_name'])) {
            $upload_dir = __DIR__ . '/uploads/activities/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = basename($_FILES['activity_file']['name']);
            $file_path = $upload_dir . $subject_id . '_' . time() . '_' . $filename;
            
            if (!move_uploaded_file($_FILES['activity_file']['tmp_name'], $file_path)) {
                $upload_message = "<div class='alert alert-danger'>Failed to move uploaded file.</div>";
                $file_path = NULL;
            }
        }
        
        if (empty($upload_message)) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO activities (subject_id, title, description, file_path, due_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issss", $subject_id, $title, $description, $file_path, $due_date);
            $stmt->execute();
            $stmt->close();
            
            $upload_message = "<div class='alert alert-success'>Activity uploaded successfully.</div>";
            
            // Refresh the activities list
            header("Location: activities.php?subject_id=$subject_id");
            exit();
        }
    }
}

// Handle submission deletion (for instructors)
if ($is_instructor && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_submission'])) {
    $submission_id = intval($_POST['submission_id']);
    
    // First get file path to delete the file
    $stmt = $conn->prepare("SELECT file_path FROM submissions WHERE id = ?");
    $stmt->execute();
    $stmt->bind_result($file_path);
    
    if ($stmt->fetch()) {
        // Delete file from server
        if (file_exists($file_path) && !empty($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $stmt->close();
        $delete_stmt = $conn->prepare("DELETE FROM submissions WHERE id = ?");
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Redirect to refresh the page
        header("Location: activities.php?subject_id=$subject_id");
        exit();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activities - <?php echo htmlspecialchars($subject_name); ?></title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        .activity-card {
            transition: transform 0.2s;
        }
        .activity-card:hover {
            transform: translateY(-3px);
        }
        .file-icon {
            font-size: 2.5rem;
            color: #007bff;
        }
        .submission-table th, .submission-table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="specific-subject.php?id=<?php echo $subject_id; ?>" class="btn btn-secondary">&larr; Back to Subject</a>
            <h2 class="mt-3">Activities for <?php echo htmlspecialchars($subject_name); ?></h2>
        </div>
        <?php if ($is_instructor): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">+ Add Activity</button>
        <?php endif; ?>
    </div>
    
    <?php echo $upload_message; ?>
    
    <?php if (count($activities) > 0): ?>
        <?php foreach ($activities as $activity): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo htmlspecialchars($activity['title']); ?></h5>
                    <?php if ($is_instructor): ?>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this activity?');">
                            <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                            <button type="submit" name="delete_activity" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <p><?php echo htmlspecialchars($activity['description']); ?></p>
                    <?php if (!empty($activity['due_date'])): ?>
                        <p><strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($activity['due_date'])); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($activity['file_path']) && file_exists($activity['file_path'])): ?>
                        <a href="<?php echo htmlspecialchars($activity['file_path']); ?>" class="btn btn-primary" download>Download Activity File</a>
                    <?php else: ?>
                        <button class="btn btn-primary" disabled>No File</button>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_instructor): ?>
                    <div class="card-body">
                        <h6>Student Submissions</h6>
                        <?php
                        // Fetch submissions for this activity
                        $submissions = [];
                        $stmt = $conn->prepare("SELECT s.id, s.file_path, s.submitted_at, s.grade, s.feedback, u.name, u.student_number, u.email 
                                                FROM submissions s 
                                                JOIN users u ON s.student_id = u.id 
                                                WHERE s.activity_id = ? 
                                                ORDER BY s.submitted_at DESC");
                        $stmt->bind_param("i", $activity['id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $submissions[] = $row;
                        }
                        $stmt->close();
                        ?>
                        
                        <?php if (count($submissions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped submission-table">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Student Number</th>
                                            <th>Email</th>
                                            <th>Submitted At</th>
                                            <th>File</th>
                                            <th>Grade</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($submissions as $submission): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($submission['name']); ?></td>
                                                <td><?php echo htmlspecialchars($submission['student_number']); ?></td>
                                                <td><?php echo htmlspecialchars($submission['email']); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></td>
                                                <td>
                                                    <?php if (!empty($submission['file_path']) && file_exists($submission['file_path'])): ?>
                                                        <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" class="btn btn-sm btn-outline-primary" download>Download</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No file</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!is_null($submission['grade'])): ?>
                                                        <?php echo htmlspecialchars($submission['grade']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not graded</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this submission?');">
                                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                                        <button type="submit" name="delete_submission" class="btn btn-sm btn-danger">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">No submissions yet.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <h4 class="alert-heading">No activities available</h4>
            <p>There are currently no activities for this subject.</p>
            <?php if ($is_instructor): ?>
                <hr>
                <p class="mb-0">As an instructor, you can add activities using the "Add Activity" button above.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<?php if ($is_instructor): ?>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload New Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date (optional)</label>
                        <input type="date" class="form-control" id="due_date" name="due_date">
                    </div>
                    <div class="mb-3">
                        <label for="activity_file" class="form-label">File (optional)</label>
                        <input type="file" class="form-control" id="activity_file" name="activity_file">
                        <div class="form-text">Allowed file types: PDF, DOC, DOCX, PPT, PPTX, ZIP, etc.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Upload Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>