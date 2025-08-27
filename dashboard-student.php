<?php
session_start();

// Check if the user is logged in (session variable exists)
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// logic para makuha yung batch year 
$email = $_SESSION['user']['email'];
preg_match('/(\d{2})/', $email, $matches);
$batch_year = isset($matches[1]) ? intval($matches[1]) : null;

// Determine current month and year
$current_month = date('n');
$current_year = intval(date('Y'));

// Academic year starts in July
$academic_year = $current_year;
if ($current_month < 7) {
    // If before July, still part of previous academic year
    $academic_year--;
}

// Calculate year level
$year_level = null;
if ($batch_year) {
    // batch_year is last two digits, e.g., 22 for 2022
    $year_level = ($academic_year - (2000 + $batch_year)) + 1;
}

// Determine semester: July–December = 1st, January–June = 2nd
$current_semester = ($current_month >= 7 && $current_month <= 12) ? '1st' : '2nd';

// Fetch subjects for this year level and semester
$subjects = [];
if ($year_level && $current_semester) {
    require 'db.php';
    $stmt = $conn->prepare("SELECT name FROM subjects WHERE year_level = ? AND semester = ? ORDER BY name");
    $stmt->bind_param("is", $year_level, $current_semester);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['name'];
    }
    $stmt->close();
}

// Fetch recent uploads by the user
$user_id = $_SESSION['user']['id'];
$user_uploads = [];
require 'db.php';
$stmt = $conn->prepare("SELECT id, title, description, file_path, created_at FROM resources WHERE uploader_id = ? ORDER BY created_at DESC LIMIT 2");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_uploads[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('assets/images/psu.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: -1;
        }
        
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold"  style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources for PSU-ACC</a>
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard-student.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="browse.php">Browse Resources</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="external.php">External Resources</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="threads.php">Forums</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" style="color: red;" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
                </li>
                
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <div class="row">
            <!-- User Profile Section -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2>Student Profile</h2>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                        <h3><?php echo $_SESSION['user']['name']; ?></h3>
                        <p><?php echo $_SESSION['user']['email']; ?></p>
                        <a href="profile_settings.php" class="btn btn-secondary">Profile Settings</a>
                    </div>
                </div>
            </div>

            <!-- User Uploads Section -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2>Your Recent Uploads</h2>
                        <a href="uploads.php" class="btn btn-primary">See more</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (!empty($user_uploads)): ?>
                                <?php foreach ($user_uploads as $upload): ?>
                                    <div class="col-md-6">
                                        <div class="card mb-4 shadow-sm">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($upload['title']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($upload['description']); ?></p>
                                                <p class="card-text"><small class="text-muted">Uploaded: <?php echo htmlspecialchars($upload['created_at']); ?></small></p>
                                                <a href="<?php echo htmlspecialchars($upload['file_path']); ?>" class="btn btn-primary" target="_blank">View Upload</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info">You have not uploaded any resources yet.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br><br> <hr>
        <div class="row">
            <h2 class="text-center">Your Subjects this Semester</h2>
            <?php if (!empty($subjects)): ?>
                <?php foreach ($subjects as $subject): ?>
                    <a href="#" class="btn btn-outline-secondary text-start fs-5 mb-3"><?php echo htmlspecialchars($subject); ?></a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">Vacation, no subjects to show.</div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
</body>
</html>
