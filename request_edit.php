<?php
// filepath: e:\CAP101-DANG FILES\archive-system\request_edit.php
session_start();
require 'db.php';
require 'notify.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resource_id = filter_input(INPUT_POST, 'resource_id', FILTER_VALIDATE_INT);
    $reason = trim(filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_SPECIAL_CHARS));
    $user_id = $_SESSION['user']['id'];
    $requester_name = $_SESSION['user']['name'];

    if ($resource_id && $reason) {
        // Insert the edit request
        $stmt = $conn->prepare("INSERT INTO edit_requests (resource_id, user_id, reason, status, requested_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("iiss", $resource_id, $user_id, $reason);
        
        if ($stmt->execute()) {
            // Get instructor for this resource
            $resource_stmt = $conn->prepare("SELECT r.id, r.filename, s.instructor_id FROM resources r JOIN subjects s ON r.subject_id = s.id WHERE r.id = ?");
            $resource_stmt->bind_param("i", $resource_id);
            $resource_stmt->execute();
            $resource_result = $resource_stmt->get_result();
            $resource = $resource_result->fetch_assoc();
            $resource_stmt->close();
            
            // Send notification to instructor
            if ($resource && $resource['instructor_id'] && $resource['instructor_id'] != $user_id) {
                notify_edit_request($resource['instructor_id'], $requester_name, $resource['filename']);
            }
            
            // Redirect back to the resource page
            header('Location: resource.php?id=' . $resource_id . '&message=Edit request submitted successfully');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: Resource ID or reason is missing.";
    }
} else {
    header('Location: browse.php');
    exit();
}
?>