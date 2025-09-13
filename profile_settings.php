<?php
session_start();

// Check if the user is logged in (session variable exists)
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

require 'db.php';

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// Initialize statistics
$upload_count = 0;
$download_count = 0;
$posts_count = 0;
$replies_count = 0;
$request_count = 0;
$pending_requests = 0;
$approved_requests = 0;
$declined_requests = 0;

// Get number of uploads by user
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM resources WHERE uploader_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$upload_count = $result->fetch_assoc()['count'];
$stmt->close();

// Get number of downloads of user's uploads (assuming a downloads table exists)
// If no downloads table exists, this will remain 0
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM downloads d JOIN resources r ON d.resource_id = r.id WHERE r.uploader_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$download_count = $result->fetch_assoc()['count'];
$stmt->close();

// Get number of forum posts by user
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM threads WHERE user_id = ? IS NOT NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$posts_count = $result->fetch_assoc()['count'];
$stmt->close();

// Get number of forum replies by user
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM replies WHERE user_id = ? AND thread_id IS NOT NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$replies_count = $result->fetch_assoc()['count'];
$stmt->close();

// Get access requests statistics
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM access_requests WHERE user_id = ? GROUP BY status");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    switch ($row['status']) {
        case 'pending':
            $pending_requests = $row['count'];
            break;
        case 'approved':
            $approved_requests = $row['count'];
            break;
        case 'declined':
            $declined_requests = $row['count'];
            break;
    }
}
$stmt->close();

// Total requests
$request_count = $pending_requests + $approved_requests + $declined_requests;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
    <!-- Bootstrap CSS -->
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard-<?php echo $user_role; ?>.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" style="color: red;" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2>Profile</h2>
                    </div>
                    <div class="card-body">
                    <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="max-width: 200px; margin-left: 255px; border: 2px solid black; padding: 10px;">
                    
                    <?php
                    // Extract and format student number from email
                    $email = $_SESSION['user']['email'];
                    $formattedStudNo = '';
                    if (preg_match('/^(\d{2})([a-z]{2})(\d{4})_/', $email, $matches)) {
                        $formattedStudNo = strtoupper($matches[1] . '-' . $matches[2] . '-' . $matches[3]);
                    }
                    ?>
                    <form>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $_SESSION['user']['name']; ?>" readonly>
                        </div>

                        <?php
                        if ($_SESSION['user']['role'] == 'student') { 
                            echo '<div class="form-group">
                            <label for="stud-no">Student Number</label>
                            <input type="text" class="form-control" id="stud-no" name="stud-no" value="' . $formattedStudNo . '" readonly>
                        </div>';
                        } else {

                        }?>
                     
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $_SESSION['user']['email']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <input type="text" class="form-control" id="role" name="role" value="<?php echo $_SESSION['user']['role'];?>" readonly>
                        </div>
                    </form>
                </div>
                </div>
            </div>
             <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2>Statistics</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Uploads:</strong> <?php echo $upload_count; ?></p>
                                <p><strong>Downloads of Your Uploads:</strong> <?php echo $download_count; ?></p>
                                <p><strong>Forum Posts:</strong> <?php echo $posts_count; ?></p>
                                <p><strong>Forum Replies:</strong> <?php echo $replies_count; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Total Requests:</strong> <?php echo $request_count; ?></p>
                                <p><strong>Pending Requests:</strong> <?php echo $pending_requests; ?></p>
                                <p><strong>Approved Requests:</strong> <?php echo $approved_requests; ?></p>
                                <p><strong>Declined Requests:</strong> <?php echo $declined_requests; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>