
<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $file = $_FILES['file'];
    $file_path = 'uploads/' . basename($file['name']);
    move_uploaded_file($file['tmp_name'], $file_path);

    // Parse the class list and update the database
    $handle = fopen($file_path, 'r');
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        $student_email = $data[0];
        $subject_id = $_POST['subject_id'];
        $sql = "INSERT INTO enrollments (student_email, subject_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $student_email, $subject_id);
        $stmt->execute();
    }
    fclose($handle);
}
?>