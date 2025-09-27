<?php
session_start();

// 1. Authentication and Authorization Check
if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit();
}

require '../../db.php'; // Database connection

$user_id = $_SESSION['user']['id'];

// 2. Input Validation
if (!isset($_GET['subject_id']) || !is_numeric($_GET['subject_id'])) {
    $_SESSION['message'] = "Invalid subject ID.";
    $_SESSION['message_type'] = "danger";
    header('Location: subjects-handled.php');
    exit();
}

$subject_id = (int)$_GET['subject_id'];

// 3. Database Deletion (Withdrawal)
// We delete the pending 'resource_edit' request made by the current user for the specified subject.
try {
    $stmt = $conn->prepare(
        "DELETE FROM access_requests 
         WHERE user_id = ? 
         AND subject_id = ? 
         AND status = 'pending' 
         AND request_type = 'resource_edit'"
    );
    $stmt->bind_param("ii", $user_id, $subject_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Your request for subject ID " . $subject_id . " has been successfully withdrawn.";
        $_SESSION['message_type'] = "success";
    } else {
        // This handles cases where the request was already approved/rejected or didn't exist
        $_SESSION['message'] = "The pending request could not be found or has already been processed.";
        $_SESSION['message_type'] = "warning";
    }
    $stmt->close();

} catch (Exception $e) {
    // Log error and notify user
    error_log("Error withdrawing request: " . $e->getMessage());
    $_SESSION['message'] = "An error occurred while withdrawing your request. Please try again.";
    $_SESSION['message_type'] = "danger";
}

// 4. Redirection back to the subjects list
header('Location: subjects-handled.php');
exit();
?>