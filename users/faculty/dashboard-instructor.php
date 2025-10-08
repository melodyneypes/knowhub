<?php
session_start();
error_log("Session user: " . print_r($_SESSION['user'], true)); // Log session user for debugging

// Check if the user is logged in (session variable exists)
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php'); // Redirect to login page if not logged in
    exit();
}

require '../../db.php';
require 'faculty_notify.php'; // Include faculty notification functions

$user_id = $_SESSION['user']['id'];

// Function to calculate time ago
function time_ago($datetime) {
    $time_diff = time() - strtotime($datetime);
    if ($time_diff < 60) return "Just now";
    if ($time_diff < 3600) return floor($time_diff/60) . " min ago";
    if ($time_diff < 86400) return floor($time_diff/3600) . " hrs ago";
    return floor($time_diff/86400) . " days ago";
}


// --- DATA FETCHING ---

// Fetch notifications for the instructor
$notifications = [];
$notif_stmt = $conn->prepare(
    "SELECT n.*, u.name as sender_name 
    FROM notifications n 
    LEFT JOIN users u ON n.sender_id = u.id 
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC 
    LIMIT 5" // Limiting to 5 recent notifications
);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
while ($row = $notif_result->fetch_assoc()) {
    $notifications[] = $row;
}
$notif_stmt->close();

// Count unread notifications
$unread_count_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_count_stmt->bind_param("i", $user_id);
$unread_count_stmt->execute();
$unread_count_result = $unread_count_stmt->get_result();
$unread_count_row = $unread_count_result->fetch_assoc();
$unread_count = $unread_count_row['unread_count'];
$unread_count_stmt->close();

// NEW: Fetch PENDING INSTRUCTOR REQUESTS
// Assuming a notification type 'instructor_request' is used for these approvals.
$pending_instructor_requests_count = 0;
$req_stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND type = 'instructor_request' AND is_read = 0");
$req_stmt->bind_param("i", $user_id);
$req_stmt->execute();
$req_result = $req_stmt->get_result();
$req_row = $req_result->fetch_assoc();
$pending_instructor_requests_count = $req_row['count'];
$req_stmt->close();


// Fetch subjects handled by this instructor for the "My Handled Subjects" card
$subjects = [];
$stmt = $conn->prepare(
    "SELECT DISTINCT s.id, s.name AS title, s.description, s.year_level, s.semester, si.block
    FROM subject_instructors si
    JOIN subjects s ON si.subject_id = s.id
    WHERE si.instructor_id = ?
    ORDER BY s.year_level DESC, s.semester DESC, s.name, si.block"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Check if this subject already exists in our array (by ID)
    $subject_exists = false;
    foreach ($subjects as $existing_subject) {
        if ($existing_subject['id'] == $row['id']) {
            $subject_exists = true;
            break;
        }
    }
    
    // Only add if it doesn't already exist
    if (!$subject_exists) {
        $subjects[] = $row;
    }
}
$stmt->close();

// Fetch first 3 resources uploaded by this instructor
$resources = [];
$stmt = $conn->prepare(
    "SELECT r.id, r.title, r.description, r.file_path, r.created_at, r.download_count, s.name as subject_name
    FROM resources r
    LEFT JOIN subjects s ON r.subject_id = s.id
    WHERE r.uploader_id = ?
    ORDER BY r.created_at DESC
    LIMIT 3"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard | KnowHub</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #007bff; /* Primary blue for links/actions */
            --secondary-color: #6c757d; /* Secondary for subdued text/elements */
            --app-brand-color: #126682; /* Custom color for app brand */
        }
        
        body {
            background-color: #f8f9fa; /* Light grey background for clean look */
        }

        /* --- Navbar Style Adjustment --- */
        .navbar-brand {
            font-weight: 700 !important;
            color: var(--app-brand-color) !important;
        }
        .nav-link {
            transition: color 0.2s;
        }
        .nav-link:hover {
            color: var(--primary-color);
        }
        .nav-link.active, .nav-link[href*="dashboard"] {
            font-weight: 500;
            color: var(--primary-color) !important;
        }

        /* --- Card Styles --- */
        .card {
            border-radius: 0.5rem;
            border: 1px solid #dee2e6; /* Light border */
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); /* Subtle shadow */
        }
        .card-header {
            background-color: #f7f7f7;
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
        }

        /* --- Profile Card Enhancements --- */
        .profile-picture-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 1rem;
            border: 3px solid var(--primary-color);
            border-radius: 50%;
            overflow: hidden;
            background-color: #e9ecef; /* Placeholder color if image fails */
        }
        .profile-picture {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        /* --- Resources Card Styling --- */
        .resource-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e9ecef;
        }
        .resource-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .file-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
        }
        .resource-title {
            font-size: 1rem;
            font-weight: 500;
        }
        .resource-badge {
            font-size: 0.75rem;
            font-weight: 400;
        }

        /* --- Notifications Styling --- */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 0.7rem;
        }
        .notification-card {
            border-left: 4px solid var(--secondary-color); /* Thicker for emphasis */
            border-radius: 0.3rem;
            padding: 0.75rem;
            transition: background-color 0.2s, border-color 0.2s;
        }
        .notification-card.unread {
            border-left-color: var(--primary-color);
            background-color: #e9f5ff; /* Lighter blue for unread */
            font-weight: 500;
        }
        .notification-icon {
            width: 30px;
            min-width: 30px; /* To prevent shrinking */
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.8rem;
            margin-right: 0.5rem;
        }
        /* Specific Icon Colors */
        .download-icon { background-color: #d4edda; color: #155724; }
        .reply-icon { background-color: #cce7ff; color: #004085; }
        .edit-request-icon { background-color: #fff3cd; color: #856404; }
        .forum-icon { background-color: #d1ecf1; color: #0c5460; }
        .activity-icon { background-color: #f8d7da; color: #721c24; }
        .access-request-icon { background-color: #e2e3e5; color: #383d41; }
        .submission-icon { background-color: #d4edda; color: #155724; }
        .comment-icon { background-color: #fff3cd; color: #856404; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard-instructor.php">KnowHub: A Digital Archive of BSIT Resources</a>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard-instructor.php">Home</a>
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
                <li class="nav-item ms-lg-3">
                    <a class="btn btn-sm btn-danger" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            
            <div class="card mb-4">
                <div class="card-header text-center">
                    <h5 class="mb-0">Instructor Profile</h5>
                </div>
                <div class="card-body text-center">
                    <div class="profile-picture-container">
                        <?php if (isset($_SESSION['user']['picture']) && $_SESSION['user']['picture']): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['user']['picture']); ?>" alt="Profile Picture" class="profile-picture">
                        <?php else: ?>
                            <div style="background-color: #7AA8FF; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 6rem; color: #fff; font-weight: bold;">I</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3 class="profile-name"><?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Instructor'); ?></h3>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($_SESSION['user']['email'] ?? 'it.faculty@email.com'); ?></p>
                    <a href="../../profile_settings.php" class="btn btn-outline-secondary">Profile Settings</a>
                </div>
            </div>

            <div class="card mb-4">
               <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Notifications</h5>  
                    <a href="notifications.php" class="btn btn-sm btn-outline-primary position-relative">
                        View All
                        <?php if ($unread_count > 0): ?>
                            <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle notification-badge">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted mb-0">No notifications yet. You're all caught up! 👍</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): 
                                // Determine icon based on type (using original logic)
                                $icon_class = 'bi-bell';
                                $icon_bg_class = 'access-request-icon'; // Default

                                if (strpos($notification['type'], 'download') !== false) { $icon_class = 'bi-download'; $icon_bg_class = 'download-icon'; }
                                elseif (strpos($notification['type'], 'reply') !== false) { $icon_class = 'bi-reply'; $icon_bg_class = 'reply-icon'; }
                                elseif (strpos($notification['type'], 'edit_request') !== false) { $icon_class = 'bi-pencil'; $icon_bg_class = 'edit-request-icon'; }
                                elseif (strpos($notification['type'], 'forum') !== false) { $icon_class = 'bi-chat-dots'; $icon_bg_class = 'forum-icon'; }
                                elseif (strpos($notification['type'], 'activity') !== false) { $icon_class = 'bi-calendar-event'; $icon_bg_class = 'activity-icon'; }
                                elseif (strpos($notification['type'], 'access_request') !== false) { $icon_class = 'bi-person-plus'; $icon_bg_class = 'access-request-icon'; }
                                elseif (strpos($notification['type'], 'submission') !== false) { $icon_class = 'bi-upload'; $icon_bg_class = 'submission-icon'; }
                                elseif (strpos($notification['type'], 'comment') !== false) { $icon_class = 'bi-chat-square-text'; $icon_bg_class = 'comment-icon'; }
                                elseif (strpos($notification['type'], 'instructor_request') !== false) { $icon_class = 'bi-person-lines-fill'; $icon_bg_class = 'edit-request-icon'; } // Specific for new request type
                            ?>
                                <a href="<?php echo htmlspecialchars($notification['link'] ?? 'notifications.php'); ?>" class="list-group-item list-group-item-action notification-card <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?> mb-2">
                                    <div class="d-flex align-items-start">
                                        <div class="notification-icon <?php echo $icon_bg_class; ?>">
                                            <i class="bi <?php echo $icon_class; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($notification['title']); ?></div>
                                            <p class="mb-1 small text-truncate"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted">
                                                <?php echo time_ago($notification['created_at']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
        <div class="col-md-8">
            
            <div class="card mb-4 bg-warning-subtle border-0">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-lines-fill h4 me-3 text-warning"></i>
                        <div>
                            <h5 class="mb-1 text-warning">Pending Instructor Requests 
                                <?php if ($pending_instructor_requests_count > 0): ?>
                                    <span class="badge rounded-pill bg-danger ms-2"><?php echo $pending_instructor_requests_count; ?></span>
                                <?php endif; ?>
                            </h5>
                            <p class="mb-0 small text-warning">Requests from colleagues to edit your resources/subjects. Please review.</p>
                        </div>
                    </div>
                    <a href="instructor-requests.php" class="btn btn-warning text-white">Review Now</a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Create Resources</h5>  
                    <a href="file_manager.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($resources) > 0): ?>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                            <?php foreach ($resources as $resource): ?>
                                <div class="col">
                                    <div class="card resource-card h-100">
                                        <div class="card-body text-center d-flex flex-column">
                                            <div class="file-icon mb-2">📄</div>
                                            <h6 class="card-title resource-title flex-grow-1"><?php echo htmlspecialchars($resource['title']); ?></h6>
                                            <?php if ($resource['subject_name']): ?>
                                                <span class="badge bg-secondary resource-badge mt-1"><?php echo htmlspecialchars($resource['subject_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer d-flex justify-content-around p-2 bg-light">
                                            <a href="../../download.php?id=<?php echo $resource['id']; ?>" class="btn btn-sm btn-primary flex-fill me-1">View</a>
                                            <a href="../../edit_resource.php?id=<?php echo $resource['id']; ?>" class="btn btn-sm btn-outline-secondary flex-fill ms-1">Edit</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Create your documents here integrated using OnlyOffice Editor.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
               <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Handled Subjects</h5>  
                    <a href="subjects-handled.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($subjects) > 0): ?>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                            <?php foreach (array_slice($subjects, 0, 6) as $subject): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm border-light">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title text-primary mb-1"><?php echo htmlspecialchars($subject['title']); ?></h5>
                                            <p class="card-text small text-muted flex-grow-1"><?php echo htmlspecialchars(substr($subject['description'], 0, 70)) . (strlen($subject['description']) > 70 ? '...' : ''); ?></p>
                                            <a href="specific-subject.php?id=<?php echo $subject['id']; ?>" class="btn btn-outline-primary btn-sm mt-2">View Subject</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">You are not assigned to any subjects yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>