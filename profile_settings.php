<?php
session_start();

// Check if the user is logged in (session variable exists)
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}
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
    <nav class="navbar navbar-light bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" style="color: white;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard-student.php">Home</a>
                </li>
                <li>
                    <a class="nav-link" href="profile.php">My Subjects</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="browse.php">Browse</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="forums.php">Forums</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="external.php">External Resources</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
                
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2>Profile Settings</h2>
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
                        <div class="form-group">
                            <label for="stud-no">Student Number</label>
                            <input type="text" class="form-control" id="stud-no" name="stud-no" value="<?php echo $formattedStudNo; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $_SESSION['user']['email']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <input type="text" class="form-control" id="role" name="role" value="Student" readonly>
                        </div>
                    </form>
                </div>
                </div>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2>Download Statistics</h2>
                    </div>
                    <div class="card-body">
                        <!-- Example statistics, replace with actual data -->
                        <p>Total Downloads: 10</p>
                        <p>Most Downloaded Resource: Resource Title 1</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2>Resource Access Logs</h2>
                    </div>
                    <div class="card-body">
                        <!-- Example logs, replace with actual data -->
                        <ul>
                            <li><a href="#">Resource Title 1</a> - Accessed on 2025-03-01</li>
                            <li><a href="#">Resource Title 2</a> - Accessed on 2025-03-02</li>
                            <li><a href="#">Resource Title 3</a> - Accessed on 2025-03-03</li>
                        </ul>
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