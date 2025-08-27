<?php
// filepath: e:\CAP101-DANG FILES\archive-system\new_thread.php
session_start();
require 'db.php';

if (!isset($_GET['forum_id'])) {
    die("Invalid forum room.");
}

$forum_id = $_GET['forum_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id']; // Assuming you store user ID in session

    $stmt = $conn->prepare("INSERT INTO threads (forum_id, title, description, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $forum_id, $title, $description, $user_id);
    $stmt->execute();

    header("Location: threads.php?forum_id=$forum_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Thread</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Create New Thread in <?php echo ucfirst(htmlspecialchars($forum_id)); ?></h2>
        <form method="post">
            <div class="mb-3">
                <label for="title" class="form-label">Thread Title</label>
                <input type="text" class="form-control" name="title" id="title" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" name="description" id="description" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Create Thread</button>
        </form>
    </div>
</body>
</html>
