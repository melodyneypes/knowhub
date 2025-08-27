<?php
session_start();
require 'db.php';
$resource_id = intval($_GET['resource_id'] ?? 0);

$stmt = $conn->prepare("SELECT rv.*, u.name FROM resource_versions rv JOIN users u ON rv.uploader_id = u.id WHERE rv.resource_id = ? ORDER BY rv.uploaded_at DESC");
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();
$versions = [];
while ($row = $result->fetch_assoc()) {
    $versions[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Version History</title>
    <link href="/assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="uploads.php" class="btn btn-secondary mb-3">&larr; Back</a>
    <h2>Version History</h2>
    <?php if ($versions): ?>
        <?php foreach ($versions as $ver): ?>
            <div class="card mb-3">
                <div class="card-body bg-info text-dark">
                    <h5><?php echo htmlspecialchars($ver['title']); ?></h5>
                    <div><?php echo date('F j, Y, g:i a', strtotime($ver['uploaded_at'])); ?></div>
                    <div><?php echo htmlspecialchars($ver['name']); ?></div>
                    <a href="<?php echo htmlspecialchars($ver['file_path']); ?>" class="btn btn-primary mt-2" target="_blank">View File</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No versions found.</div>
    <?php endif; ?>
</div>
</body>
</html>