<?php
require '../../db.php';

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$access_reason = trim($_POST['access_reason']);

// Save guest request for admin approval
$stmt = $conn->prepare("INSERT INTO guests_requests (name, email, access_reason, requested_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("sss", $name, $email, $access_reason);
$stmt->execute();
$stmt->close();

echo "<script>alert('Your request has been submitted. Please wait for admin approval.'); window.location='../../login.php';</script>";
?>