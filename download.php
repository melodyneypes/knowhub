<?php
session_start();
require 'db.php';
require 'notify.php';
require 'vendor/autoload.php'; // For PhpWord

use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;

// Function to add watermark to PDF
function add_watermark_to_pdf($pdf_file_path, $watermark_text) {
    // Read the existing PDF
    $pdf_content = file_get_contents($pdf_file_path);
    
    // Create a new Dompdf instance
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    
    // Create HTML with watermark
    $html = '
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                position: relative;
                margin: 0;
                padding: 0;
            }
            .watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 80px;
                color: rgba(200, 200, 200, 0.3);
                z-index: -1;
                pointer-events: none;
                text-align: center;
                width: 100%;
            }
            .content {
                position: relative;
                z-index: 1;
            }
        </style>
    </head>
    <body>
        <div class="watermark">' . htmlspecialchars($watermark_text) . '</div>
        <div class="content"></div>
    </body>
    </html>';
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Save watermarked PDF
    file_put_contents($pdf_file_path, $dompdf->output());
}

// Validate resource_id
$resource_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$resource_id) {
    die("Invalid resource ID.");
}

// Fetch resource
$sql = "SELECT * FROM resources WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();
$resource = $result->fetch_assoc();
$stmt->close();

if (!$resource) {
    die("Resource not found.");
}

// Check if user is authorized to download this resource
$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// Authorization check
$authorized = false;
if ($user_role === 'admin') {
    // Admins can download everything
    $authorized = true;
} else if ($resource['uploader_id'] == $user_id) {
    // Users can download their own resources
    $authorized = true;
} else {
    // For other users, check if the uploader is an admin
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $resource['uploader_id']);
    $stmt->execute();
    $uploader_result = $stmt->get_result();
    $uploader = $uploader_result->fetch_assoc();
    $stmt->close();
    
    // Only allow download if uploader is an admin
    if ($uploader && $uploader['role'] === 'admin') {
        $authorized = true;
    }
}

if (!$authorized) {
    die("You are not authorized to download this resource.");
}

// Get uploader information
$uploader_id = isset($resource['uploader_id']) ? $resource['uploader_id'] : null;
$downloader_name = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Unknown';

// Send notification to uploader (only if uploader_id is valid and not the downloader)
if ($uploader_id && isset($_SESSION['user']['id']) && $uploader_id != $_SESSION['user']['id']) {
    notify_file_download($uploader_id, $downloader_name, $resource['title']);
}

// Increment download count
$stmt = $conn->prepare("UPDATE resources SET download_count = download_count + 1 WHERE id = ?");
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$stmt->close();

$file_path = $resource['file_path'];
$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));