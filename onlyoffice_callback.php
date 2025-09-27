<?php
// onlyoffice_callback.php - Simplified version
require 'db.php';
require 'vendor/autoload.php';

use \Firebase\JWT\JWT;

// Define the secret key (MUST match the one used in onlyoffice_editor.php)
$secret_key = 'JyqijJ7aTxcBojv1IgKgKxvtS5pLhm90';

// --- RECEIVE AND DECODE CALLBACK DATA ---
$data = file_get_contents('php://input');
$json = json_decode($data, true);

// Always return success for empty requests
if (empty($json)) {
    http_response_code(200);
    echo '{"error":0}';
    exit();
}

// Get token from JSON body (OnlyOffice sends it here, not in headers)
$token = $json['token'] ?? '';

try {
    // Decode and verify the JWT token
    $decoded = (array) JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
} catch (\Exception $e) {
    error_log("OnlyOffice Callback Error: Invalid JWT: " . $e->getMessage());
    http_response_code(403);
    echo '{"error":1}';
    exit();
}

// Extract data
$status = $json['status'] ?? 0;
$resource_id = $_GET['resourceId'] ?? null;
$user_id = $decoded['editorConfig']['user']['id'] ?? 'unknown';
$file_content = $json['file'] ?? null; // Base64 encoded file content

// Handle document saving (status 2 = document closed and should be saved)
if ($status === 2 && $resource_id && $file_content) {
    // Save audit trail
    $stmt = $conn->prepare("INSERT INTO resource_audits (resource_id, user_id, action, details) VALUES (?, ?, ?, ?)");
    $action = 'document_saved';
    $details = 'Document saved by user ' . $user_id;
    $stmt->bind_param("iiss", $resource_id, $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
    
    // Create a simple version backup
    $stmt = $conn->prepare("SELECT file_path FROM resources WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($resource = $result->fetch_assoc()) {
        $original_path = 'uploads/' . basename($resource['file_path']);
        $version_path = 'uploads/versions/' . $resource_id . '_' . time() . '_' . basename($resource['file_path']);
        
        // Create versions directory if it doesn't exist
        if (!is_dir('uploads/versions')) {
            mkdir('uploads/versions', 0777, true);
        }
        
        // Copy current file to versions directory
        if (file_exists($original_path)) {
            copy($original_path, $version_path);
            
            // Save version record
            $stmt = $conn->prepare("INSERT INTO resource_versions (resource_id, file_path, uploader_id) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $resource_id, $version_path, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Save the new file content
        $file_data = base64_decode($file_content);
        file_put_contents($original_path, $file_data);
        
        // Update database
        $stmt = $conn->prepare("UPDATE resources SET updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $resource_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Always respond with success
http_response_code(200);
echo '{"error":0}';
exit();
?>