<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'instructor') {
    header('Location: ../../login.php');
    exit();
}

require '../../db.php';

$user_id = $_SESSION['user']['id'];

// 1. Get subject ID from URL and validation
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid subject ID.";
    exit();
}
$subject_id = intval($_GET['id']);

// 2. Fetch subject details
$stmt = $conn->prepare("SELECT name, description FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$stmt->bind_result($subject_name, $subject_description);
if (!$stmt->fetch()) {
    echo "Subject not found.";
    exit();
}
$stmt->close();

// 3. Define the current file manager context
$current_folder_id = isset($_GET['folder']) ? intval($_GET['folder']) : null;
$current_file_id = isset($_GET['file']) ? intval($_GET['file']) : null; // Check for file viewing

// --- DATABASE FUNCTIONS (ADAPTED FOR SUBJECT CONTEXT) ---

/**
 * Fetches all folders for the subject, optionally filtered by parent ID.
 * Assumes file_manager table has a subject_id column.
 */
function getFolders($conn, $subjectId, $parentId) {
    $folders = [];
    $sql = "SELECT id, name FROM file_manager WHERE item_type = 'folder' AND id = ? AND parent_id " . ($parentId ? "= ?" : "IS NULL") . " ORDER BY name";
    
    $stmt = $conn->prepare($sql);
    if ($parentId) {
        $stmt->bind_param("ii", $subjectId, $parentId);
    } else {
        $stmt->bind_param("i", $subjectId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $folders[] = $row;
    }
    $stmt->close();
    return $folders;
}

/**
 * Fetches all files for the subject, optionally filtered by parent ID.
 */
function getFiles($conn, $subjectId, $parentId) {
    $files = [];
    $sql = "SELECT id, name, file_path FROM file_manager WHERE item_type = 'file' AND id = ? AND parent_id " . ($parentId ? "= ?" : "IS NULL") . " ORDER BY name";
    
    $stmt = $conn->prepare($sql);
    if ($parentId) {
        $stmt->bind_param("ii", $subjectId, $parentId);
    } else {
        $stmt->bind_param("i", $subjectId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
    $stmt->close();
    return $files;
}


/**
 * Recursively builds the HTML structure for the folder tree, including files.
 */
function getFolderTreeHtml($conn, $subjectId, $parentId = null, $currentFolderId = null, $currentFileId = null) {
    $folders = getFolders($conn, $subjectId, $parentId);
    $files = getFiles($conn, $subjectId, $parentId);
    $html = '';

    if (!empty($folders) || !empty($files)) {
        // Simple logic to check if this list should be initially expanded
        $is_expanded = false;
        if ($parentId === null || $parentId == $currentFolderId) {
            $is_expanded = true;
        } elseif ($currentFolderId !== null) {
             // Check if the current folder is a descendant of this parent
             $temp_id = $currentFolderId;
             while ($temp_id !== null) {
                 $stmt = $conn->prepare("SELECT parent_id FROM file_manager WHERE id = ? AND subject_id = ?");
                 $stmt->bind_param("ii", $temp_id, $subjectId);
                 $stmt->execute();
                 $result = $stmt->get_result();
                 $row = $result->fetch_assoc();
                 $stmt->close();
                 
                 if ($row && $row['parent_id'] == $parentId) {
                     $is_expanded = true;
                     break;
                 }
                 $temp_id = $row ? $row['parent_id'] : null;
             }
        }
        
        $html .= '<ul class="list-unstyled ' . ($parentId !== null ? 'ps-3 collapse' : 'mt-2') . ' ' . ($is_expanded ? 'show' : '') . '" id="folder-' . ($parentId ?? 'root') . '">';

        // --- RENDER FOLDERS ---
        foreach ($folders as $folder) {
            $folder_id = $folder['id'];
            $is_current = $folder_id == $currentFolderId;
            $has_children = !empty(getFolders($conn, $subjectId, $folder_id)) || !empty(getFiles($conn, $subjectId, $folder_id));
            
            // Re-check expansion status for the individual folder
            $is_folder_expanded = $is_current || ($is_expanded && $parentId !== null);

            $html .= '<li class="mb-1">';
            $html .= '<div class="d-flex align-items-center">';
            
            // Toggle button
            if ($has_children) {
                $html .= '<a class="text-decoration-none me-1 text-muted" data-bs-toggle="collapse" href="#folder-' . $folder_id . '" role="button" aria-expanded="' . ($is_folder_expanded ? 'true' : 'false') . '" aria-controls="folder-' . $folder_id . '">';
                $html .= '<i class="bi bi-chevron-right fs-6" style="transition: transform 0.15s; ' . ($is_folder_expanded ? 'transform: rotate(90deg);' : '') . '"></i>';
                $html .= '</a>';
            } else {
                $html .= '<span class="me-1" style="width: 1rem;"></span>';
            }

            // Folder link
            $html .= '<a href="specific-subject.php?id=' . $subjectId . '&folder=' . $folder_id . '" class="text-truncate text-decoration-none ' . ($is_current ? 'fw-bold text-primary' : 'text-dark') . '">';
            $html .= '<i class="bi bi-folder me-2"></i>';
            $html .= htmlspecialchars($folder['name']);
            $html .= '</a>';
            $html .= '</div>';
            
            // Recursively generate children
            $html .= getFolderTreeHtml($conn, $subjectId, $folder_id, $currentFolderId, $currentFileId);
            
            $html .= '</li>';
        }
        
        // --- RENDER FILES ---
        foreach ($files as $file) {
            $is_current_file = $file['id'] == $currentFileId;
            $html .= '<li class="mb-1 ps-2">'; // Indent files slightly
            $html .= '<div class="d-flex align-items-center">';
            
            // File link
            $html .= '<a href="specific-subject.php?id=' . $subjectId . '&file=' . $file['id'] . '" class="text-truncate text-decoration-none ' . ($is_current_file ? 'fw-bold text-success' : 'text-dark') . '">';
            $html .= '<i class="bi bi-file-earmark-text me-2"></i>'; 
            $html .= htmlspecialchars($file['name']);
            $html .= '</a>';
            $html .= '</div>';
            $html .= '</li>';
        }
        // --- END FILES ---


        $html .= '</ul>';
    }
    return $html;
}

// --- FILE MANAGER LOGIC ---

// Handle folder creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_folder'])) {
    $folder_name = trim($_POST['folder_name']);
    if (!empty($folder_name)) {
        if ($current_folder_id) {
            $stmt = $conn->prepare("INSERT INTO file_manager (parent_id, subject_id, item_type, name, uploader_id) VALUES (?, ?, 'folder', ?, ?)");
            $stmt->bind_param("iisi", $current_folder_id, $subject_id, $folder_name, $user_id);
        } else {
            // Root folder for the subject
            $stmt = $conn->prepare("INSERT INTO file_manager (parent_id, subject_id, item_type, name, uploader_id) VALUES (NULL, ?, 'folder', ?, ?)");
            $stmt->bind_param("isi", $subject_id, $folder_name, $user_id);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: specific-subject.php?id=" . $subject_id . ($current_folder_id ? "&folder=" . $current_folder_id : ""));
        exit();
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload'])) {
    $file_name = $_FILES['file_upload']['name'];
    $file_tmp = $_FILES['file_upload']['tmp_name'];
    
    if (!empty($file_name) && is_uploaded_file($file_tmp)) {
        
        // --- MODIFIED: Create subject-specific upload directory ---
        $subject_upload_dir = __DIR__ . '/../../uploads/file_manager/' . $subject_id . '/';
        
        if (!is_dir($subject_upload_dir)) {
            // Recursively create the folder if it doesn't exist
            mkdir($subject_upload_dir, 0777, true); 
        }
        // --- END MODIFIED ---
        
        // Generate unique file name
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
        $target_path = $subject_upload_dir . $unique_name; // Use subject-specific path
        
        if (move_uploaded_file($file_tmp, $target_path)) {
            // Save to database
            if ($current_folder_id) {
                $stmt = $conn->prepare("INSERT INTO file_manager (parent_id, subject_id, item_type, name, file_path, uploader_id) VALUES (?, ?, 'file', ?, ?, ?)");
                $stmt->bind_param("iissi", $current_folder_id, $subject_id, $file_name, $target_path, $user_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO file_manager (parent_id, subject_id, item_type, name, file_path, uploader_id) VALUES (NULL, ?, 'file', ?, ?, ?)");
                $stmt->bind_param("issi", $subject_id, $file_name, $target_path, $user_id);
            }
            $stmt->execute();
            $stmt->close();
            
            header("Location: specific-subject.php?id=" . $subject_id . ($current_folder_id ? "&folder=" . $current_folder_id : ""));
            exit();
        }
    }
}

// --- FILE/FOLDER CONTEXT SETUP ---

$current_folder = null;
$display_folder_id = $current_folder_id; // Default to folder ID from URL

// 1. Check if a file is being viewed
$current_file = null;
if ($current_file_id) {
    // Check subject_id here to ensure file belongs to the current subject
    $stmt = $conn->prepare("SELECT * FROM file_manager WHERE id = ? AND subject_id = ? AND item_type = 'file'");
    $stmt->bind_param("ii", $current_file_id, $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_file = $result->fetch_assoc();
    $stmt->close();

    // If file is found, set the display context to its parent folder
    if ($current_file) {
        $display_folder_id = $current_file['parent_id'];
    } else {
        // Invalid file ID for this subject, redirect to subject home
        header("Location: specific-subject.php?id=" . $subject_id);
        exit();
    }
}

// 2. Fetch current folder info for breadcrumb/title based on the determined display folder ID
if ($display_folder_id) {
    // Check subject_id here to ensure folder belongs to the current subject
    $stmt = $conn->prepare("SELECT * FROM file_manager WHERE id = ? AND subject_id = ? AND item_type = 'folder'");
    $stmt->bind_param("ii", $display_folder_id, $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_folder = $result->fetch_assoc();
    $stmt->close();
    
    if (!$current_file && !$current_folder) {
        header("Location: specific-subject.php?id=" . $subject_id);
        exit();
    }
}

// Build breadcrumb path
function buildBreadcrumb($conn, $folder_id, $subject_id, $subject_name) {
    $path = [];
    $current_id = $folder_id;
    
    while ($current_id !== null) {
        $stmt = $conn->prepare("SELECT id, name, parent_id FROM file_manager WHERE id = ? AND subject_id = ?");
        $stmt->bind_param("ii", $current_id, $subject_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            array_unshift($path, $row); // Add to beginning of array
            $current_id = $row['parent_id'];
        } else {
            break;
        }
        $stmt->close();
    }
    
    // Add subject root (equivalent to the old "Root")
    array_unshift($path, ['id' => null, 'name' => htmlspecialchars($subject_name) . ' Resources', 'parent_id' => null]);
    
    return $path;
}

$breadcrumb = buildBreadcrumb($conn, $display_folder_id, $subject_id, $subject_name);

if ($current_file) {
    // If viewing a file, append the file name to the breadcrumb
    $breadcrumb[] = ['id' => $current_file_id, 'name' => htmlspecialchars($current_file['name']), 'parent_id' => $display_folder_id, 'is_file' => true];
    
    // Clear folder/file lists since we are viewing a file, not the folder grid
    $folders = [];
    $files = [];
} else {
    // Fetch folders in current directory
    $folders = getFolders($conn, $subject_id, $current_folder_id);

    // Fetch files in current directory
    $files = getFiles($conn, $subject_id, $current_folder_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($subject_name); ?> - Resources</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .folder-icon {
            font-size: 2rem;
            color: #007bff;
        }
        .file-icon {
            font-size: 2rem;
            color: #6c757d;
        }
        .breadcrumb a {
            text-decoration: none;
        }
        .item-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .item-actions {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .item-card:hover .item-actions {
            opacity: 1;
        }
        /* Style for the link tree container */
        .file-tree-sidebar {
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            padding: 1rem;
            min-height: 80vh; /* Ensure the sidebar is tall enough */
        }
        .file-tree-sidebar h4 {
            color: #126682d1;
            border-bottom: 2px solid #126682d1;
            padding-bottom: 0.5rem;
        }
        /* Style for tree links */
        .file-tree-sidebar a {
            padding: 0.25rem 0;
            display: block;
            border-radius: 0.25rem;
            font-size: 0.95rem;
        }
        .file-tree-sidebar a:hover {
            background-color: #e9ecef;
        }
        .file-tree-sidebar li {
            padding-left: 0;
        }
        .file-tree-sidebar .text-success {
            color: #198754 !important;
        }
        .action-buttons a {
            margin-right: 10px;
            margin-bottom: 10px;
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

<div class="container-fluid mt-4">
    <div class="row">
        
        <!-- Left Sidebar: Subject Links and File Tree -->
        <div class="col-md-3 file-tree-sidebar">
            <h4 class="mb-3"><i class="bi bi-book-fill me-2"></i><?php echo htmlspecialchars($subject_name); ?></h4>
            
            <div class="mb-4">
                <h5>Subject Actions:</h5>
                <div class="d-flex flex-wrap action-buttons flex-column">
                    <a href="classlist_block_a.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-primary">
                        Block A List
                    </a>
                    <a href="classlist_block_b.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-primary">
                        Block B List
                    </a>
                    <a href="activities.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-secondary">
                        Activities
                    </a>
                    <a href="quizzes.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-outline-warning">
                        Quizzes & Exams
                    </a>
                </div>
            </div>

            <h4 class="mb-3"><i class="bi bi-folder-fill me-2"></i>Subject Resources</h4>
            
            <!-- Root Link -->
            <a href="specific-subject.php?id=<?php echo $subject_id; ?>" class="text-decoration-none d-block p-1 ps-2 mb-2 <?php echo $current_folder_id === null && $current_file_id === null ? 'fw-bold text-primary bg-light' : 'text-dark'; ?>">
                <i class="bi bi-house-fill me-2"></i> <?php echo htmlspecialchars($subject_name); ?> Root
            </a>

            <!-- Recursive Folder Tree (Scoped to Subject) -->
            <?php echo getFolderTreeHtml($conn, $subject_id, null, $display_folder_id, $current_file_id); ?>
        </div>

        <!-- Main Content Area (Right Column) -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-folder-open"></i> 
                    <?php if ($current_file): ?>
                        File View
                    <?php else: ?>
                        Current Folder: <?php echo htmlspecialchars($current_folder['name'] ?? 'Root'); ?>
                    <?php endif; ?>
                </h2>
                
                <?php if (!$current_file): // Only show controls in folder view ?>
                <div>
                    <!-- Document Creation Dropdown -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-plus"></i> Create Document
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" 
                                   onclick="createOnlyOfficeDocument('docx', '<?php echo $current_folder_id; ?>', '<?php echo $subject_id; ?>'); return false;">
                                    <i class="bi bi-file-earmark-word"></i> Word Document (.docx)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" 
                                   onclick="createOnlyOfficeDocument('xlsx', '<?php echo $current_folder_id; ?>', '<?php echo $subject_id; ?>'); return false;">
                                    <i class="bi bi-file-earmark-excel"></i> Spreadsheet (.xlsx)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" 
                                   onclick="createOnlyOfficeDocument('pptx', '<?php echo $current_folder_id; ?>', '<?php echo $subject_id; ?>'); return false;">
                                    <i class="bi bi-file-earmark-ppt"></i> Presentation (.pptx)
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                        <i class="bi bi-folder-plus"></i> New Folder
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                        <i class="bi bi-file-earmark-arrow-up"></i> Upload File
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumb as $index => $crumb): ?>
                        <?php 
                        $crumb_link = 'specific-subject.php?id=' . $subject_id;
                        if ($crumb['id'] !== null && !isset($crumb['is_file'])) {
                            $crumb_link .= '&folder=' . $crumb['id'];
                        }
                        ?>
                        <?php if ($index === count($breadcrumb) - 1): ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo htmlspecialchars($crumb['name']); ?>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo $crumb_link; ?>">
                                    <?php echo htmlspecialchars($crumb['name']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
            
            <!-- Content Area: File Viewer or Folder/Files -->
            <?php if ($current_file): ?>
                <!-- Display File Content -->
                <div class="card shadow-lg mt-5">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-file-text me-2"></i> Viewing: <?php echo htmlspecialchars($current_file['name']); ?></h4>
                        <div>
                            <a href="dl_file.php?id=<?php echo $current_file['id']; ?>" class="btn btn-primary btn-sm me-2">
                                <i class="bi bi-download"></i> Download Original
                            </a>
                            <!-- Link back to the parent folder -->
                            <a href="specific-subject.php?id=<?php echo $subject_id; ?>&folder=<?php echo $current_file['parent_id'] ?? ''; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-lg"></i> Close View
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        // Check if file exists on disk
                        if (!file_exists($current_file['file_path'])) {
                            echo "<div class='alert alert-danger text-center'>
                                        <i class='bi bi-bug-fill me-2'></i> 
                                        Error: File not found on server disk.
                                    </div>";
                        } else {
                            try {
                                $content = file_get_contents($current_file['file_path']);
                                $mime_type = function_exists('mime_content_type') ? mime_content_type($current_file['file_path']) : 'text/plain';

                                // Basic display logic based on MIME type (extendable for richer content)
                                if (str_starts_with($mime_type, 'image/')) {
                                    // Display image
                                    $base64 = base64_encode($content);
                                    echo "<img src='data:$mime_type;base64,$base64' class='img-fluid rounded shadow-sm' alt='{$current_file['name']}' style='max-height: 70vh; display: block; margin: 0 auto;'>";
                                } elseif (str_starts_with($mime_type, 'text/') || $mime_type === 'application/json' || str_contains($mime_type, 'xml') || str_contains($mime_type, 'application/x-php')) {
                                    // Display text content (for code, text files, etc.)
                                    echo '<pre class="p-3 bg-light border rounded" style="max-height: 60vh; overflow: auto; white-space: pre-wrap; word-break: break-all;">' . htmlspecialchars($content) . '</pre>';
                                } else {
                                    // Fallback for non-displayable types
                                    echo "<div class='alert alert-warning text-center'>
                                            <i class='bi bi-exclamation-triangle-fill me-2'></i> 
                                            Preview not available for file type: <strong>{$mime_type}</strong>. 
                                            This feature supports basic text and image formats. Please download the file to view.
                                        </div>";
                                }
                            } catch (Exception $e) {
                                echo "<div class='alert alert-danger text-center'>
                                            <i class='bi bi-bug-fill me-2'></i> 
                                            Error reading file content.
                                        </div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php elseif (empty($folders) && empty($files)): ?>
                <!-- Empty Folder Content -->
                <div class="text-center py-5">
                    <i class="bi bi-folder2-open" style="font-size: 4rem; color: #ccc;"></i>
                    <h3 class="mt-3">This folder is empty</h3>
                    <p class="text-muted">Create a new folder or upload a file to get started.</p>
                </div>
            <?php else: ?>
                <!-- Folder/File Grid Content -->
                <div class="row">
                    <!-- Folders -->
                    <?php foreach ($folders as $folder): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card item-card h-100">
                                <div class="card-body text-center">
                                    <div class="folder-icon mb-2">
                                        <i class="bi bi-folder-fill"></i>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($folder['name']); ?></h5>
                                    <small class="text-muted">Folder</small>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center item-actions">
                                    <a href="specific-subject.php?id=<?php echo $subject_id; ?>&folder=<?php echo $folder['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-box-arrow-in-right"></i> Open
                                    </a>
                                    <!-- Deletion link must include subject_id for proper redirect -->
                                    <a href="delete_file.php?id=<?php echo $folder['id']; ?>&type=folder&subject_id=<?php echo $subject_id; ?><?php echo $current_folder_id ? '&folder=' . $current_folder_id : ''; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this folder and all its contents?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Files -->
                    <?php foreach ($files as $file): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card item-card h-100">
                                <div class="card-body text-center">
                                    <div class="file-icon mb-2">
                                        <i class="bi bi-file-earmark"></i>
                                    </div>
                                    <h5 class="card-title text-truncate"><?php echo htmlspecialchars($file['name']); ?></h5>
                                    <small class="text-muted">
                                        <?php 
                                        if (isset($file['file_path']) && file_exists($file['file_path'])) {
                                            echo round(filesize($file['file_path']) / 1024, 2) . " KB";
                                        } else {
                                            echo "File not found";
                                        }
                                        ?>
                                    </small>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center item-actions">
                                    <a href="specific-subject.php?id=<?php echo $subject_id; ?>&file=<?php echo $file['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="dl_file.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <a href="delete_file.php?id=<?php echo $file['id']; ?>&type=file&subject_id=<?php echo $subject_id; ?><?php echo $current_folder_id ? '&folder=' . $current_folder_id : ''; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this file?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Folder Modal -->
<div class="modal fade" id="createFolderModal" tabindex="-1" aria-labelledby="createFolderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="specific-subject.php?id=<?php echo $subject_id; ?><?php echo $current_folder_id ? '&folder=' . $current_folder_id : ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="createFolderModalLabel">Create New Folder for <?php echo htmlspecialchars($subject_name); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="folder_name" class="form-label">Folder Name</label>
                        <input type="text" class="form-control" id="folder_name" name="folder_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_folder" class="btn btn-primary">Create Folder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload File Modal -->
<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" action="specific-subject.php?id=<?php echo $subject_id; ?><?php echo $current_folder_id ? '&folder=' . $current_folder_id : ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadFileModalLabel">Upload File to <?php echo htmlspecialchars($subject_name); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file_upload" class="form-label">Select File</label>
                        <input type="file" class="form-control" id="file_upload" name="file_upload" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload File</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>

<!-- Updated JavaScript function for OnlyOffice document creation to include subject_id -->
<script>
/**
 * Redirects to the backend script responsible for initiating OnlyOffice document creation.
 * Now includes subjectId in the URL.
 * @param {string} type - The document type/extension (docx, xlsx, pptx).
 * @param {string|null} folderId - The ID of the folder where the new file should be saved.
 * @param {string} subjectId - The ID of the subject.
 */
function createOnlyOfficeDocument(type, folderId, subjectId) {
    let url = 'create_document.php?type=' + type + '&subject_id=' + subjectId;
    if (folderId && folderId !== '0' && folderId !== '') {
        url += '&folder=' + folderId;
    }
    // Redirects the user to the document creation/editor page
    window.location.href = url;
}
</script>
</body>
</html>
