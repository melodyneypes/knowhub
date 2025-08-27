<?php
// filepath: e:\CAP101-DANG FILES\archive-system\edit_upload.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
require 'db.php';

$user_id = $_SESSION['user']['id'];
$upload_id = intval($_GET['id'] ?? 0);

// Fetch the upload
$stmt = $conn->prepare("SELECT id, title, description, file_path FROM resources WHERE id = ? AND uploader_id = ?");
$stmt->bind_param("ii", $upload_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$upload = $result->fetch_assoc();
$stmt->close();

if (!$upload) {
    die("File not found or you do not have permission to edit this file.");
}

// Handle update
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    // Handle file replacement (optional)
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = time() . '_' . basename($file['name']);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $file_path = 'uploads/' . $filename;
            // Save new version
            $stmt = $conn->prepare("INSERT INTO resource_versions (resource_id, uploader_id, title, description, file_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $upload_id, $user_id, $title, $description, $file_path);
            $stmt->execute();
            $stmt->close();
            $msg = "New version uploaded!";
        } else {
            $msg = "Failed to upload new version.";
        }
    } else {
        $file_path = $upload['file_path'];
    }

    // Update DB
    $update = $conn->prepare("UPDATE resources SET title = ?, description = ?, file_path = ? WHERE id = ? AND uploader_id = ?");
    $update->bind_param("sssii", $title, $description, $file_path, $upload_id, $user_id);
    if ($update->execute()) {
        $msg = "File updated successfully!";
        // Refresh upload info
        $upload['title'] = $title;
        $upload['description'] = $description;
        $upload['file_path'] = $file_path;
    } else {
        $msg = "Failed to update file.";
    }
    $update->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Upload</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <a href="uploads.php" class="btn btn-secondary mb-3">&larr; Back to My Uploads</a>
        <div class="card">
            <div class="card-header">
                <h2>Edit Upload</h2>
            </div>
            <div class="card-body">
                <?php if ($msg): ?>
                    <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($upload['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" required><?php echo htmlspecialchars($upload['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current File</label><br>
                        <a href="<?php echo htmlspecialchars($upload['file_path']); ?>" target="_blank" class="btn btn-outline-primary">View File</a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Replace File (optional)</label>
                        <input type="file" name="file" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>