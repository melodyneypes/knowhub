<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || !isset($_GET['id'])) {
    header('Location: threads.php');
    exit();
}

$thread_id = $_GET['id'];
$user_id = $_SESSION['user']['id'];

// Fetch the thread to get its forum_id and verify ownership
$stmt_check = $conn->prepare("SELECT forum_id FROM threads WHERE id = ? AND user_id = ?");
$stmt_check->bind_param("ii", $thread_id, $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$thread = $result_check->fetch_assoc();
$stmt_check->close();

if ($thread) {
    $stmt_delete = $conn->prepare("DELETE FROM threads WHERE id = ? AND user_id = ?");
    $stmt_delete->bind_param("ii", $thread_id, $user_id);

    if ($stmt_delete->execute()) {
        header('Location: threads.php?forum_id=' . urlencode($thread['forum_id']));
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $stmt_delete->close();
} else {
    echo "You are not authorized to delete this thread.";
}
?>
