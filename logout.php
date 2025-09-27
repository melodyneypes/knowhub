<?php
session_start();
require 'db.php';

// Log the logout action if user is logged in
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $action = 'logout';
    $details = 'User logged out';
    $role = $_SESSION['user']['role'];
    $email = $_SESSION['user']['email'];
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, role, email, action, timestamp, details) VALUES (?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("issss", $user_id, $role, $email, $action, $details);
    $stmt->execute();
    $stmt->close();
}

// Destroy session and redirect
session_destroy();
header('Location: login.php');
exit();
?>