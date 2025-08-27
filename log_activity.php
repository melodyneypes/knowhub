
<?php
function log_activity($user_id, $action, $resource_id) {
    require 'db.php';
    $sql = "INSERT INTO activity_feed (user_id, action, resource_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $user_id, $action, $resource_id);
    $stmt->execute();
    $stmt->close();
}
?>