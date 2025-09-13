<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Handle approve request
if (isset($_GET['approve_id'])) {
    $id = intval($_GET['approve_id']);
    
    // Get request details
    $stmt = $conn->prepare("SELECT name, email, batch_year FROM alumni_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $req = $result->fetch_assoc();
        
        // Create alumni user
        $stmt = $conn->prepare("INSERT INTO users (name, email, role, year_level) VALUES (?, ?, 'alumni', ?)");
        $stmt->bind_param("ssi", $req['name'], $req['email'], $req['batch_year']);
        $stmt->execute();
        $stmt->close();
        
        // Remove request
        $conn->query("DELETE FROM alumni_requests WHERE id = $id");
        
        // Send approval email
        if (sendApprovalEmail($req['email'], $req['name'])) {
            $_SESSION['message'] = "Alumni access approved! Email notification sent.";
        } else {
            $_SESSION['message'] = "Alumni access approved! But failed to send email notification.";
        }
    } else {
        $_SESSION['message'] = "Request not found.";
    }
}

// Handle decline request
if (isset($_GET['decline_id'])) {
    $id = intval($_GET['decline_id']);
    
    // Get request details
    $stmt = $conn->prepare("SELECT name, email FROM alumni_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $req = $result->fetch_assoc();
        
        // Remove request
        $conn->query("DELETE FROM alumni_requests WHERE id = $id");
        
        // Send decline email
        if (sendDeclineEmail($req['email'], $req['name'])) {
            $_SESSION['message'] = "Alumni request declined. Email notification sent.";
        } else {
            $_SESSION['message'] = "Alumni request declined. But failed to send email notification.";
        }
    } else {
        $_SESSION['message'] = "Request not found.";
    }
}

// Function to send approval email using PHPMailer
function sendApprovalEmail($email, $name) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings - Update these with your actual SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true;
        $mail->Username   = 'melodycantilloneypes@gmail.com'; // SMTP username
        $mail->Password   = 'bpcgmicemrtitejb'; // SMTP password (use App Password for Gmail)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('noreply@knowhub.com', 'KnowHub Request Response');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Alumni Request Approved - KnowHub';
        $mail->Body    = "
        <html>
        <head>
            <title>Alumni Request Approved</title>
        </head>
        <body>
            <h2>KnowHub Archive System</h2>
            <p>Dear " . htmlspecialchars($name) . ",</p>
            <p>Your alumni access request has been approved. You can now log in to the system using your Gmail account with the role of Alumni.</p>
            <p>Please visit <a href='http://localhost/login.php'>the login page</a> to access the system.</p>
            <p>Best regards,<br>The KnowHub Team</p>
        </body>
        </html>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to send decline email using PHPMailer
function sendDeclineEmail($email, $name) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings - Update these with your actual SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'melodycantilloneypes@gmail.com';
        $mail->Password   = 'bpcgmicemrtitejb';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('noreply@knowhub.com', 'KnowHub Request Declined');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Alumni Request Declined - KnowHub';
        $mail->Body    = "
        <html>
        <head>
            <title>Alumni Request Declined</title>
        </head>
        <body>
            <h2>KnowHub</h2>
            <p>Dear " . htmlspecialchars($name) . ",</p>
            <p>We regret to inform you that your alumni access request has been declined.</p>
            <p>If you believe this is an error, please contact the system administrator.</p>
            <p>Best regards,<br>The KnowHub Team</p>
        </body>
        </html>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Fetch recent notifications (latest 5)
$notifications = [];
$notif_stmt = $conn->prepare("SELECT message, created_at FROM notifications ORDER BY created_at DESC LIMIT 5");
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
while ($row = $notif_result->fetch_assoc()) {
    $notifications[] = $row;
}
$notif_stmt->close();

// Fetch recent user logs (latest 5)
$user_logs = [];
$log_stmt = $conn->prepare("SELECT users.name, user_logs.action, user_logs.details, user_logs.timestamp 
    FROM user_logs 
    JOIN users ON user_logs.user_id = users.id 
    ORDER BY user_logs.timestamp DESC LIMIT 5");
$log_stmt->execute();
$log_result = $log_stmt->get_result();
while ($row = $log_result->fetch_assoc()) {
    $user_logs[] = $row;
}
$log_stmt->close();

// Fetch pending alumni requests (latest 5)
$alumni_requests = [];
$req_stmt = $conn->prepare("SELECT id, name, email, batch_year, requested_at FROM alumni_requests ORDER BY requested_at DESC LIMIT 5");
$req_stmt->execute();
$req_result = $req_stmt->get_result();
while ($row = $req_result->fetch_assoc()) {
    $alumni_requests[] = $row;
}
$req_stmt->close();

// Fetch all alumni requests for the modal section
$all_alumni_requests = [];
$result = $conn->query("SELECT * FROM alumni_requests ORDER BY requested_at DESC");
while ($row = $result->fetch_assoc()) {
    $all_alumni_requests[] = $row;
}

// Add this at the top of your navbar file
if (isset($_SESSION['user'])) {
    require 'db.php';
    $user_id = $_SESSION['user']['id'];
    $unread_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->bind_param("i", $user_id);
    $unread_stmt->execute();
    $unread_result = $unread_stmt->get_result();
    $unread_row = $unread_result->fetch_assoc();
    $unread_count = $unread_row['unread_count'];
    $unread_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
      body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: #fff;
        }
        .sidebar .nav-link {
            color: #fff;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: #495057;
            color: #ffc107;
        }
        .profile-img {
            max-width: 120px;
            margin: 20px auto 10px auto;
            display: block;
            border-radius: 50%;
            border: 3px solid #fff;
        }
    </style>
</head>
<body>
    <div class="d-flex">
    <!-- Sidebar Navigation -->
    <div class="sidebar d-flex flex-column p-3" style="width: 250px;">
        <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3 mx-auto" style="max-width: 70px;">
        <h5 class="text-center mb-4"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h5>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard-admin.php"><i class="bi bi-house"></i> Home</a>
            <a class="nav-link" href="notifications.php"> <i class="bi bi-bell"></i>
                Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link" href="manage_instructors.php"><i class="bi bi-people"></i> Manage Subject Instructors</a>
            <a class="nav-link" href="threads-admin.php"><i class="bi bi-chat-dots"></i> Forums</a>
            <a class="nav-link" href="browse.php"><i class="bi bi-folder"></i> Resources</a>
            <a class="nav-link" href="admin_user_logs.php"><i class="bi bi-journal-text"></i> User Logs</a>
            <a class="nav-link" href="logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
            
        </nav>
    </div>
    <!-- Main Content -->
    <div class="main-content flex-1 col-md-10">
        <div class="container-fluid py-4">
            <h2 class="mb-4">Welcome, Admin!</h2>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Recent Notifications -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <strong>Recent Notifications</strong>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <li class="list-group-item">
                                        <span class="fw-bold"><?php echo htmlspecialchars($notif['message']); ?></span>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($notif['created_at']); ?></small>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">No notifications yet.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <!-- Recent User Logs -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <strong>Recent User Logs</strong>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($user_logs)): ?>
                                <?php foreach ($user_logs as $log): ?>
                                    <li class="list-group-item">
                                        <span class="fw-bold"><?php echo htmlspecialchars($log['name']); ?></span>
                                        <?php echo htmlspecialchars($log['action']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['timestamp']); ?></small>
                                        <?php if (!empty($log['details'])): ?>
                                            <br><small><?php echo htmlspecialchars($log['details']); ?></small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">No user logs yet.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <!-- Quick Search -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <strong>Quick Search</strong>
                        </div>
                        <div class="card-body">
                            <form method="get" action="search.php">
                                <input type="text" name="q" class="form-control mb-2" placeholder="Search users, subjects, resources...">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- Pending Alumni Requests -->
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                            <strong>Pending Alumni Requests</strong>
                            <?php if (!empty($all_alumni_requests)): ?>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#allRequestsModal">
                                    View All (<?php echo count($all_alumni_requests); ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($alumni_requests)): ?>
                                <?php foreach ($alumni_requests as $req): ?>
                                    <li class="list-group-item">
                                        <span class="fw-bold"><?php echo htmlspecialchars($req['name']); ?></span>
                                        <br>
                                        <small><?php echo htmlspecialchars($req['email']); ?> | Batch <?php echo htmlspecialchars($req['batch_year']); ?></small>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($req['requested_at']); ?></small>
                                        <br>
                                        <button class="btn btn-success btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $req['id']; ?>">
                                            Approve
                                        </button>
                                        <button class="btn btn-danger btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#declineModal<?php echo $req['id']; ?>">
                                            Decline
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">No pending alumni requests.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    
    <!-- Modals for approval confirmation -->
    <?php foreach ($alumni_requests as $req): ?>
        <!-- Approve Modal -->
        <div class="modal fade" id="approveModal<?php echo $req['id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel<?php echo $req['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveModalLabel<?php echo $req['id']; ?>">Confirm Approval</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to approve alumni access for <strong><?php echo htmlspecialchars($req['name']); ?></strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="?approve_id=<?php echo $req['id']; ?>" class="btn btn-success">Yes, Approve</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Decline Modal -->
        <div class="modal fade" id="declineModal<?php echo $req['id']; ?>" tabindex="-1" aria-labelledby="declineModalLabel<?php echo $req['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="declineModalLabel<?php echo $req['id']; ?>">Confirm Decline</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to decline alumni access for <strong><?php echo htmlspecialchars($req['name']); ?></strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="?decline_id=<?php echo $req['id']; ?>" class="btn btn-danger">Yes, Decline</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Modal for all requests -->
    <?php if (!empty($all_alumni_requests)): ?>
        <div class="modal fade" id="allRequestsModal" tabindex="-1" aria-labelledby="allRequestsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="allRequestsModalLabel">All Pending Alumni Requests</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (!empty($all_alumni_requests)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Batch Year</th>
                                            <th>Requested At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_alumni_requests as $req): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($req['name']); ?></td>
                                                <td><?php echo htmlspecialchars($req['email']); ?></td>
                                                <td><?php echo htmlspecialchars($req['batch_year']); ?></td>
                                                <td><?php echo htmlspecialchars($req['requested_at']); ?></td>
                                                <td>
                                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $req['id']; ?>">
                                                        Approve
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#declineModal<?php echo $req['id']; ?>">
                                                        Decline
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No pending alumni requests.</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Bootstrap JS and Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>