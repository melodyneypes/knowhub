<?php
// filepath: e:\CAP101-DANG FILES\archive-system\uploads.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
require 'db.php';

$user_id = $_SESSION['user']['id'];
$user_uploads = [];
$stmt = $conn->prepare("SELECT id, title, description, file_path, created_at FROM resources WHERE uploader_id = ? ORDER BY created_at DESC");
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
    <title>User's Upload</title>
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
    <a href="dashboard-student.php" class="btn btn-secondary mt-5" style="margin-left: 25px;">BACK</a>
    <!-- User Uploads Section -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2>Your Uploads</h2>
                        <a href="upload.php" class="btn btn-primary">Upload</a>
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
                                                <a href="<?php echo 'http://127.0.0.1:3000/' . $upload['file_path']; ?>" class="btn btn-primary" target="_blank">View Upload</a>
                                                <a href="version_history.php?resource_id=<?php echo $upload['id']; ?>" class="btn btn-outline-primary">Version History</a>
                                                
                                                <!-- Embed ONLYOFFICE Docs viewer/editor -->
                                                <a href="onlyoffice_editor.php?file=<?php echo urlencode('http://host.docker.internal:3000/' . $upload['file_path']); ?>&title=<?php echo urlencode($upload['title']); ?>" class="btn btn-success">Edit in ONLYOFFICE</a>
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
    </div>
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>