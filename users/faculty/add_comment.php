<?php
session_start();
require '../../db.php';
require 'users/faculty/faculty_notify.php';

if (!isset($_SESSION['user'])) {
    die("You must be logged in to comment.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resource_id = intval($_POST['resource_id']);
    $user_id = $_SESSION['user']['id'];
    $comment = trim($_POST['comment']);
    $commenter_name = $_SESSION['user']['name'];
    
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO resource_comments (resource_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $resource_id, $user_id, $comment);
        
        if ($stmt->execute()) {
            // Get resource details including uploader
            $resource_stmt = $conn->prepare("SELECT r.title, r.uploader_id, u.role FROM resources r JOIN users u ON r.uploader_id = u.id WHERE r.id = ?");
            $resource_stmt->bind_param("i", $resource_id);
            $resource_stmt->execute();
            $resource_result = $resource_stmt->get_result();
            $resource = $resource_result->fetch_assoc();
            $resource_stmt->close();
            
            // Send notification to resource uploader if they are an instructor and it's not their own comment
            if ($resource && $resource['uploader_id'] != $user_id && $resource['role'] === 'instructor') {
                notify_faculty_resource_comment($resource['uploader_id'], $commenter_name, $resource['title'], $user_id);
            }
            
            // Redirect back to the resource page
            header("Location: resource.php?id=" . $resource_id);
            exit();
        } else {
            die("Error adding comment: " . $stmt->error);
        }
        
        $stmt->close();
    }
}

header("Location: browse.php");
exit();
?>