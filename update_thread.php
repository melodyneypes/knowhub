<?php 
// filepath: E:\CAP101-DANG FILES\archive-system\update_thread.php
session_start();
require 'db.php';

// 1. Check if the user is logged in
if (!isset($_SESSION['user'])) {
    echo "unauthorized";
    exit();
}

// 2. Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 3. Retrieve and sanitize input data
    $thread_id = filter_input(INPUT_POST, 'thread_id', FILTER_VALIDATE_INT);
    $new_description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    $user_id = $_SESSION['user']['id'];

    // 4. Validate input
    if ($thread_id && $new_description) {
        // 5. Verify the user is the owner of the thread
        $stmt_check = $conn->prepare("SELECT forum_id FROM threads WHERE id = ? AND user_id = ?");
        $stmt_check->bind_param("ii", $thread_id, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $thread = $result_check->fetch_assoc();
        $stmt_check->close();

        // 6. If the thread exists and belongs to the user, update it
        if ($thread) {
            $stmt_update = $conn->prepare("UPDATE threads SET description = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_description, $thread_id);
            
            if ($stmt_update->execute()) {
                // Return success message to the AJAX call
                echo "success";
            } else {
                // Return a specific error message
                echo "error: " . $conn->error;
            }
            $stmt_update->close();
        } else {
            // Unauthorized access attempt
            echo "unauthorized";
        }
    } else {
        // Missing data in the POST request
        echo "error: missing data";
    }
} else {
    // Invalid request method
    echo "error: invalid request";
}
?>
