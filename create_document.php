<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Get parameters
$subject_id = $_GET['subject_id'] ?? null;
$document_type = $_GET['type'] ?? 'docx';

// Validate document type
$allowed_types = ['docx', 'xlsx', 'pptx'];
if (!in_array($document_type, $allowed_types)) {
    die("Invalid document type");
}

// Map document types to file extensions and default names
$type_info = [
    'docx' => ['extension' => 'docx', 'name' => 'New Document'],
    'xlsx' => ['extension' => 'xlsx', 'name' => 'New Spreadsheet'],
    'pptx' => ['extension' => 'pptx', 'name' => 'New Presentation']
];

$extension = $type_info[$document_type]['extension'];
$default_name = $type_info[$document_type]['name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (empty($title)) {
        $error = "Title is required";
    } else {
        // Create upload directory if not exists
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
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
        
        // Insert into resources table
        $stmt = $conn->prepare("INSERT INTO resources (title, description, file_path, id, uploader_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssii", $title, $description, $file_path, $id, $_SESSION['user']['id']);
        
        if ($stmt->execute()) {
            $resource_id = $stmt->insert_id;
            $stmt->close();
            
            // Redirect to OnlyOffice editor
            $file_url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $file_path;
            header("Location: onlyoffice_editor.php?file=" . urlencode($file_url) . "&title=" . urlencode($title) . "&resource_id=" . $resource_id);
            exit();
        } else {
            $error = "Failed to create document: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Document</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" style="color: #126682d1;" href="#">KnowHub: A Digital Archive of BSIT Resources</a>
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard-<?php echo $_SESSION['user']['role']; ?>.php">Home</a>
                </li>
                <?php if ($_SESSION['user']['role'] === 'instructor'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="subjects-handled.php">My Handled Subjects</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="browse.php">Browse</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="threads.php">Forums</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Create New <?php echo ucfirst($default_name); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label">Document Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($default_name); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            
                            <?php if ($subject_id): ?>
                                <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subject_id); ?>">
                            <?php else: ?>
                                <div class="mb-3">
                                    <label for="subject_id" class="form-label">Subject</label>
                                    <select class="form-select" id="subject_id" name="subject_id" required>
                                        <option value="">Select a subject</option>
                                        <?php
                                        // Fetch subjects based on user role
                                        if ($_SESSION['user']['role'] === 'instructor') {
                                            $stmt = $conn->prepare("SELECT s.id, s.name FROM subjects s JOIN subject_instructors si ON s.id = si.subject_id WHERE si.instructor_id = ? ORDER BY s.name");
                                            $stmt->bind_param("i", $_SESSION['user']['id']);
                                        } else {
                                            $stmt = $conn->prepare("SELECT id, name FROM subjects ORDER BY name");
                                        }
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        while ($row = $result->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo $_SESSION['user']['role'] === 'instructor' ? 'subjects-handled.php' : 'upload.php'; ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Create and Edit Document</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>