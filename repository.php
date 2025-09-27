<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_SESSION['user']['role'];
$user_id = $_SESSION['user']['id'];

// Fetch academic years and semesters
$years = [];
$stmt = $conn->prepare("SELECT DISTINCT academic_year FROM resources WHERE academic_year IS NOT NULL ORDER BY academic_year DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $years[] = $row['academic_year'];
}
$stmt->close();

$semesters = [];
$stmt = $conn->prepare("SELECT DISTINCT semester FROM resources WHERE semester IS NOT NULL ORDER BY semester");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $semesters[] = $row['semester'];
}
$stmt->close();

// Filter parameters
$selected_year = $_GET['year'] ?? '';
$selected_semester = $_GET['semester'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query based on filters
$sql = "SELECT r.*, s.name as subject_name, u.name as uploader_name 
        FROM resources r 
        JOIN subjects s ON r.subject_id = s.id 
        JOIN users u ON r.uploader_id = u.id 
        WHERE 1=1";

$params = [];
$types = "";

if ($selected_year) {
    $sql .= " AND r.academic_year = ?";
    $params[] = $selected_year;
    $types .= "s";
}

if ($selected_semester) {
    $sql .= " AND r.semester = ?";
    $params[] = $selected_semester;
    $types .= "s";
}

if ($search_query) {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR s.name LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY r.academic_year DESC, r.semester, s.name, r.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$resources = [];
while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Repository</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        .resource-card {
            transition: transform 0.2s;
        }
        .resource-card:hover {
            transform: translateY(-3px);
        }
        .file-icon {
            font-size: 2.5rem;
            color: #007bff;
        }
        .filter-card {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Academic Repository</h2>
        <?php if ($user_role == 'admin' || $user_role == 'instructor'): ?>
            <a href="dashboard-<?php echo $user_role; ?>.php" class="btn btn-secondary">Back to Dashboard</a>
        <?php else: ?>
            <a href="dashboard-student.php" class="btn btn-secondary">Back to Dashboard</a>
        <?php endif; ?>
    </div>
    
    <!-- Filters -->
    <div class="card filter-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="year" class="form-label">Academic Year</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">All Years</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo htmlspecialchars($year); ?>" <?php echo $selected_year == $year ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="semester" class="form-label">Semester</label>
                    <select class="form-select" id="semester" name="semester">
                        <option value="">All Semesters</option>
                        <?php foreach ($semesters as $semester): ?>
                            <option value="<?php echo htmlspecialchars($semester); ?>" <?php echo $selected_semester == $semester ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($semester); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by title, description, or subject..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Repository Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?php echo count($resources); ?></h5>
                    <p class="card-text">Total Resources</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?php echo count($years); ?></h5>
                    <p class="card-text">Academic Years</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?php echo count($semesters); ?></h5>
                    <p class="card-text">Semesters</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">
                        <?php 
                        $subjects = [];
                        foreach ($resources as $resource) {
                            $subjects[$resource['subject_name']] = true;
                        }
                        echo count($subjects);
                        ?>
                    </h5>
                    <p class="card-text">Subjects</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resources -->
    <?php if (count($resources) > 0): ?>
        <div class="row">
            <?php foreach ($resources as $resource): ?>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card resource-card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="text-center mb-3">
                                <div class="file-icon">📄</div>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($resource['title']); ?></h5>
                            <p class="card-text flex-grow-1">
                                <strong>Subject:</strong> <?php echo htmlspecialchars($resource['subject_name']); ?><br>
                                <strong>Year:</strong> <?php echo htmlspecialchars($resource['academic_year'] ?? 'N/A'); ?><br>
                                <strong>Semester:</strong> <?php echo htmlspecialchars($resource['semester'] ?? 'N/A'); ?><br>
                                <?php echo htmlspecialchars(substr($resource['description'], 0, 100)) . (strlen($resource['description']) > 100 ? '...' : ''); ?>
                            </p>
                            <div class="mt-auto">
                                <small class="text-muted">Uploaded by: <?php echo htmlspecialchars($resource['uploader_name']); ?></small><br>
                                <small class="text-muted">Downloads: <?php echo $resource['download_count'] ?? 0; ?></small><br>
                                <small class="text-muted">Date: <?php echo date('M j, Y', strtotime($resource['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <a href="download.php?id=<?php echo $resource['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                            <a href="version_history.php?resource_id=<?php echo $resource['id']; ?>" class="btn btn-info btn-sm">History</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <h4 class="alert-heading">No resources found</h4>
            <p>Try adjusting your filters or search terms.</p>
        </div>
    <?php endif; ?>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>