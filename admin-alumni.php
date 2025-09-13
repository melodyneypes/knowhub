<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle alumni request approval
if (isset($_GET['approve_id'])) {
    $request_id = intval($_GET['approve_id']);
    
    // Get the request details
    $stmt = $conn->prepare("SELECT name, email, batch_year FROM alumni_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
        
        // Add user to users table with alumni role
        $stmt = $conn->prepare("INSERT INTO users (email, name, picture, role) VALUES (?, ?, ?, 'alumni')");
        $empty_picture = ''; // No picture for now, can be updated later
        $stmt->bind_param("sss", $request['email'], $request['name'], $empty_picture);
        $stmt->execute();
        
        // Delete the request
        $stmt = $conn->prepare("DELETE FROM alumni_requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        
        // Send approval email
        sendApprovalEmail($request['email'], $request['name']);
        
        // Redirect back with success message
        header("Location: admin_alumni_requests.php?message=approved");
        exit();
    }
}

// Handle alumni request decline
if (isset($_GET['decline_id'])) {
    $request_id = intval($_GET['decline_id']);
    
    // Get the request details
    $stmt = $conn->prepare("SELECT email, name FROM alumni_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
        
        // Delete the request
        $stmt = $conn->prepare("DELETE FROM alumni_requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        
        // Send decline email
        sendDeclineEmail($request['email'], $request['name']);
        
        // Redirect back with success message
        header("Location: admin_alumni_requests.php?message=declined");
        exit();
    }
}

// Function to send approval email
function sendApprovalEmail($email, $name) {
    $subject = "Alumni Request Approved - KnowHub Archive System";
    $message = "
    <html>
    <head>
        <title>Alumni Request Approved</title>
    </head>
    <body>
        <h2>KnowHub Archive System</h2>
        <p>Dear $name,</p>
        <p>Your alumni access request has been approved. You can now log in to the system using your Gmail account with the role of Alumni.</p>
        <p>Please visit <a href='http://yourdomain.com/login.php'>the login page</a> to access the system.</p>
        <p>Best regards,<br>The KnowHub Team</p>
    </body>
    </html>
    ";
    
    // Always set content-type when sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@yourdomain.com" . "\r\n";
    
    mail($email, $subject, $message, $headers);
}

// Function to send decline email
function sendDeclineEmail($email, $name) {
    $subject = "Alumni Request Declined - KnowHub Archive System";
    $message = "
    <html>
    <head>
        <title>Alumni Request Declined</title>
    </head>
    <body>
        <h2>KnowHub Archive System</h2>
        <p>Dear $name,</p>
        <p>We regret to inform you that your alumni access request has been declined.</p>
        <p>If you believe this is an error, please contact the system administrator.</p>
        <p>Best regards,<br>The KnowHub Team</p>
    </body>
    </html>
    ";
    
    // Always set content-type when sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@yourdomain.com" . "\r\n";
    
    mail($email, $subject, $message, $headers);
}
?>