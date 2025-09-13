<?php
// resources.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

// Get subject ID from URL
if (!isset($_GET['subject_id']) || !is_numeric($_GET['subject_id'])) {
    echo "Invalid subject ID.";
    exit();
}
$subject_id = intval($_GET['subject_id']);

// Fetch subject details
$stmt = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$stmt->bind_result($subject_name);
if (!$stmt->fetch()) {
    echo "Subject not found.";
    exit();
}
$stmt->close();

// Check if user is instructor for this subject
$is_instructor = false;
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT id FROM subject_instructors WHERE subject_id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $subject_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $is_instructor = true;
}
$stmt->close();

// Fetch resources for this subject
$resources = [];
$stmt = $conn->prepare("SELECT id, title, description, file_path, created_at FROM resources WHERE subject_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}
$stmt->close();

// Handle resource deletion (for instructors)
if ($is_instructor && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource'])) {
    $resource_id = intval($_POST['resource_id']);
    
    // First get file path to delete the file
    $stmt = $conn->prepare("SELECT file_path FROM resources WHERE id = ? AND subject_id = ?");
    $stmt->bind_param("ii", $resource_id, $subject_id);
    $stmt->execute();
    $stmt->bind_result($file_path);
    
    if ($stmt->fetch()) {
        // Delete file from server
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $stmt->close();
        $delete_stmt = $conn->prepare("DELETE FROM resources WHERE id = ? AND subject_id = ?");
        $delete_stmt->bind_param("ii", $resource_id, $subject_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Redirect to refresh the page
        header("Location: resources.php?subject_id=$subject_id");
        exit();
    }
    $stmt->close();
}

// Handle resource upload (for instructors)
$upload_message = '';
if ($is_instructor && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resource_file'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (empty($title)) {
        $upload_message = "<div class='alert alert-danger'>Title is required.</div>";
    } else {
        $file = $_FILES['resource_file'];
        if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
            $upload_dir = __DIR__ . '/uploads/resources/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = basename($file['name']);
            $target = $upload_dir . $subject_id . '_' . time() . '_' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target)) {
                // Save to database
                $stmt = $conn->prepare("INSERT INTO resources (subject_id, title, description, file_path, uploader_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("isssi", $subject_id, $title, $description, $target, $user_id);
                $stmt->execute();
                $stmt->close();
                
                $upload_message = "<div class='alert alert-success'>Resource uploaded successfully.</div>";
                
                // Refresh the resources list
                header("Location: resources.php?subject_id=$subject_id");
                exit();
            } else {
                $upload_message = "<div class='alert alert-danger'>Failed to move uploaded file.</div>";
            }
        } else {
            $upload_message = "<div class='alert alert-danger'>Failed to upload file.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resources - <?php echo htmlspecialchars($subject_name); ?></title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        .resource-card {
            transition: transform 0.2s;
        }
        .resource-card:hover {
            transform: translateY(-3px);
        }
        .file-icon {
            font-size: 2.5rem;
            color: #007bff;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="specific-subject.php?id=<?php echo $subject_id; ?>" class="btn btn-secondary">&larr; Back to Subject</a>
            <h2 class="mt-3">Resources for <?php echo htmlspecialchars($subject_name); ?></h2>
        </div>
        <?php if ($is_instructor): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">+ Add Resource</button>
        <?php endif; ?>
    </div>
    
    <?php echo $upload_message; ?>
    
    <?php if (count($resources) > 0): ?>
        <div class="row">
            <?php foreach ($resources as $resource): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card resource-card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="text-center mb-3">
                                <div class="file-icon">📄</div>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($resource['title']); ?></h5>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($resource['description']); ?></p>
                            <div class="mt-auto">
                                <small class="text-muted">Uploaded: <?php echo date('M j, Y', strtotime($resource['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" class="btn btn-primary btn-sm" download>Download</a>
                            <?php if ($is_instructor): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this resource?');">
                                    <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                    <button type="submit" name="delete_resource" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <h4 class="alert-heading">No resources available</h4>
            <p>There are currently no resources for this subject.</p>
            <?php if ($is_instructor): ?>
                <hr>
                <p class="mb-0">As an instructor, you can add resources using the "Add Resource" button above.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<?php if ($is_instructor): ?>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload New Resource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="resource_file" class="form-label">File</label>
                        <input type="file" class="form-control" id="resource_file" name="resource_file" required>
                        <div class="form-text">Allowed file types: PDF, DOC, DOCX, PPT, PPTX, ZIP, etc.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Upload Resource</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>