
<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resource_id = $_POST['resource_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user']['id'];

    // Check if the current date is before the school year starts
    $current_date = date('Y-m-d');
    $school_year_start = '2025-09-01';

    if ($current_date < $school_year_start) {
        $sql = "UPDATE resources SET title = ?, description = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $title, $description, $resource_id, $user_id);
        $stmt->execute();
    } else {
        echo "You cannot edit this resource after the school year starts.";
    }
}
?>