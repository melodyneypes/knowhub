<?php
session_start();
require '../../db.php';
$subject_id = intval($_GET['subject_id'] ?? 0);
$block = 'B';
$msg = '';

// Handle CSV upload for Block B
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['class_list_b'])) {
    $file = $_FILES['class_list_b'];
    if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
        $handle = fopen($file['tmp_name'], 'r');
        $added = 0;
        $line_number = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $line_number++;
            
            // Skip empty lines
            if (count($data) < 2 || (empty($data[0]) && empty($data[1]))) {
                continue;
            }
            
            // Map columns automatically based on common patterns
            $name = '';
            $email = '';
            $student_number = '';
            
            // Try to identify columns based on common patterns
            foreach ($data as $index => $value) {
                $value = trim($value);
                if (empty($value)) continue;
                
                // Check if it looks like an email
                if (strpos($value, '@') !== false && strpos($value, '.') !== false) {
                    $email = $value;
                } 
                // Check if it looks like a student number (contains mostly digits)
                else if (preg_match('/^[0-9\-]+$/', $value) && strlen($value) >= 5) {
                    $student_number = $value;
                } 
                // Assume it's a name if it's not the other two
                else if (empty($name) && strlen($value) > 1) {
                    $name = $value;
                }
            }
            
            // If we couldn't identify all fields, try positional approach
            if (empty($name) || empty($email)) {
                // Assume order is: name, email, student_number (if exists)
                if (isset($data[0])) $name = trim($data[0]);
                if (isset($data[1])) $email = trim($data[1]);
                if (isset($data[2])) $student_number = trim($data[2]);
            }
            
            if (!empty($name) && !empty($email)) {
                // Check if user exists
                $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $user_stmt->bind_param("s", $email);
                $user_stmt->execute();
                $user_stmt->bind_result($student_id);
                if ($user_stmt->fetch()) {
                    $user_stmt->close();
                    // Update user's block and student_number if they already exist
                    $update_stmt = $conn->prepare("UPDATE users SET block = ?, student_number = ? WHERE id = ?");
                    $update_stmt->bind_param("ssi", $block, $student_number, $student_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                } else {
                    $user_stmt->close();
                    // Insert new user with block information
                    $insert_stmt = $conn->prepare("INSERT INTO users (name, email, student_number, role, block) VALUES (?, ?, ?, 'student', ?)");
                    $insert_stmt->bind_param("ssss", $name, $email, $student_number, $block);
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
        $msg = "$added student(s) added to Block B.";
    } else {
        $msg = "Failed to upload file.";
    }
}

// Fetch class list for Block B
$class_list = [];
$stmt = $conn->prepare(
    "SELECT u.name, u.email, u.student_number, u.block
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

// Fetch subject name for display
$subject_stmt = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
$subject_stmt->bind_param("i", $subject_id);
$subject_stmt->execute();
$subject_stmt->bind_result($subject_name);
$subject_stmt->fetch();
$subject_stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Class List Block B - <?php echo htmlspecialchars($subject_name); ?></title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        .upload-instructions {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
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
    <a href="specific-subject.php?id=<?php echo $subject_id; ?>" class="btn btn-secondary mb-3">&larr; Back to Subject</a>
    <h2>Upload Class List for Block B - <?php echo htmlspecialchars($subject_name); ?></h2>
    <?php if ($msg): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    
    <div class="upload-instructions">
        <h5>Upload Instructions:</h5>
        <ul>
            <li>CSV file should contain student data without headers</li>
            <li>Columns will be automatically detected as: Name, Email, Student Number</li>
            <li>Supported formats: name,email or name,email,student_number or student_number,name,email, etc.</li>
            <li>Each line should contain at least name and email</li>
        </ul>
    </div>
    
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="class_list_b" class="form-label">Upload CSV File</label>
            <input type="file" name="class_list_b" accept=".csv" class="form-control" required>
            <div class="form-text">CSV format: name,email,student_number (one student per line)</div>
        </div>
        <button type="submit" class="btn btn-success">Upload</button>
    </form>
    <hr>
    <h4>Current Class List (Block B)</h4>
    <?php if (count($class_list) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Student Number</th>
                        <th>Block</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($class_list as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($student['block'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">No students enrolled in Block B.</div>
    <?php endif; ?>
    
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>