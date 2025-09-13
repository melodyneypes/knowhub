<?php
// external-instructor.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

$user_id = $_SESSION['user']['id'];
$message = '';

// Handle adding new resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource'])) {
    $subject = trim($_POST['subject']);
    $title = trim($_POST['title']);
    $url = trim($_POST['url']);
    
    if (!empty($subject) && !empty($title) && !empty($url)) {
        // Validate URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $stmt = $conn->prepare("INSERT INTO instructor_resources (user_id, subject, title, url, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $user_id, $subject, $title, $url);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Resource added successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Error adding resource. Please try again.</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Please enter a valid URL.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please fill in all fields.</div>';
    }
}

// Handle deleting resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource'])) {
    $resource_id = intval($_POST['resource_id']);
    
    // Verify the resource belongs to this user
    $stmt = $conn->prepare("DELETE FROM instructor_resources WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $resource_id, $user_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Resource deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error deleting resource.</div>';
    }
    $stmt->close();
}

// Handle search
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$filtered_resources = [];

if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM instructor_resources WHERE user_id = ? AND (subject LIKE ? OR title LIKE ?) ORDER BY subject, created_at DESC");
    $search_param = "%$search%";
    $stmt->bind_param("iss", $user_id, $search_param, $search_param);
} else {
    $stmt = $conn->prepare("SELECT * FROM instructor_resources WHERE user_id = ? ORDER BY subject, created_at DESC");
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subject = $row['subject'];
    if (!isset($filtered_resources[$subject])) {
        $filtered_resources[$subject] = [];
    }
    $filtered_resources[$subject][] = $row;
}
$stmt->close();

// Get all subjects for the dropdown
$subjects = [];
$stmt = $conn->prepare("SELECT DISTINCT name FROM subjects ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row['name'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Instructor Resources</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        .resource-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .subject-card {
            margin-bottom: 2rem;
        }
        .resource-link {
            text-decoration: none;
            color: #007bff;
        }
        .resource-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<!-- Navbar -->
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
                <a class="nav-link active" href="external-instructor.php">External Resources</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" style="color: red;" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container mt-5">
    <h2 class="mb-4">My Instructor Resources</h2>
    <p class="text-muted mb-4">Add and manage resources to help you create learning materials for your students.</p>
    
    <?php echo $message; ?>
    
    <!-- Add Resource Form -->
    <div class="card mb-5">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Add New Resource</h4>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
                            <?php endforeach; ?>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="title" class="form-label">Resource Title</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g., C Programming Tutorial" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="url" class="form-label">Resource URL</label>
                    <input type="url" class="form-control" id="url" name="url" placeholder="https://example.com/resource" required>
                </div>
                <button type="submit" name="add_resource" class="btn btn-primary">Add Resource</button>
            </form>
        </div>
    </div>
    
    <!-- Search Form -->
    <form method="GET" action="external-instructor.php" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search my resources..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button class="btn btn-primary" type="submit">Search</button>
            <button class="btn btn-secondary" type="reset" onclick="window.location.href='external-instructor.php';">Reset</button>
        </div>
    </form>
    
    <!-- Resources Display -->
    <?php if (empty($filtered_resources)): ?>
        <div class="alert alert-info">
            <?php if ($search !== ''): ?>
                No resources found matching your search.
            <?php else: ?>
                You haven't added any resources yet. Use the form above to add your first resource!
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($filtered_resources as $subject => $links): ?>
                <div class="col-md-12 subject-card">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo htmlspecialchars($subject); ?></h4>
                            <span class="badge bg-light text-dark"><?php echo count($links); ?> resource(s)</span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($links as $link): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card resource-card">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="resource-link">
                                                        <?php echo htmlspecialchars($link['title']); ?>
                                                    </a>
                                                </h5>
                                                <p class="card-text text-muted small">
                                                    Added on <?php echo date('M j, Y', strtotime($link['created_at'])); ?>
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                        Access Resource
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this resource?');">
                                                        <input type="hidden" name="resource_id" value="<?php echo $link['id']; ?>">
                                                        <button type="submit" name="delete_resource" class="btn btn-outline-danger btn-sm">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>