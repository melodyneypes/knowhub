<?php
// filepath: users/admin/review_guest.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}
require '../../db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require '../../vendor/autoload.php';

// ------------------------------
// HANDLE APPROVE/REJECT ACTIONS
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    
    // Get guest request info
    $stmt = $conn->prepare("SELECT name, email, access_reason FROM guests_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();
    
    if ($request) {
        if ($_POST['action'] === 'approve') {
            // =======================================================
            // RESTORED USER CREATION/UPDATE LOGIC WITH ROLE ASSIGNMENT
            // =======================================================
            // Check if guest already exists in users table
            $stmt = $conn->prepare("SELECT id, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $request['email']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Existing user found — update role to guest
                $user = $result->fetch_assoc();
                $stmt->close();

                // Update role only if it's not already guest
                if ($user['role'] !== 'guest') {
                    $update = $conn->prepare("UPDATE users SET role = 'guest' WHERE id = ?");
                    $update->bind_param("i", $user['id']);
                    $update->execute();
                    $update->close();
                    error_log("Updated user role to guest: " . $request['email']);
                }
            } else {
                // No user found — create one with guest role
                $picture = '../../assets/images/default-profile.png'; // relative to admin folder
                $stmt->close();

                // Insert new user with explicit guest role
                $insert = $conn->prepare("INSERT INTO users (email, name, picture, role) VALUES (?, ?, ?, 'guest')");
                $insert->bind_param("sss", $request['email'], $request['name'], $picture);
                $insert->execute();
                $insert->close();

                error_log("Inserted new guest user: " . $request['email']);
            }
            // =======================================================
            
            // Send approval email
            sendApprovalEmail($request['email'], $request['name'], $request['access_reason']);
        } else {
            // Send rejection email
            sendRejectionEmail($request['email'], $request['name']);
        }

        // Update status in guests_requests
        $stmt = $conn->prepare("UPDATE guests_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $action, $request_id);
        $stmt->execute();
        $stmt->close();

        $message = "Guest request " . $action . " successfully. Email sent to " . htmlspecialchars($request['email']);
    }
}

// ------------------------------
// EMAIL FUNCTIONS (PHPMailer)
// ------------------------------
function sendApprovalEmail($email, $name, $reason) {
    $mail = new PHPMailer(true);
    try {
        // Gmail SMTP setup
        $mail->isSMTP();
        $mail->Host      = 'smtp.gmail.com';
        $mail->SMTPAuth  = true;
        $mail->Username  = 'knowhub.alaminos.2025@gmail.com';
        $mail->Password  = 'qbsf iejs ohdo xzpg'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port      = 587;

        // Recipients
        $mail->setFrom('knowhub.alaminos.2025@gmail.com', 'KnowHub Archive System');
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Guest Access Approved - KnowHub Archive System';
        $mail->Body = "
        <html>
        <head><title>Guest Access Approved</title></head>
        <body>
            <h2>KnowHub Archive System</h2>
            <p>Dear $name,</p>
            <p>Your guest access request has been <b>approved</b>.</p>
            <p><strong>Reason for access:</strong> $reason</p>
            <p>You can now log in using your Gmail account.</p>
            <p>Access the system here: 
               <a href='http://" . $_SERVER['HTTP_HOST'] . "/login.php'>KnowHub Login</a></p>
            <br>
            <p>Best regards,<br><b>KnowHub Team</b></p>
        </body>
        </html>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Approval email failed: {$mail->ErrorInfo}");
        return false;
    }
}

function sendRejectionEmail($email, $name) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host      = 'smtp.gmail.com';
        $mail->SMTPAuth  = true;
        $mail->Username  = 'knowhub.alaminos.2025@gmail.com';
        $mail->Password  = 'qbsf iejs ohdo xzpg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port      = 587;

        $mail->setFrom('knowhub.alaminos.2025@gmail.com', 'KnowHub Archive System');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Guest Access Declined - KnowHub Archive System';
        $mail->Body = "
        <html>
        <body>
            <h2>KnowHub Archive System</h2>
            <p>Dear $name,</p>
            <p>We regret to inform you that your guest access request has been <b>declined</b>.</p>
            <p>If you believe this is an error, please contact the system administrator.</p>
            <p>Best regards,<br><b>KnowHub Team</b></p>
        </body>
        </html>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Rejection email failed: {$mail->ErrorInfo}");
        return false;
    }
}

// ------------------------------
// FETCH PENDING GUEST REQUESTS
// ------------------------------
$pending_requests = [];
$stmt = $conn->prepare("SELECT * FROM guests_requests WHERE status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_requests[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Guest Requests</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
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
        .card-header {
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <div class="sidebar d-flex flex-column p-3" style="width: 240px;">
        <img src="<?php echo $_SESSION['user']['picture']; ?>" alt="Profile Picture" class="profile-img">
        <h5 class="text-center mb-4"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h5>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard-admin.php"><i class="bi bi-house"></i> Dashboard</a>
            <a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i> Notifications</a>
            <a class="nav-link" href="manage_instructors.php"><i class="bi bi-person-badge"></i> Manage Instructors</a>
            <a class="nav-link" href="threads-admin.php"><i class="bi bi-chat-dots"></i> Forums</a>
            <a class="nav-link" href="../../browse.php"><i class="bi bi-folder"></i> Resources</a>
            <a class="nav-link" href="user_logs.php"><i class="bi bi-journal-text"></i> User Logs</a>
            <a class="nav-link" href="documents.php"><i class="bi bi-plus-circle"></i> Create Document</a>
            <a class="nav-link" href="manage_subjects.php"><i class="bi bi-gear"></i> Manage Subjects</a>
            <a class="nav-link active" href="review_guest.php"><i class="bi bi-people"></i> Review Guest Requests</a>
            <a class="nav-link" href="../../logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content flex-grow-1">
        <h2 class="mb-4 fw-bold">Pending Guest Requests</h2>
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (count($pending_requests) > 0): ?>
            <?php foreach ($pending_requests as $req): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($req['name']); ?></h5>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($req['email']); ?></p>
                        <p><strong>Access Reason:</strong> <?php echo htmlspecialchars($req['access_reason']); ?></p>
                        <p><strong>Requested At:</strong> <?php echo htmlspecialchars($req['requested_at']); ?></p>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm ms-2">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No pending guest requests.</div>
        <?php endif; ?>
    </div>
</div>
<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>