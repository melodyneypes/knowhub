<?php
// documents.php - List and filter documents created with OnlyOffice
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

require '../../db.php';

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// Handle document deletion
if (isset($_GET['delete_id']) && isset($_GET['type'])) {
    $delete_id = intval($_GET['delete_id']);
    $doc_type = $_GET['type'];
    
    if ($doc_type === 'admin') {
        // Delete admin document
        $stmt = $conn->prepare("SELECT file_path FROM admin_documents WHERE id = ? AND uploader_id = ?");
        $stmt->bind_param("ii", $delete_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Delete file from filesystem
            $file_path = $row['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete record from database
            $delete_stmt = $conn->prepare("DELETE FROM admin_documents WHERE id = ? AND uploader_id = ?");
            $delete_stmt->bind_param("ii", $delete_id, $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            $_SESSION['message'] = "Document deleted successfully.";
        }
        $stmt->close();
    } else {
        // Delete subject document (only if user is the uploader)
        $stmt = $conn->prepare("SELECT file_path FROM resources WHERE id = ? AND uploader_id = ?");
        $stmt->bind_param("ii", $delete_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Delete file from filesystem
            $file_path = $row['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete record from database
            $delete_stmt = $conn->prepare("DELETE FROM resources WHERE id = ? AND uploader_id = ?");
            $delete_stmt->bind_param("ii", $delete_id, $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            $_SESSION['message'] = "Document deleted successfully.";
        }
        $stmt->close();
    }
    
    // Redirect to avoid resubmission
    header("Location: documents.php");
    exit();
}

// Search parameters
$search_query = $_GET['search'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$type_filter = $_GET['type'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

// Validate sort parameters
$allowed_sort_columns = ['title', 'created_at', 'updated_at'];
$allowed_sort_orders = ['ASC', 'DESC'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'created_at';
}
if (!in_array($sort_order, $allowed_sort_orders)) {
    $sort_order = 'DESC';
}

// Fetch all subjects
$stmt = $conn->prepare("SELECT DISTINCT id, name FROM subjects ORDER BY name");
$stmt->execute();
$subjects_result = $stmt->get_result();
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Fetch document types
$document_types = [
    'docx' => 'Word Document',
    'xlsx' => 'Spreadsheet',
    'pptx' => 'Presentation'
];

// Build the query for OnlyOffice documents (both admin and subject-specific)
$documents = [];

// Get admin documents
$admin_sql = "SELECT ad.*, NULL as subject_name, u.name as uploader_name, 'admin' as doc_type 
              FROM admin_documents ad 
              LEFT JOIN users u ON ad.uploader_id = u.id";

$admin_params = [];
$admin_param_types = "";

// Add search condition for admin documents
if (!empty($search_query)) {
    $admin_sql .= " WHERE (ad.title LIKE ? OR ad.description LIKE ?)";
    $search_param = "%$search_query%";
    $admin_params[] = $search_param;
    $admin_params[] = $search_param;
    $admin_param_types .= "ss";
}

// Add type filter for admin documents
if (!empty($type_filter)) {
    if (!empty($search_query)) {
        $admin_sql .= " AND ad.file_type = ?";
    } else {
        $admin_sql .= " WHERE ad.file_type = ?";
    }
    $admin_params[] = $type_filter;
    $admin_param_types .= "s";
}

$admin_stmt = $conn->prepare($admin_sql);
if (!empty($admin_params)) {
    $admin_stmt->bind_param($admin_param_types, ...$admin_params);
}
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();

while ($row = $admin_result->fetch_assoc()) {
    $documents[] = $row;
}
$admin_stmt->close();

// Get subject-specific documents
$subject_sql = "SELECT r.*, s.name as subject_name, u.name as uploader_name, 'subject' as doc_type 
                FROM resources r 
                LEFT JOIN subjects s ON r.subject_id = s.id 
                LEFT JOIN users u ON r.uploader_id = u.id 
                WHERE r.file_type IN ('docx', 'xlsx', 'pptx')";

$subject_params = [];
$subject_param_types = "";

// Add search condition for subject documents
if (!empty($search_query)) {
    $subject_sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $search_param = "%$search_query%";
    $subject_params[] = $search_param;
    $subject_params[] = $search_param;
    $subject_param_types .= "ss";
}

// Add subject filter for subject documents
if (!empty($subject_filter)) {
    $subject_sql .= " AND r.subject_id = ?";
    $subject_params[] = $subject_filter;
    $subject_param_types .= "i";
}

// Add type filter for subject documents
if (!empty($type_filter)) {
    $subject_sql .= " AND r.file_type = ?";
    $subject_params[] = $type_filter;
    $subject_param_types .= "s";
}

$subject_stmt = $conn->prepare($subject_sql);
if (!empty($subject_params)) {
    $subject_stmt->bind_param($subject_param_types, ...$subject_params);
}
$subject_stmt->execute();
$subject_result = $subject_stmt->get_result();

while ($row = $subject_result->fetch_assoc()) {
    $documents[] = $row;
}
$subject_stmt->close();

// Sort documents by the specified column and order
usort($documents, function($a, $b) use ($sort_by, $sort_order) {
    $result = 0;
    
    if ($sort_by === 'title') {
        $result = strcmp($a['title'], $b['title']);
    } else if ($sort_by === 'created_at') {
        $result = strtotime($a['created_at']) <=> strtotime($b['created_at']);
    } else if ($sort_by === 'updated_at') {
        $a_updated = isset($a['updated_at']) ? strtotime($a['updated_at']) : strtotime($a['created_at']);
        $b_updated = isset($b['updated_at']) ? strtotime($b['updated_at']) : strtotime($b['created_at']);
        $result = $a_updated <=> $b_updated;
    }
    
    return ($sort_order === 'DESC') ? -$result : $result;
});

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnlyOffice Documents</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #eee;
        }
        .sidebar .nav-link {
            color: #333;
            font-weight: 500;
            padding: 12px 20px;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #e9ecef;
            color: #126682d1;
        }
        .profile-img {
            max-width: 60px;
            margin: 20px auto 10px auto;
            display: block;
            border-radius: 50%;
            border: 2px solid #126682d1;
        }
        .main-content {
            padding: 40px 30px;
        }
        .document-card {
            transition: all 0.3s;
            border-left: 4px solid #0d6efd;
        }
        .document-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,.15);
        }
        .filter-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
        }
        .form-control, .form-select {
            margin-bottom: 15px;
        }
        .document-icon {
            font-size: 2rem;
            color: #0d6efd;
        }
        .admin-badge {
            background-color: #6f42c1;
        }
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">OnlyOffice Documents</h2>
            <div>
                <a href="create_document.php" class="btn btn-primary"><i class="bi bi-file-earmark-plus"></i> Create New Document</a>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5>Filter Documents</h5>
            <form method="GET" class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search in title or description" value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="subject">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>" <?php echo ($subject_filter == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($document_types as $ext => $name): ?>
                            <option value="<?php echo $ext; ?>" <?php echo ($type_filter == $ext) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="documents.php" class="btn btn-outline-secondary w-100 mt-2"><i class="bi bi-x-circle"></i> Clear</a>
                </div>
            </form>
        </div>

        <!-- Sorting Options -->
        <div class="mb-3 d-flex justify-content-end">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Sort by
                </button>
                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                    <li><a class="dropdown-item" href="?sort=title&order=ASC<?php echo buildSortQuery(['search' => $search_query, 'subject' => $subject_filter, 'type' => $type_filter]); ?>">Title (A-Z)</a></li>
                    <li><a class="dropdown-item" href="?sort=title&order=DESC<?php echo buildSortQuery(['search' => $search_query, 'subject' => $subject_filter, 'type' => $type_filter]); ?>">Title (Z-A)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?sort=created_at&order=DESC<?php echo buildSortQuery(['search' => $search_query, 'subject' => $subject_filter, 'type' => $type_filter]); ?>">Newest First</a></li>
                    <li><a class="dropdown-item" href="?sort=created_at&order=ASC<?php echo buildSortQuery(['search' => $search_query, 'subject' => $subject_filter, 'type' => $type_filter]); ?>">Oldest First</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?sort=updated_at&order=DESC<?php echo buildSortQuery(['search' => $search_query, 'subject' => $subject_filter, 'type' => $type_filter]); ?>">Recently Updated</a></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <?php if (!empty($documents)): ?>
                <?php foreach ($documents as $document): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card document-card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <?php
                                    $icon = 'bi-file-earmark-text';
                                    $file_type = $document['file_type'];
                                    switch ($file_type) {
                                        case 'docx':
                                            $icon = 'bi-file-earmark-word';
                                            break;
                                        case 'xlsx':
                                            $icon = 'bi-file-earmark-excel';
                                            break;
                                        case 'pptx':
                                            $icon = 'bi-file-earmark-ppt';
                                            break;
                                    }
                                    ?>
                                    <i class="bi <?php echo $icon; ?> document-icon"></i>
                                    <div>
                                        <span class="badge bg-primary"><?php echo strtoupper(htmlspecialchars($file_type)); ?></span>
                                        <?php if ($document['doc_type'] === 'admin'): ?>
                                            <span class="badge admin-badge">ADMIN</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($document['title']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($document['description'] ?? 'No description', 0, 100)) . (strlen($document['description'] ?? '') > 100 ? '...' : ''); ?></p>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <?php if ($document['subject_name']): ?>
                                            <i class="bi bi-journal-bookmark"></i> <?php echo htmlspecialchars($document['subject_name']); ?><br>
                                        <?php else: ?>
                                            <i class="bi bi-person-badge"></i> My Document<br>
                                        <?php endif; ?>
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($document['uploader_name'] ?? 'Unknown'); ?><br>
                                        <i class="bi bi-calendar"></i> Created: <?php echo date('M j, Y', strtotime($document['created_at'])); ?><br>
                                        <?php if (isset($document['updated_at']) && $document['updated_at'] != $document['created_at']): ?>
                                            <i class="bi bi-arrow-repeat"></i> Updated: <?php echo date('M j, Y', strtotime($document['updated_at'])); ?><br>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="d-flex justify-content-between">
                                    <?php if ($document['doc_type'] === 'admin'): ?>
                                        <a href="../../onlyoffice_editor.php?file=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($document['file_path'], '/')); ?>&title=<?php echo urlencode($document['title']); ?>&document_id=<?php echo $document['id']; ?>&type=admin" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <div class="btn-group" role="group">
                                            <a href="../../view_file.php?file=<?php echo urlencode(ltrim($document['file_path'], '/')); ?>&title=<?php echo urlencode($document['title']); ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <?php if ($document['uploader_id'] == $user_id): ?>
                                                <a href="?delete_id=<?php echo $document['id']; ?>&type=admin" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this document? This action cannot be undone.')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <a href="../../onlyoffice_editor.php?file=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($document['file_path'], '/')); ?>&title=<?php echo urlencode($document['title']); ?>&resource_id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <div class="btn-group" role="group">
                                            <a href="../../view_file.php?file=<?php echo urlencode(ltrim($document['file_path'], '/')); ?>&title=<?php echo urlencode($document['title']); ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <?php if ($document['uploader_id'] == $user_id): ?>
                                                <a href="?delete_id=<?php echo $document['id']; ?>&type=subject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this document? This action cannot be undone.')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark-x" style="font-size: 3rem; color: #ccc;"></i>
                        <h4 class="mt-3">No documents found</h4>
                        <p class="text-muted">
                            <?php if (!empty($search_query) || !empty($subject_filter) || !empty($type_filter)): ?>
                                No documents match your filter criteria. 
                                <a href="documents.php">Clear filters</a> to see all documents.
                            <?php else: ?>
                                There are no OnlyOffice documents created yet.
                            <?php endif; ?>
                        </p>
                        <div class="mt-3">
                            <a href="create_document.php" class="btn btn-primary"><i class="bi bi-file-earmark-plus"></i> Create New Document</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>

<?php
// Helper function to build query string for sort links
function buildSortQuery($params) {
    $query = '';
    foreach ($params as $key => $value) {
        if (!empty($value)) {
            $query .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }
    return $query;
}
?>

</body>
</html>