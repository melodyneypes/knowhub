
<?php
session_start();
require '../../db.php';

// Check if user is logged in and is a faculty member
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'instructor') {
    header('Location: ../../login.php');
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

// Check if faculty member is assigned to this subject
if ($subject_id) {
    $stmt = $conn->prepare("SELECT id FROM subject_instructors WHERE instructor_id = ? AND subject_id = ?");
    $stmt->bind_param("ii", $_SESSION['user']['id'], $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        die("You are not assigned to this subject.");
    }
    $stmt->close();
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
        $upload_dir = '../uploads/';
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
        $stmt = $conn->prepare("INSERT INTO resources (title, description, file_path, file_type, subject_id, uploader_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssii", $title, $description, $file_path, $extension, $subject_id, $_SESSION['user']['id']);
        
        if ($stmt->execute()) {
            $resource_id = $stmt->insert_id;
            $stmt->close();
            
            // Redirect to OnlyOffice editor
            $file_url = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/created_document_by_faculty' . $filename;
            header("Location: ../../onlyoffice_editor.php?file=" . urlencode($file_url) . "&title=" . urlencode($title) . "&resource_id=" . $resource_id);
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
    <title>Create Document</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header h2 {
            color: #007bff;
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
    
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2>Create New Document</h2>
                <p class="text-muted">Create a new document using OnlyOffice editor</p>
            </div>
            
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
                
                <div class="mb-3">
                    <label class="form-label">Document Type</label>
                    <div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="document_type" id="docx" value="docx" <?php echo ($document_type === 'docx') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="docx">
                                Word Document (.docx)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="document_type" id="xlsx" value="xlsx" <?php echo ($document_type === 'xlsx') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="xlsx">
                                Spreadsheet (.xlsx)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="document_type" id="pptx" value="pptx" <?php echo ($document_type === 'pptx') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="pptx">
                                Presentation (.pptx)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="subjects-handled.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create and Edit Document</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>