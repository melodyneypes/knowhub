<?php
session_start();
error_log("Session user: " . print_r($_SESSION['user'], true)); // Log session user for debugging

// Check if the user is logged in (session variable exists)
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

require 'db.php';

$user_id = $_SESSION['user']['id'];

// Fetch subjects handled by this instructor
$handled_subjects = [];
$stmt = $conn->prepare(
    "SELECT s.id, s.name AS title, s.description, si.block
     FROM subject_instructors si
     JOIN subjects s ON si.subject_id = s.id
     WHERE si.instructor_id = ?
     ORDER BY s.year_level, s.semester, s.name, si.block"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $handled_subjects[] = $row;
}
$stmt->close();

// Fetch all subjects with instructor information
$all_subjects = [];
$stmt = $conn->prepare(
    "SELECT s.id, s.name AS title, s.description,
     GROUP_CONCAT(DISTINCT si.block ORDER BY si.block) as blocks,
     GROUP_CONCAT(DISTINCT u.name ORDER BY u.name) as instructors
     FROM subjects s
     LEFT JOIN subject_instructors si ON s.id = si.subject_id
     LEFT JOIN users u ON si.instructor_id = u.id
     GROUP BY s.id, s.name, s.description
     ORDER BY s.year_level, s.semester, s.name"
);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $all_subjects[] = $row;
}
$stmt->close();

// Check if user has requested access to any subject for resource editing
$pending_requests = [];
$stmt = $conn->prepare("SELECT subject_id FROM access_requests WHERE user_id = ? AND status = 'pending' AND request_type = 'resource_edit'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_requests[] = $row['subject_id'];
}
$stmt->close();

// Check approved requests
$approved_requests = [];
$stmt = $conn->prepare("SELECT subject_id FROM access_requests WHERE user_id = ? AND status = 'approved' AND request_type = 'resource_edit'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $approved_requests[] = $row['subject_id'];
}
$stmt->close();

// Separate handled, pending approval, and other subjects
$handled_subjects_list = [];
$pending_approval_subjects = [];
$not_handled_subjects = [];

foreach ($all_subjects as $subject) {
    $is_handled = false;
    foreach ($handled_subjects as $handled) {
        if ($handled['id'] == $subject['id']) {
            $is_handled = true;
            break;
        }
    }
    
    if ($is_handled) {
        $handled_subjects_list[] = $subject;
    } else if (in_array($subject['id'], $pending_requests)) {
        $pending_approval_subjects[] = $subject;
    } else {
        $not_handled_subjects[] = $subject;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Subjects</title>
    <!-- Bootstrap CSS -->
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        .subject-card {
            transition: transform 0.2s;
        }
        .subject-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .handled-badge {
            background-color: #28a745;
        }
        .not-handled-badge {
            background-color: #ffc107;
            color: #212529;
        }
        .pending-badge {
            background-color: #17a2b8;
            color: white;
        }
        .approved-badge {
            background-color: #6f42c1;
            color: white;
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
    <h2 class="mb-4">All Subjects</h2>
    <p class="text-muted">View all subjects. You can only manage resources for subjects you are assigned to.</p>
    
    <div class="mb-4">
        <input type="text" id="subjectSearch" class="form-control" placeholder="Search subjects...">
    </div>
    
    <h3 class="mt-4 mb-3">Handled Subjects</h3>
    <?php if (count($handled_subjects_list) > 0): ?>
        <div class="row" id="handledSubjectsContainer">
            <?php foreach ($handled_subjects_list as $subject): ?>
                <div class="col-md-6 col-lg-4 mb-4 subject-item">
                    <div class="card subject-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($subject['title']); ?></h5>
                            <span class="badge handled-badge">
                                Handled
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?php echo htmlspecialchars($subject['description']); ?></p>
                            
                            <?php if (!empty($subject['blocks'])): ?>
                                <p class="card-text">
                                    <strong>Blocks:</strong> 
                                    <?php echo htmlspecialchars($subject['blocks']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($subject['instructors'])): ?>
                                <p class="card-text">
                                    <strong>Instructors:</strong> 
                                    <?php echo htmlspecialchars($subject['instructors']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="d-flex flex-wrap gap-2">
                                <a href="specific-subject.php?id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm">
                                    View Subject
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No handled subjects found.</div>
    <?php endif; ?>

    <h3 class="mt-4 mb-3">Subjects Pending Approval</h3>
    <?php if (count($pending_approval_subjects) > 0): ?>
        <div class="row" id="pendingSubjectsContainer">
            <?php foreach ($pending_approval_subjects as $subject): ?>
                <div class="col-md-6 col-lg-4 mb-4 subject-item">
                    <div class="card subject-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($subject['title']); ?></h5>
                            <span class="badge pending-badge">
                                Pending Approval
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?php echo htmlspecialchars($subject['description']); ?></p>
                            
                            <?php if (!empty($subject['blocks'])): ?>
                                <p class="card-text">
                                    <strong>Blocks:</strong> 
                                    <?php echo htmlspecialchars($subject['blocks']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($subject['instructors'])): ?>
                                <p class="card-text">
                                    <strong>Instructors:</strong> 
                                    <?php echo htmlspecialchars($subject['instructors']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="d-flex flex-wrap gap-2">
                                <a href="specific-subject-not-handled.php?id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm">
                                    View Subject
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No subjects pending approval.</div>
    <?php endif; ?>

    <h3 class="mt-4 mb-3">Other Subjects</h3>
    <?php if (count($not_handled_subjects) > 0): ?>
        <div class="row" id="notHandledSubjectsContainer">
            <?php foreach ($not_handled_subjects as $subject): ?>
                <div class="col-md-6 col-lg-4 mb-4 subject-item">
                    <div class="card subject-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($subject['title']); ?></h5>
                            <span class="badge not-handled-badge">
                                Not Handled
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?php echo htmlspecialchars($subject['description']); ?></p>
                            
                            <?php if (!empty($subject['blocks'])): ?>
                                <p class="card-text">
                                    <strong>Blocks:</strong> 
                                    <?php echo htmlspecialchars($subject['blocks']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($subject['instructors'])): ?>
                                <p class="card-text">
                                    <strong>Instructors:</strong> 
                                    <?php echo htmlspecialchars($subject['instructors']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="d-flex flex-wrap gap-2">
                                <a href="specific-subject-not-handled.php?id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm">
                                    View Subject
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No other subjects found.</div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle search functionality
    var searchInput = document.getElementById('subjectSearch');
    var subjectItems = document.querySelectorAll('.subject-item');
    
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var filter = this.value.toLowerCase();
            
            subjectItems.forEach(function(item) {
                var text = item.textContent.toLowerCase();
                item.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
</script>
</body>
</html>