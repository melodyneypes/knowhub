<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    die("You must be logged in to comment.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resource_id = intval($_POST['resource_id']);
    $user_id = $_SESSION['user']['id'];
    $comment = trim($_POST['comment']);
    
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO resource_comments (resource_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $resource_id, $user_id, $comment);
        
        if ($stmt->execute()) {
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