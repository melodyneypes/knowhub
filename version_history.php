<?php
// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php'; // Assumes this file establishes $conn

// --- Helper Function ---
function getResourceUploaderName($conn, $uploader_id) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $uploader_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $name = $result->fetch_assoc()['name'] ?? 'Unknown User';
    $stmt->close();
    return $name;
}
// --- End Helper Function ---


// --- 1. Authentication and Setup ---
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$resource_id = filter_input(INPUT_GET, 'resource_id', FILTER_VALIDATE_INT);
if (!$resource_id) {
    die("Invalid resource ID.");
}

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];


// --- 2. Fetch Resource Details ---
$stmt = $conn->prepare("
    SELECT 
        r.*, 
        s.name AS subject_name, 
        u.name AS uploader_name
    FROM resources r 
    JOIN subjects s ON r.subject_id = s.id 
    JOIN users u ON r.uploader_id = u.id 
    WHERE r.id = ?
");
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$resource = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resource) {
    die("Resource not found.");
}


// --- 3. Check User Access Permissions (Full Logic) ---
$can_access = ($user_role == 'admin');

if (!$can_access && $user_role == 'instructor') {
    // Check if instructor teaches this subject
    $stmt = $conn->prepare("SELECT id FROM subject_instructors WHERE subject_id = ? AND instructor_id = ?");
    $stmt->bind_param("ii", $resource['subject_id'], $user_id);
    $stmt->execute();
    $can_access = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

if (!$can_access && $user_role == 'student') {
    // Check if student is enrolled in the subject OR if they uploaded the resource
    $is_enrolled = false;
    $stmt = $conn->prepare("SELECT id FROM subject_students WHERE subject_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $resource['subject_id'], $user_id);
    $stmt->execute();
    $is_enrolled = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    $uploaded_by_student = ($resource['uploader_id'] == $user_id);
    
    $can_access = $is_enrolled || $uploaded_by_student;
}

if (!$can_access) {
    die("Access denied. You do not have permission to view this resource's history.");
}


// --- 4. Fetch Version History ---
// Fetch ALL historical versions, ordered newest to oldest by creation time.
$stmt = $conn->prepare("
    SELECT 
        rv.*, 
        u.name 
    FROM resource_version rv 
    JOIN users u ON rv.uploader_id = u.id 
    WHERE rv.resource_id = ? 
    ORDER BY rv.created_at DESC
"); 
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$versions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// --- 5. Construct All Versions Array ---

$all_versions = [];
$is_first_historical = true; // Flag to identify the newest historical version

// --- 5a. Add the ACTIVE Version (Current Live File) ---
// This file is in the main /uploads/ folder.
$active_version = [
    'id' => 'current',
    'title' => $resource['title'] . ' (Active Version)',
    'description' => 'This is the current, active version of the document.',
    // Correctly construct the active file path
    'file_path' => 'uploads/versions/' . basename($resource['file_path']), 
    'created_at' => $resource['updated_at'] ?? $resource['created_at'],
    'name' => $resource['uploader_name'],
    'is_active' => true, 
    'is_original' => false,
    'is_newest_historical' => false
];
// Add active version to the beginning for display (used for version numbering)
$all_versions[] = $active_version;


// --- 5b. Add Historical Versions ---
foreach ($versions as $ver) {
    // Correctly construct the historical file path (assuming file_path in DB is just the filename)
    $ver['file_path'] = 'uploads/versions/' . basename($ver['file_path']); 
    
    // Add flags for styling
    $ver['is_active'] = false;
    $ver['is_original'] = false;
    
    if ($is_first_historical) {
        $ver['is_newest_historical'] = true; // Flag for highlighting
        $is_first_historical = false;
    } else {
        $ver['is_newest_historical'] = false;
    }
    
    $all_versions[] = $ver;
}


// --- 5c. Add the ORIGINAL Version ---
$is_original_included = false;
if (count($versions) > 0) {
    $last_version_index = count($versions) - 1;
    if (strtotime($versions[$last_version_index]['created_at']) <= strtotime($resource['created_at'])) {
        $is_original_included = true;
    }
}

if (!$is_original_included) {
    $original_version = [
        'id' => 0,
        'title' => 'Original Upload',
        'description' => 'Initial file creation and upload. Document structure established.',
        'file_path' => 'uploads/' . basename($resource['file_path']), 
        'created_at' => $resource['created_at'],
        'name' => getResourceUploaderName($conn, $resource['uploader_id']), 
        'is_active' => false,
        'is_original' => true, 
        'is_newest_historical' => false
    ];
    $all_versions[] = $original_version;
}

// Re-index $all_versions to start from 0 after all additions
$all_versions = array_values($all_versions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Version History - <?php echo htmlspecialchars($resource['title']); ?></title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        /* (CSS styles remain the same) */
        body { background-color: #f8f9fa; }
        .main-document-overview {
            background-color: #ffffff;
            border-radius: .25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .version-card {
            min-height: 220px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            border-left: 5px solid transparent;
        }
        .version-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        .version-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .newest-historical-card {
             border-left: 5px solid #0d6efd;
             background-color: #e6f7ff;
        }
        .original-version-card {
            border-left: 5px solid #dc3545;
            background-color: #f8d7da;
        }
        .revert-btn {
             background-color: #dc3545;
             border-color: #dc3545;
        }
        .view-btn {
             background-color: #198754;
             border-color: #198754;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <button onclick="window.history.back();" class="btn btn-dark mb-4">BACK</button>
    
    <div class="main-document-overview card p-4 mb-5">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-3"><?php echo htmlspecialchars($resource['title']); ?>.<?php echo pathinfo($resource['file_path'], PATHINFO_EXTENSION); ?></h3>
            
            <a href="view_version.php?file_path=<?php echo urlencode($active_version['file_path']); ?>" target="_blank" class="btn btn-success view-btn text-white">
                View Active Version
            </a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <p class="mb-1 text-muted">Current File Overview</p>
                <div class="d-flex align-items-center mb-2">
                    <strong class="version-title me-3">Version: V<?php echo count($all_versions); ?>.0</strong> 
                    <span class="badge bg-success">Status: Active</span>
                </div>
                <p class="mb-0">Owner: **<?php echo htmlspecialchars($resource['uploader_name']); ?>**</p>
            </div>
            
            <div class="col-md-6 text-end">
                <p class="mb-0">Last Modified: <?php echo date('F j, Y, g:i a', strtotime($active_version['created_at'])); ?></p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <?php foreach ($all_versions as $ver): ?>
            <?php
            // Skip the Active Version from the main grid
            if ($ver['is_active']) continue; 

            // Determine classes for styling
            $card_class = 'version-card h-100 p-3 ';
            
            if ($ver['is_newest_historical']) {
                $card_class .= 'newest-historical-card';
            } elseif ($ver['is_original']) {
                $card_class .= 'original-version-card';
            } else {
                $card_class .= 'bg-light';
            }
            
            // Generate a simple version number (e.g., V1.0, V2.0) for display
            $version_index = array_search($ver, $all_versions);
            $version_number_display = 'Version ' . (count($all_versions) - $version_index) . '.0';
            
            if ($ver['is_newest_historical']) {
                $version_number_display = 'Version 2.0';
            }
            elseif ($ver['is_original']) {
                $version_number_display = 'Version 1.0';
            }
            ?>
            <div class="col-md-6 mb-4">
                <div class="card <?php echo $card_class; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="version-title mb-0 text-dark"><?php echo $version_number_display; ?></h5>
                            <small class="text-muted">Released on <?php echo date('Y-m-d H:i', strtotime($ver['created_at'])); ?></small>
                        </div>
                        
                        <p class="text-muted mb-3">by **<?php echo htmlspecialchars($ver['name']); ?>**</p>

                        <p class="card-text mb-3">
                            **Changes:** <?php echo htmlspecialchars($ver['description'] ?? 'Initial file creation and upload. Document structure established.'); ?>
                        </p>
                        
                        <div class="mt-auto pt-2">
                            <a href="view_version.php?file_path=<?php echo urlencode($ver['file_path']); ?>" target="_blank" class="btn btn-sm view-btn me-2 text-white">
                                View Version
                            </a>
                            <a href="revert.php?version_id=<?php echo $ver['id']; ?>" class="btn btn-sm revert-btn text-white">
                                Revert to This
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($versions)): ?>
        <div class="alert alert-info text-center mt-4">
            No historical versions found yet.
        </div>
    <?php endif; ?>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>