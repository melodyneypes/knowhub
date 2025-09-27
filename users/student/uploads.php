<?php
// filepath: e:\\CAP101-DANG FILES\\archive-system\\uploads.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit();
}
require '../../db.php';

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
                <!-- Navigation links here -->
                <li class="nav-item">
                    <a class="nav-link text-white" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="../../logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">My Uploaded Resources</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (!empty($user_uploads)): ?>
                                <?php foreach ($user_uploads as $upload): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body d-flex flex-column">
                                                <h5 class="card-title"><?php echo htmlspecialchars($upload['title']); ?></h5>
                                                <p class="card-text text-muted small">Uploaded on: <?php echo date('F j, Y', strtotime($upload['created_at'])); ?></p>
                                                <p class="card-text"><?php echo htmlspecialchars($upload['description']); ?></p>
                                            </div>
                                            <div class="card-footer d-flex flex-column">
                                                <a href="<?php echo 'http://127.0.0.1:3000/' . $upload['file_path']; ?>" class="btn btn-primary" target="_blank">Download</a>
                                                <a href="../../version_history.php?resource_id=<?php echo $upload['id']; ?>" class="btn btn-outline-primary">Version History</a>
                                                
                                                <!-- Embed ONLYOFFICE Docs viewer/editor -->
                                                <!-- [FIX] Added &resource_id= parameter to the link -->
                                                <a href="../../onlyoffice_editor.php?file=<?php echo urlencode('http://host.docker.internal:3000/' . $upload['file_path']); ?>&title=<?php echo urlencode($upload['title']); ?>&resource_id=<?php echo $upload['id']; ?>" class="btn btn-success" style="margin-top: 5px;">View and Edit in ONLYOFFICE</a>
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
