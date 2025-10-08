<?php
session_start();
require 'db.php';

// Log the incoming request for debugging
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'input' => file_get_contents("php://input"),
    'get_params' => $_GET,
    'post_params' => $_POST,
    'session_user' => $_SESSION['user']['id'] ?? null
];
file_put_contents(__DIR__ . '/callback_debug.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Read input data from OnlyOffice
$input = file_get_contents("php://input");
$data = json_decode($input, true);

$status = $data["status"] ?? null;
$resourceId = $_GET['resourceId'] ?? ($_POST['resourceId'] ?? 0);
$userId = $_SESSION['user']['id'] ?? 0;

// Always respond with {"error":0} so OnlyOffice knows callback worked
$response = ["error" => 0];

// Handle different statuses that indicate document changes
// Status 2 = Document is ready for saving
// Status 6 = Document is being edited but needs force save
if (($status == 2 || $status == 6) && $resourceId) { 
    // Check if we have a download URL or file content
    $downloadUrl = $data['url'] ?? null;
    $fileContent = $data['file'] ?? null; // Some versions send base64 encoded file directly

    // Log what we received
    $receiveLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => 'Received callback with file data',
        'resource_id' => $resourceId,
        'user_id' => $userId,
        'has_download_url' => !empty($downloadUrl),
        'has_file_content' => !empty($fileContent),
        'status' => $status
    ];
    file_put_contents(__DIR__ . '/callback_debug.log', json_encode($receiveLog, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

    if ($downloadUrl || $fileContent) {
        // Ensure uploads dir exists
        $uploadDir = __DIR__ . "/uploads/versions/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Create new version filename
        $versionFileName = "resource_" . $resourceId . "_v" . time() . ".docx";
        $versionFilePath = $uploadDir . $versionFileName;

        $success = false;
        if ($fileContent) {
            // File content sent directly as base64
            $decodedContent = base64_decode($fileContent);
            if ($decodedContent !== false) {
                file_put_contents($versionFilePath, $decodedContent);
                $success = true;
            }
        } elseif ($downloadUrl) {
            // Download the file from Document Server
            $fileContent = file_get_contents($downloadUrl);
            if ($fileContent !== false) {
                file_put_contents($versionFilePath, $fileContent);
                $success = true;
            }
        }

        if ($success) {
            try {
                // Check if $conn is a valid mysqli object
                if (!isset($conn) || !is_object($conn) || get_class($conn) !== 'mysqli') {
                    throw new Exception('Database connection is not valid: ' . (isset($conn) ? gettype($conn) : 'not set'));
                }
                
                // Log before database operations
                $dbLog = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Starting database operations',
                    'resource_id' => $resourceId,
                    'user_id' => $userId
                ];
                file_put_contents(__DIR__ . '/callback_debug.log', json_encode($dbLog, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
                
                // Get the next version number with a simple query
                $versionResult = $conn->query("SELECT IFNULL(MAX(version_number), 0) + 1 as next_version FROM resource_version WHERE resource_id = " . intval($resourceId));
                $nextVersion = 1;
                if ($versionResult && $row = $versionResult->fetch_assoc()) {
                    $nextVersion = $row['next_version'];
                }
                
                // Insert into resource_version (your actual table name)
                $filePath = "uploads/versions/" . $versionFileName;
                $stmt = $conn->prepare("INSERT INTO resource_version (resource_id, version_number, file_path) VALUES (?, ?, ?)");
                
                if (!$stmt) {
                    throw new Exception('Prepare statement failed for resource_version: ' . $conn->error);
                }
                
                $stmt->bind_param("iis", $resourceId, $nextVersion, $filePath);
                
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed for resource_version: ' . $stmt->error);
                }
                $stmt->close();

                // Update main resources table with latest file
                $stmt = $conn->prepare("UPDATE resources SET file_path = ?, updated_at = NOW() WHERE id = ?");
                
                if (!$stmt) {
                    throw new Exception('Prepare statement failed for resources: ' . $conn->error);
                }
                
                $stmt->bind_param("si", $filePath, $resourceId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed for resources: ' . $stmt->error);
                }
                $stmt->close();

                // Insert into resource_audit
                $stmt = $conn->prepare("INSERT INTO resource_audit (resource_id, user_id, action) VALUES (?, ?, 'edited')");
                
                if (!$stmt) {
                    throw new Exception('Prepare statement failed for resource_audit: ' . $conn->error);
                }
                
                $stmt->bind_param("ii", $resourceId, $userId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed for resource_audit: ' . $stmt->error);
                }
                $stmt->close();
                
                // Log successful operation
                $logSuccess = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Successfully saved document version',
                    'resource_id' => $resourceId,
                    'user_id' => $userId,
                    'file_path' => $filePath,
                    'version_number' => $nextVersion
                ];
                file_put_contents(__DIR__ . '/callback_success.log', json_encode($logSuccess, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
            } catch (Exception $e) {
                // Log error
                $logError = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Database error: ' . $e->getMessage(),
                    'resource_id' => $resourceId,
                    'user_id' => $userId,
                    'trace' => $e->getTraceAsString()
                ];
                file_put_contents(__DIR__ . '/callback_error.log', json_encode($logError, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
            }
        } else {
            // Log download failure
            $logFail = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Failed to retrieve file content',
                'download_url' => $downloadUrl,
                'file_param_exists' => !empty($fileContent)
            ];
            file_put_contents(__DIR__ . '/callback_fail.log', json_encode($logFail, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);