<?php
// filepath: e:\CAP101-DANG FILES\archive-system\alumni_request.php
require 'db.php';

$name = trim($_POST['alumni_name']);
$email = trim($_POST['alumni_email']);
$batch_year = intval($_POST['batch_year']);

// Save request for admin approval
$stmt = $conn->prepare("INSERT INTO alumni_requests (name, email, batch_year, requested_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("ssi", $name, $email, $batch_year);
$stmt->execute();
$stmt->close();

echo "<script>alert('Your request has been submitted. Please wait for admin approval.'); window.location='login.php';</script>";
?>