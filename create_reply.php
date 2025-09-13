<?php
// filepath: E:\CAP101-DANG FILES\archive-system\create_reply.php
session_start();
require 'db.php';
require 'notify.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $thread_id = filter_input(INPUT_POST, 'thread_id', FILTER_VALIDATE_INT);
    $content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS));
    $user_id = $_SESSION['user']['id'];
    $replier_name = $_SESSION['user']['name'];

    if ($thread_id && $content) {
        $stmt = $conn->prepare("INSERT INTO replies (thread_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $thread_id, $user_id, $content);
        
        if ($stmt->execute()) {
            // Get original post author
            $thread_stmt = $conn->prepare("SELECT t.user_id, t.title, u.name as author_name FROM threads t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
            $thread_stmt->bind_param("i", $thread_id);
            $thread_stmt->execute();
            $thread_result = $thread_stmt->get_result();
            $thread = $thread_result->fetch_assoc();
            $thread_stmt->close();
            
            // Send notification to original post author
            if ($thread && $thread['user_id'] != $user_id) {
                notify_post_reply($thread['user_id'], $replier_name, $thread['title']);
            }
            
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