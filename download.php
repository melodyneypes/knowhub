
<?php
session_start();
require 'db.php';

$resource_id = $_GET['resource_id'];
$sql = "SELECT * FROM resources WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();
$resource = $result->fetch_assoc();

if (pathinfo($resource['file_path'], PATHINFO_EXTENSION) == 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($resource['file_path']) . '"');
    readfile($resource['file_path']);
} else {
    echo "Only PDF files can be downloaded.";
}
?>