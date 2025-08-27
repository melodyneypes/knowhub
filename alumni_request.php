<?php
require 'db.php';

$name = trim($_POST['alumni_name']);
$email = trim($_POST['alumni_email']);
$batch_year = intval($_POST['batch_year']);

// Save request for admin approval
$stmt = $conn->prepare("INSERT INTO alumni_requests (name, email, batch_year, requested_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("ssi", $name, $email, $batch_year);
$stmt->execute();
$stmt->close();

// Add notification for admin
$notif_msg = "Alumni access requested: $name ($email), Batch $batch_year";
$notif_stmt = $conn->prepare("INSERT INTO notifications (message, created_at) VALUES (?, NOW())");
$notif_stmt->bind_param("s", $notif_msg);
$notif_stmt->execute();
$notif_stmt->close();

echo "<script>alert('Your request has been submitted. Please wait for admin approval.'); window.location='login.php';</script>";