<?php
session_start();
require 'db.php';

// Validate resource_id
$resource_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$resource_id) {
    die("Invalid resource ID.");
}

// Fetch resource
$sql = "SELECT * FROM resources WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();
$resource = $result->fetch_assoc();
$stmt->close();

if (!$resource) {
    die("Resource not found.");
}

$file_path = $resource['file_path'];
$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// For PDF files, display inline (view in browser)
if ($ext == 'pdf' && file_exists($file_path)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
    readfile($file_path);
    exit;
} 
// For other supported file types, redirect to OnlyOffice editor
else if (in_array($ext, ['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt'])) {
    $file_url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $file_path;
    header('Location: onlyoffice_editor.php?file=' . urlencode($file_url) . '&title=' . urlencode($resource['title']));
    exit;
} 
// For other files, download them
else {
    header('Location: download.php?id=' . $resource_id);
    exit;
}
?>