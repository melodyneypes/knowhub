<?php
session_start();
require 'db.php';
require 'vendor/autoload.php'; // Include JWT library

use \Firebase\JWT\JWT;

// Get file parameters
$file_url = $_GET['file'] ?? '';
$title = $_GET['title'] ?? 'Document';
$resource_id = $_GET['resource_id'] ?? 0;

// Validate file and resource ID
if (empty($file_url) || empty($resource_id)) {
    // This error should no longer happen if uploads.php is fixed
    die('Error: Missing file URL or resource ID.'); 
}

// [CRITICAL FIX START]
// OnlyOffice Document Server (running in Docker) cannot access '127.0.0.1' or 'localhost' 
// on your host machine. We must rewrite the file URL to 'host.docker.internal'.

$host_for_callback = $_SERVER['HTTP_HOST'];
$server_port = 3000;

// 1. Determine the hostname that OnlyOffice MUST use to download the file.
// If the file URL is using 127.0.0.1 (local machine access), replace it with host.docker.internal.
if (strpos($file_url, '127.0.0.1') !== false) {
    // Assuming your web server is running on port 3000, replace 127.0.0.1 with host.docker.internal
    $doc_server_file_url = str_replace('127.0.0.1', 'host.docker.internal', $file_url);
} else {
    // If it's already using a public IP or other hostname, keep it.
    $doc_server_file_url = $file_url;
}

// 2. Determine the hostname for the callback URL.
// The Document Server must send the save command back to a host it can reach.
$callback_hostname = 'host.docker.internal';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
// The callback must include the port if your PHP server isn't on standard ports (e.g., :3000)
$callback_url = $protocol . "://" . $callback_hostname . ":" . $server_port . '/onlyoffice_callback.php?resourceId=' . $resource_id; // [CRITICAL FIX END]

// Get file extension from URL
$file_path = parse_url($doc_server_file_url, PHP_URL_PATH);
$file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
$file_extension = strtolower($file_extension);

// Map extensions to OnlyOffice supported types
$extension_map = [
    'docx' => 'word', 'doc' => 'word', 'odt' => 'word',
    'xlsx' => 'cell', 'xls' => 'cell', 'ods' => 'cell',
    'pptx' => 'slide', 'ppt' => 'slide', 'odp' => 'slide',
    'txt' => 'word', 'pdf' => 'word'
];

$file_type = $extension_map[$file_extension] ?? 'word';

// Generate document key based on file URL and the resource ID
$key = md5($doc_server_file_url . $resource_id); 

// Build OnlyOffice configuration
$config = [
    "document" => [
        "fileType" => $file_extension,
        "key" => $key,
        "title" => $title,
        "url" => $doc_server_file_url, // Use the Docker-accessible URL here
        "permissions" => [
            "download" => true,
            "edit" => ($file_type !== 'pdf'),
            "print" => true
        ]
    ],
    "documentType" => $file_type,
    "editorConfig" => [
        "mode" => "edit",
        "lang" => "en",
        "callbackUrl" => $callback_url, // Use the Docker-accessible callback URL
        "user" => [
            "id" => $_SESSION['user']['id'] ?? 'user1',
            "name" => $_SESSION['user']['name'] ?? 'User'
        ]
    ]
];

// Define the secret key (use the one from your OnlyOffice container configuration)
$secret_key = 'JyqijJ7aTxcBojv1IgKgKxvtS5pLhm90'; 

// Generate JWT token with proper structure for OnlyOffice
$payload = $config;

$token = JWT::encode($payload, $secret_key, 'HS256');

// Add token to the config correctly for OnlyOffice
$config["token"] = $token;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Document - <?php echo htmlspecialchars($title); ?></title>
    <!-- ✅ FIXED: Ensure this points to your OnlyOffice Document Server (Docker, port 8082) -->
    <script type="text/javascript" src="http://localhost:8082/web-apps/apps/api/documents/api.js"></script>
    <style>
    body {
        margin: 0;
        padding: 0;
        overflow: hidden;
        font-family: Arial, sans-serif;
        height: 100vh;
    }
    #placeholder {
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        overflow: auto; /* Enable scrolling */
    }
    .error-message {
        padding: 20px;
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        border-radius: 4px;
        margin: 20px;
    }
</style>
</head>
<body>
    <div id="placeholder"></div>
    <script>
        var config = <?php echo json_encode($config, JSON_PRETTY_PRINT); ?>;
        
        // Add error handling
        config.events = {
            'onError': function(event) {
                console.error('OnlyOffice Error:', event);
                if (event.data.errorCode === -4) {
                    // Check if the file URL used was the internal Docker one (which is necessary for the editor)
                    var fileUrlForDisplay = "<?php echo htmlspecialchars($file_url); ?>"; 
                    var docServerFileUrl = "<?php echo htmlspecialchars($doc_server_file_url); ?>";

                    document.getElementById('placeholder').innerHTML = 
                        '<div class="error-message">' +
                        '<h3>Document Download Failed (Error -4)</h3>' +
                        '<p>The Document Server failed to download the file from the specified URL.</p>' +
                        '<p><strong>Original URL:</strong> ' + fileUrlForDisplay + '</p>' +
                        '<p><strong>Document Server Attempted to Use:</strong> ' + docServerFileUrl + '</p>' +
                        '<p>Please ensure that the <code>' + docServerFileUrl + '</code> address is reachable from within your Docker network.</p>' +
                        '</div>';
                }
            },
            'onReady': function() {
                console.log('OnlyOffice editor is ready');
            }
        };
        
        // Check if OnlyOffice API is loaded
        if (typeof DocsAPI === 'undefined') {
            document.getElementById('placeholder').innerHTML = 
                '<div class="error-message">' +
                '<h3>OnlyOffice API Error</h3>' +
                '<p>Failed to load OnlyOffice API.</p>' +
                '<p>Please check if OnlyOffice Document Server is running on http://localhost:8082</p>' +
                '<p><a href="http://localhost:8082" target="_blank">Check OnlyOffice Status</a></p>' +
                '</div>';
        } else {
            try {
                var docEditor = new DocsAPI.DocEditor("placeholder", config);
            } catch (e) {
                console.error('Failed to initialize OnlyOffice:', e);
                document.getElementById('placeholder').innerHTML = 
                    '<div class="error-message">' +
                    '<h3>OnlyOffice Error</h3>' +
                    '<p>Failed to initialize OnlyOffice editor.</p>' +
                    '<p>Error: ' + e.message + '</p>' +
                    '<p>Please check if OnlyOffice Document Server is running on http://localhost:8082</p>' +
                    '</div>';
            }
        }
    </script>
</body>
</html>
