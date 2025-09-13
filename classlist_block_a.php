<?php
session_start();
require 'db.php';
$subject_id = intval($_GET['subject_id'] ?? 0);
$block = 'A';
$msg = '';

// Handle CSV upload for Block A
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['class_list_a'])) {
    $file = $_FILES['class_list_a'];
    if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
        $handle = fopen($file['tmp_name'], 'r');
        $added = 0;
        while (($data = fgetcsv($handle)) !== false) {
            // CSV columns: name,email
            if (count($data) >= 2) {
                $name = trim($data[0]);
                $email = trim($data[1]);
                // Check if user exists
                $user_stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
                $user_stmt->bind_param("s", $name);
                $user_stmt->execute();
                $user_stmt->bind_result($student_id);
                if ($user_stmt->fetch()) {
                    $user_stmt->close();
                    // Update user's block if they already exist
                    $update_stmt = $conn->prepare("UPDATE users SET block = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $block, $student_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                } else {
                    $user_stmt->close();
                    // Insert new user with block information
                    $insert_stmt = $conn->prepare("INSERT INTO users (name, email, role, block) VALUES (?, ?, 'student', ?)");
                    $insert_stmt->bind_param("sss", $name, $email, $block);
                    $insert_stmt->execute();
                    $student_id = $insert_stmt->insert_id;
                    $insert_stmt->close();
                }
                // Enroll student in subject/block if not already enrolled
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
        $msg = "$added student(s) added to Block A.";
    } else {
        $msg = "Failed to upload file.";
    }
}

// Fetch class list for Block A
$class_list = [];
$stmt = $conn->prepare(
    "SELECT u.name, u.email, u.block
     FROM subject_students ss
     JOIN users u ON ss.student_id = u.id
     WHERE ss.subject_id = ? AND u.block = ?"
);
$stmt->bind_param("is", $subject_id, $block);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $class_list[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Class List Block A</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="specific-subject.php?id=<?php echo $subject_id; ?>" class="btn btn-secondary mb-3">&larr; Back to Subject</a>
    <h2>Upload Class List for Block A</h2>
    <?php if ($msg): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="class_list_a" class="form-label">Upload CSV File</label>
            <input type="file" name="class_list_a" accept=".csv" class="form-control" required>
            <div class="form-text">CSV format: name,email (one student per line)</div>
        </div>
        <button type="submit" class="btn btn-success">Upload</button>
    </form>
    <hr>
    <h4>Current Class List (Block A)</h4>
    <?php if (count($class_list) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Block</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($class_list as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['block'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">No students enrolled in Block A.</div>
    <?php endif; ?>
</body>
</html>