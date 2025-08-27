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

// (Optional) Fetch students enrolled in this subject
$students = [];
$res = $conn->query(
    "SELECT u.name, u.email
     FROM subject_students ss
     JOIN users u ON ss.student_id = u.id
     WHERE ss.subject_id = $subject_id"
);
while ($row = $res->fetch_assoc()) {
    $students[] = $row;
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($subject_name); ?> - Subject Details</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="dashboard-instructor.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
    <div class="card mb-4">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($subject_name); ?></h2>
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($subject_description)); ?></p>
            <div class="mb-3">
                <strong>Instructors:</strong><br>
                <?php foreach ($instructors as $block => $list): ?>
                    <span class="badge bg-info text-dark me-2">Block <?php echo htmlspecialchars($block); ?>:</span>
                    <?php foreach ($list as $inst): ?>
                        <?php echo htmlspecialchars($inst['name']); ?>
                    <?php endforeach; ?>
                    <br>
                <?php endforeach; ?>
            </div>
            <div class="mb-3">
                <a href="classlist_block_a.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-primary me-2">
                    Class List Block A
                </a>
                <a href="classlist_block_b.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-primary me-2">
                    Class List Block B
                </a>
            </div>

            <div class="mb-3">
                <a href="students.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-success me-2">Students</a>
                <a href="resources.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-info me-2">Resources</a>
                <a href="quizzes.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-warning me-2">Quizzes</a>
                <a href="exams.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-danger me-2">Exams</a>
            </div>
        </div>
    </div>

    <!-- Example: Show edit/add/delete if user is instructor for this subject -->
    <?php if ($is_instructor): ?>
    <div class="card mb-4">
        <div class="card-header">
            <strong>Manage Subject Files</strong>
        </div>
        <div class="card-body">
            <a href="add_file.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-success me-2">Add File</a>
            <a href="edit_files.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-primary me-2">Edit Files</a>
            <a href="delete_files.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-danger me-2">Delete Files</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Enrolled Students</h4>
            <form method="post" enctype="multipart/form-data" class="d-flex align-items-center">
                <input type="file" name="class_list" accept=".csv" class="form-control form-control-sm me-2" required>
                <button type="submit" class="btn btn-success btn-sm">Upload Class List</button>
            </form>
        </div>
        <div class="card-body">
            <?php
            // Handle class list upload
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['class_list'])) {
                $file = $_FILES['class_list'];
                if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
                    $handle = fopen($file['tmp_name'], 'r');
                    $added = 0;
                    while (($data = fgetcsv($handle)) !== false) {
                        // CSV columns: name,email
                        if (count($data) >= 2) {
                            $name = trim($data[0]);
                            $email = trim($data[1]);
                            // Check if user exists
                            $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                            $user_stmt->bind_param("s", $email);
                            $user_stmt->execute();
                            $user_stmt->bind_result($student_id);
                            if ($user_stmt->fetch()) {
                                $user_stmt->close();
                            } else {
                                $user_stmt->close();
                                $insert_stmt = $conn->prepare("INSERT INTO users (name, email, role) VALUES (?, ?, 'student')");
                                $insert_stmt->bind_param("ss", $name, $email);
                                $insert_stmt->execute();
                                $student_id = $insert_stmt->insert_id;
                                $insert_stmt->close();
                            }
                            // Enroll student in subject if not already enrolled
                            $enroll_stmt = $conn->prepare("SELECT id FROM subject_students WHERE subject_id = ? AND student_id = ?");
                            $enroll_stmt->bind_param("ii", $subject_id, $student_id);
                            $enroll_stmt->execute();
                            $enroll_stmt->store_result();
                            if ($enroll_stmt->num_rows == 0) {
                                $enroll_stmt->close();
                                $add_stmt = $conn->prepare("INSERT INTO subject_students (subject_id, student_id) VALUES (?, ?)");
                                $add_stmt->bind_param("ii", $subject_id, $student_id);
                                $add_stmt->execute();
                                $add_stmt->close();
                                $added++;
                            } else {
                                $enroll_stmt->close();
                            }
                        }
                    }
                    fclose($handle);
                    echo "<div class='alert alert-success mb-2'>$added student(s) added to the class list.</div>";
                } else {
                    echo "<div class='alert alert-danger mb-2'>Failed to upload file.</div>";
                }
            }
            ?>
            <?php if (count($students) > 0): ?>
                <ul class="list-group">
                    <?php foreach ($students as $student): ?>
                        <li class="list-group-item">
                            <?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['email']); ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="alert alert-info">No students enrolled in this subject.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upload Class Files Section -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Upload Class Files</h4>
            <form method="post" enctype="multipart/form-data" class="d-flex align-items-center">
                <input type="file" name="class_file" class="form-control form-control-sm me-2" required>
                <button type="submit" class="btn btn-primary btn-sm">Upload File</button>
            </form>
        </div>
        <div class="card-body">
            <?php
            // Handle class file upload
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
                        echo "<div class='alert alert-success mb-2'>File uploaded successfully.</div>";
                        // Optionally, save file info to database here
                    } else {
                        echo "<div class='alert alert-danger mb-2'>Failed to move uploaded file.</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger mb-2'>Failed to upload file.</div>";
                }
            }
            ?>
            <p class="mb-0 text-muted">Accepted file types: PDF, DOCX, PPTX, ZIP, etc.</p>
        </div>
    </div>
</div>
</body>
</html>