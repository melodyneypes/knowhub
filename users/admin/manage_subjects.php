<?php
// filepath: e:\CAP101-DANG FILES\archive-system\manage_subjects.php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require '../../db.php';

// Handle subject addition
if (isset($_POST['add_subject'])) {
    $name = $_POST['name'];
    $year_level = $_POST['year_level'];
    $semester = $_POST['semester'];
    $description = $_POST['description'] ?? '';

    $stmt = $conn->prepare("INSERT INTO subjects (name, year_level, semester, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siis", $name, $year_level, $semester, $description);
    if ($stmt->execute()) {
        $success_message = "Subject added successfully.";
    } else {
        $error_message = "Failed to add subject.";
    }
    $stmt->close();
}

// Handle subject deletion
if (isset($_POST['delete_subject'])) {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success_message = "Subject deleted successfully.";
    } else {
        $error_message = "Failed to delete subject.";
    }
    $stmt->close();
}

// Get filter parameters
$search_query = $_GET['search'] ?? '';
$year_filter = $_GET['year_level'] ?? '';
$semester_filter = $_GET['semester'] ?? '';

// Build query with filters
$sql = "SELECT * FROM subjects WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_query)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $search_param = "%$search_query%";
    array_push($params, $search_param, $search_param);
    $types .= "ss";
}

if (!empty($year_filter)) {
    $sql .= " AND year_level = ?";
    array_push($params, $year_filter);
    $types .= "i";
}

if (!empty($semester_filter)) {
    $sql .= " AND semester = ?";
    array_push($params, $semester_filter);
    $types .= "s";
}

$sql .= " ORDER BY year_level, semester";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Get unique year levels and semesters for filter dropdowns
$year_levels = [];
$semester_values = [];
$stmt = $conn->prepare("SELECT DISTINCT year_level FROM subjects ORDER BY year_level");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $year_levels[] = $row['year_level'];
}
$stmt->close();

$stmt = $conn->prepare("SELECT DISTINCT semester FROM subjects ORDER BY semester");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $semester_values[] = $row['semester'];
}
$stmt->close();

// Unread notification count for sidebar
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $_SESSION['user']['id']);
$stmt->execute();
$stmt->bind_result($unread_count);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #fff; border-right: 1px solid #eee; }
        .sidebar .nav-link { color: #333; font-weight: 500; padding: 12px 20px; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #e9ecef; color: #126682d1; }
        .profile-img { max-width: 60px; margin: 20px auto 10px auto; display: block; border-radius: 50%; border: 2px solid #126682d1; }
        .main-content { padding: 40px 30px; }
        .badge { font-size: 0.9em; }
        .compact-form .form-control, .compact-form .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
            height: calc(1.5em + 0.75rem + 2px);
        }
        .compact-form .form-group {
            margin-bottom: 0.5rem;
        }
        .filter-section {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar Navigation -->
    <div class="sidebar d-flex flex-column p-3" style="width: 240px;">
        <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="profile-img">
        <h5 class="text-center mb-4"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h5>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard-admin.php"><i class="bi bi-house"></i> Dashboard</a>
            <a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i> Notifications
            </a>
            <a class="nav-link" href="manage_instructors.php"><i class="bi bi-person-badge"></i> Manage Instructors</a>
            <a class="nav-link" href="threads-admin.php"><i class="bi bi-chat-dots"></i> Forums</a>
            <a class="nav-link" href="../../browse.php"><i class="bi bi-folder"></i> Resources</a>
            <a class="nav-link" href="user_logs.php"><i class="bi bi-journal-text"></i> User Logs</a>
            <a class="nav-link" href="documents.php"><i class="bi bi-plus-circle"></i> Create Document</a>
            <a class="nav-link" href="manage_subjects.php"><i class="bi bi-gear"></i> Manage Subjects</a>
            <a class="nav-link" href="review_alumni.php"><i class="bi bi-gear"></i> Review Requesting Access</a>
            <a class="nav-link" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
        <div class="mt-auto text-center">
            <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span><br>
            <span class="text-muted">Administrator</span>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-content flex-grow-1">
        <h2 class="mb-4 fw-bold">Manage Subjects</h2>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Add New Subject Form (Compact) -->
        <div class="card mb-4">
            <div class="card-header">Add New Subject</div>
            <div class="card-body">
                <form method="POST" class="compact-form">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <input type="number" class="form-control" id="year_level" name="year_level" placeholder="Year Level" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <input type="text" class="form-control" id="semester" name="semester" placeholder="Semester" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <input type="text" class="form-control" id="description" name="description" placeholder="Description">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="add_subject" class="btn btn-primary w-100">Add</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by name or description" value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-3">
                    <label for="year_level" class="form-label">Year Level</label>
                    <select class="form-select" id="year_level" name="year_level">
                        <option value="">All Years</option>
                        <?php foreach ($year_levels as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo ($year_filter == $year) ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="semester" class="form-label">Semester</label>
                    <select class="form-select" id="semester" name="semester">
                        <option value="">All Semesters</option>
                        <?php foreach ($semester_values as $sem): ?>
                            <option value="<?php echo htmlspecialchars($sem); ?>" <?php echo ($semester_filter == $sem) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sem); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="manage_subjects.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Display Existing Subjects -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Existing Subjects</span>
                <span class="badge bg-secondary"><?php echo count($subjects); ?> subjects</span>
            </div>
            <div class="card-body">
                <?php if (!empty($subjects)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Year Level</th>
                                    <th>Semester</th>
                                    <th>Description</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subject['id']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['year_level']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['description']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['created_at']); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($subject['id']); ?>">
                                                <button type="submit" name="delete_subject" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this subject?\n\nName: <?php echo htmlspecialchars($subject['name'], ENT_QUOTES); ?>\nID: <?php echo $subject['id']; ?>\n\nThis action cannot be undone.')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No subjects found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>