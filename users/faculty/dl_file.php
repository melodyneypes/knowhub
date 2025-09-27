<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit();
}

require '../../db.php';

$user_id = $_SESSION['user']['id'];
$file_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$file_id) {
    die("Invalid file ID.");
}

// Fetch file information
$stmt = $conn->prepare("SELECT * FROM file_manager WHERE id = ? AND uploader_id = ? AND item_type = 'file'");
$stmt->bind_param("ii", $file_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();
$stmt->close();

if (!$file) {
    die("File not found or you don't have permission to access it.");
}

// Check if file exists on server
if (!file_exists($file['file_path'])) {
    die("File not found on server.");
}

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file['file_path']));

// Clear output buffer
ob_clean();
flush();

// Output file content
readfile($file['file_path']);
exit;