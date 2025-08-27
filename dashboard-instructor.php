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
$subjects = [];
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
    $subjects[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-light bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" style="color: white;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard-instructor.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="browse.php">Browse</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="threads.php">Forums</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="external.php">External Resources</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>
    <div class="container mt-5">
        <div class="row">
            <!-- User Profile Section (left column) -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Instructor Profile</h2>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                        <h3><?php echo $_SESSION['user']['name']; ?></h3>
                        <p><?php echo $_SESSION['user']['email']; ?></p>
                        <a href="profile_settings.php" class="btn btn-secondary">Profile Settings</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Move Subjects Handled below the row -->
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Subjects Handled</h2>
                    </div>
                    <div class="card-body">
                        <input type="text" id="subjectSearch" class="form-control mb-3" placeholder="Search subject or block...">
                        <?php if (count($subjects) > 0): ?>
                            <ul class="list-group" id="subjectsList">
                                <?php foreach ($subjects as $subject): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($subject['title']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($subject['description']); ?></small>
                                        </div>
                                        <span class="badge bg-info text-white ms-2">
                                            Block: <?php echo htmlspecialchars($subject['block']); ?>
                                        </span>
                                        <a href="specific-subject.php?id=<?php echo $subject['id']; ?>" class="btn btn-primary btn-sm ms-3">View Subject</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-info">No subjects assigned.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS (optional, for responsive navbar) -->
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('subjectSearch');
    var subjectsList = document.getElementById('subjectsList');
    if (searchInput && subjectsList) {
        searchInput.addEventListener('keyup', function() {
            var filter = this.value.toLowerCase();
            var rows = subjectsList.querySelectorAll('li');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
    </script>
</body>
</html>