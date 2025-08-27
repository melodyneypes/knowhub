
<?php
function self_destruct_file($file_path, $expiry_time) {
    if (file_exists($file_path) && (time() - filemtime($file_path)) > $expiry_time) {
        unlink($file_path);
    }
}
?>