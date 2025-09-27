<?php
// resources.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit();
}

require '../../db.php';

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

// Fetch resources for this subject with download counts
$resources = [];
$stmt = $conn->prepare("SELECT r.id, r.title, r.description, r.file_path, r.created_at, r.download_count, u.name as uploader_name 
                        FROM resources r 
                        JOIN users u ON r.uploader_id = u.id 
                        WHERE r.subject_id = ? 
                        ORDER BY r.created_at DESC");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Get downloaders information
    $downloaders = [];
    $download_stmt = $conn->prepare("SELECT u.name, d.downloaded_at 
                                     FROM downloads d 
                                     JOIN users u ON d.user_id = u.id 
                                     WHERE d.resource_id = ? 
                                     ORDER BY d.downloaded_at DESC 
                                     LIMIT 5");
    $download_stmt->bind_param("i", $row['id']);
    $download_stmt->execute();
    $download_result = $download_stmt->get_result();
    while ($download_row = $download_result->fetch_assoc()) {
        $downloaders[] = $download_row;
    }
    $download_stmt->close();
    
    $row['downloaders'] = $downloaders;
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
                
                // Get the inserted resource ID
                $resource_id = $stmt->insert_id;
                $stmt->close();
                
                // Save initial version
                $stmt = $conn->prepare("INSERT INTO resource_versions (resource_id, title, description, file_path, uploader_id, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("isssii", $resource_id, $title, $description, $target, $user_id);
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
        .download-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard-instructor.php">Home</a>
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
            <li class="nav-item">
                <a class="nav-link" style="color: red;" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
            </li>
        </ul>
    </div>
</nav>
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
                                <small class="text-muted">Uploaded: <?php echo date('M j, Y', strtotime($resource['created_at'])); ?></small><br>
                                <small class="text-muted">By: <?php echo htmlspecialchars($resource['uploader_name']); ?></small><br>
                                <small class="text-muted">Downloads: <?php echo $resource['download_count'] ?? 0; ?></small>
                            </div>
                            
                            <?php if (!empty($resource['downloaders'])): ?>
                                <div class="download-info">
                                    <small class="text-muted">Recently downloaded by:</small>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($resource['downloaders'] as $downloader): ?>
                                            <li><small><?php echo htmlspecialchars($downloader['name']); ?> (<?php echo date('M j, g:ia', strtotime($downloader['downloaded_at'])); ?>)</small></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <a href="../../download.php?id=<?php echo $resource['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                            <a href="../../version_history.php?resource_id=<?php echo $resource['id']; ?>" class="btn btn-info btn-sm">History</a>
                            <?php if ($is_instructor): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this resource?');" class="ms-1">
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
<?php if (!empty($resource['downloaders'])): ?>
    <div class="download-info">
        <small class="text-muted">Recently downloaded by:</small>
            <ul class="list-unstyled mb-0">
                <?php foreach ($resource['downloaders'] as $downloader): ?>
                    <li><small><?php echo htmlspecialchars($downloader['name']); ?> (<?php echo date('M j, g:ia', strtotime($downloader['downloaded_at'])); ?>)</small></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
<div class="d-flex justify-content-center">
    <div class="col-md- mt-3">
        <h6>Comments:</h6>
        <?php
        // Fetch comments for this resource
        $comments = [];
        $comment_stmt = $conn->prepare("SELECT rc.*, u.name as user_name FROM resource_comments rc JOIN users u ON rc.user_id = u.id WHERE rc.resource_id = ? ORDER BY rc.created_at DESC");
        $comment_stmt->bind_param("i", $resource['id']);
        $comment_stmt->execute();
        $comment_result = $comment_stmt->get_result();
        while ($comment_row = $comment_result->fetch_assoc()) {
            $comments[] = $comment_row;
        }
        $comment_stmt->close();
        ?>
        
        <?php if (count($comments) > 0): ?>
            <?php foreach ($comments as $comment): ?>
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong><?php echo htmlspecialchars($comment['user_name']); ?></strong>
                        <small class="text-muted"><?php echo date('M j, Y g:ia', strtotime($comment['created_at'])); ?></small>
                    </div>
                    <p class="mb-0"><?php echo htmlspecialchars($comment['comment']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">No comments yet.</p>
        <?php endif; ?>
        
        <form method="POST" action="add_comment.php" class="mt-2">
            <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
            <div class="mb-2">
                <textarea name="comment" class="form-control" placeholder="Add a comment..." rows="2" required></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Post Comment</button>
        </form>
    </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>