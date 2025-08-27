
<?php
function encrypt_file($file_path, $key) {
    $data = file_get_contents($file_path);
    $encrypted_data = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    file_put_contents($file_path, $encrypted_data);
}
?>