<?php
session_start();
require 'db.php';
require 'vendor/autoload.php'; // Include JWT library

use \Firebase\JWT\JWT;

// Get file parameters
$file_url = $_GET['file'] ?? '';
$title = $_GET['title'] ?? 'Document';

// Validate file exists
if (empty($file_url)) {
    die('Error: No file specified');
}

// Get file extension from URL
$file_path = parse_url($file_url, PHP_URL_PATH);
$file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
$file_extension = strtolower($file_extension);

// Map extensions to OnlyOffice supported types
$extension_map = [
    'docx' => 'word',
    'doc' => 'word',
    'odt' => 'word',
    'xlsx' => 'cell',
    'xls' => 'cell',
    'ods' => 'cell',
    'pptx' => 'slide',
    'ppt' => 'slide',
    'odp' => 'slide',
    'txt' => 'word',
    'pdf' => 'word'
];

$file_type = $extension_map[$file_extension] ?? 'word';

// Generate document key based on file URL
$key = md5($file_url . time());

// Build OnlyOffice configuration
$config = [
    "document" => [
        "fileType" => $file_extension,
        "key" => $key,
        "title" => $title,
        "url" => $file_url,
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
        "user" => [
            "id" => $_SESSION['user']['id'] ?? 'user1',
            "name" => $_SESSION['user']['name'] ?? 'User'
        ]
    ]
];

// Define the secret key (use the one from your OnlyOffice container configuration)
$secret_key = 'JyqijJ7aTxcBojv1IgKgKxvtS5pLhm90'; // This matches the secret in your OnlyOffice config

// Generate JWT token with proper structure for OnlyOffice
$payload = [
    "document" => [
        "fileType" => $file_extension,
        "key" => $key,
        "title" => $title,
        "url" => $file_url,
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
        "user" => [
            "id" => $_SESSION['user']['id'] ?? 'user1',
            "name" => $_SESSION['user']['name'] ?? 'User'
        ]
    ]
];

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
    <script type="text/javascript" src="http://localhost:8082/web-apps/apps/api/documents/api.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: Arial, sans-serif;
        }
        #placeholder {
            width: 100%;
            height: auto; /* Set a specific height */
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
                    document.getElementById('placeholder').innerHTML = 
                        '<div class="error-message">' +
                        '<h3>Document Download Failed</h3>' +
                        '<p>Please check if the file exists and is accessible.</p>' +
                        '<p>File URL: <?php echo htmlspecialchars($file_url); ?></p>' +
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