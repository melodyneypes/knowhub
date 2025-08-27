<?php
// filepath: e:\CAP101-DANG FILES\archive-system\add_post.php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $thread_id = $_POST['thread_id'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO posts (thread_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $thread_id, $user_id, $content);
    $stmt->execute();

    header("Location: view_thread.php?id=$thread_id");
    exit();
}
