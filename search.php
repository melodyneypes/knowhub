<?php
session_start();
require 'db.php';

// Check if query parameter exists, using 'q' as that's what the form sends
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

$resources = [];
$users = [];
$subjects = [];

if (!empty($search_query)) {
    // Search resources - limit results to 10 for quick search functionality
    $sql = "SELECT * FROM resources WHERE title LIKE ? OR description LIKE ? ORDER BY title LIMIT 10";
    $stmt = $conn->prepare($sql);
    $search_term = '%' . $search_query . '%';
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    $resources = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Search users - limit results to 10
    $sql = "SELECT id, email, role FROM users WHERE email LIKE ? ORDER BY email LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Search subjects - limit results to 10
    $sql = "SELECT * FROM subjects WHERE name LIKE ? OR description LIKE ? ORDER BY name LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    $subjects = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // If no search query, show all resources/users/subjects (limited to 10 each)
    // This is for the admin quick search functionality
    $sql = "SELECT * FROM resources ORDER BY title LIMIT 10";
    $result = $conn->query($sql);
    if ($result) {
        $resources = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $sql = "SELECT id, email, role FROM users ORDER BY email LIMIT 10";
    $result = $conn->query($sql);
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    $sql = "SELECT * FROM subjects ORDER BY name LIMIT 10";
    $result = $conn->query($sql);
    if ($result) {
        $subjects = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Search Results</h2>
        
        <!-- Resources Section -->
        <h3>Resources</h3>
        <?php if (empty($resources)): ?>
            <div class="alert alert-warning">No resources found.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($resources as $resource): ?>
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($resource['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($resource['description']); ?></p>
                            <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" class="btn btn-primary">Download</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Users Section -->
        <h3>Users</h3>
        <?php if (empty($users)): ?>
            <div class="alert alert-warning">No users found.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($users as $user): ?>
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($user['email']); ?></h5>
                            <p class="card-text"><small class="text-muted"><?php echo htmlspecialchars($user['role']); ?></small></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Subjects Section -->
        <h3>Subjects</h3>
        <?php if (empty($subjects)): ?>
            <div class="alert alert-warning">No subjects found.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($subjects as $subject): ?>
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <?php 
                            // Check if 'code' key exists, if not use 'name' or 'id'
                            $subject_name = isset($subject['name']) ? $subject['name'] : 'Unnamed Subject';
                            ?>
                            <h5 class="card-title"><?php echo htmlspecialchars($subject_name); ?></h5>
                            <p class="card-text"><?php echo isset($subject['description']) ? htmlspecialchars($subject['description']) : 'No description available'; ?></p>
                            <?php if (isset($subject['year_level'])): ?>
                                <p class="card-text"><small class="text-muted">Year Level: <?php echo htmlspecialchars($subject['year_level']); ?></small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>