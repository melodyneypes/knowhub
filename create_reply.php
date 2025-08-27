<?php
// filepath: E:\CAP101-DANG FILES\archive-system\create_reply.php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $thread_id = filter_input(INPUT_POST, 'thread_id', FILTER_VALIDATE_INT);
    $content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS));
    $user_id = $_SESSION['user']['id'];

    if ($thread_id && $content) {
        $stmt = $conn->prepare("INSERT INTO replies (thread_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $thread_id, $user_id, $content);
        
        if ($stmt->execute()) {
            // Redirect back to the threads page after successful reply
            header('Location: threads.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: Thread ID or reply content is missing.";
    }
} else {
    header('Location: threads.php');
    exit();
}
?>