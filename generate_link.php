
<?php
function generate_expired_link($resource_id, $expiry_time) {
    $token = bin2hex(random_bytes(16));
    $expiry_timestamp = time() + $expiry_time;
    // Store token and expiry_timestamp in the database
    return "download.php?token=$token";
}
?>