<?php
// filepath: e:\CAP101-DANG FILES\archive-system\create_thread.php
session_start();
require 'db.php';
require 'notify.php';

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forum_id = $_POST['forum_id'] ?? null;
    $description = $_POST['description'] ?? null;
    $id = $_SESSION['user']['id'];
    $poster_name = $_SESSION['user']['name'];
    $title = 'Re: ' . $description; // You might want to create a separate input for title

    if ($forum_id && $description) {
        // Insert the new thread into the threads table
        $stmt = $conn->prepare("INSERT INTO threads (forum_id, user_id, title, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $forum_id, $id, $title, $description);  // $id is user_id from session

        if ($stmt->execute()) {
            // Get instructor for this forum
            $forum_stmt = $conn->prepare("SELECT s.id, f.id as forum_id FROM threads f JOIN subjects s ON f.id = s.id WHERE f.id = ?");
            $forum_stmt->bind_param("s", $forum_id);
            $forum_stmt->execute();
            $forum_result = $forum_stmt->get_result();
            $forum = $forum_result->fetch_assoc();
            $forum_stmt->close();
            
            // Send notification to instructor
            if ($forum && $forum['instructor_id'] && $forum['instructor_id'] != $id) {
                notify_instructor_forum_post($forum['instructor_id'], $poster_name, $forum['forum_name']);
            }
            
            // Redirect back to the forum page after successful post
            header('Location: threads.php?forum_id=' . urlencode($forum_id));
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: Forum ID or description is missing.";
    }
} else {
    // If the form wasn't submitted, redirect to a safe page
    header('Location: index.php');
    exit();
}
?>