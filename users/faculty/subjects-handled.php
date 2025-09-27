<?php
session_start();
error_log("Session user: " . print_r($_SESSION['user'], true)); // Log session user for debugging

// Check if the user is logged in (session variable exists)
if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php'); // Redirect to login page if not logged in
    exit();
}

require '../../db.php';

$user_id = $_SESSION['user']['id'];

// --- Data Fetching Logic ---

// Fetch subjects handled by this instructor (simplified list for grouping)
$handled_subjects = [];
$stmt = $conn->prepare(
    "SELECT s.id, s.name AS title, s.description, si.block
     FROM subject_instructors si
     JOIN subjects s ON si.subject_id = s.id
     WHERE si.instructor_id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $handled_subjects[] = $row;
}
$stmt->close();

// Fetch ALL subjects with comprehensive instructor information
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

// Check approved requests (Though currently unused, keeping for potential future logic)
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

$handled_ids = array_column($handled_subjects, 'id');

foreach ($all_subjects as $subject) {
    $subject_id = $subject['id'];
    
    if (in_array($subject_id, $handled_ids)) {
        $handled_subjects_list[] = $subject;
    } else if (in_array($subject_id, $pending_requests)) {
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
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        /* General Styles */
        :root {
            --app-brand-color: #126682; /* Custom color for app brand */
        }
        body { background-color: #f8f9fa; }
        .navbar-brand { 
            font-weight: 700 !important; 
            color: var(--app-brand-color) !important;
        }

        /* Card Styles */
        .subject-card {
            transition: transform 0.2s;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
        }
        .subject-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .card-header {
            background-color: #f7f7f7;
            font-weight: 600;
        }

        /* Badge Styles */
        .handled-badge { background-color: #28a745; color: white; }
        .not-handled-badge { background-color: #ffc107; color: #212529; }
        .pending-badge { background-color: #17a2b8; color: white; }
        .approved-badge { background-color: #6f42c1; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard-instructor.php">KnowHub: A Digital Archive of BSIT Resources</a>
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard-instructor.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active fw-bold" href="subjects-handled.php" style="color: #007bff;">My Handled Subjects</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="threads.php">Forums</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="external-instructor.php">External Resources</a>
            </li>
            <li class="nav-item ms-lg-2">
                <a class="btn btn-sm btn-danger" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container mt-5">
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['message_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        // Clear the session messages after displaying
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>
    
    <h2 class="mb-4">All Subjects</h2>
    <p class="text-muted">Subjects are grouped by your assignment status. Only **Handled Subjects** allow you to create and manage resources directly.</p>
    
    <div class="mb-4">
        <input type="text" id="subjectSearch" class="form-control" placeholder="Search subjects by title...">
    </div>
    
    <h3 class="mt-4 mb-3 text-success">Handled Subjects <span class="badge bg-success rounded-pill"><?php echo count($handled_subjects_list); ?></span></h3>
    <?php if (count($handled_subjects_list) > 0): ?>
        <div class="row" id="handledSubjectsContainer">
            <?php foreach ($handled_subjects_list as $subject): ?>
                <div class="col-md-6 col-lg-4 mb-4 subject-item">
                    <div class="card subject-card h-100 border-success">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-white"><?php echo htmlspecialchars($subject['title']); ?></h5>
                            <span class="badge bg-light text-success handled-badge">Handled</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($subject['description'], 0, 100)) . (strlen($subject['description']) > 100 ? '...' : ''); ?></p>
                            
                            <?php if (!empty($subject['blocks'])): ?>
                                <p class="card-text small mb-1">
                                    <strong>Blocks:</strong> 
                                    <span class="text-primary"><?php echo htmlspecialchars($subject['blocks']); ?></span>
                                </p>
                            <?php endif; ?>
                            
                            <p class="card-text small mb-3">
                                <strong>Instructors:</strong> 
                                <span class="text-secondary"><?php echo htmlspecialchars($subject['instructors'] ?? 'N/A'); ?></span>
                            </p>
                            
                            <div class="d-flex flex-wrap gap-2 mt-auto">
                                <a href="specific-subject.php?id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm">
                                    View Resources
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-success btn-sm dropdown-toggle" type="button" id="createDropdown<?php echo $subject['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        Create Document
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="createDropdown<?php echo $subject['id']; ?>">
                                        <li><a class="dropdown-item" href="create_document.php?subject_id=<?php echo $subject['id']; ?>&type=docx">Word Document</a></li>
                                        <li><a class="dropdown-item" href="create_document.php?subject_id=<?php echo $subject['id']; ?>&type=xlsx">Spreadsheet</a></li>
                                        <li><a class="dropdown-item" href="create_document.php?subject_id=<?php echo $subject['id']; ?>&type=pptx">Presentation</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">You are not assigned to handle any subjects yet.</div>
    <?php endif; ?>
    
    <hr class="my-5">

    <h3 class="mt-5 mb-3 text-info">Subjects Pending Approval <span class="badge bg-info rounded-pill"><?php echo count($pending_approval_subjects); ?></span></h3>
    <p class="text-muted">You have requested access to edit resources in these subjects. Waiting for approval from the subject instructor(s).</p>
    <?php if (count($pending_approval_subjects) > 0): ?>
        <div class="row" id="pendingSubjectsContainer">
            <?php foreach ($pending_approval_subjects as $subject): ?>
                <div class="col-md-6 col-lg-4 mb-4 subject-item">
                    <div class="card subject-card h-100 border-info">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-white"><?php echo htmlspecialchars($subject['title']); ?></h5>
                            <span class="badge bg-light text-info pending-badge">Pending</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($subject['description'], 0, 100)) . (strlen($subject['description']) > 100 ? '...' : ''); ?></p>
                            
                            <?php if (!empty($subject['blocks'])): ?>
                                <p class="card-text small mb-1">
                                    <strong>Blocks:</strong> 
                                    <span class="text-primary"><?php echo htmlspecialchars($subject['blocks']); ?></span>
                                </p>
                            <?php endif; ?>
                            
                            <p class="card-text small mb-3">
                                <strong>Instructors:</strong> 
                                <span class="text-secondary"><?php echo htmlspecialchars($subject['instructors'] ?? 'N/A'); ?></span>
                            </p>
                            
                            <div class="d-flex flex-wrap gap-2 mt-auto">
                                <a href="specific-subject-not-handled.php?id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm">
                                    View Subject
                                </a>
                                
                                <a href="withdraw_request.php?subject_id=<?php echo $subject['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to withdraw your request to edit resources for <?php echo htmlspecialchars($subject['title']); ?>? This action is immediate.');">
                                    Withdraw Request
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

    <hr class="my-5">

    <h3 class="mt-5 mb-3 text-secondary">Other Subjects <span class="badge bg-secondary rounded-pill"><?php echo count($not_handled_subjects); ?></span></h3>
    <p class="text-muted">These subjects are not handled by you. You can view resources and **request access** to contribute.</p>
    <?php if (count($not_handled_subjects) > 0): ?>
        <div class="row" id="otherSubjectsContainer">
            <?php foreach ($not_handled_subjects as $subject): ?>
                <div class="col-md-6 col-lg-4 mb-4 subject-item">
                    <div class="card subject-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($subject['title']); ?></h5>
                            <span class="badge bg-warning text-dark not-handled-badge">Not Handled</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($subject['description'], 0, 100)) . (strlen($subject['description']) > 100 ? '...' : ''); ?></p>
                            
                            <?php if (!empty($subject['blocks'])): ?>
                                <p class="card-text small mb-1">
                                    <strong>Blocks:</strong> 
                                    <span class="text-primary"><?php echo htmlspecialchars($subject['blocks']); ?></span>
                                </p>
                            <?php endif; ?>
                            
                            <p class="card-text small mb-3">
                                <strong>Instructors:</strong> 
                                <span class="text-secondary"><?php echo htmlspecialchars($subject['instructors'] ?? 'N/A'); ?></span>
                            </p>
                            
                            <div class="d-flex flex-wrap gap-2 mt-auto">
                                <a href="specific-subject-not-handled.php?id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm">
                                    View Subject
                                </a>
                                <a href="request_access.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                    Request Edit Access
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

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
<script>
    // Simple search functionality
    document.getElementById('subjectSearch').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const subjectItems = document.querySelectorAll('.subject-item');
        
        subjectItems.forEach(item => {
            // Search in card title and body text
            const cardContent = item.textContent.toLowerCase();
            
            if (cardContent.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>