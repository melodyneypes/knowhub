<?php
// filepath: e:\CAP101-DANG FILES\archive-system\admin_alumni_requests.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require 'db.php';

// Approve request
if (isset($_GET['approve_id'])) {
    $id = intval($_GET['approve_id']);
    $req = $conn->query("SELECT * FROM alumni_requests WHERE id=$id")->fetch_assoc();
    if ($req) {
        // Create alumni user
        $stmt = $conn->prepare("INSERT INTO users (name, email, role, batch_year) VALUES (?, ?, 'alumni', ?)");
        $stmt->bind_param("ssi", $req['name'], $req['email'], $req['batch_year']);
        $stmt->execute();
        $stmt->close();
        // Remove request
        $conn->query("DELETE FROM alumni_requests WHERE id=$id");
        $msg = "Alumni access approved!";
    }
}

// List requests
$requests = $conn->query("SELECT * FROM alumni_requests ORDER BY requested_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Alumni Access Requests</title>
    <link href="assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Alumni Access Requests</h2>
    <?php if (isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>
    <table class="table">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Batch Year</th><th>Requested At</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php while ($r = $requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['name']); ?></td>
                <td><?php echo htmlspecialchars($r['email']); ?></td>
                <td><?php echo htmlspecialchars($r['batch_year']); ?></td>
                <td><?php echo htmlspecialchars($r['requested_at']); ?></td>
                <td>
                    <a href="?approve_id=<?php echo $r['id']; ?>" class="btn btn-success btn-sm"
                       onclick="return confirm('Approve this alumni?');">Approve</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>