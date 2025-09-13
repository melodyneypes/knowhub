<?php
// browse.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// Search parameters
$search_query = $_GET['search'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$tag_filter = $_GET['tag'] ?? '';
$instructor_filter = $_GET['instructor'] ?? '';

// Fetch all subjects
$stmt = $conn->prepare("SELECT DISTINCT id, name FROM subjects ORDER BY year_level");
$stmt->execute();
$subjects_result = $stmt->get_result();
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Fetch all instructors
$stmt = $conn->prepare("SELECT DISTINCT id, name FROM users WHERE role = 'instructor' ORDER BY name");
$stmt->execute();
$instructors_result = $stmt->get_result();
$instructors = [];
while ($row = $instructors_result->fetch_assoc()) {
    $instructors[] = $row;
}
$stmt->close();

// Fetch all tags
$stmt = $conn->prepare("SELECT DISTINCT tag_name FROM tags ORDER BY tag_name");
$stmt->execute();
$tags_result = $stmt->get_result();
$tags = [];
while ($row = $tags_result->fetch_assoc()) {
    $tags[] = $row;
}
$stmt->close();

// Build the query for resources
$sql = "SELECT r.*, u.name as uploader_name, s.name, s.id 
        FROM resources r 
        JOIN users u ON r.uploader_id = u.id 
        LEFT JOIN subjects s ON r.subject_id = s.id 
        WHERE 1=1";

$params = [];
$types = "";

// Add search condition
if (!empty($search_query)) {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $search_param = "%$search_query%";
    array_push($params, $search_param, $search_param);
    $types .= "ss";
}

// Add subject filter
if (!empty($subject_filter)) {
    $sql .= " AND s.id = ?";
    $params[] = $subject_filter;
    $types .= "s";
}

// Add tag filter
if (!empty($tag_filter)) {
    $sql .= " AND r.id IN (SELECT resource_id FROM resource_tags WHERE tag_name = ?)";
    $params[] = $tag_filter;
    $types .= "s";
}

// Add instructor filter
if (!empty($instructor_filter)) {
    $sql .= " AND s.id IN (SELECT subject_id FROM subject_instructors WHERE instructor_id = ?)";
    $params[] = $instructor_filter;
    $types .= "i";
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resources_result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Resources - KnowHub</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .main-container {
            margin-top: 2rem;
        }
        .search-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .resource-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }
        .resource-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .filter-sidebar {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
        }
        .tag {
            display: inline-block;
            background-color: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .subject-badge {
            background-color: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .instructor-badge {
            background-color: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .no-results {
            text-align: center;
            padding: 3rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
         .access-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        .access-granted {
            background-color: #d4edda;
            color: #155724;
        }
        .access-denied {
            background-color: #f8d7da;
            color: #721c24;
        }
        .access-pending {
            background-color: #fff3cd;
            color: #856404;
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
                    <a class="nav-link" href="dashboard-<?php echo $user_role === 'student' ? 'student' : 'admin'; ?>.php">Home</a>
                </li>
                <?php if ($user_role === 'student'): ?>
                <li>
                    <a class="nav-link" href="dashboard-student.php">My Subjects</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link active" href="browse.php">Browse</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="threads.php">Forums</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="external.php">External Resources</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" style="color: red;" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container main-container">
        <div class="search-container">
            <h2 class="mb-4">Browse Resources</h2>
            <form method="GET" action="browse.php">
                <div class="row">
                    <div class="col-md-9">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" name="search" placeholder="Search by title, description, or keywords..." value="<?php echo htmlspecialchars($search_query); ?>">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-secondary w-100" type="button" data-bs-toggle="modal" data-bs-target="#filtersModal">Advanced Filters</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="filter-sidebar">
                    <h5>Filters</h5>
                    <form method="GET" action="browse.php">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject" class="form-select">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo htmlspecialchars($subject['id']); ?>" <?php echo ($subject_filter === $subject['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['id'] . ' - ' . $subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tag</label>
                            <select name="tag" class="form-select">
                                <option value="">All Tags</option>
                                <?php foreach ($tags as $tag): ?>
                                    <option value="<?php echo htmlspecialchars($tag['tag']); ?>" <?php echo ($tag_filter === $tag['tag']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tag['tag']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Instructor</label>
                            <select name="instructor" class="form-select">
                                <option value="">All Instructors</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo htmlspecialchars($instructor['id']); ?>" <?php echo ($instructor_filter == $instructor['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($instructor['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        <a href="browse.php" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                    </form>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>
                        <?php 
                        if (!empty($search_query)) {
                            echo "Search Results for \"" . htmlspecialchars($search_query) . "\"";
                        } else {
                            echo "All Resources";
                        }
                        ?>
                    </h4>
                    <span class="text-muted">
                        <?php echo $resources_result->num_rows; ?> resource(s) found
                    </span>
                </div>
                
                <?php if ($resources_result->num_rows > 0): ?>
                    <?php while ($resource = $resources_result->fetch_assoc()): ?>
                        <div class="resource-card">
                            <div class="d-flex justify-content-between">
                                <h5><?php echo htmlspecialchars($resource['title']); ?></h5>
                                <span class="text-muted"><?php echo date('M j, Y', strtotime($resource['created_at'])); ?></span>
                            </div>
                            
                            <p><?php echo htmlspecialchars($resource['description']); ?></p>
                            
                            <div class="mb-2">
                                <?php if (!empty($resource['id'])): ?>
                                    <span class="subject-badge">
                                        <?php echo htmlspecialchars($resource['id']); ?>
                                        <?php if (!empty($resource['subject_name'])): ?>
                                            - <?php echo htmlspecialchars($resource['name']); ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($resource['uploader_name'])): ?>
                                    <span class="ms-2">Uploaded by: <?php echo htmlspecialchars($resource['uploader_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php
                            // Fetch tags for this resource
                            $tag_stmt = $conn->prepare("SELECT tag FROM resource_tags WHERE resource_id = ?");
                            $tag_stmt->bind_param("i", $resource['id']);
                            $tag_stmt->execute();
                            $tags_result = $tag_stmt->get_result();
                            ?>
                            
                            <?php if ($tags_result->num_rows > 0): ?>
                                <div class="mb-3">
                                    <?php while ($tag = $tags_result->fetch_assoc()): ?>
                                        <span class="tag"><?php echo htmlspecialchars($tag['tag']); ?></span>
                                    <?php endwhile; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php
                            $tag_stmt->close();
                            
                            // Fetch instructors for this subject
                            if (!empty($resource['subject_id'])) {
                                $inst_stmt = $conn->prepare("SELECT u.name FROM subject_instructors si JOIN users u ON si.instructor_id = u.id WHERE si.subject_id = ?");
                                $inst_stmt->bind_param("i", $resource['subject_id']);
                                $inst_stmt->execute();
                                $instructors_result = $inst_stmt->get_result();
                            }
                            ?>
                            
                            <?php if (!empty($resource['subject_id']) && $instructors_result->num_rows > 0): ?>
                                <div class="mb-3">
                                    <strong>Instructors:</strong>
                                    <?php while ($instructor = $instructors_result->fetch_assoc()): ?>
                                        <span class="instructor-badge ms-1"><?php echo htmlspecialchars($instructor['name']); ?></span>
                                    <?php endwhile; ?>
                                    <?php if ($user_role === 'student'): ?>
                                        <button class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#requestAccessModal" 
                                                data-subject-id="<?php echo $resource['subject_id']; ?>" 
                                                data-subject-name="<?php echo htmlspecialchars($resource['subject_name']); ?>">
                                            Request Access
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($resource['subject_id'])): ?>
                                <?php $inst_stmt->close(); ?>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>File Type:</strong> 
                                    <span class="ms-1"><?php echo htmlspecialchars(pathinfo($resource['file_path'], PATHINFO_EXTENSION)); ?></span>
                                </div>
                                <a href="download.php?id=<?php echo $resource['id']; ?>" class="btn btn-primary">Download</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <h4>No resources found</h4>
                        <p>Try adjusting your search or filter criteria</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Request Access Modal -->
    <div class="modal fade" id="requestAccessModal" tabindex="-1" aria-labelledby="requestAccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestAccessModalLabel">Request Access to Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="request_access.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="subject_id" id="subjectId">
                        <p>You are requesting access to: <strong id="subjectName"></strong></p>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Request</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Advanced Filters Modal -->
    <div class="modal fade" id="filtersModal" tabindex="-1" aria-labelledby="filtersModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filtersModalLabel">Advanced Filters</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="browse.php">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject" class="form-select">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo htmlspecialchars($subject['id']); ?>" <?php echo ($subject_filter === $subject['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['id'] . ' - ' . $subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tag</label>
                            <select name="tag" class="form-select">
                                <option value="">All Tags</option>
                                <?php foreach ($tags as $tag): ?>
                                    <option value="<?php echo htmlspecialchars($tag['tag']); ?>" <?php echo ($tag_filter === $tag['tag']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tag['tag']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Instructor</label>
                            <select name="instructor" class="form-select">
                                <option value="">All Instructors</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo htmlspecialchars($instructor['id']); ?>" <?php echo ($instructor_filter == $instructor['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($instructor['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        // Handle request access modal
        var requestAccessModal = document.getElementById('requestAccessModal');
        requestAccessModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var subjectId = button.getAttribute('data-subject-id');
            var subjectName = button.getAttribute('data-subject-name');
            
            var modal = this;
            modal.querySelector('#subjectId').value = subjectId;
            modal.querySelector('#subjectName').textContent = subjectName;
        });
        
        // Preserve search query when applying filters
        document.querySelectorAll('form[method="GET"]').forEach(function(form) {
            var searchInput = form.querySelector('input[name="search"]');
            if (!searchInput && document.querySelector('input[name="search"]')) {
                var searchValue = document.querySelector('input[name="search"]').value;
                if (searchValue) {
                    var hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'search';
                    hiddenInput.value = searchValue;
                    form.appendChild(hiddenInput);
                }
            }
        });
    </script>
</body>
</html>