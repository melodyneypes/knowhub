<?php
// filepath: e:\CAP101-DANG FILES\archive-system\manage_instructors.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require 'db.php';

// Fetch all subjects
$subjects = [];
$subj_result = $conn->query("SELECT id, name FROM subjects ORDER BY year_level, semester, name");
while ($row = $subj_result->fetch_assoc()) {
    $subjects[] = $row;
}

// Fetch all instructors
$instructors = [];
$inst_result = $conn->query("SELECT id, name, email FROM users WHERE role = 'instructor' ORDER BY name");
while ($row = $inst_result->fetch_assoc()) {
    $instructors[] = $row;
}

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject_id'], $_POST['instructor_id'], $_POST['block'])) {
    $subject_id = intval($_POST['subject_id']);
    $instructor_id = intval($_POST['instructor_id']);
    $block = $_POST['block'];

    if ($block === 'AB') {
        // Assign to both blocks if not already assigned
        foreach (['A', 'B'] as $b) {
            $check = $conn->prepare("SELECT id FROM subject_instructors WHERE subject_id = ? AND block = ?");
            $check->bind_param("is", $subject_id, $b);
            $check->execute();
            $check->store_result();
            if ($check->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO subject_instructors (subject_id, instructor_id, assigned_at, block) VALUES (?, ?, NOW(), ?)");
                $stmt->bind_param("iis", $subject_id, $instructor_id, $b);
                $stmt->execute();
                $stmt->close();
            }
            $check->close();
        }
        $msg = "Instructor assigned to both Block A and Block B!";
    } else {
        // Assign to single block
        $check = $conn->prepare("SELECT id FROM subject_instructors WHERE subject_id = ? AND block = ?");
        $check->bind_param("is", $subject_id, $block);
        $check->execute();
        $check->store_result();
        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO subject_instructors (subject_id, instructor_id, assigned_at, block) VALUES (?, ?, NOW(), ?)");
            $stmt->bind_param("iis", $subject_id, $instructor_id, $block);
            $stmt->execute();
            $stmt->close();
            $msg = "Instructor assigned successfully!";
        } else {
            $msg = "This block is already assigned for this subject.";
        }
        $check->close();
    }
}

// Handle delete assignment
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_stmt = $conn->prepare("DELETE FROM subject_instructors WHERE id = ?");
    $del_stmt->bind_param("i", $delete_id);
    $del_stmt->execute();
    $del_stmt->close();
    header("Location: manage_instructors.php");
    exit();
}

// Handle reset all assignments
if (isset($_GET['reset_all']) && $_GET['reset_all'] == 1) {
    $conn->query("TRUNCATE TABLE subject_instructors");
    header("Location: manage_instructors.php");
    exit();
}

// Fetch current assignments
$assignments = [];
$res = $conn->query("SELECT si.id, s.name AS subject, u.name AS instructor, si.block
    FROM subject_instructors si
    JOIN subjects s ON si.subject_id = s.id
    JOIN users u ON si.instructor_id = u.id
    ORDER BY s.year_level, s.semester, s.name");
while ($row = $res->fetch_assoc()) {
    $assignments[] = $row;
}

// Fetch all subjects that are NOT yet assigned for the selected block
$assigned_subjects = [];
$res = $conn->query("SELECT subject_id, block FROM subject_instructors");
while ($row = $res->fetch_assoc()) {
    $assigned_subjects[$row['subject_id'] . '_' . $row['block']] = true;
}

// Count assignments per subject for both blocks
$subject_block_counts = [];
$res = $conn->query("SELECT subject_id, block FROM subject_instructors");
while ($row = $res->fetch_assoc()) {
    $sid = $row['subject_id'];
    $blk = $row['block'];
    if (!isset($subject_block_counts[$sid])) $subject_block_counts[$sid] = [];
    $subject_block_counts[$sid][$blk] = true;
}

// Fetch all assignments
$assignments_raw = [];
$res = $conn->query("SELECT si.id, s.name AS subject, u.name AS instructor, si.block, si.subject_id, si.instructor_id
    FROM subject_instructors si
    JOIN subjects s ON si.subject_id = s.id
    JOIN users u ON si.instructor_id = u.id
    ORDER BY s.year_level, s.semester, s.name, u.name, si.block");
while ($row = $res->fetch_assoc()) {
    $key = $row['subject_id'] . '_' . $row['instructor_id'];
    if (!isset($assignments_raw[$key])) {
        $assignments_raw[$key] = [
            'id' => $row['id'], // Use the first assignment id for delete
            'subject' => $row['subject'],
            'instructor' => $row['instructor'],
            'blocks' => [],
        ];
    }
    $assignments_raw[$key]['blocks'][] = $row['block'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subject Instructors</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
     <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: #fff;
        }
        .sidebar .nav-link {
            color: #fff;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #495057;
            color: #ffc107;
        }
        .profile-img {
            max-width: 120px;
            margin: 20px auto 10px auto;
            display: block;
            border-radius: 50%;
            border: 3px solid #fff;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar d-flex flex-column p-3" style="width: 250px;">
            <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3 mx-auto" style="max-width: 70px;">
        <h5 class="text-center mb-4"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h5>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard-admin.php"><i class="bi bi-house"></i> Home</a>
            <a class="nav-link" href="profile_settings.php"><i class="bi bi-person"></i> Edit Profile</a>
            <a class="nav-link" href="admin_notifications.php"><i class="bi bi-bell"></i> Notifications</a>
            <a class="nav-link active" href="manage_instructors.php"><i class="bi bi-people"></i> Manage Subject Instructors</a>
            <a class="nav-link" href="threads.php"><i class="bi bi-chat-dots"></i> Forums</a>
            <a class="nav-link" href="browse.php"><i class="bi bi-folder"></i> Resources</a>
            <a class="nav-link" href="admin_user_logs.php"><i class="bi bi-journal-text"></i> User Logs</a>
            <a class="nav-link" href="logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
        </div>
        <div class="main-content flex-1">
            <div class="container-fluid py-4">
                <main class="col-md-12 ms-sm-auto px-md-1">
                    <div class="container mt-5">
                        <h2>Assign Subjects to Instructors</h2>
                        <?php if (isset($msg)): ?>
                            <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
                        <?php endif; ?>
                        <!-- Add Block selection to the form -->
                <form method="post" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="subject_id" class="form-label">Subject</label>
                        <select name="subject_id" id="subject_id" class="form-select" required>
                            <option value="">Select subject</option>
                            <?php
                            $block_selected = isset($_POST['block']) ? $_POST['block'] : 'A';
                            foreach ($subjects as $subject):
                                $sid = $subject['id'];
                                $blockA = isset($subject_block_counts[$sid]['A']);
                                $blockB = isset($subject_block_counts[$sid]['B']);
                                if (!($blockA && $blockB)):
                            ?>
                            <option value="<?php echo $sid; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="instructor_id" class="form-label">Instructor</label>
                        <select name="instructor_id" id="instructor_id" class="form-select" required>
                            <option value="">Select instructor</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?php echo $instructor['id']; ?>"><?php echo htmlspecialchars($instructor['name'] . " ({$instructor['email']})"); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="block" class="form-label">Block</label>
                        <select name="block" id="block" class="form-select" required onchange="this.form.submit()">
                            <option value="A" <?php if(isset($_POST['block']) && $_POST['block']=='A') echo 'selected'; ?>>Block A</option>
                            <option value="B" <?php if(isset($_POST['block']) && $_POST['block']=='B') echo 'selected'; ?>>Block B</option>
                            <option value="AB" <?php if(isset($_POST['block']) && $_POST['block']=='AB') echo 'selected'; ?>>Block A & B</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Assign</button>
                    </div>
                </form>
                        <form method="get" onsubmit="return confirm('Are you sure you want to reset all assignments?');">
                            <button type="submit" name="reset_all" value="1" class="btn btn-danger mb-2">Reset All Assignments</button>
                        </form>
                        <h4>All Assignments</h4>
                        <!-- Add Search box -->
                <div class="mb-3">
                    <input type="text" id="assignmentSearch" class="form-control" placeholder="Search by subject or instructor...">
                </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Instructor</th>
                                    <th>Block</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments_raw as $a): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($a['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($a['instructor']); ?></td>
                                        <td>
                                            <?php
                                            sort($a['blocks']);
                                            if (in_array('A', $a['blocks']) && in_array('B', $a['blocks'])) {
                                                echo 'Block A & B';
                                            } else {
                                                echo 'Block ' . implode(' & Block ', $a['blocks']);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="manage_instructors.php?delete_id=<?php echo $a['id']; ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this assignment?');">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </main>
            </div>
        </div>
    </div>
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
<script>
document.getElementById('assignmentSearch').addEventListener('keyup', function() {
    var filter = this.value.toLowerCase();
    var rows = document.querySelectorAll('table.table tbody tr');
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>
</body>
</html>