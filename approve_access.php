
<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resource_id = $_POST['resource_id'];
    $student_id = $_POST['student_id'];
    $approved = $_POST['approved'];

    $sql = "UPDATE resource_access SET approved = ? WHERE resource_id = ? AND student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $approved, $resource_id, $student_id);
    $stmt->execute();
}
?>