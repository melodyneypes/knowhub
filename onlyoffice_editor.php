<?php
session_start();
require 'db.php';

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
    'docx' => 'docx',
    'doc' => 'docx',
    'odt' => 'docx',
    'xlsx' => 'xlsx',
    'xls' => 'xlsx',
    'ods' => 'xlsx',
    'pptx' => 'pptx',
    'ppt' => 'pptx',
    'odp' => 'pptx',
    'txt' => 'docx',
    'pdf' => 'pdf'
];

$file_type = $extension_map[$file_extension] ?? 'docx';

// Generate document key based on file URL
$key = md5($file_url . time());

// Build OnlyOffice configuration
$config = [
    "document" => [
        "fileType" => $file_type,
        "key" => $key,
        "title" => $title,
        "url" => $file_url
    ],
    "editorConfig" => [
        "mode" => "edit",
        "lang" => "en",
        "user" => [
            "id" => $_SESSION['user']['id'] ?? 'user1',
            "name" => $_SESSION['user']['name'] ?? 'User'
        ]
    ]
];

// Add permissions based on file type
if ($file_type === 'pdf') {
    $config["document"]["permissions"] = [
        "edit" => false,
        "download" => true,
        "print" => true
    ];
    $config["editorConfig"]["mode"] = "view";
}
?>
<!DOCTYPE html>
<html>
<head>
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
            height: 100vh;
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
        
        try {
            var docEditor = new DocsAPI.DocEditor("placeholder", config);
        } catch (e) {
            console.error('Failed to initialize OnlyOffice:', e);
            document.getElementById('placeholder').innerHTML = 
                '<div class="error-message">' +
                '<h3>OnlyOffice Error</h3>' +
                '<p>Failed to initialize OnlyOffice editor.</p>' +
                '<p>Please check if OnlyOffice Document Server is running on http://localhost:8082</p>' +
                '</div>';
        }
    </script>
</body>
</html>
