<?php
session_start();

// Check if the user is logged in and is an instructor (based on your existing check)
if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit();
}

require '../../db.php'; // Database connection
require 'faculty_notify.php'; // Notification functions
// Include any necessary helper functions, like time_ago

$user_id = $_SESSION['user']['id'];

// Fetch detailed pending instructor requests (Example Query - adjust to your schema)
// This query assumes 'notifications' is used, but you might need a dedicated table
$requests = [];
$req_stmt = $conn->prepare(
    "SELECT n.*, u.name as sender_name 
    FROM notifications n 
    LEFT JOIN users u ON n.sender_id = u.id 
    WHERE n.user_id = ? AND n.type = 'instructor_request' AND n.is_read = 0
    ORDER BY n.created_at DESC"
);
$req_stmt->bind_param("i", $user_id);
$req_stmt->execute();
$req_result = $req_stmt->get_result();
while ($row = $req_result->fetch_assoc()) {
    $requests[] = $row;
}
$req_stmt->close();

// *** Add Logic for Approving/Rejecting Requests (via POST handling) ***
// This is critical for the page's functionality and requires more detailed backend logic.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Requests | KnowHub</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    </head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard-instructor.php">KnowHub: A Digital Archive of BSIT Resources</a>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard-instructor.php">Home</a>
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
                <li class="nav-item ms-lg-3">
                    <a class="btn btn-sm btn-danger" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
    <div class="container mt-5">
        <h2 class="mb-4">Pending Instructor Requests</h2>
        
        <div class="card">
            <div class="card-header">
                List of Requests
            </div>
            <div class="card-body">
                <?php if (empty($requests)): ?>
                    <div class="alert alert-success" role="alert">
                        No pending instructor requests at the moment. You're all caught up! 🎉
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($requests as $request): ?>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($request['title']); ?></h5>
                                    <p class="mb-1 small text-muted">
                                        Request by: **<?php echo htmlspecialchars($request['sender_name'] ?? 'System'); ?>** - Details: <?php echo htmlspecialchars($request['message']); ?> 
                                    </p>
                                    <small class="text-secondary"><?php echo time_ago($request['created_at']); ?></small>
                                </div>
                                <div class="btn-group" role="group">
                                    <a href="handle_request.php?action=approve&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                    <a href="handle_request.php?action=reject&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>