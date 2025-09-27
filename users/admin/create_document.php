<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require '../../db.php';

// Handle document creation
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $document_type = $_POST['document_type'] ?? 'docx';
    
    if ($title) {
        // Create upload directory if not exists
        $upload_dir = '../../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $extension = $document_type;
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $title) . '.' . $extension;
        $file_path = $upload_dir . $filename;
        
        // Create a basic template file based on type
        if ($extension === 'docx') {
            // Create a minimal DOCX file
            $content = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:body>
<w:p>
<w:r>
<w:t>' . htmlspecialchars($title) . '</w:t>
</w:r>
</w:p>
</w:body>
</w:document>';
            file_put_contents($file_path, $content);
        } else if ($extension === 'xlsx') {
            // Create a minimal XLSX file
            $content = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheets>
<sheet name="Sheet1" sheetId="1"/>
</sheets>
</workbook>';
            file_put_contents($file_path, $content);
        } else if ($extension === 'pptx') {
            // Create a minimal PPTX file
            $content = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<presentation xmlns="http://schemas.openxmlformats.org/presentationml/2006/main">
<sldMasterIdLst>
<sldMasterId id="2147483648"/>
</sldMasterIdLst>
</presentation>';
            file_put_contents($file_path, $content);
        }
        
        // Insert into admin_documents table (no subject_id needed)
        $stmt = $conn->prepare("INSERT INTO admin_documents (title, description, file_path, file_type, uploader_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssi", $title, $description, $file_path, $extension, $_SESSION['user']['id']);
        
        if ($stmt->execute()) {
            $document_id = $stmt->insert_id;
            $stmt->close();
            
            // Redirect to OnlyOffice editor
            $file_url = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $filename;
            header("Location: ../../onlyoffice_editor.php?file=" . urlencode($file_url) . "&title=" . urlencode($title) . "&document_id=" . $document_id . "&type=admin");
            exit();
        } else {
            $message = "Failed to create document: " . $conn->error;
        }
    } else {
        $message = "Please fill in the title.";
    }
}

// Unread notification count for sidebar
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $_SESSION['user']['id']);
$stmt->execute();
$stmt->bind_result($unread_count);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Document</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #fff; border-right: 1px solid #eee; }
        .sidebar .nav-link { color: #333; font-weight: 500; padding: 12px 20px; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #e9ecef; color: #126682d1; }
        .profile-img { max-width: 60px; margin: 20px auto 10px auto; display: block; border-radius: 50%; border: 2px solid #126682d1; }
        .main-content { padding: 40px 30px; }
        .badge { font-size: 0.9em; }
    </style>
</head>
<body>
<div class="d-flex">
     <!-- Sidebar Navigation -->
    <div class="sidebar d-flex flex-column p-3" style="width: 240px;">
        <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="profile-img">
        <h5 class="text-center mb-4"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h5>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard-admin.php"><i class="bi bi-house"></i> Dashboard</a>
            <a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i> Notifications
            </a>
            <a class="nav-link" href="manage_instructors.php"><i class="bi bi-person-badge"></i> Manage Instructors</a>
            <a class="nav-link" href="threads-admin.php"><i class="bi bi-chat-dots"></i> Forums</a>
            <a class="nav-link" href="../../browse.php"><i class="bi bi-folder"></i> Resources</a>
            <a class="nav-link" href="user_logs.php"><i class="bi bi-journal-text"></i> User Logs</a>
            <a class="nav-link" href="documents.php"><i class="bi bi-plus-circle"></i> Create Document</a>
            <a class="nav-link" href="manage_subjects.php"><i class="bi bi-gear"></i> Manage Subjects</a>
            <a class="nav-link" href="review_alumni.php"><i class="bi bi-gear"></i> Review Requesting Access</a>
            <a class="nav-link" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
        <div class="mt-auto text-center">
            <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span><br>
            <span class="text-muted">Administrator</span>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-content flex-grow-1">
        <h2 class="mb-4 fw-bold">Create Document</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post" class="card p-4 shadow-sm" style="max-width: 700px;">
            <div class="mb-3">
                <label for="title" class="form-label">Document Title</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Document Type</label>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="document_type" id="docx" value="docx" checked>
                        <label class="form-check-label" for="docx">
                            Word Document (.docx)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="document_type" id="xlsx" value="xlsx">
                        <label class="form-check-label" for="xlsx">
                            Spreadsheet (.xlsx)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="document_type" id="pptx" value="pptx">
                        <label class="form-check-label" for="pptx">
                            Presentation (.pptx)
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="documents.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create and Edit Document</button>
            </div>
        </form>
    </div>
</div>
<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>