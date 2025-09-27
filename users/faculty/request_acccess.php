
<?php
session_start();
require '../../db.php';
require 'users/faculty/faculty_notify.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'] ?? null;
    $reason = trim($_POST['reason'] ?? '');
    $user_id = $_SESSION['user']['id'];
    $user_name = $_SESSION['user']['name'];
    
    if (!$subject_id || !is_numeric($subject_id) || empty($reason)) {
        $_SESSION['message'] = 'Invalid request parameters.';
        header('Location: browse.php');
        exit();
    }
    
    // Check if user already requested access to this subject
    $stmt = $conn->prepare("SELECT id FROM access_requests WHERE user_id = ? AND subject_id = ?");
    $stmt->bind_param("ii", $user_id, $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['message'] = 'You have already requested access to this subject.';
        header('Location: ../../browse.php');
        exit();
    }
    $stmt->close();
    
    // Insert access request
    $stmt = $conn->prepare("INSERT INTO access_requests (user_id, subject_id, reason, status, request_type, created_at) VALUES (?, ?, ?, 'pending', 'subject_access', NOW())");
    $stmt->bind_param("iiss", $user_id, $subject_id, $reason, $status);
    
    if ($stmt->execute()) {
        // Get subject name for notification
        $subject_stmt = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
        $subject_stmt->bind_param("i", $subject_id);
        $subject_stmt->execute();
        $subject_stmt->bind_result($subject_name);
        $subject_stmt->fetch();
        $subject_stmt->close();
        
        // Notify faculty members who handle this subject
        $faculty_stmt = $conn->prepare("SELECT instructor_id FROM subject_instructors WHERE subject_id = ?");
        $faculty_stmt->bind_param("i", $subject_id);
        $faculty_stmt->execute();
        $faculty_result = $faculty_stmt->get_result();
        
        while ($faculty_row = $faculty_result->fetch_assoc()) {
            $faculty_id = $faculty_row['instructor_id'];
            notify_faculty_access_request($faculty_id, $user_name, $subject_name, $user_id);
        }
        $faculty_stmt->close();
        
        $_SESSION['message'] = 'Access request submitted successfully. Please wait for approval.';
    } else {
        $_SESSION['message'] = 'Error submitting request. Please try again.';
    }
    $stmt->close();
    
    header('Location: ../../browse.php');
    exit();
} else {
    header('Location: ../../browse.php');
    exit();
}
?>